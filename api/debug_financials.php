<?php
// api/debug_financials.php
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$farmId = 'e9e9a3e6-c43b-467e-b96c-c43d9309e0ed'; // Hardcoded for your farm

try {
    // 1. Get Harvests (Revenue) - Join with Crops and Fields to filter by Farm
    $sqlH = "SELECT h.id, h.harvest_date, h.crop_id, h.total_revenue, h.quantity 
             FROM harvests h
             JOIN crops c ON h.crop_id = c.id
             JOIN fields f ON c.field_id = f.id
             WHERE f.farm_id = :farm_id";
    $stmtH = $db->prepare($sqlH);
    $stmtH->execute([':farm_id' => $farmId]);
    $harvests = $stmtH->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get Activities (Cost)
    $sqlA = "SELECT id, activity_date, crop_id, total_cost, activity_type FROM activities WHERE farm_id = :farm_id";
    $stmtA = $db->prepare($sqlA);
    $stmtA->execute([':farm_id' => $farmId]);
    $activities = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    $suspicious = [];

    foreach ($harvests as $h) {
        $rev = floatval($h['total_revenue']);
        if ($rev <= 0) continue;

        foreach ($activities as $a) {
            $cost = floatval($a['total_cost']);

            // Check for exact match or very close match (floating point)
            if (abs($rev - $cost) < 1.0) {
                $suspicious[] = [
                    'message' => 'POTENTIAL DUPLICATE FOUND!',
                    'amount' => $rev,
                    'harvest' => [
                        'id' => $h['id'],
                        'date' => $h['harvest_date'],
                        'revenue' => $h['total_revenue']
                    ],
                    'activity' => [
                        'id' => $a['id'],
                        'type' => $a['activity_type'],
                        'date' => $a['activity_date'],
                        'cost' => $a['total_cost']
                    ],
                    'advice' => 'You likely entered the Harvest Value as the Activity Cost. Delete this Activity.'
                ];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($suspicious),
        'suspicious_matches' => $suspicious,
        'all_harvest_revenue' => array_column($harvests, 'total_revenue'),
        'all_activity_costs' => array_column($activities, 'total_cost')
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
