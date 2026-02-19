<?php
// fix_schema.php - Upload to htdocs/api/fix_schema.php
require_once __DIR__ . '/config/database.php';

header("Content-Type: text/plain");

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "=== DATABASE SCHEMA FIXER ===\n\n";

    $queries = [
        "ALTER TABLE farms MODIFY coordinates TEXT",
        "ALTER TABLE farms MODIFY boundary TEXT",
        "ALTER TABLE fields MODIFY boundary TEXT",
        "ALTER TABLE crops MODIFY boundary TEXT",
        "ALTER TABLE infrastructure MODIFY boundary TEXT"
    ];

    foreach ($queries as $sql) {
        echo "Running: $sql... ";
        try {
            $db->exec($sql);
            echo "DONE\n";
        } catch (Exception $e) {
            echo "FAILED - " . $e->getMessage() . "\n";
        }
    }

    echo "\nFix complete. Please try updating your farm/parcel again.\n";
} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}
echo "=== PROCESS COMPLETE ===\n";
