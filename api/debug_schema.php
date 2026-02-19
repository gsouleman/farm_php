<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->query("DESCRIBE activities");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'columns' => array_column($columns, 'Field')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
