<?php
// db_check.php - Upload to htdocs/api/db_check.php
require_once __DIR__ . '/config/database.php';

header("Content-Type: text/plain");

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "=== DATABASE SCHEMA CHECK ===\n\n";

    $tables = ['farms', 'fields', 'crops', 'weather_data', 'infrastructure'];

    foreach ($tables as $table) {
        echo "Table: $table\n";
        try {
            $stmt = $db->query("DESCRIBE $table");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "  - {$row['Field']} ({$row['Type']})\n";
            }
        } catch (Exception $e) {
            echo "  ERROR: Table not found or access denied.\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}
echo "=== CHECK COMPLETE ===\n";
