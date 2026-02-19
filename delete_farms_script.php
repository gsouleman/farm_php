<?php
// Temporary script to delete farms
require_once __DIR__ . '/api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // The target user ID
    $userId = '78b88453-8b3e-4a29-a180-b1c5aa5b7483';
    
    // Delete all farms for this user
    // Note: FOREIGN KEY CASCADE should handle fields, crops, etc.
    $stmt = $db->prepare("DELETE FROM farms WHERE owner_id = ?");
    $stmt->execute([$userId]);
    
    echo "Successfully deleted " . $stmt->rowCount() . " farms for user $userId.";
} catch (Exception $e) {
    echo "Error deleting farms: " . $e->getMessage();
}
