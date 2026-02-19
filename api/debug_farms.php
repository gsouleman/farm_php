<?php
require_once __DIR__ . '/config/database.php';

header("Content-Type: application/json");

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->query("DESCRIBE farms");
    $schema = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'table' => 'farms',
        'schema' => $schema
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
