<?php
// Temporary helper file for investor presentation with charts
// Add this to the end of ReportController.php

private function outputInvestorPresentationHTML($farmId, $financials, $capitalData, $cropData, $assetData)
{
    // Calculate metrics
    $totalRevenue = $financials['total_revenue'] ?? 0;
    $totalCosts = $financials['total_costs'] ?? 0;
    $netProfit = $financials['net_profit'] ?? 0;
    $roi = $totalCosts > 0 ? (($netProfit / $totalCosts) * 100) : 0;
    $totalCapitalInvested = array_sum(array_column($capitalData, 'amount_invested'));
    $totalAssetValue = array_sum(array_column($assetData, 'total_value'));

    // Get farm name
    $stmt = $this->conn->prepare("SELECT name FROM farms WHERE id = :id");
    $stmt->execute([':id' => $farmId]);
    $farm = $stmt->fetch(PDO::FETCH_ASSOC);
    $farmName = $farm['name'] ?? 'Farm';

    // Generate simplified charts (Canvas-based would be better, but using simple HTML/CSS bars for now)
    
    header('Content-Type: text/html');
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Investor Presentation - $farmName</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 40px 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
            position: relative;
        }
        .header h1 {
            font-size: 42px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .header .subtitle {
            font-size: 18px;
            opacity: 0.9;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            border-bottom: 3px solid #2e7d32;
        }
        .metric-card {
            padding: 40px 30px;
            text-align: center;
            border-right: 1px solid #e0e0e0;
        }
        .metric-card:last-child {
            border-right: none;
        }
        .metric-label {
            font-size: 13px;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 1px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: #2e7d32;
        }
        .metric-value.negative {
            color: #d32f2f;
        }
        .content {
            padding: 50px 40px;
        }
        .section {
            margin-bottom: 60px;
        }
        .section-title {
            font-size: 28px;
            color: #2e7d32;
            margin-bottom: 24px;
            font-weight: 600;
            border-bottom: 3px solid #2e7d32;
            padding-bottom: 12px;
        }
        .chart-container {
            background: #fafafa;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid #e0e0e0;
        }
        .chart-title {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .bar-chart {
            margin: 20px 0;
        }
        .bar-item {
            margin-bottom: 15px;
        }
        .bar-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .bar-bg {
            background: #e0e0e0;
            height: 30px;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }
        .bar-fill {
            background: linear-gradient(90deg, #2e7d32, #4caf50);
            height: 100%;
            display: flex;
            align-items: center;
            padding-left: 10px;
            color: white;
            font-weight: 600;
            font-size: 13px;
        }
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #2e7d32;
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .highlight {
            background: #fff9e6 !important;
            fontweight: 600;
        }
        .footer {
            background: #f5f5f5;
            padding: 30px 40px;
            text-align: center;
            font-size: 14px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #2e7d32;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            z-index: 1000;
        }
        .print-btn:hover {
            background: #1b5e20;
        }
        @media print {
            body { padding: 0; background: white; }
            .print-btn { display: none; }
            .container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-btn">📄 Print / Save as PDF</button>
    
    <div class="container">
        <div class="header">
            <h1>Investor Presentation</h1>
            <div class="subtitle">$farmName</div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">Total Capital Invested</div>
                <div class="metric-value">XAF {$this->formatNumber($totalCapitalInvested)}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">ROI</div>
                <div class="metric-value " . ($roi < 0 ? 'negative' : '') . ">{$this->formatPercent($roi)}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Total Revenue</div>
                <div class="metric-value">XAF {$this->formatNumber($totalRevenue)}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Net Profit</div>
                <div class="metric-value " . ($netProfit < 0 ? 'negative' : '') . ">XAF {$this->formatNumber($netProfit)}</div>
            </div>
        </div>

        <div class="content">
            <!-- Capital Allocation Chart -->
            <div class="section">
                <h2 class="section-title">Capital Allocation</h2>
                <div class="chart-container">
                    <div class="chart-title">Investment Distribution by Category</div>
                    <div class="bar-chart">
HTML;

    $maxCapital = !empty($capitalData) ? max(array_column($capitalData, 'amount_invested')) : 1;
    foreach ($capitalData as $item) {
        $cat = htmlspecialchars($item['category'] ?: 'Other');
        $amount = $item['amount_invested'];
        $percent = $maxCapital > 0 ? ($amount / $maxCapital * 100) : 0;
        $formatted = $this->formatNumber($amount);
        
        echo <<<BAR
                        <div class="bar-item">
                            <div class="bar-label">$cat</div>
                            <div class="bar-bg">
                                <div class="bar-fill" style="width: {$percent}%">XAF $formatted</div>
                            </div>
                        </div>
BAR;
    }

    echo <<<HTML
                    </div>
                </div>
            </div>

            <!-- Asset Breakdown -->
            <div class="section">
                <h2 class="section-title">Asset Valuation</h2>
                <div class="chart-container">
                    <div class="chart-title">Asset Details (Total: XAF {$this->formatNumber($totalAssetValue)})</div>
                    <table>
                        <tr>
                            <th>Asset Type</th>
                            <th>Count</th>
                            <th>Value (XAF)</th>
                            <th>% of Total</th>
                        </tr>
HTML;

    foreach ($assetData as $asset) {
        $type = htmlspecialchars($asset['asset_type'] ?: 'Other');
        $count = $asset['count'];
        $value = $this->formatNumber($asset['total_value']);
        $percent = $totalAssetValue > 0 ? round(($asset['total_value'] / $totalAssetValue) * 100, 1) : 0;
        echo "<tr><td>$type</td><td>$count</td><td>$value</td><td>{$percent}%</td></tr>";
    }

    echo <<<HTML
                    </table>
                </div>
           </div>

            <!-- Crop Performance -->
            <div class="section">
                <h2 class="section-title">Crop Performance & ROI</h2>
                <div class="chart-container">
                    <div class="chart-title">Revenue by Crop</div>
                    <div class="bar-chart">
HTML;

    $maxRevenue = !empty($cropData) ? max(array_column($cropData, 'revenue')) : 1;
    foreach ($cropData as $crop) {
        $cropName = htmlspecialchars($crop['crop_name']);
        $revenue = $crop['revenue'];
        $percent = $maxRevenue > 0 ? ($revenue / $maxRevenue * 100) : 0;
        $formatted = $this->formatNumber($revenue);
        
        echo <<<BAR
                        <div class="bar-item">
                            <div class="bar-label">$cropName</div>
                            <div class="bar-bg">
                                <div class="bar-fill" style="width: {$percent}%">XAF $formatted</div>
                            </div>
                        </div>
BAR;
    }

    echo <<<HTML
                    </div>
                </div>
                
                <table>
                    <tr>
                        <th>Crop</th>
                        <th>Area (ha)</th>
                        <th>Expected Yield</th>
                        <th>Actual Yield</th>
                        <th>Revenue (XAF)</th>
                        <th>Performance</th>
                    </tr>
HTML;

    foreach ($cropData as $crop) {
        $cropName = htmlspecialchars($crop['crop_name']);
        $area = number_format($crop['planted_area'], 2);
        $expected = number_format($crop['expected_yield'], 0);
        $actual = number_format($crop['total_yield'], 0);
        $revenue = $this->formatNumber($crop['revenue']);
        
        $performance = $crop['expected_yield'] > 0 ? 
            round(($crop['total_yield'] / $crop['expected_yield']) * 100, 1) : 0;
        $perfClass = $performance >= 80 ? 'highlight' : '';
        
        echo "<tr class='$perfClass'>";
        echo "<td>$cropName</td>";
        echo "<td>$area</td>";
        echo "<td>$expected</td>";
        echo "<td>$actual</td>";
        echo "<td>$revenue</td>";
        echo "<td>{$performance}%</td>";
        echo "</tr>";
    }

    echo <<<HTML
                </table>
            </div>

            <!-- Financial Summary -->
            <div class="section">
                <h2 class="section-title">Financial Summary</h2>
                <table>
                    <tr>
                        <th>Metric</th>
                        <th>Amount (XAF)</th>
                    </tr>
                    <tr>
                        <td>Total Revenue</td>
                        <td>{$this->formatNumber($totalRevenue)}</td>
                    </tr>
                    <tr>
                        <td>Total Operating Costs</td>
                        <td>{$this->formatNumber($totalCosts)}</td>
                    </tr>
                    <tr class="highlight">
                        <td><strong>Net Profit</strong></td>
                        <td><strong>{$this->formatNumber($netProfit)}</strong></td>
                    </tr>
                    <tr>
                        <td>Asset Value</td>
                        <td>{$this->formatNumber($totalAssetValue)}</td>
                    </tr>
                    <tr class="highlight">
                        <td><strong>Return on Investment (ROI)</strong></td>
                        <td><strong>{$this->formatPercent($roi)}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            © " .date('Y'). " $farmName | Generated by Farm Management System on " . date('F d, Y \\a\\t H:i') . "
        </div>
    </div>
</body>
</html>
HTML;
    exit;
}

private function formatNumber($number) {
    return number_format($number, 2);
}

private function formatPercent($number) {
    return number_format($number, 2) . '%';
}

private function getCurrentYear() {
    return date('Y');
}

private function formatDate($date) {
    return date('F d, Y \\a\\t H:i', strtotime($date));
}
