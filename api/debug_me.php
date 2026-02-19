<?php

/**
 * Debugging script for AuthController::me()
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $auth = new AuthController($db);

    echo json_encode([
        'status' => 'Testing AuthController::me()',
        'server_headers' => getallheaders_custom(),
        'result' => $auth->me()
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

function getallheaders_custom()
{
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}
