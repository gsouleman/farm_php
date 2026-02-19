<?php
// Database Test Script
header("Content-Type: application/json");
require_once 'config/db.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    
    // Check if users table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'users'");
    $usersExists = $tableCheck->rowCount() > 0;

    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection established.',
        'mysql_version' => $result['version'],
        'users_table_exists' => $usersExists,
        'php_version' => PHP_VERSION,
        'config_checks' => [
            'DB_HOST' => defined('DB_HOST') ? DB_HOST : 'NOT DEFINED',
            'DB_NAME' => defined('DB_NAME') ? DB_NAME : 'NOT DEFINED',
            'DB_USER' => defined('DB_USER') ? DB_USER : 'NOT DEFINED'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed.',
        'error_detail' => $e->getMessage()
    ]);
}
