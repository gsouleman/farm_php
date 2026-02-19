private function generateGrowthCapitalReport($farmId, $format, $type = 'growth')
{
// 1. Calculate total capital invested (all expenses)
$sqlCapital = "SELECT
a.activity_type as category,
SUM(a.total_cost) as amount
FROM activities a
WHERE a.farm_id = :farm_id1 AND a.transaction_type = 'expense'
GROUP BY a.activity_type
ORDER BY amount DESC";

$stmtCapital = $this->conn->prepare($sqlCapital);
$stmtCapital->execute([':farm_id1' => $farmId]);
$capitalData = $stmtCapital->fetchAll(PDO::FETCH_ASSOC);

// 2. Calculate total income for ROI
$sqlIncome = "SELECT
SUM(COALESCE(h.quantity, 0) * COALESCE(h.price_per_unit, 0)) as total_income
FROM harvests h
JOIN crops c ON h.crop_id = c.id
JOIN fields f ON c.field_id = f.id
WHERE f.farm_id = :farm_id2";

$stmtIncome = $this->conn->prepare($sqlIncome);
$stmtIncome->execute([':farm_id2' => $farmId]);
$incomeResult = $stmtIncome->fetch(PDO::FETCH_ASSOC);
$totalIncome = $incomeResult['total_income'] ?? 0;

// 3. Calculate land utilization
$sqlLand = "SELECT
COUNT(*) as total_fields,
SUM(area) as total_area,
SUM(CASE WHEN crops.id IS NOT NULL THEN 1 ELSE 0 END) as utilized_fields,
SUM(CASE WHEN crops.id IS NOT NULL THEN fields.area ELSE 0 END) as utilized_area
FROM fields
LEFT JOIN crops ON fields.id = crops.field_id AND crops.status != 'harvested'
WHERE fields.farm_id = :farm_id3";

$stmtLand = $this->conn->prepare($sqlLand);
$stmtLand->execute([':farm_id3' => $farmId]);
$landData = $stmtLand->fetch(PDO::FETCH_ASSOC);

// Calculate metrics
$totalCapital = array_sum(array_column($capitalData, 'amount'));
$roi = $totalCapital > 0 ? (($totalIncome - $totalCapital) / $totalCapital) * 100 : 0;
$utilizationRate = $landData['total_area'] > 0 ?
($landData['utilized_area'] / $landData['total_area']) * 100 : 0;

$summary = [
['label' => 'Total Capital Invested', 'value' => number_format($totalCapital, 2)],
['label' => 'Return on Investment (ROI)', 'value' => number_format($roi, 2) . '%', 'highlight' => $roi >= 0],
['label' => 'Land Utilization', 'value' => number_format($utilizationRate, 1) . '%'],
['label' => 'Revenue Generated', 'value' => number_format($totalIncome, 2)]
];

if ($format === 'csv') {
$this->outputCSV($capitalData, 'growth_capital_report.csv');
} elseif ($format === 'html_print') {
// Prepare chart data
$categories = [];
$amounts = [];
$colors = ['#2e7d32', '#388e3c', '#4caf50', '#66bb6a', '#81c784', '#a5d6a7'];

foreach ($capitalData as $idx => $row) {
$categories[] = $row['category'];
$amounts[] = (float)$row['amount'];
}

$chartData = [
'capitalAllocation' => [
'labels' => $categories,
'data' => $amounts,
'colors' => array_slice($colors, 0, count($categories))
],
'roiMetrics' => [
'ro' => $roi,
'capital' => $totalCapital,
'revenue' => $totalIncome
],
'landUtilization' => [
'utilized' => $landData['utilized_area'],
'total' => $landData['total_area'],
'percentage' => $utilizationRate
]
];

$this->outputGrowthHTMLPrint(
$this->getReportTitle($type),
$capitalData,
['Category', 'Amount Invested'],
$farmId,
$summary,
$chartData
);
}

return ['success' => true, 'data' => $capitalData];
}