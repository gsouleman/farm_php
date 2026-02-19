<?php
ini_set('display_errors', 1);
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

    $sql = "SELECT activity_type, transaction_type, COUNT(*) as count FROM activities GROUP BY activity_type, transaction_type";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $results]);
} catch (Exception $e) {
    // Fallback if transaction_type column doesn't exist (though it should)
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
