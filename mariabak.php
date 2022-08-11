#!/usr/bin/env php
<?php
/**
 * mariabak
 *
 * mariabak is a command-line script to make MariaDB/MySQL database backup a breeze, using mysqldump.
 *
 * @package    mariabak
 * @version    1.2.1
 * @author     Lawrence Lagerlof <llagerlof@gmail.com>
 * @link       http://github.com/llagerlof/mariabak
 * @license    https://opensource.org/licenses/MIT MIT
 */

// Selected databases
$databases_selected = pvalues('--databases');

// Selected tables to ignore data, but keeping structure
$tables_ignored_data = pvalues('--ignore-tables');

// Connection details
$host = pvalue('--host') ?: 'localhost';
$port = pvalue('--port') ?: 3306;
$user = pvalue('--user') ?: 'root';
$password = pvalue('--password') ?: '';
$password_interactive = pvalue('-p');

// Just list the databases
$list_databases = pvalue('-list');

// Validate if any required option were provided. If not, show help.
if (!$databases_selected && $list_databases !== true) {
    echo "\n> mariabak: Performs a backup on selected databases.\n\n";
    echo "  Usage:\n\n";
    echo "    List databases:\n\n";
    echo "      $ mariabak -list          # if you used the installer\n\n";
    echo "      $ php mariabak.php -list  # if you didn't use the installer\n\n";
    echo "    Backup all databases. A directory will be created in current directory:\n\n";
    echo "      $ mariabak --databases=*\n\n";
    echo "    Backup one database, asking for the server password interactively:\n\n";
    echo "      $ mariabak --databases=db1 --host=localhost --user=root -p\n\n";
    echo "    Backup some databases, ignore some tables data but preserve its structure:\n\n";
    echo "      $ mariabak --databases=db1,db2,db3 --ignore-tables=db2.table1,db2.table2,db3.table_a\n\n";
    echo "    Backup one database, passing the server password inline:\n\n";
    echo "      $ mariabak --databases=db1 --host=localhost --port=3306 --user=root --password=hunter2\n\n";
    die();
}

/*
    Ask for the password (-p)

    - Hiding the password only works on Linux shell.
    - The Linux read command performs a trim() on typed string, so
      if your password start or end with spaces, use --password=" yourpassword " instead.
*/
if ($password_interactive === true) {
    if (strtolower(php_uname('s')) == 'linux') {
        echo 'Enter password: ';
        // Thank you, Antony Penn, for the hidden typing technique (https://www.php.net/manual/en/function.readline.php)
        $handle = popen("read -s; echo \$REPLY", 'r');
        // read command adds a line break at the end of string, so it must be removed.
        $password = str_replace(PHP_EOL, '', fgets($handle, 256));
        pclose($handle);
    } else {
        $password = readline('Enter password (CAUTION: the password will be printed while you type): ');
    }
}

// Connect to MariaDB database using PDO
try {
    $db = new PDO('mysql:host=' . $host, $user, $password);
} catch (Exception $e) {
    die("> Error connecting to database server.  (exception message: " . $e->getMessage() . ")\n");
}

// Get all databases names
$databases = statement("show databases", "Error retrieving databases list.")->fetchAll(PDO::FETCH_COLUMN);

// List databases and exit
if ($list_databases === true) {
    echo("> Databases:\n");
    foreach ($databases as $database) {
        echo("  - " . $database . "\n");
    }
    die();
}

// Get the @@GLOBAL.basedir to use on directory name
$basedir = statement('select @@GLOBAL.basedir as basedir', 'Error retrieving @@GLOBAL.basedir.')->fetchColumn();

// Get the @@GLOBAL.datadir to use on directory name
$datadir = statement('select @@GLOBAL.datadir as datadir', 'Error retrieving @@GLOBAL.datadir.')->fetchColumn();

// Get all system variables. Will be included in SYSTEM_VARIABLES.txt
$system_variables = statement('show variables','Error retrieving system variables.')->fetchAll(PDO::FETCH_ASSOC);
$csv_system_variables = array2csv($system_variables);

