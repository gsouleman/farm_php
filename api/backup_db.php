<?php
// backup_db.php
// A simple PHP script to export the database.
// Upload this to your 'api' folder and visit https://your-site/api/backup_db.php

// Configure credentials (taken from config/db.php)
$host = 'sql112.infinityfree.com';
$user = 'if0_41077803';
$pass = 'AJkTbv7Btng4';
$name = 'if0_41077803_farm';

// Security: Optional simple key to prevent unauthorized access
// Usage: backup_db.php?key=mysecret
$protection_key = 'profarm_backup';

if (isset($_GET['key']) && $_GET['key'] !== $protection_key) {
    die("Access denied. Incorrect key.");
}

// 2. Connect to Database
$mysqli = new mysqli($host, $user, $pass, $name);
$mysqli->select_db($name);
$mysqli->query("SET NAMES 'utf8'");

// 3. Get All Tables
$result = $mysqli->query('SHOW TABLES');
$tables = [];
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

// 4. Generate SQL Script
$content = "-- MySQL Dump for ProFarm on " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $table) {
    // Structure
    $result = $mysqli->query('SHOW CREATE TABLE ' . $table);
    $row = $result->fetch_row();
    $content .= "\n\n" . $row[1] . ";\n\n";

    // Data
    $result = $mysqli->query('SELECT * FROM ' . $table);
    while ($row = $result->fetch_row()) {
        $content .= "INSERT INTO $table VALUES(";
        $values = [];
        foreach ($row as $data) {
            $data = $mysqli->real_escape_string($data);
            $values[] = "'$data'";
        }
        $content .= implode(', ', $values);
        $content .= ");\n";
    }
}

// 5. Force Download
$backup_name = $name . "_backup_" . date('Y-m-d_H-i-s') . ".sql";
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . $backup_name . "\"");
echo $content;
exit;
