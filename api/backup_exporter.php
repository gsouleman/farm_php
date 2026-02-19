<?php

/**
 * PRO FARM - Database Backup Utility
 * Version 1.0
 * 
 * This script exports your MySQL database as a .sql file.
 * INSTRUCTIONS:
 * 1. Upload this file to your /htdocs/api/ folder on InfinityFree.
 * 2. Visit https://profarm.free.nf/api/backup_exporter.php in your browser.
 * 3. The download will start automatically.
 * 4. IMPORTANT: Delete this file from the server after the download is finished for security!
 */

// Enable error reporting for this script
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database credentials
$host = 'sql112.infinityfree.com';
$db_name = 'if0_41077803_farm';
$username = 'if0_41077803';
$password = 'AJkTbv7Btng4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $tables = [];
    $query = $pdo->query("SHOW TABLES");
    while ($row = $query->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $output = "-- PRO FARM Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Host: $host\n";
    $output .= "-- Database: $db_name\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $output .= "--\n-- Table structure for table `$table`\n--\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";

        $createTableQuery = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $output .= $createTableQuery[1] . ";\n\n";

        $output .= "--\n-- Dumping data for table `$table`\n--\n";
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();

        foreach ($rows as $row) {
            $output .= "INSERT INTO `$table` VALUES (";
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = $pdo->quote($value);
                }
            }
            $output .= implode(', ', $values) . ");\n";
        }
        $output .= "\n";
    }

    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Header for file download
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="profarm_backup_' . date('Y-m-d_His') . '.sql"');
    header('Content-Length: ' . strlen($output));

    echo $output;
    exit;
} catch (Exception $e) {
    die("Backup failed: " . $e->getMessage());
}
