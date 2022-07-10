<?php

// Connect to MariaDB database using PDO
try {
    $db = new PDO('mysql:host=localhost', 'root', '');
} catch (Exception $e) {
    die("> Error connecting to database server.  (exception message: " . $e->getMessage() . ")\n");
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

// Print databases found
print_r($databases);

echo "\n";

$backup_dir = dirname(__FILE__) . '/dump';

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
    $a = readline("Ovewrite existing files? (y/n) [default n]: ");
    if (trim(strtolower($a)) != 'y') {
        die("\n> Backup cancelled.\n");
    }
}

// Make a backup of each database in the list to a separate file using exec()
foreach ($databases as $database) {
    echo "\n> Backuping database {$database} to $backup_dir... ";
    $cmd = "mysqldump -u root --single-transaction $database > ./dump/$database.sql";
    exec($cmd);
    echo "done.\n\n";
}

echo "Backup finished.\n";
