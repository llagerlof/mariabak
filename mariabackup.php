<?php

// Connect to MariaDB database using PDO
try {
    $db = new PDO('mysql:host=localhost', 'root', '');
} catch (Exception $e) {
    die("> Error connecting to database server.  (exception message: " . $e->getMessage() . ")");
}

// Get all database names
try {
    $stmt = $db->query("SHOW DATABASES");
} catch (Exception $e) {
    die("> Error retrieving databases list.  (exception message: " . $e->getMessage() . ")");
}

// Check if at least one database was returned
if ($stmt->rowCount() == 0) {
    die("> No databases found.");
}

try {
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    die("> Error fetching database names from statement.  (exception message: " . $e->getMessage() . ")");
}

// Print databases found
print_r($databases);

// Make a backup of each database in the list to a separate file using exec()
foreach ($databases as $database) {
    echo "> Backuping database {$database}... ";
    $cmd = "mysqldump -u root --single-transaction $database > $database.sql";
    exec($cmd);
    echo "done.\n\n";
}

echo 'Backup finished.';
