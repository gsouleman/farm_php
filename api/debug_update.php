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
    die(json_encode(['success' => false, 'message' => 'Configuration file not found']));
}

require_once $configFile;
require_once __DIR__ . '/models/Activity.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $activity = new Activity($db);

    // Get the ID from query param
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    $type = isset($_GET['type']) ? $_GET['type'] : 'expense';

    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'Please provide an activity ID in the URL, e.g., ?id=123&type=expense']));
    }

    // 1. Fetch current state
    $current = $activity->findById($id);
    if (!$current) {
        die(json_encode(['success' => false, 'message' => 'Activity not found']));
    }

    // 2. Attempt update
    $data = ['transaction_type' => $type];

    // Manual update to see if it works via model
    $updated = $activity->update($id, $data);

    echo json_encode([
        'success' => true,
        'original_transaction_type' => $current['transaction_type'] ?? 'MISSING_IN_FETCH',
        'requested_type' => $type,
        'updated_record' => $updated
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
