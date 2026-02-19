<?php
// weather_diag.php - Upload to htdocs/api/weather_diag.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: text/plain");

echo "=== OPEN-METEO API CONNECTIVITY TEST ===\n\n";

$lat = "4.05"; // Douala, Cameroon
$lng = "9.7";
$url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lng}&daily=temperature_2m_max,temperature_2m_min,weathercode,precipitation_probability_max&timezone=auto";

echo "Target URL: $url\n\n";

if (!function_exists('curl_init')) {
    die("FATAL ERROR: cURL is not installed or enabled on this server!\n");
}

echo "Initiating cURL request...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";

if ($response === false) {
    echo "cURL Error: $error\n";
} else {
    echo "Response received! Size: " . strlen($response) . " bytes\n";
    $json = json_decode($response, true);
    if (isset($json['daily'])) {
        echo "SUCCESS: Fetched " . count($json['daily']['time']) . " days of forecast.\n";
        echo "First day max temp: " . $json['daily']['temperature_2m_max'][0] . "°C\n";
    } else {
        echo "ERROR: Response was not a valid forecast. Response content:\n";
        echo $response . "\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
