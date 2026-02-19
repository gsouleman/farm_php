<?php

/**
 * Debug Login Script
 * Upload to api/debug_login.php to diagnose auth issues
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

    // Get the admin user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => 'admin@farm.local']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $results['error'] = 'No user found with email admin@farm.local';
        $results['all_users'] = [];

        // Get all users to see what exists
        $allStmt = $conn->query("SELECT id, email, first_name, last_name FROM users");
        $results['all_users'] = $allStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $results['user_found'] = true;
        $results['user_id'] = $user['id'];
        $results['user_email'] = $user['email'];
        $results['user_is_active'] = $user['is_active'];

        // Check which password column exists
        $results['has_password_column'] = isset($user['password']);
        $results['has_password_hash_column'] = isset($user['password_hash']);

        // Get the stored hash (first 20 chars for security)
        $storedHash = $user['password_hash'] ?? $user['password'] ?? 'NOT_FOUND';
        $results['stored_hash_preview'] = substr($storedHash, 0, 20) . '...';
        $results['stored_hash_length'] = strlen($storedHash);

        // Test password verification with 'admin123'
        $testPassword = 'admin123';
        $results['test_password'] = $testPassword;
        $results['password_verify_result'] = password_verify($testPassword, $storedHash);

        // Generate correct hash for comparison
        $correctHash = password_hash($testPassword, PASSWORD_DEFAULT);
        $results['new_correct_hash'] = $correctHash;

        // SQL to fix
        $results['fix_sql'] = "UPDATE users SET password_hash = '$correctHash' WHERE email = 'admin@farm.local';";
    }

    // Show table structure
    $structStmt = $conn->query("DESCRIBE users");
    $results['table_columns'] = [];
    while ($col = $structStmt->fetch(PDO::FETCH_ASSOC)) {
        $results['table_columns'][] = $col['Field'];
    }
} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
