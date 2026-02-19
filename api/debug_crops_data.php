<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Crop.php';
require_once __DIR__ . '/models/Field.php';

$database = new Database();
$db = $database->getConnection();

$crop = new Crop($db);
$field = new Field($db);

$farms_stmt = $db->query("SELECT id, name FROM farms LIMIT 1");
$farm = $farms_stmt->fetch(PDO::FETCH_ASSOC);

if (!$farm) {
    echo json_encode(["error" => "No farms found"]);
    exit;
}

$farmId = $farm['id'];
echo json_encode([
    "info" => "Debugging Farm: " . $farm['name'] . " ($farmId)",
    "fields" => $field->findByFarmId($farmId),
    "crops_by_farm" => $crop->findByFarmId($farmId),
    "all_crops_raw" => $db->query("SELECT id, name, crop_type, field_id, status FROM crops")->fetchAll(PDO::FETCH_ASSOC)
], JSON_PRETTY_PRINT);
