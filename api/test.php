<?php

/**
 * Debug Test File for InfinityFree
 * Upload this to api/test.php to diagnose issues
 * 
 * Visit: https://profarm.free.nf/api/test.php
 * 
 * DELETE THIS FILE AFTER DEBUGGING!
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$results = [];

// 1. PHP Version Check
$results['php_version'] = phpversion();
$results['php_ok'] = version_compare(phpversion(), '7.4.0', '>=');

// 2. Required Extensions
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$results['extensions'] = [];
foreach ($extensions as $ext) {
    $results['extensions'][$ext] = extension_loaded($ext);
}

// 3. Database Connection Test
$results['database'] = ['connection' => false, 'error' => null];
try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        $results['database']['connection'] = true;

        // Check if users table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'users'");
        $results['database']['users_table_exists'] = $stmt->rowCount() > 0;

        // Count users
        if ($results['database']['users_table_exists']) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
            $row = $stmt->fetch();
            $results['database']['user_count'] = $row['count'];
        }
    }
} catch (Exception $e) {
    $results['database']['error'] = $e->getMessage();
}

// 4. File Existence Check
$files = [
    'config/database.php',
    'controllers/AuthController.php',
    'controllers/FarmController.php',
    'models/User.php',
    'models/Farm.php',
    '.htaccess',
    'index.php'
];
$results['files'] = [];
foreach ($files as $file) {
    $results['files'][$file] = file_exists(__DIR__ . '/' . $file);
}

// 5. .htaccess readable
$results['htaccess_contents'] = file_exists(__DIR__ . '/.htaccess') ?
    file_get_contents(__DIR__ . '/.htaccess') : 'NOT FOUND';

// 6. Current directory
$results['current_dir'] = __DIR__;
$results['document_root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'N/A';

// 7. Request Info
$results['request_uri'] = $_SERVER['REQUEST_URI'] ?? 'N/A';
$results['request_method'] = $_SERVER['REQUEST_METHOD'] ?? 'N/A';

// 8. Memory and time limits
$results['memory_limit'] = ini_get('memory_limit');
$results['max_execution_time'] = ini_get('max_execution_time');

// Output results
echo json_encode($results, JSON_PRETTY_PRINT);
