<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Rename 'North' -> 'East'
    $stmt1 = $db->prepare("UPDATE fields SET name = REPLACE(name, 'North', 'East') WHERE name LIKE '%North%'");
    $stmt1->execute();
    $count1 = $stmt1->rowCount();

    // 2. Rename 'South' -> 'West'
    $stmt2 = $db->prepare("UPDATE fields SET name = REPLACE(name, 'South', 'West') WHERE name LIKE '%South%'");
    $stmt2->execute();
    $count2 = $stmt2->rowCount();

    echo json_encode([
        "success" => true,
        "message" => "Migration complete.",
        "north_to_east" => $count1,
        "south_to_west" => $count2
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
