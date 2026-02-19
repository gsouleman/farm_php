<?php

/**
 * update_crops_v2.php
 * Production patch to enrich the cameroon_crops table with comprehensive technical data.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $crops = [
        ['Maize', 'Zea mays', 'Mais', 'cereal', 100, 600, '20-30°C', 'All regions', 'Mar-Jun, Aug-Dec', '1.5-3.5 t/ha'],
        ['Rice', 'Oryza sativa', 'Riz', 'cereal', 120, 1200, '25-35°C', 'North, Far North, NW', 'Jun-Oct', '2-4 t/ha'],
        ['Cassava', 'Manihot esculenta', 'Manioc', 'tuber', 270, 800, '25-30°C', 'Centre, South, Littoral, West', 'Mar-Jun, Aug-Sep', '10-25 t/ha'],
        ['Sorghum', 'Sorghum bicolor', 'Sorgho', 'cereal', 140, 550, '20-30°C', 'North, Far North, West', 'Mar-Jun, Aug-Oct', '1.0-2.5 t/ha'],
        ['Millet', 'Pennisetum glaucum', 'Millet', 'cereal', 110, 500, '20-32°C', 'Far North, North, West', 'Mar-Jun', '0.8-1.5 t/ha'],
        ['Yam', 'Dioscorea spp.', 'Igname', 'tuber', 210, 1200, '25-30°C', 'West, Centre, East', 'Mar-Oct', '12-25 t/ha'],
        ['Cocoyam', 'Colocasia esculenta', 'Macabo/Taro', 'tuber', 270, 1750, '20-28°C', 'Littoral, West, SW', 'Mar-Jun, Aug-Sep', '8-15 t/ha'],
        ['Sweet Potato', 'Ipomoea batatas', 'Patate', 'tuber', 105, 600, '20-28°C', 'West, NW, North', 'Mar-Jun, Aug-Oct', '10-20 t/ha'],
        ['Irish Potato', 'Solanum tuberosum', 'Pomme de terre', 'tuber', 100, 650, '15-20°C', 'West, NW', 'Mar-Jun, Aug-Oct', '15-30 t/ha'],
        ['Groundnuts', 'Arachis hypogaea', 'Arachide', 'legume', 110, 600, '22-28°C', 'North, West, Far North', 'Mar-Jun, Aug-Oct', '0.7-2.0 t/ha'],
        ['Beans', 'Phaseolus vulgaris', 'Haricot', 'legume', 95, 600, '15-27°C', 'West, NW', 'Mar-Jun, Aug-Oct', '0.8-2.5 t/ha'],
        ['Soybeans', 'Glycine max', 'Soja', 'legume', 110, 700, '20-30°C', 'West, NW, Centre', 'Mar-Jun, Aug-Oct', '1.0-2.0 t/ha'],
        ['Egusi', 'Cucumeropsis mannii', 'Melon', 'legume', 110, 450, '25-35°C', 'West, North, Centre', 'Apr-Jun, Sep-Oct', '0.5-0.8 t/ha'],
        ['Plantain', 'Musa paradisiaca', 'Plantain', 'fruit', 400, 2000, '25-30°C', 'South, Centre, West, SW', 'Mar-Nov', '15-30 t/ha'],
        ['Banana', 'Musa spp.', 'Banane', 'fruit', 330, 2000, '25-30°C', 'Littoral, SW, West', 'Year-round', '20-40 t/ha'],
        ['Avocado', 'Persea americana', 'Avocatier', 'fruit', 1500, 1600, '20-30°C', 'West, Littoral, SW', 'Feb-Nov', '80-100k fruits/ac'],
        ['Mango', 'Mangifera indica', 'Manguier', 'fruit', 1500, 1000, '24-27°C', 'North, West, Centre', 'Mar-May', '15-30 t/ha'],
        ['Citrus', 'Citrus spp.', 'Orange/Citron', 'fruit', 1500, 1200, '23-30°C', 'Littoral, Centre, West', 'Oct, Mar-Apr', '30-60 t/ha'],
        ['Papaya', 'Carica papaya', 'Papayer', 'fruit', 300, 1100, '25-35°C', 'All regions', 'Jun-Sep', '30-45 t/ha'],
        ['Pineapple', 'Ananas comosus', 'Ananas', 'fruit', 450, 1400, '23-32°C', 'Littoral, SW, Centre', 'Nov-Apr', '35-80 t/ha'],
        ['Safou', 'Dacryodes edulis', 'Prunier', 'fruit', 1500, 2000, '18-28°C', 'West, Centre, Littoral', 'Jun-Sep', '100-150 kg/tree'],
        ['Cocoa', 'Theobroma cacao', 'Cacao', 'cash_crop', 1500, 1800, '20-30°C', 'Centre, South, SW, East', 'Sep-Dec, May-Jul', '0.5-1.5 t/ha'],
        ['Coffee Arabica', 'Coffea arabica', 'Café Arabica', 'cash_crop', 1200, 1500, '18-24°C', 'West, NW', 'Sep-Dec', '1.0-2.0 t/ha'],
        ['Coffee Robusta', 'Coffea canephora', 'Café Robusta', 'cash_crop', 1200, 2000, '22-28°C', 'East, Littoral, West', 'Nov-Feb', '0.8-1.5 t/ha'],
        ['Oil Palm', 'Elaeis guineensis', 'Palmier à huile', 'cash_crop', 1400, 2500, '24-30°C', 'Littoral, SW, South', 'Year-round', '10-20 t/ha'],
        ['Rubber', 'Hevea brasiliensis', 'Hévéa', 'cash_crop', 2200, 2000, '24-28°C', 'South, SW', 'Year-round', '1.0-2.5 t/ha'],
        ['Cotton', 'Gossypium hirsutum', 'Coton', 'cash_crop', 160, 800, '20-35°C', 'North, Far North', 'Jun-Nov', '1.5-3.0 t/ha'],
        ['Tomato', 'Solanum lycopersicum', 'Tomate', 'vegetable', 110, 500, '18-28°C', 'All regions', 'Year-round', '15-40 t/ha'],
        ['Cabbage', 'Brassica oleracea', 'Chou', 'vegetable', 85, 450, '15-25°C', 'West, NW, Centre', 'Year-round', '20-50 t/ha'],
        ['Onion', 'Allium cepa', 'Oignon', 'vegetable', 130, 500, '15-30°C', 'Far North, North, West', 'Oct-Mar', '15-30 t/ha'],
        ['Carrot', 'Daucus carota', 'Carotte', 'vegetable', 85, 400, '15-25°C', 'West, NW, Adamawa', 'Year-round', '10-25 t/ha'],
        ['Pepper', 'Capsicum spp.', 'Piment', 'vegetable', 100, 500, '20-30°C', 'All regions', 'Year-round', '5-15 t/ha'],
        ['Ginger', 'Zingiber officinale', 'Gingembre', 'vegetable', 240, 1500, '25-30°C', 'West, Centre, NW', 'Mar-Nov', '10-20 t/ha']
    ];

    $stmt = $db->prepare("INSERT INTO cameroon_crops 
        (crop_name, scientific_name, local_names, crop_type, growing_period_days, water_requirement_mm, optimal_temperature, main_regions, planting_seasons, yield_range) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        scientific_name = VALUES(scientific_name),
        growing_period_days = VALUES(growing_period_days),
        water_requirement_mm = VALUES(water_requirement_mm),
        optimal_temperature = VALUES(optimal_temperature),
        main_regions = VALUES(main_regions),
        planting_seasons = VALUES(planting_seasons),
        yield_range = VALUES(yield_range)");

    foreach ($crops as $crop) {
        $stmt->execute($crop);
    }

    echo json_encode(['success' => true, 'message' => count($crops) . ' crops updated successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
