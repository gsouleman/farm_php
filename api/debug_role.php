<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

try {
    $db = (new Database())->getConnection();
    $email = 'admin@farmer.local';

    $stmt = $db->prepare("SELECT id, email, role, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "User Found Details:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: [" . $user['role'] . "]\n";
        echo "Is Active: " . $user['is_active'] . "\n";
        echo "\nDEBUG: role === 'admin' is " . ($user['role'] === 'admin' ? 'TRUE' : 'FALSE') . "\n";
    } else {
        echo "User '{$email}' not found in database.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
