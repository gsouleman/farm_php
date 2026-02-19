<?php

/**
 * Debug Tables Script
 * Check all table structures to find column mismatches
 * 
 * DELETE AFTER DEBUG!
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';

$results = [];

try {
    $database = new Database();
    $conn = $database->getConnection();

    // List of tables to check
    $tables = ['users', 'farms', 'fields', 'crops', 'activities', 'harvests', 'infrastructure', 'inputs', 'contracts'];

    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("DESCRIBE $table");
            $columns = [];
            while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $col['Field'];
            }
            $results[$table] = [
                'exists' => true,
                'columns' => $columns
            ];

            // Get row count
            $countStmt = $conn->query("SELECT COUNT(*) as cnt FROM $table");
            $results[$table]['row_count'] = $countStmt->fetch()['cnt'];
        } catch (Exception $e) {
            $results[$table] = [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} catch (Exception $e) {
    $results['connection_error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
