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

    // 1. Set 'harvesting' activities to 'income'
    $sqlIncome = "UPDATE activities SET transaction_type = 'income' WHERE activity_type = 'harvesting'";
    $stmtIncome = $db->prepare($sqlIncome);
    $stmtIncome->execute();
    $incomeCount = $stmtIncome->rowCount();

    // 2. Ensure everything else is 'expense' (default, but good to be sure)
    // Note: We don't want to overwrite if something else was already set to 'income' by the user recently, 
    // but since the column is new and defaulted to 'expense', this is safe for now unless there are other income types.
    // Actually, 'sale' might be another one? strict 'harvesting' check is safer for now.

    echo json_encode([
        'success' => true,
        'message' => "Data migration complete. Updated $incomeCount harvesting activities to 'income'."
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
