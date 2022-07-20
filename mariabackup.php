<?php
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

// Print databases found
print_r($databases);

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
file_put_contents("$backup_dir/SYSTEM_VARIABLES.txt", print_r($system_variables, true));
echo "done.\n";

// Make a backup of each database in the list to a separate file using exec()
foreach ($databases as $database) {
    echo "\n> Backuping database {$database} to $backup_dir... ";
    $cmd = "mysqldump --routines --triggers --single-transaction -u root $database > $backup_dir/$database.sql";
    exec($cmd);
    echo "done.\n";
}

die ("Backup finished.\n");


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