// Get all users and hosts. Will be included in USER_HOSTS.txt
$user_hosts = statement('select distinct u.user as user, u.host as host from mysql.user u', 'Error retrieving users and hosts.')->fetchAll(PDO::FETCH_ASSOC);
$csv_user_hosts = array2csv($user_hosts);

// Get all grants for $user_hosts. Will be included in GRANTS.txt
$grants_commands = '';
foreach ($user_hosts as $user_host) {
    $grants_commands .= $user_host['user'] . '@' . $user_host['host'] . "\n\n";

    $grants = statement("show grants for '" . $user_host['user'] . "'@'" . $user_host['host']."'", 'Error retrieving grants.')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($grants as $grant) {
        $grants_commands .= $grant . "\n";
    }
    $grants_commands .= "\n\n";
}

// Check if at least one database was returned
if (count($databases) === 0) {
    die("> No databases found.\n");
}

// Make sure one or more selected databases exists
if (in_array('*', $databases_selected)) {
    $databases_selected = $databases;
} else {
    $databases_selected = array_intersect($databases, $databases_selected);
}

// Check if at least one database was selected
if (empty($databases_selected)) {
    die("> No database(s) selected.\n");
}

// Print selected databases
echo("\n> Selected databases:\n");
foreach ($databases_selected as $database) {
    echo("  - " . $database . "\n");
}

echo "\n";

// Create the backup directory
$backup_dir_basename = 'backup-db_' . date('Y-m-d_H-i-s') . '_basedir[' . str2filename($basedir) . ']_datadir[' . str2filename($datadir) . ']';
$backup_dir = getcwd() . "/$backup_dir_basename";

if (file_exists($backup_dir) && !is_dir($backup_dir)) {
    die("> Error: Directory '$backup_dir_basename' could not be created because a file with the same name already exists.\n");
}

if (!is_dir($backup_dir)) {
    if (!is_writable(getcwd())) {
        die("> Error: Directory '$backup_dir_basename' could not be created. Permission denied.\n");
    } else {
        echo("> BACKUP STARTED...\n\n");
        echo "> Creating backup directory '$backup_dir_basename' ...\n";
        mkdir($backup_dir, 0776);
    }
}

// Check if backup directory already have backup files (.sql)
if (!empty(glob("$backup_dir/*.sql"))) {
    echo "> Error: Directory '$backup_dir_basename' already contains backup files.\n\n";
    $a = readline("Overwrite existing files? (y/n) [default n]: ");
    if (trim(strtolower($a)) != 'y') {
        die("\n> Backup cancelled.\n");
    }
}

// Identify databases with events
$events = statement('select event_schema from information_schema.events', 'Error retrieving events.')->fetchAll(PDO::FETCH_COLUMN);

// Make a backup of each database in the list to a separate file using mysqldump
foreach ($databases_selected as $database) {
    echo "\n> Backuping database '{$database}' ... ";

    // Backup structure
    $cmd_structure = "mysqldump --single-transaction --skip-triggers --no-data --host=$host --port=$port --user=$user --password=$password $database > $backup_dir/$database.sql";
    exec($cmd_structure);

    $arguments_ignored_tables = argumentsIgnoredTables($database);

    // Backup data
    $cmd_data = "mysqldump --single-transaction --routines --triggers --no-create-info $arguments_ignored_tables --host=$host --port=$port --user=$user --password=$password $database >> $backup_dir/$database.sql";
    exec($cmd_data);

    // Backup events
    if (in_array($database, $events)) {
        $cmd_events = "mysqldump --no-create-db --no-create-info --no-data --skip-triggers --events --host=$host --port=$port --user=$user --password=$password $database > $backup_dir/$database.events.sql";
        exec($cmd_events);
    }

    echo "done.\n";
}

// Backup system variables
echo "\n> Backuping system variables to 'SYSTEM_VARIABLES.txt' ... ";
file_put_contents("$backup_dir/SYSTEM_VARIABLES.txt", $csv_system_variables);
echo "done.\n";

// Backup users ans hosts
echo "\n> Backuping users and hosts to 'USER_HOSTS.txt' ... ";
file_put_contents("$backup_dir/USERS_HOSTS.txt", $csv_user_hosts);
echo "done.\n";

