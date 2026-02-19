<?php
// api/controllers/PlantingSeasonController.php

require_once __DIR__ . '/../rainfall_onset.php';

class PlantingSeasonController
{
    private $db;
    private $detector;

    public function __construct($db)
    {
        $this->db = $db;
        $this->detector = new RainfallOnsetDetector();
    }

    /**
     * Get all agricultural regions
     */
    public function getRegions()
    {
        $stmt = $this->db->query("SELECT * FROM cameroon_regions ORDER BY name ASC");
        return ['success' => true, 'data' => $stmt->fetchAll()];
    }

    /**
     * Get all crop profiles
     */
    public function getCrops()
    {
        $stmt = $this->db->query("SELECT * FROM cameroon_crops ORDER BY crop_name ASC");
        return ['success' => true, 'data' => $stmt->fetchAll()];
    }

    /**
     * Get agro-ecological zones
     */
    public function getZones()
    {
        $stmt = $this->db->query("SELECT * FROM agro_ecological_zones");
        return ['success' => true, 'data' => $stmt->fetchAll()];
    }

    /**
     * Get traditional calendars
     */
    public function getCalendars($regionId = null)
    {
        $sql = "SELECT tc.*, cr.name as region_name FROM traditional_calendars tc 
                JOIN cameroon_regions cr ON tc.region_id = cr.id";
        if ($regionId) {
            $stmt = $this->db->prepare($sql . " WHERE tc.region_id = ?");
            $stmt->execute([$regionId]);
        } else {
            $stmt = $this->db->query($sql);
        }
        return ['success' => true, 'data' => $stmt->fetchAll()];
    }

    /**
     * Analyze Rainfall Onset for a specific location
     */
    public function analyzeOnset()
    {
        $lat = $_GET['lat'] ?? null;
        $lng = $_GET['lng'] ?? null;
        $regionType = $_GET['zone'] ?? 'equatorial';

        if (!$lat || !$lng) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Coordinates required'];
        }

        // Fetch last 90 days of rainfall from Open-Meteo Archive/Forecast
        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lng}&daily=precipitation_sum&past_days=92&forecast_days=7&timezone=auto";
        $apiData = $this->fetchRaw($url);

        if (!$apiData || !isset($apiData['daily'])) {
            return ['success' => false, 'message' => 'Failed to fetch precipitation data'];
        }

        $daily = $apiData['daily'];
        $precipData = [];
        for ($i = 0; $i < count($daily['time']); $i++) {
            $precipData[] = [
                'date' => $daily['time'][$i],
                'precip' => $daily['precipitation_sum'][$i] ?? 0
            ];
        }

        $onset = $this->detector->detectOnset($precipData, $regionType);

        return [
            'success' => true,
            'data' => [
                'onset' => $onset,
                'location' => ['lat' => $lat, 'lng' => $lng],
                'region_type' => $regionType,
                'summary' => $this->generateSummary($onset, $regionType),
                'precip_history' => $precipData
            ]
        ];
    }

    private function generateSummary($onset, $zone)
    {
        if (!$onset || !isset($onset['start_date'])) {
            return "Stable rainfall onset not yet detected for this season. Monitor for consecutive wet days (>1mm) before planting.";
        }

        $date = date('F j, Y', strtotime($onset['start_date']));
        $msg = "Rainfall onset detected on {$date} with {$onset['confidence']}% confidence. ";

        if ($onset['confidence'] > 80) {
            $msg .= "This is a strong signal. Ideal for main crop planting (Maize, Groundnuts).";
        } else if ($onset['confidence'] > 60) {
            $msg .= "Conditions are favorable, but monitor soil moisture depth (min 15cm) before high-value seeding.";
        } else {
            $msg .= "Onset signal is weak; potentially a false start. Consider staggered planting.";
        }

        return $msg;
    }

    private function fetchRaw($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
