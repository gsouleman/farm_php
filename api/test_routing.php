<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/ReportController.php';

$db = (new Database())->getConnection();

echo "<h1>Direct Routing Test</h1>";

$tests = [
    'financials' => 'Comprehensive Financial Report',
    'growth' => 'Growth & Capital Report',
    'crop-budget' => 'Crop Budget Analysis',
    'inventory' => 'Inventory Valuation'
];

foreach ($tests as $type => $expectedTitle) {
    echo "<h3>Testing: $type</h3>";

    // Simulate the request
    $_GET['farm_id'] = 1;
    $_GET['format'] = 'json';

    $controller = new ReportController($db);

    try {
        ob_start();
        $result = $controller->produce($type);
        $output = ob_get_clean();

        echo "<p>✅ Route executed</p>";
        echo "<p>Expected Title: <strong>$expectedTitle</strong></p>";
        echo "<pre>Result: " . print_r($result, true) . "</pre>";
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }

    echo "<hr>";
}
