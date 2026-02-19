<?php
// Simple health check - No DB dependencies
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// Check headers (especially Authorization)
$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
}

// Special check for the fix we put in .htaccess
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth_received = "YES (via HTTP_AUTHORIZATION)";
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $auth_received = "YES (via REDIRECT_HTTP_AUTHORIZATION)";
} elseif (isset($headers['Authorization'])) {
    $auth_received = "YES (via getallheaders)";
} else {
    $auth_received = "NO";
}

echo json_encode([
    "status" => "online",
    "message" => "PHP is executing successfully.",
    "auth_header_detected" => $auth_received,
    "php_version" => PHP_VERSION,
    "current_time" => date("Y-m-d H:i:s")
]);
