<?php
require_once __DIR__ . '/../models/Weather.php';

class WeatherController
{
    private $db;
    private $weather;

    public function __construct($db)
    {
        $this->db = $db;
        $this->weather = new Weather($db);
    }

    /**
     * Get weather data
     */
    public function index($farmId = null)
    {
        if ($farmId) {
            $data = $this->weather->findRecentByFarm($farmId, 7);
        } else {
            $data = $this->weather->findAll();
        }

        // Parse JSON data
        foreach ($data as &$record) {
            if (isset($record['data']) && is_string($record['data'])) {
                $record['data'] = json_decode($record['data'], true);
            }
        }

        return ['success' => true, 'data' => $data];
    }

    /**
     * Get current weather
     */
    public function current($farmId)
    {
        $lat = $_GET['lat'] ?? null;
        $lng = $_GET['lng'] ?? null;

        // Auto-resolve coordinates from farm if not provided
        if (!$lat || !$lng) {
            $sql = "SELECT coordinates FROM farms WHERE id = :farm_id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':farm_id' => $farmId]);
            $farm = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($farm && $farm['coordinates']) {
                $coords = json_decode($farm['coordinates'], true);
                $lat = $coords['lat'] ?? $coords['latitude'] ?? null;
                $lng = $coords['lng'] ?? $coords['longitude'] ?? null;
            }
        }

        if ($lat && $lng) {
            // Enhanced URL with ag-specific metrics: 
            // - current: temperature, wind, weather_code
            // - hourly: relative_humidity, soil_temperature_0_to_10cm, evapotranspiration, vapor_pressure_deficit
            $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lng}" .
                "&current=temperature_2m,wind_speed_10m,weather_code" .
                "&hourly=relative_humidity_2m,soil_temperature_0_to_10cm,evapotranspiration,vapor_pressure_deficit" .
                "&timezone=auto&forecast_days=1";

            $apiData = $this->fetchRaw($url);

            if ($apiData && isset($apiData['current'])) {
                $current = $apiData['current'];
                $hourly = $apiData['hourly'];
                // Get most recent hourly data (index 0 for current hour in 1-day forecast)
                $idx = 0;

                return [
                    'temperature_avg' => $current['temperature_2m'],
                    'temp_c' => $current['temperature_2m'],
                    'conditions' => $this->getWeatherDescription($current['weather_code']),
                    'condition' => ['text' => $this->getWeatherDescription($current['weather_code'])],
                    'humidity' => $hourly['relative_humidity_2m'][$idx] ?? 60,
                    'wind_speed' => $current['wind_speed_10m'],
                    'wind_kph' => $current['wind_speed_10m'],
                    'soil_temp' => $hourly['soil_temperature_0_to_10cm'][$idx] ?? null,
                    'et0' => $hourly['evapotranspiration'][$idx] ?? null,
                    'vpd' => $hourly['vapor_pressure_deficit'][$idx] ?? null,
                    'location' => ['name' => 'Farm Field Site']
                ];
            }
        }

        // Fallback: Try to get today's cached weather from DB
        $today = date('Y-m-d');
        $cached = $this->weather->findByFarmAndDate($farmId, $today);

        if ($cached) {
            if (isset($cached['data']) && is_string($cached['data'])) {
                $cached['data'] = json_decode($cached['data'], true);
            }
            return $cached['data'] ?? $cached;
        }

        return [
            'temperature_avg' => 28,
            'temp_c' => 28,
            'conditions' => 'Partly Cloudy',
            'condition' => ['text' => 'Partly Cloudy'],
            'humidity' => 65,
            'wind_speed' => 12,
            'wind_kph' => 12,
            'pressure' => 1012,
            'precipitation' => 0,
            'uv_index' => 4,
            'dew_point' => 18,
            'location' => ['name' => 'Yaoundé (Estimate)']
        ];
    }

    /**
     * Store weather data
     */
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['farm_id'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Farm ID is required'];
        }

        $weather = $this->weather->create($data);

        if (isset($weather['data']) && is_string($weather['data'])) {
            $weather['data'] = json_decode($weather['data'], true);
        }

        http_response_code(201);
        return ['success' => true, 'message' => 'Weather data stored successfully', 'data' => $weather];
    }

    /**
     * Get forecast
     */
    public function forecast($farmId)
    {
        $lat = $_GET['lat'] ?? null;
        $lng = $_GET['lng'] ?? null;

        // Auto-resolve coordinates from farm if not provided
        if (!$lat || !$lng) {
            $sql = "SELECT coordinates FROM farms WHERE id = :farm_id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':farm_id' => $farmId]);
            $farm = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($farm && $farm['coordinates']) {
                $coords = json_decode($farm['coordinates'], true);
                $lat = $coords['lat'] ?? $coords['latitude'] ?? null;
                $lng = $coords['lng'] ?? $coords['longitude'] ?? null;
            }
        }

        if ($lat && $lng) {
            $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lng}&daily=temperature_2m_max,temperature_2m_min,weather_code,precipitation_probability_max,et0_fao_evapotranspiration&timezone=auto";
            $apiData = $this->fetchRaw($url);

            if ($apiData && isset($apiData['daily'])) {
                $daily = $apiData['daily'];
                $forecast = [];
                for ($i = 0; $i < count($daily['time']); $i++) {
                    $forecast[] = [
                        'date' => $daily['time'][$i],
                        'max_temp' => $daily['temperature_2m_max'][$i],
                        'min_temp' => $daily['temperature_2m_min'][$i],
                        'day' => [
                            'maxtemp_c' => $daily['temperature_2m_max'][$i],
                            'mintemp_c' => $daily['temperature_2m_min'][$i],
                            'condition' => ['text' => $this->getWeatherDescription($daily['weather_code'][$i])],
                            'daily_chance_of_rain' => $daily['precipitation_probability_max'][$i] ?? 0,
                            'et0' => $daily['et0_fao_evapotranspiration'][$i] ?? 0
                        ]
                    ];
                }
                return $forecast;
            }
        }

        return [];
    }

    /**
     * Helper to fetch data from Open-Meteo
     */
    private function fetchRaw($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        return null;
    }

    /**
     * WMO Weather interpretation codes (WW)
     */
    private function getWeatherDescription($code)
    {
        $codes = [
            0 => 'Clear sky',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45 => 'Fog',
            48 => 'Depositing rime fog',
            51 => 'Light drizzle',
            53 => 'Moderate drizzle',
            55 => 'Dense drizzle',
            61 => 'Slight rain',
            63 => 'Moderate rain',
            65 => 'Heavy rain',
            71 => 'Slight snow fall',
            73 => 'Moderate snow fall',
            75 => 'Heavy snow fall',
            77 => 'Snow grains',
            80 => 'Slight rain showers',
            81 => 'Moderate rain showers',
            82 => 'Violent rain showers',
            85 => 'Slight snow showers',
            86 => 'Heavy snow showers',
            95 => 'Thunderstorm',
            96 => 'Thunderstorm with slight hail',
            99 => 'Thunderstorm with heavy hail'
        ];
        return $codes[$code] ?? 'Cloudy';
    }
}
