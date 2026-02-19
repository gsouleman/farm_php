<?php
// api/seed_agri_data.php

require_once __DIR__ . '/config/database.php';

class CameroonDataSeeder
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function seedAllData()
    {
        echo "Seeding Cameroon agricultural database...\n";

        $this->createSchema();
        $this->seedRegions();
        $this->seedAgroEcologicalZones();
        $this->seedCrops();
        $this->seedTraditionalCalendars();
        $this->seedSoilTypes();
        $this->seedClimateRisks();
        $this->seedMarketCenters();

        echo "Database seeding completed!\n";
    }

    private function createSchema()
    {
        $queries = [
            "DROP TABLE IF EXISTS market_centers",
            "DROP TABLE IF EXISTS climate_risks",
            "DROP TABLE IF EXISTS soil_types",
            "DROP TABLE IF EXISTS traditional_calendars",
            "DROP TABLE IF EXISTS cameroon_crops",
            "DROP TABLE IF EXISTS agro_ecological_zones",
            "DROP TABLE IF EXISTS cameroon_regions",

            "CREATE TABLE cameroon_regions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(50) NOT NULL,
                capital VARCHAR(50),
                population INT,
                area_km2 FLOAT,
                agro_zone VARCHAR(30),
                main_livelihood TEXT,
                rainfall_mm FLOAT,
                altitude_range VARCHAR(50),
                latitude DECIMAL(10,8),
                longitude DECIMAL(11,8),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE agro_ecological_zones (
                id INT PRIMARY KEY AUTO_INCREMENT,
                zone_name VARCHAR(100) NOT NULL,
                regions TEXT,
                soil_type TEXT,
                rainfall_pattern VARCHAR(100),
                growing_seasons INT,
                main_constraint TEXT,
                adaptation_strategies TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE cameroon_crops (
                id INT PRIMARY KEY AUTO_INCREMENT,
                crop_name VARCHAR(100) NOT NULL,
                scientific_name VARCHAR(100),
                local_names TEXT,
                crop_type ENUM('cereal', 'tuber', 'legume', 'vegetable', 'fruit', 'cash_crop'),
                growing_period_days INT,
                water_requirement_mm FLOAT,
                optimal_temperature VARCHAR(50),
                soil_ph_range VARCHAR(50),
                main_regions TEXT,
                planting_seasons VARCHAR(100),
                yield_range VARCHAR(100),
                key_varieties TEXT,
                pests_diseases TEXT,
                gdd_base_temp FLOAT DEFAULT 10,
                gdd_required INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE traditional_calendars (
                id INT PRIMARY KEY AUTO_INCREMENT,
                region_id INT,
                ethnic_group VARCHAR(100),
                crop_name VARCHAR(100),
                planting_signs TEXT,
                planting_month VARCHAR(50),
                harvesting_month VARCHAR(50),
                special_rituals TEXT,
                reliability_score INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (region_id) REFERENCES cameroon_regions(id)
            )",

            "CREATE TABLE soil_types (
                id INT PRIMARY KEY AUTO_INCREMENT,
                region_id INT,
                soil_name VARCHAR(100),
                texture VARCHAR(50),
                ph_range VARCHAR(50),
                organic_matter VARCHAR(50),
                drainage VARCHAR(50),
                fertility VARCHAR(50),
                suitable_crops TEXT,
                improvement_needs TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (region_id) REFERENCES cameroon_regions(id)
            )",

            "CREATE TABLE climate_risks (
                id INT PRIMARY KEY AUTO_INCREMENT,
                region_id INT,
                risk_type ENUM('drought', 'flood', 'heatwave', 'late_rains', 'early_rains', 'strong_winds', 'pest_outbreak'),
                typical_months VARCHAR(100),
                probability FLOAT,
                impact_level ENUM('low', 'medium', 'high', 'very_high'),
                mitigation_strategies TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (region_id) REFERENCES cameroon_regions(id)
            )",

            "CREATE TABLE market_centers (
                id INT PRIMARY KEY AUTO_INCREMENT,
                region_id INT,
                market_name VARCHAR(100),
                city VARCHAR(100),
                main_products TEXT,
                market_days VARCHAR(100),
                peak_season VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (region_id) REFERENCES cameroon_regions(id)
            )"
        ];

        foreach ($queries as $query) {
            $this->db->exec($query);
        }
        echo "Schema created.\n";
    }

    private function seedRegions()
    {
        $regions = [
            ['Adamawa', 'Ngaoundéré', 1200000, 63701, 'sudanian', 'cattle rearing, maize, beans', 1500, '900-1400m', 7.321389, 13.583889],
            ['Centre', 'Yaoundé', 4000000, 68926, 'equatorial', 'food crops, cocoa, coffee', 1600, '700-1100m', 3.848033, 11.502075],
            ['East', 'Bertoua', 830000, 109002, 'equatorial', 'cocoa, coffee, timber, food crops', 1500, '500-900m', 4.577556, 13.675580],
            ['Far North', 'Maroua', 3900000, 34263, 'sahelian', 'cotton, millet, sorghum, livestock', 800, '300-500m', 10.592405, 14.315936],
            ['Littoral', 'Douala', 3500000, 20239, 'equatorial', 'banana, plantain, palm oil, fishing', 4000, '0-200m', 4.051056, 9.767868],
            ['North', 'Garoua', 2200000, 66090, 'sudanian', 'cotton, maize, sorghum, livestock', 1000, '200-500m', 9.306840, 13.393389],
            ['Northwest', 'Bamenda', 2000000, 17300, 'sudano_guinean', 'coffee, maize, beans, potatoes', 2000, '1000-2000m', 5.963051, 10.159101],
            ['South', 'Ebolowa', 750000, 47191, 'equatorial', 'cocoa, coffee, food crops', 1800, '500-800m', 2.904841, 11.152077],
            ['Southwest', 'Buea', 1500000, 25410, 'equatorial', 'banana, oil palm, rubber, cocoa', 3000, '0-4095m', 4.155719, 9.241078],
            ['West', 'Bafoussam', 1900000, 13892, 'sudano_guinean', 'coffee, maize, beans, potatoes', 1500, '1000-1500m', 5.478605, 10.417126]
        ];

        $stmt = $this->db->prepare("INSERT INTO cameroon_regions (name, capital, population, area_km2, agro_zone, main_livelihood, rainfall_mm, altitude_range, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($regions as $region) {
            $stmt->execute($region);
        }
        echo "Regions seeded.\n";
    }

    private function seedAgroEcologicalZones()
    {
        $zones = [
            ['Equatorial Forest', 'Littoral, South, Southwest, Centre, East', 'Ferralsols, Acrisols', 'Bimodal (Mar-Jun, Sep-Nov)', 2, 'Soil acidity, high pest pressure', 'Use acid-tolerant varieties, IPM'],
            ['Sudano-Guinean Savannah', 'West, Northwest, Adamawa', 'Ferruginous, volcanic', 'Unimodal (Apr-Oct)', 1, 'Soil erosion, declining fertility', 'Contour farming, organic matter'],
            ['Sudanian Savannah', 'North, Adamawa', 'Tropical ferruginous', 'Unimodal (May-Sep)', 1, 'Water stress, soil degradation', 'Water harvesting, drought-tolerant v.'],
            ['Sahelian', 'Far North', 'Aridisols, sandy', 'Short unimodal (Jun-Sep)', 1, 'Drought, high temperatures', 'Irrigation, short-cycle v.'],
            ['Western Highlands', 'Northwest, West', 'Volcanic, Andosols', 'Bimodal (Mar-Jun, Aug-Oct)', 2, 'Soil erosion on slopes', 'Terracing, agroforestry']
        ];
        $stmt = $this->db->prepare("INSERT INTO agro_ecological_zones (zone_name, regions, soil_type, rainfall_pattern, growing_seasons, main_constraint, adaptation_strategies) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($zones as $zone) {
            $stmt->execute($zone);
        }
        echo "Zones seeded.\n";
    }

    private function seedCrops()
    {
        $crops = [
            ['Maize', 'Zea mays', 'Mais', 'cereal', 100, 600, '20-30°C', '5.5-7.0', 'All regions', 'Mar-Jun, Aug-Dec', '1.5-3.5 t/ha', 'CMS 8704, CMS 9015', 'Fall armyworm', 10, 1200],
            ['Rice', 'Oryza sativa', 'Riz', 'cereal', 120, 1200, '25-35°C', '5.0-6.5', 'North, Far North, NW', 'Jun-Oct', '2-4 t/ha', 'NERICA 3, NERICA 8', 'Blast', 10, 1500],
            ['Cassava', 'Manihot esculenta', 'Manioc', 'tuber', 270, 800, '25-30°C', '4.5-7.0', 'Centre, South, Littoral, West', 'Mar-Jun, Aug-Sep', '10-25 t/ha', 'TME 419, TMS 92/0326', 'Mosaic, Root rot', 15, 1800],
            ['Sorghum', 'Sorghum bicolor', 'Sorgho', 'cereal', 140, 550, '20-30°C', '5.0-7.5', 'North, Far North, West', 'Mar-Jun, Aug-Oct', '1.0-2.5 t/ha', 'IRAD S-35, CS-54', 'Striga', 10, 1300],
            ['Millet', 'Pennisetum glaucum', 'Millet', 'cereal', 110, 500, '20-32°C', '5.0-7.0', 'Far North, North, West', 'Mar-Jun', '0.8-1.5 t/ha', 'Local Early, Local Late', 'Mildew', 10, 1100],
            ['Yam', 'Dioscorea spp.', 'Igname', 'tuber', 210, 1200, '25-30°C', '5.5-6.5', 'West, Centre, East', 'Mar-Oct', '12-25 t/ha', 'White Yam, Yellow Yam', 'Anthracnose', 15, 1600],
            ['Cocoyam', 'Colocasia esculenta', 'Macabo/Taro', 'tuber', 270, 1750, '20-28°C', '5.5-7.0', 'Littoral, West, SW', 'Mar-Jun, Aug-Sep', '8-15 t/ha', 'Irad White, Ibo Macabo', 'Root rot', 15, 1900],
            ['Sweet Potato', 'Ipomoea batatas', 'Patate', 'tuber', 105, 600, '20-28°C', '5.0-7.0', 'West, NW, North', 'Mar-Jun, Aug-Oct', '10-20 t/ha', 'TIB 1, OFSP', 'Weevil', 12, 1100],
            ['Irish Potato', 'Solanum tuberosum', 'Pomme de terre', 'tuber', 100, 650, '15-20°C', '5.0-6.0', 'West, NW', 'Mar-Jun, Aug-Oct', '15-30 t/ha', 'Cipira, Bambui Red', 'Late blight', 7, 1000],
            ['Groundnuts', 'Arachis hypogaea', 'Arachide', 'legume', 110, 600, '22-28°C', '5.5-7.0', 'North, West, Far North', 'Mar-Jun, Aug-Oct', '0.7-2.0 t/ha', 'JL 24, 28-206', 'Rosette', 10, 1400],
            ['Beans', 'Phaseolus vulgaris', 'Haricot', 'legume', 95, 600, '15-27°C', '6.0-7.5', 'West, NW', 'Mar-Jun, Aug-Oct', '0.8-2.5 t/ha', 'NITU, GLP 2', 'Anthracnose', 10, 900],
            ['Soybeans', 'Glycine max', 'Soja', 'legume', 110, 700, '20-30°C', '6.0-7.0', 'West, NW, Centre', 'Mar-Jun, Aug-Oct', '1.0-2.0 t/ha', 'TGX 1910-14F', 'Rust', 12, 1100],
            ['Plantain', 'Musa paradisiaca', 'Plantain', 'fruit', 400, 2000, '25-30°C', '5.5-7.0', 'South, Centre, West, SW', 'Mar-Nov', '15-30 t/ha', 'Big Ebanga, Batard', 'Black sigatoka', 12, 3500],
            ['Banana', 'Musa spp.', 'Banane', 'fruit', 330, 2000, '25-30°C', '5.5-7.0', 'Littoral, SW, West', 'Year-round', '20-40 t/ha', 'Grande Naine, Cavendish', 'Panama disease', 12, 3000],
            ['Cocoa', 'Theobroma cacao', 'Cacao', 'cash_crop', 1500, 1800, '20-30°C', '5.0-7.5', 'Centre, South, SW, East', 'Sep-Dec, May-Jul', '0.5-1.5 t/ha', 'Hybrid, Forastero', 'Black pod', 15, 5000],
            ['Coffee Arabica', 'Coffea arabica', 'Café Arabica', 'cash_crop', 1200, 1500, '18-24°C', '5.5-6.5', 'West, NW', 'Sep-Dec', '1.0-2.0 t/ha', 'Java, Blue Mountain', 'Leaf rust', 10, 2500],
            ['Coffee Robusta', 'Coffea canephora', 'Café Robusta', 'cash_crop', 1200, 2000, '22-28°C', '5.5-6.5', 'East, Littoral, West', 'Nov-Feb', '0.8-1.5 t/ha', 'IFC 1', 'CBB', 10, 3000],
            ['Oil Palm', 'Elaeis guineensis', 'Palmier à huile', 'cash_crop', 1400, 2500, '24-30°C', '4.0-6.0', 'Littoral, SW, South', 'Year-round', '10-20 t/ha', 'Tenera', 'Fusarium', 15, 6000],
            ['Rubber', 'Hevea brasiliensis', 'Hévéa', 'cash_crop', 2200, 2000, '24-28°C', '4.5-6.5', 'South, SW', 'Year-round', '1.0-2.5 t/ha', 'GT 1, PB 217', 'Oidium', 15, 8000],
            ['Cotton', 'Gossypium hirsutum', 'Coton', 'cash_crop', 160, 800, '20-35°C', '5.8-7.5', 'North, Far North', 'Jun-Nov', '1.5-3.0 t/ha', 'IRAD Hybrid', 'Bollworm', 15, 2000],
            ['Tomato', 'Solanum lycopersicum', 'Tomate', 'vegetable', 110, 500, '18-28°C', '5.5-7.0', 'All regions', 'Year-round', '15-40 t/ha', 'Rio Grande, Cobra', 'Late blight', 12, 1200],
            ['Cabbage', 'Brassica oleracea', 'Chou', 'vegetable', 85, 450, '15-25°C', '6.0-7.0', 'West, NW, Centre', 'Year-round', '20-50 t/ha', 'KK Cross', 'DBM', 10, 900],
            ['Onion', 'Allium cepa', 'Oignon', 'vegetable', 130, 500, '15-30°C', '6.0-7.0', 'Far North, North, West', 'Oct-Mar', '15-30 t/ha', 'Galmi Violet', 'Thrips', 10, 1400],
            ['Carrot', 'Daucus carota', 'Carotte', 'vegetable', 85, 400, '15-25°C', '5.5-7.0', 'West, NW, Adamawa', 'Year-round', '10-25 t/ha', 'New Kuroda', 'Nematodes', 8, 800],
            ['Pepper', 'Capsicum spp.', 'Piment', 'vegetable', 100, 500, '20-30°C', '6.0-7.0', 'All regions', 'Year-round', '5-15 t/ha', 'Habanero', 'Viruses', 15, 1500],
            ['Ginger', 'Zingiber officinale', 'Gingembre', 'vegetable', 240, 1500, '25-30°C', '5.5-6.5', 'West, Centre, NW', 'Mar-Nov', '10-20 t/ha', 'Local Sharp', 'Soft rot', 15, 2500]
        ];
        $stmt = $this->db->prepare("INSERT INTO cameroon_crops (crop_name, scientific_name, local_names, crop_type, growing_period_days, water_requirement_mm, optimal_temperature, soil_ph_range, main_regions, planting_seasons, yield_range, key_varieties, pests_diseases, gdd_base_temp, gdd_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($crops as $crop) {
            $stmt->execute($crop);
        }
    }

    private function seedTraditionalCalendars()
    {
        $stmt = $this->db->prepare("INSERT INTO traditional_calendars (region_id, ethnic_group, crop_name, planting_signs, planting_month, harvesting_month, reliability_score) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([10, 'Bamileke', 'Maize', 'Nkong rains, waxing moon', 'March-April', 'July-August', 85]);
        $stmt->execute([4, 'Mafa', 'Millet', 'First thunder, Acacia leaves', 'June', 'October', 90]);
    }

    private function seedSoilTypes()
    {
        $stmt = $this->db->prepare("INSERT INTO soil_types (region_id, soil_name, texture, ph_range, suitable_crops, improvement_needs) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([7, 'Volcanic soils', 'Loam', '6.0-7.0', 'Potatoes, maize, coffee', 'Terracing']);
    }

    private function seedClimateRisks()
    {
        $stmt = $this->db->prepare("INSERT INTO climate_risks (region_id, risk_type, typical_months, probability, impact_level) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([4, 'drought', 'March-May', 0.7, 'high']);
    }

    private function seedMarketCenters()
    {
        $stmt = $this->db->prepare("INSERT INTO market_centers (region_id, market_name, city, market_days) VALUES (?, ?, ?, ?)");
        $stmt->execute([2, 'Mfoundi Market', 'Yaoundé', 'Daily']);
    }
}

// Execution
try {
    $database = new Database();
    $db = $database->getConnection();

    $seeder = new CameroonDataSeeder($db);
    $seeder->seedAllData();
} catch (Exception $e) {
    die("Seeding failed: " . $e->getMessage());
}
