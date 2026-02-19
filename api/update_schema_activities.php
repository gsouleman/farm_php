<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Try to include database configuration with case sensitivity handling
$configFile = __DIR__ . '/config/Database.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/config/database.php';
}

if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuration file not found: ' . $configFile]);
    exit;
}

require_once $configFile;

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if column exists
    $checkSql = "SHOW COLUMNS FROM activities LIKE 'transaction_type'";
    $stmt = $db->prepare($checkSql);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Column transaction_type already exists.']);
    } else {
        // Add column
        $addSql = "ALTER TABLE activities ADD COLUMN transaction_type VARCHAR(20) DEFAULT 'expense'";
        $db->exec($addSql);
        echo json_encode(['success' => true, 'message' => 'Column transaction_type added successfully.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
