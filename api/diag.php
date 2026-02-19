<?php
// diag.php - Upload this to htdocs/api/diag.php to debug 500 errors

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: text/plain");

echo "=== FARM MANAGEMENT SYSTEM DIAGNOSTIC ===\n\n";

echo "Checking Database Config...\n";
if (file_exists(__DIR__ . '/config/database.php')) {
    echo "Found database.php\n";
    require_once __DIR__ . '/config/database.php';
    try {
        $database = new Database();
        $db = $database->getConnection();
        echo "Database Connection: SUCCESS\n";
    } catch (Exception $e) {
        echo "Database Connection: FAILED - " . $e->getMessage() . "\n";
    }
} else {
    echo "ERROR: config/database.php missing!\n";
}

echo "\nChecking Controllers...\n";
$controllers = [
    'AuthController',
    'UserController',
    'FarmController',
    'FieldController',
    'CropController',
    'ActivityController',
    'InfrastructureController',
    'TeamController',
    'WeatherController'
];

foreach ($controllers as $name) {
    $file = __DIR__ . "/controllers/{$name}.php";
    if (file_exists($file)) {
        try {
            require_once $file;
            echo "Loaded: $name\n";
            if (isset($db)) {
                $instance = new $name($db);
                echo "Instantiated: $name\n";
            }
        } catch (Throwable $t) {
            echo "ERROR Loading $name: " . $t->getMessage() . " in " . $t->getFile() . " on line " . $t->getLine() . "\n";
        }
    } else {
        echo "MISSING: $name ($file)\n";
    }
}

echo "\nChecking Weather Connectivity...\n";
if (function_exists('curl_init')) {
    echo "cURL: AVAILABLE\n";
} else {
    echo "cURL: MISSING (Weather forecast will fail)\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
