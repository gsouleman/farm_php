<?php
/**
 * Password Hash Generator for InfinityFree
 * Upload to api/hash.php and visit to generate correct hash
 * 
 * DELETE THIS FILE AFTER USE!
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Generate hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo json_encode([
    'password' => $password,
    'hash' => $hash,
    'note' => 'Run this SQL in phpMyAdmin to fix admin password:',
    'sql' => "UPDATE users SET password = '$hash' WHERE email = 'admin@farm.local';"
], JSON_PRETTY_PRINT);
