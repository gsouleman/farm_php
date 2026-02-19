<?php

class ReportGenerator
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function produce($type)
    {
        $farmId = $_GET['farm_id'] ?? null;
        $format = $_GET['format'] ?? 'json';

        if (!$farmId) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Farm ID is required'];
        }

        switch ($type) {
            case 'financials':
            case 'growth':
            case 'crop-budget':
                return $this->generateFinancialReport($farmId, $format);

            case 'inventory':
                return $this->generateInventoryReport($farmId, $format);

            case 'activities':
            case 'activity-log':
            case 'risk':
                return $this->generateActivityLog($farmId, $format);

            default:
                return $this->generateFinancialReport($farmId, $format);
        }
    }

    private function generateFinancialReport($farmId, $format)
    {
        $sqlExpenses = "SELECT 
                            'Expense' as type,
                            a.activity_date as date,
                            a.activity_type as category,
                            f.name as field_name,
                            a.total_cost as amount
                        FROM activities a
                        LEFT JOIN fields f ON a.field_id = f.id
                        WHERE a.farm_id = :farm_id1";

        $sqlIncome = "SELECT 
                            'Income' as type,
                            h.harvest_date as date,
                            c.crop_type as category,
                            f.name as field_name,
                            (COALESCE(h.quantity, 0) * COALESCE(h.price_per_unit, 0)) as amount
                        FROM harvests h
                        JOIN crops c ON h.crop_id = c.id
                        JOIN fields f ON c.field_id = f.id
                        WHERE f.farm_id = :farm_id2";

        $sql = "$sqlExpenses UNION ALL $sqlIncome ORDER BY date DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':farm_id1' => $farmId, ':farm_id2' => $farmId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($format === 'csv') {
            $this->outputCSV($data, 'financial_report.csv');
        } elseif ($format === 'html_print') {
            $this->outputHTMLPrint("Financial Report", $data, ['Type', 'Date', 'Category', 'Field', 'Amount']);
        }

        return ['success' => true, 'data' => $data];
    }

    private function generateInventoryReport($farmId, $format)
    {
        $sql = "SELECT 
                    name, 
                    type, 
                    quantity as current_stock, 
                    unit, 
                    cost_per_unit as unit_cost, 
                    (quantity * cost_per_unit) as total_value 
                FROM inputs 
                WHERE farm_id = :farm_id
                ORDER BY type, name";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':farm_id' => $farmId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($format === 'csv') {
            $this->outputCSV($data, 'inventory_report.csv');
        } elseif ($format === 'html_print') {
            $this->outputHTMLPrint("Inventory Valuation Report", $data, ['Item', 'Type', 'Stock', 'Unit', 'Unit Cost', 'Total Value']);
        }

        return ['success' => true, 'data' => $data];
    }

    private function generateActivityLog($farmId, $format)
    {
        $sql = "SELECT 
                    a.activity_date, 
                    f.name as field, 
                    a.activity_type, 
                    a.notes,
                    a.work_status 
                FROM activities a
                JOIN fields f ON a.field_id = f.id
                WHERE a.farm_id = :farm_id
                ORDER BY a.activity_date DESC
                LIMIT 100";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':farm_id' => $farmId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($format === 'csv') {
            $this->outputCSV($data, 'activity_log.csv');
        } elseif ($format === 'html_print') {
            $this->outputHTMLPrint("Activity Log (Last 100)", $data, ['Date', 'Field', 'Type', 'Notes', 'Status']);
        }

        return ['success' => true, 'data' => $data];
    }

    public function productionCost($cropId)
    {
        $sql = "SELECT 
                    SUM(a.total_cost) as total_cost,
                    a.activity_type
                FROM activities a
                LEFT JOIN fields f ON a.field_id = f.id
                WHERE f.current_crop_id = :crop_id
                GROUP BY a.activity_type";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':crop_id' => $cropId]);
        $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0;
        foreach ($breakdown as $row) {
            $total += $row['total_cost'];
        }

        return [
            'success' => true,
            'data' => [
                'crop_id' => $cropId,
                'total_cost' => $total,
                'breakdown' => $breakdown
            ]
        ];
    }

    public function costs($farmId)
    {
        return ['success' => true, 'data' => []];
    }

    public function timeline($farmId, $days)
    {
        return ['success' => true, 'data' => []];
    }

    public function dashboard($farmId)
    {
        return ['success' => true, 'data' => []];
    }

    public function cropBudget($farmId)
    {
        return ['success' => true, 'data' => []];
    }

    public function production($farmId)
    {
        return ['success' => true, 'data' => []];
    }

    private function outputCSV($data, $filename)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit();
    }

    private function outputHTMLPrint($title, $data, $headers)
    {
        header('Content-Type: text/html');
        echo "
        <html>
        <head>
            <title>$title</title>
            <style>
                body { font-family: sans-serif; padding: 20px; }
                h1 { text-align: center; color: #333; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .footer { margin-top: 30px; font-size: 12px; color: #888; text-align: center; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body onload='window.print()'>
            <button class='no-print' onclick='window.print()' style='margin-bottom: 20px; padding: 10px 20px; cursor:pointer;'>🖨️ Print / Save as PDF</button>
            <h1>PRO FARM - $title</h1>
            <p style='text-align:center'>Generated on: " . date('Y-m-d H:i') . "</p>
            <table>
                <thead><tr>";

        foreach ($headers as $h) {
            echo "<th>$h</th>";
        }

        echo "</tr></thead><tbody>";

        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell ?? '') . "</td>";
            }
            echo "</tr>";
        }

        echo "</tbody></table>
            <div class='footer'>Generated by ProFarm Enterprise System</div>
        </body>
        </html>";
        exit();
    }
}
