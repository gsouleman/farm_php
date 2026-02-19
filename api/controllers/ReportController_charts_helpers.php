    // Helper method to get monthly financial trends
    private function getMonthlyFinancialTrend($farmId)
    {
    $sql = "SELECT
    DATE_FORMAT(date, '%Y-%m') as month,
    SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END) as income,
    SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END) as expenses
    FROM (
    SELECT
    'Expense' as type,
    a.activity_date as date,
    a.total_cost as amount
    FROM activities a
    WHERE a.farm_id = :farm_id1 AND a.transaction_type = 'expense'
    UNION ALL
    SELECT
    'Income' as type,
    h.harvest_date as date,
    (COALESCE(h.quantity, 0) * COALESCE(h.price_per_unit, 0)) as amount
    FROM harvests h
    JOIN crops c ON h.crop_id = c.id
    JOIN fields f ON c.field_id = f.id
    WHERE f.farm_id = :farm_id2
    ) combined
    GROUP BY month
    ORDER BY month DESC
    LIMIT 12";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':farm_id1' => $farmId, ':farm_id2' => $farmId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $incomeData = [];
    $expenseData = [];

    foreach (array_reverse($results) as $row) {
    $labels[] = $row['month'];
    $incomeData[] = (float)$row['income'];
    $expenseData[] = (float)$row['expenses'];
    }

    return [
    'labels' => $labels,
    'income' => $incomeData,
    'expenses' => $expenseData
    ];
    }

    private function outputHTMLPrintWithCharts($title, $data, $headers, $farmId = null, $summary = [], $chartData = [])
    {
    $farmName = $farmId ? $this->getFarmName($farmId) : 'Farm Report';

    header('Content-Type: text/html');
    echo "
    <!DOCTYPE html>
    <html>

    <head>
        <title>$title - $farmName</title>
        <script src='https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'></script>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                padding: 40px;
                background: #f5f5f5;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .header {
                text-align: center;
                border-bottom: 3px solid #2e7d32;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }

            h1 {
                color: #2e7d32;
                margin: 0;
                font-size: 28px;
            }

            .farm-name {
                color: #666;
                font-size: 16px;
                margin-top: 8px;
            }

            .date {
                color: #999;
                font-size: 14px;
                margin-top: 8px;
            }

            .charts-section {
                margin: 30px 0;
            }

            .charts-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
                margin-bottom: 30px;
            }

            .chart-container {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 8px;
            }

            .chart-title {
                font-size: 14px;
                font-weight: 600;
                color: #333;
                margin-bottom: 15px;
                text-align: center;
            }

            canvas {
                max-height: 300px;
            }

            .summary {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
                background: #f9f9f9;
                padding: 20px;
                border-radius: 8px;
            }

            .summary-item {
                text-align: center;
            }

            .summary-label {
                color: #666;
                font-size: 12px;
                text-transform: uppercase;
                margin-bottom: 8px;
            }

            .summary-value {
                font-size: 24px;
                font-weight: bold;
                color: #333;
            }

            .summary-value.positive {
                color: #2e7d32;
            }

            .summary-value.negative {
                color: #d32f2f;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }

            th {
                background-color: #2e7d32;
                color: white;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 12px;
            }

            tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            tr:hover {
                background-color: #f0f0f0;
            }

            .footer {
                margin-top: 30px;
                font-size: 12px;
                color: #888;
                text-align: center;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }

            .print-btn {
                background: #2e7d32;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                margin-bottom: 20px;
            }

            .print-btn:hover {
                background: #1b5e20;
            }

            @media print {
                .no-print {
                    display: none;
                }

                body {
                    background: white;
                    padding: 0;
                }

                .container {
                    box-shadow: none;
                }

                .charts-grid {
                    page-break-inside: avoid;
                }
            }
        </style>
    </head>

    <body>
        <div class='container'>
            <button class='no-print print-btn' onclick='window.print()'>🖨️ Print / Save as PDF</button>

            <div class='header'>
                <h1>$title</h1>
                <div class='farm-name'>$farmName</div>
                <div class='date'>Generated on: " . date('F d, Y \a\t H:i') . "</div>
            </div>";

            // Summary Statistics
            if (!empty($summary)) {
            echo "<div class='summary'>";
                foreach ($summary as $item) {
                $valueClass = '';
                if (isset($item['highlight'])) {
                $valueClass = $item['highlight'] ? 'positive' : 'negative';
                }
                echo "<div class='summary-item'>";
                    echo "<div class='summary-label'>{$item['label']}</div>";
                    echo "<div class='summary-value $valueClass'>{$item['value']}</div>";
                    echo "</div>";
                }
                echo "</div>";
            }

            // Charts Section
            if (!empty($chartData)) {
            echo "<div class='charts-section'>";
                echo "<div class='charts-grid'>";

                    // Pie Chart
                    if (isset($chartData['pieChart'])) {
                    echo "<div class='chart-container'>";
                        echo "<div class='chart-title'>Income vs Expenses Distribution</div>";
                        echo "<canvas id='pieChart'></canvas>";
                        echo "</div>";
                    }

                    // Line Chart
                    if (isset($chartData['monthlyTrend'])) {
                    echo "<div class='chart-container'>";
                        echo "<div class='chart-title'>Monthly Revenue Trend (Last 12 Months)</div>";
                        echo "<canvas id='lineChart'></canvas>";
                        echo "</div>";
                    }

                    echo "</div>
            </div>";
            }

            // Data Table
            echo "<table>
                <thead>
                    <tr>";
                        foreach ($headers as $h) {
                        echo "<th>$h</th>";
                        }
                        echo "</tr>
                </thead>
                <tbody>";

                    foreach ($data as $row) {
                    echo "<tr>";
                        foreach ($row as $cell) {
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                        }
                        echo "</tr>";
                    }

                    echo "</tbody>
            </table>";

            echo "
            <div class='footer'>
                <p>&copy; " . date('Y') . " $farmName | Generated by Farm Management System</p>
            </div>
        </div>

        <script>
            ";

            // Generate Chart.js code
            if (!empty($chartData)) {
                if (isset($chartData['pieChart'])) {
                    $labels = json_encode($chartData['pieChart']['labels']);
                    $data = json_encode($chartData['pieChart']['data']);
                    $colors = json_encode($chartData['pieChart']['colors']);

                    echo "
                    new Chart(document.getElementById('pieChart'), {
                        type: 'pie',
                        data: {
                            labels: $labels,
                            datasets: [{
                                data: $data,
                                backgroundColor: $colors,
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                    ";
                }

                if (isset($chartData['monthlyTrend'])) {
                    $labels = json_encode($chartData['monthlyTrend']['labels']);
                    $incomeData = json_encode($chartData['monthlyTrend']['income']);
                    $expenseData = json_encode($chartData['monthlyTrend']['expenses']);

                    echo "
                    new Chart(document.getElementById('lineChart'), {
                        type: 'line',
                        data: {
                            labels: $labels,
                            datasets: [{
                                label: 'Income',
                                data: $incomeData,
                                borderColor: '#2e7d32',
                                backgroundColor: 'rgba(46, 125, 50, 0.1)',
                                tension: 0.4,
                                fill: true
                            }, {
                                label: 'Expenses',
                                data: $expenseData,
                                borderColor: '#d32f2f',
                                backgroundColor: 'rgba(211, 47, 47, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                    ";
                }
            }

            echo "
        </script>
    </body>

    </html>";
    exit();
    }