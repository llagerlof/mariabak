<?php
// Validate if any option was provided
if (count($argv) == 1) {
    echo("> Usage:\n");
    echo("  php mariabackup.php --databases=*\n");
    die("  php mariabackup.php --databases=db1,db2\n");
}

// Extract option --databases
foreach($argv as $arg) {
    if (strpos($arg, "--databases=") !== false) {
        $param_pieces = explode("=", $arg);
        $databases_selected = explode(",", $param_pieces[1]);
    }
}

// Connect to MariaDB database using PDO
try {
    $db = new PDO('mysql:host=localhost', 'root', '');
} catch (Exception $e) {
    die("> Error connecting to database server.  (exception message: " . $e->getMessage() . ")\n");
}

// Get the @@GLOBAL.basedir and @@GLOBAL.basedir to use on directory name
try {
    $stmt = $db->query("select @@GLOBAL.basedir as basedir");
} catch (Exception $e) {
    die("> Error retrieving @@GLOBAL.basedir.  (exception message: " . $e->getMessage() . ")\n");
}
$basedir = $stmt->fetchColumn();

// Get the @@GLOBAL.datadir and @@GLOBAL.datadir to use on directory name
try {
    $stmt = $db->query("select @@GLOBAL.datadir as datadir");
} catch (Exception $e) {
    die("> Error retrieving @@GLOBAL.datadir.  (exception message: " . $e->getMessage() . ")\n");
}
$datadir = $stmt->fetchColumn();

// Get all system variables. Will be included in SYSTEM_VARIABLES.txt
try {
    $stmt = $db->query("show variables");
} catch (Exception $e) {
    die("> Error retrieving system variables.  (exception message: " . $e->getMessage() . ")\n");
}
$system_variables = $stmt->fetchAll(PDO::FETCH_ASSOC);
$csv_system_variables = array2csv($system_variables);

// Get all users and hosts. Will be included in USER_HOSTS.txt
try {
    $stmt = $db->query("select distinct u.user as user, u.host as host from mysql.user u");
} catch (Exception $e) {
    die("> Error retrieving users and hosts.  (exception message: " . $e->getMessage() . ")\n");
}
$user_hosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$csv_user_hosts = array2csv($user_hosts);

// Get all grants for $user_hosts. Will be included in PERMISSIONS.txt
$grants_commands = '';
foreach ($user_hosts as $user_host) {
    $grants_commands .= $user_host['user'] . '@' . $user_host['host'] . "\n\n";

    try {
        $stmt = $db->query("show grants for '" . $user_host['user'] . "'@'" . $user_host['host']."'");
        $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($grants as $grant) {
            $grants_commands .= $grant . "\n";
        }
        $grants_commands .= "\n\n";
    } catch (Exception $e) {
        die("> Error retrieving grants.  (exception message: " . $e->getMessage() . ")\n");
    }
}

// Get all database names
try {
    $stmt = $db->query("SHOW DATABASES");
} catch (Exception $e) {
    die("> Error retrieving databases list.  (exception message: " . $e->getMessage() . ")\n");
}

// Check if at least one database was returned
if ($stmt->rowCount() == 0) {
    die("> No databases found.\n");
}

try {
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    die("> Error fetching database names from statement.  (exception message: " . $e->getMessage() . ")\n");
}

// Make sure all selected databases exist
if (in_array('*', $databases_selected)) {
    $databases_selected = $databases;
} else {
    $databases_selected = array_intersect($databases, $databases_selected);
}

// Check if at least one database was selected
if (empty($databases_selected)) {
    die("> No database(s) selected.\n");
}

// Print selected database(s)
echo("> Selected databases:\n");
foreach ($databases_selected as $database) {
    echo("  " . $database . "\n");
}

echo "\n";

$backup_dir = getcwd() . '/backup-db_' . date('Y-m-d_H-i-s') . '_basedir[' . str2filename($basedir) . ']_datadir[' . str2filename($datadir) . ']';

if (file_exists($backup_dir) && !is_dir($backup_dir)) {
    die("> Error: Directory $backup_dir could not be created because a file with the same name already exists.\n");
}

if (!is_dir($backup_dir)) {
    if (!is_writable(dirname(__FILE__))) {
        die("> Error: Directory $backup_dir could not be created. Permission denied.\n");
    } else {
        echo "> Creating directory $backup_dir...\n";
        mkdir($backup_dir, 0776);
    }
}

if (!empty(glob("$backup_dir/*.sql"))) {
    echo "> Error: Directory $backup_dir already contains backup files.\n\n";
    $a = readline("Overwrite existing files? (y/n) [default n]: ");
    if (trim(strtolower($a)) != 'y') {
        die("\n> Backup cancelled.\n");
    }
}

// Backup system variables
echo "\n> Backuping system variables to SYSTEM_VARIABLES.txt... ";
file_put_contents("$backup_dir/SYSTEM_VARIABLES.txt", $csv_system_variables);
echo "done.\n";

// Backup users ans hosts
echo "\n> Backuping users and hosts to USER_HOSTS.txt... ";
file_put_contents("$backup_dir/USERS_HOSTS.txt", $csv_user_hosts);
echo "done.\n";

// Backup grants
echo "\n> Backuping grants to PERMISSIONS.txt... ";
file_put_contents("$backup_dir/PERMISSIONS.txt", $grants_commands);
echo "done.\n";

// Make a backup of each database in the list to a separate file using exec()
foreach ($databases_selected as $database) {
    echo "\n> Backuping database {$database} to $backup_dir... ";
    $cmd = "mysqldump --routines --triggers --single-transaction -u root $database > $backup_dir/$database.sql";
    exec($cmd);
    echo "done.\n";
}

die("> Backup finished.\n");


/* Utility functions */

/**
 * Convert any string to a valid filename
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
