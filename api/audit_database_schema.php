<?php

/**
 * Database Schema Audit Script
 * Identifies available tables and data for report customization
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== FARM DATABASE SCHEMA AUDIT ===\n\n";

    // 1. List all tables
    echo "1. AVAILABLE TABLES:\n";
    echo str_repeat("-", 50) . "\n";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    // 2. Check activities table structure
    echo "\n\n2. ACTIVITIES TABLE STRUCTURE:\n";
    echo str_repeat("-", 50) . "\n";
    $stmt = $db->query("DESCRIBE activities");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo sprintf("  %-20s %-15s %s\n", $col['Field'], $col['Type'], $col['Null']);
    }

    // 3. Get distinct activity types
    echo "\n\n3. ACTIVITY TYPES IN DATABASE:\n";
    echo str_repeat("-", 50) . "\n";
    $stmt = $db->query("SELECT DISTINCT activity_type, COUNT(*) as count FROM activities GROUP BY activity_type ORDER BY count DESC");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($types as $type) {
        echo sprintf("  %-30s : %d records\n", $type['activity_type'] ?? 'NULL', $type['count']);
    }

    // 4. Check infrastructure table
    if (in_array('infrastructure', $tables)) {
        echo "\n\n4. INFRASTRUCTURE TABLE STRUCTURE:\n";
        echo str_repeat("-", 50) . "\n";
        $stmt = $db->query("DESCRIBE infrastructure");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo sprintf("  %-20s %-15s\n", $col['Field'], $col['Type']);
        }

        $stmt = $db->query("SELECT COUNT(*) as count FROM infrastructure");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\n  Total infrastructure records: $count\n";

        if ($count > 0) {
            $stmt = $db->query("SELECT type, COUNT(*) as count FROM infrastructure GROUP BY type");
            $infTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "\n  Infrastructure types:\n";
            foreach ($infTypes as $type) {
                echo sprintf("    - %-20s : %d\n", $type['type'], $type['count']);
            }
        }
    }

    // 5. Check fields table
    if (in_array('fields', $tables)) {
        echo "\n\n5. FIELDS TABLE STRUCTURE:\n";
        echo str_repeat("-", 50) . "\n";
        $stmt = $db->query("DESCRIBE fields");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo sprintf("  %-20s %-15s\n", $col['Field'], $col['Type']);
        }
    }

    // 6. Check inputs table
    if (in_array('inputs', $tables)) {
        echo "\n\n6. INPUTS TABLE STRUCTURE:\n";
        echo str_repeat("-", 50) . "\n";
        $stmt = $db->query("DESCRIBE inputs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo sprintf("  %-20s %-15s\n", $col['Field'], $col['Type']);
        }

        $stmt = $db->query("SELECT input_type, COUNT(*) as count FROM inputs GROUP BY input_type");
        $inputTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($inputTypes)) {
            echo "\n  Input types:\n";
            foreach ($inputTypes as $type) {
                echo sprintf("    - %-20s : %d\n", $type['input_type'], $type['count']);
            }
        }
    }

    // 7. Check for weather data
    $weatherTables = array_filter($tables, function ($t) {
        return stripos($t, 'weather') !== false;
    });
    if (!empty($weatherTables)) {
        echo "\n\n7. WEATHER TABLES:\n";
        echo str_repeat("-", 50) . "\n";
        foreach ($weatherTables as $wt) {
            echo "  - $wt\n";
            $stmt = $db->query("DESCRIBE $wt LIMIT 5");
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                echo sprintf("    %-20s %-15s\n", $col['Field'], $col['Type']);
            }
        }
    }

    echo "\n\n=== AUDIT COMPLETE ===\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
