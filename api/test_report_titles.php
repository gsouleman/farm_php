<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/ReportController.php';

echo "<h1>Report Routing Diagnostic</h1>";

$db = (new Database())->getConnection();
$controller = new ReportController($db);

// Use reflection to call private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('getReportTitle');
$method->setAccessible(true);

$testCases = [
    'activities' => 'Operations Report',
    'machinery' => 'Machinery Health Log',
    'soil-health' => 'Soil Health Trends',
    'activity-log' => 'Compliance Audit Log',
    'water' => 'Water Usage Efficiency'
];

echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #f0f0f0;'><th>Report Type</th><th>Expected Title</th><th>Actual Title</th><th>Status</th></tr>";

foreach ($testCases as $type => $expectedTitle) {
    $actualTitle = $method->invoke($controller, $type);
    $status = ($actualTitle === $expectedTitle) ? '✅ PASS' : '❌ FAIL';
    $color = ($actualTitle === $expectedTitle) ? '#d4edda' : '#f8d7da';

    echo "<tr style='background: $color;'>";
    echo "<td><strong>$type</strong></td>";
    echo "<td>$expectedTitle</td>";
    echo "<td>$actualTitle</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Check if new methods exist
echo "<h2>Method Existence Check</h2>";
$requiredMethods = [
    'generateFarmSummary',
    'generateGrowthCapitalReport',
    'generateCropBudgetReport'
];

foreach ($requiredMethods as $methodName) {
    $exists = method_exists($controller, $methodName);
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "<p>$status - <strong>$methodName</strong></p>";
}

echo "<hr><p><strong>If you see FAILs above, the old ReportController.php is still on the server!</strong></p>";
echo "<p>Upload the new version from: <code>c:\\Farm2.0_PHP\\api\\controllers\\ReportController.php</code></p>";
