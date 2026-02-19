<?php

/**
 * Controller Content Diagnostic
 */

header('Content-Type: application/json');

function listDir($dir)
{
    if (!is_dir($dir)) return "Not a directory";
    $files = scandir($dir);
    $result = [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        $result[] = [
            'name' => $file,
            'size' => is_dir($path) ? '-' : filesize($path)
        ];
    }
    return $result;
}

$apiPath = __DIR__ . '/api';
if (!is_dir($apiPath)) $apiPath = __DIR__; // For root usage

echo json_encode([
    'controllers_found' => listDir($apiPath . '/controllers'),
    'planting_controller_exists' => file_exists($apiPath . '/controllers/PlantingSeasonController.php'),
    'instruction' => file_exists($apiPath . '/controllers/PlantingSeasonController.php')
        ? "Controller IS present. Please Rebuild & Redeploy your Frontend (npm run build)."
        : "MISSING CONTROLLER: Please upload api/controllers/PlantingSeasonController.php to your server."
]);