// Backup grants
echo "\n> Backuping grants to 'GRANTS.txt' ... ";
file_put_contents("$backup_dir/GRANTS.txt", $grants_commands);
echo "done.\n";

die("\n> BACKUP FINISHED.\n\n");


/* Utility functions */

/**
 * Convert string to a valid filename
 *
 * @param string $str Any string
 *
 * @return string Valid and safe filename
 */
function str2filename(string $str): string
{
    // Replace ":\" with "."
    $str = preg_replace('/:\\\/', '.', $str);
    // Replace "\" with "."
    $str = preg_replace('/\\\/', '.', $str);
    // Replace "/" with "."
    $str = preg_replace('/\//', '.', $str);
    // Replace all spaces with underscores
    $str = str_replace(' ', '_', $str);
    // Replace all non alphanumeric characters, not including "-", "_" and ".", with dashes
    $str = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', $str);
    // Replace a sequence of "." with one "."
    $str = preg_replace('/\.+/', '.', $str);
    // Replace a sequence of blank characters with one space
    $str = preg_replace('/\s+/', ' ', $str);
    // Replace a sequence of underscores with one underscore
    $str = preg_replace('/_+/', '_', $str);
    // Replace a sequence of hiphens with one hiphen
    $str = preg_replace('/-+/', '-', $str);
    // Remove all "-", "_" and "." from the end of the string
    $str = preg_replace('/[\-_\.]+$/', '', $str);
    // Remove all "-", "_" and "." from the start of the string
    $str = preg_replace('/^[\-_\.]+/', '', $str);

    return trim($str);
}

/**
 * Convert a 2d array, table-like, to CSV format
 *
 * @param array $array_2d
 *
 * @return string CSV
 */
function array2csv(array $array_2d): string
{
    if (!is_array($array_2d)) {
        return '';
    }
    if (empty($array_2d)) {
        return '';
    }

    $csv = '';
    $column_position = 1;
    $column_count = count($array_2d[0]);
    foreach ($array_2d[0] as $column => $value) {
        $csv .= $column . ($column_position != $column_count ? ',' : "\n");
        $column_position++;
    }
    foreach ($array_2d as $row) {
        $csv .= implode(',', $row) . "\n";
    }

    return $csv;
}

/**
 * Build a string to represent the ignored tables arguments
 *
 * @param string $database The database name.
 *
 * @return string
 */
function argumentsIgnoredTables($database): string
{
    global $tables_ignored_data;

    if (trim($database) == '' || empty($tables_ignored_data)) {
        return '';
    }

    $ignored_filtered = array_filter($tables_ignored_data, function($table) use ($database) {
        return preg_match('/^' . $database . '\./', $table);
    });

    $built_arguments = array_map(function($table_name) {
        return '--ignore-table=' . $table_name;
    }, $ignored_filtered);

    return implode(' ', $built_arguments);
}

/**
 * Parameter value. Return the parameter value passed to the script, or false if not found in the command line arguments.
 *
 * @param string $param
 *
 * @return string|bool
 */
function pvalue(string $param)
{
    global $argv;

    $param_value = false;
    foreach($argv as $arg) {
        if (preg_match('/^'.$param . '=' . '/', $arg)) {
            $param_pieces = explode("=", $arg);
            $param_value = $param_pieces[1] ?? '';
        } elseif (preg_match('/^' . $param . '/', $arg)) {
            $param_value = true;
        }
    }

    return $param_value;
}

/**
 * Parameter values. Return an array with all the parameter comma-separated values.
 *
 * @param string $param
 *
 * @return array
 */
function pvalues(string $param): array
{
    global $argv;

    $param_value = pvalue($param);

    return array_filter(explode(',', $param_value));
}

/**
 * Execute a query and return a PDOStatement object.
 *
 * @param string $query
 * @param string $error_message
 *
 * @return PDOStatement
 */
function statement(string $query, string $error_message): PDOStatement
{
    global $db;

    try {
        $stmt = $db->query($query);
    } catch (Exception $e) {
        die("> " . $error_message . "  (exception message: " . $e->getMessage() . ")\n");
    }

    return $stmt;
}
