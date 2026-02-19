<?php
/**
 * Migration Executor Script
 * This script reads migration_ready.sql and executes it against the InfinityFree database.
 */

require_once __DIR__ . '/../api/config/db.php';

echo "Starting Migration...\n";

try {
    $db = Database::getInstance();
    
    $sqlFile = __DIR__ . '/../sql/migration_ready.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    
    // Split SQL by semicolons at the end of lines
    // This is a simple split, might fail on complex triggers or stored procedures, 
    // but migration_ready.sql seems to use standard statements.
    $queries = preg_split("/;[\r\n]+/", $sql);

    $total = count($queries);
    $success = 0;
    $errors = 0;

    // Enable foreign key checks at the end might be inside the script, but let's be safe
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            $db->exec($query);
            $success++;
        } catch (PDOException $e) {
            echo "Error executing query: " . substr($query, 0, 100) . "...\n";
            echo "Message: " . $e->getMessage() . "\n";
            $errors++;
        }
    }

    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "\nMigration Finished!\n";
    echo "Total queries processed: $total\n";
    echo "Successfully executed: $success\n";
    echo "Errors encountered: $errors\n";

} catch (Exception $e) {
    echo "Critical Error: " . $e->getMessage() . "\n";
}
