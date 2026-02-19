<?php
require_once 'config/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $email = 'gsouleman@gmail.com';
    $stmt = $db->prepare("SELECT id, email, role, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'not_found', 'message' => 'User not found']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
