<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/ReportController.php';

$database = new Database();
$db = $database->getConnection();

echo "Instantiating ReportController...<br>";
$controller = new ReportController($db);

if (method_exists($controller, 'index')) {
    echo "Method 'index' exists!<br>";
} else {
    echo "Method 'index' DOES NOT exist!<br>";
    echo "Available methods: <pre>" . print_r(get_class_methods($controller), true) . "</pre>";
}

echo "Done.";
