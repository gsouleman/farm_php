<?php
// AuthHelper for InfinityFree (handles stripped headers)

class AuthHelper {
    public static function getUserId() {
        $token = self::getBearerToken();
        if (!$token) return null;

        try {
            $parts = explode('.', $token);
            $payload = count($parts) > 1 ? $parts[1] : $parts[0]; // Support both raw and standard JWT
            $decoded = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
            return $decoded['id'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    private static function getBearerToken() {
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
        
        $authHeader = null;
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
