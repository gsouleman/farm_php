<?php
// Standalone Database Backup Script
// HARDCODED CREDENTIALS to bypass include/path issues on InfinityFree

// Disable time limit if allowed, otherwise ignore warning
@set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Credentials from your config file
$host = 'sql112.infinityfree.com';
$db_name = 'if0_41077803_farm';
$username = 'if0_41077803';
$password = 'AJkTbv7Btng4';

try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // If we reached here, connection is good.
    // Now start generating the dump.

    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sqlScript = "-- Database Backup: " . date('Y-m-d H:i:s') . "\n\n";
    $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n";

    foreach ($tables as $table) {
        // Structure
        $row2 = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
        $sqlScript .= "\n\n" . $row2[1] . ";\n\n";

        // Data
        $stmt2 = $pdo->query("SELECT * FROM $table");
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $sqlScript .= "INSERT INTO $table VALUES(";
            $values = [];
            foreach ($row as $value) {
                if (is_null($value)) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . addslashes($value) . "'";
                }
            }
            $sqlScript .= implode(',', $values) . ");\n";
        }
    }

    $sqlScript .= "\nSET FOREIGN_KEY_CHECKS=1;";

    // Check if we have data
    if (strlen($sqlScript) < 100) {
        throw new Exception("Generated SQL is suspiciously short. Something went wrong.");
    }

    // Output headers for download
    if (ob_get_level()) ob_end_clean();
    $filename = 'profarm_backup_standalone_' . date('Ymd_His') . '.sql';

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($sqlScript));

    echo $sqlScript;
    exit;
} catch (PDOException $e) {
    header('Content-Type: text/plain');
    echo "DATABASE CONNECTION ERROR: " . $e->getMessage();
} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo "GENERAL ERROR: " . $e->getMessage();
}
