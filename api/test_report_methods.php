<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/ReportController.php';

$db = (new Database())->getConnection();
$controller = new ReportController($db);

// Check which methods exist
$methods = get_class_methods($controller);

echo "<h1>ReportController Methods Check</h1>";
echo "<h3>Looking for new report methods:</h3>";

$required_methods = [
    'generateGrowthCapitalReport',
    'generateCropBudgetReport',
    'generateComplianceAuditReport',
    'generateRiskIncidentReport',
    'generateOperationsReport'
];

foreach ($required_methods as $method) {
    $exists = method_exists($controller, $method);
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "<p>$status - $method</p>";
}

echo "<hr><h3>All available methods:</h3>";
echo "<pre>" . print_r($methods, true) . "</pre>";
