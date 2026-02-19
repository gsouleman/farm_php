<?php
// Targeted patch script to enrich cameroon_crops table with Highland data

header("Content-Type: text/plain");
require_once __DIR__ . '/config/database.php';

echo "=== CROP DATA ENRICHMENT PATCH 2026 ===\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    $crops = [
        ['Sorghum', 'Sorghum bicolor', 'Sorgho', 'cereal', 140, 550, '20-30°C', '5.0-7.5', 'North, Far North, West', 'Mar-Jun, Aug-Oct', '1.0-2.5 t/ha', 'S-35', 'Striga', 10, 1300],
        ['Millet', 'Pennisetum glaucum', 'Millet', 'cereal', 110, 500, '20-32°C', '5.0-7.0', 'Far North, North, West', 'Mar-Jun', '0.8-1.5 t/ha', 'Bourema', 'Mildew', 10, 1100],
        ['Yam', 'Dioscorea spp.', 'Igname', 'tuber', 210, 1200, '25-30°C', '5.5-6.5', 'West, Centre, East', 'Mar-Oct', '12-25 t/ha', 'White Yam', 'Anthracnose', 15, 1600],
        ['Cocoyam', 'Colocasia esculenta', 'Macabo/Taro', 'tuber', 270, 1750, '20-28°C', '5.5-7.0', 'Littoral, West, SW', 'Mar-Jun, Aug-Sep', '8-15 t/ha', 'Irad White', 'Root rot', 15, 1900],
        ['Sweet Potato', 'Ipomoea batatas', 'Patate', 'tuber', 105, 600, '20-28°C', '5.0-7.0', 'West, NW, North', 'Mar-Jun, Aug-Oct', '10-20 t/ha', 'TIB 1', 'Weevil', 12, 1100],
        ['Irish Potato', 'Solanum tuberosum', 'Pomme de terre', 'tuber', 100, 650, '15-20°C', '5.0-6.0', 'West, NW', 'Mar-Jun, Aug-Oct', '15-30 t/ha', 'Cipira', 'Late blight', 7, 1000],
        ['Beans (Haricot)', 'Phaseolus vulgaris', 'Haricot', 'legume', 95, 600, '15-27°C', '6.0-7.5', 'West, NW', 'Mar-Jun, Aug-Oct', '0.8-2.5 t/ha', 'NITU', 'Anthracnose', 10, 900],
        ['Coffee Arabica', 'Coffea arabica', 'Café Arabica', 'cash_crop', 1200, 1500, '18-24°C', '5.5-6.5', 'West, NW', 'Sep-Dec (Harvest)', '1.0-2.0 t/ha', 'Java, Blue Mountain', 'Leaf rust', 10, 2500],
        ['Plantain', 'Musa paradisiaca', 'Plantain', 'fruit', 400, 2000, '25-30°C', '5.5-7.0', 'South, Centre, West, SW', 'Mar-Nov', '15-30 t/ha', 'Big Ebanga', 'Black sigatoka', 12, 3500],
        ['Banana', 'Musa spp.', 'Banane', 'fruit', 330, 2000, '25-30°C', '5.5-7.0', 'Littoral, SW, West', 'Year-round', '20-40 t/ha', 'Grande Naine', 'Panama disease', 12, 3000],
        ['Groundnut', 'Arachis hypogaea', 'Arachide', 'legume', 110, 600, '22-28°C', '5.5-7.0', 'North, West, Far North', 'Mar-Jun, Aug-Oct', '0.7-2.0 t/ha', 'JL 24', 'Rosette', 10, 1400]
    ];

    $checkStmt = $db->prepare("SELECT id FROM cameroon_crops WHERE crop_name = ?");
    $insertStmt = $db->prepare("INSERT INTO cameroon_crops (crop_name, scientific_name, local_names, crop_type, growing_period_days, water_requirement_mm, optimal_temperature, soil_ph_range, main_regions, planting_seasons, yield_range, key_varieties, pests_diseases, gdd_base_temp, gdd_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $updateStmt = $db->prepare("UPDATE cameroon_crops SET scientific_name=?, local_names=?, crop_type=?, growing_period_days=?, water_requirement_mm=?, optimal_temperature=?, soil_ph_range=?, main_regions=?, planting_seasons=?, yield_range=?, key_varieties=?, pests_diseases=?, gdd_base_temp=?, gdd_required=? WHERE id=?");

    foreach ($crops as $crop) {
        $name = $crop[0];
        $checkStmt->execute([$name]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            echo "Updating existing crop: $name\n";
            // Remove name from data for update stmt, add id to the end
            $data = array_slice($crop, 1);
            $data[] = $existing['id'];
            $updateStmt->execute($data);
        } else {
            echo "Inserting new crop: $name\n";
            $insertStmt->execute($crop);
        }
    }

    echo "\nPatch completed successfully!\n";
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
}
