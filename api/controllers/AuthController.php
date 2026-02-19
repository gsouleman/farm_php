<?php
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private $db;
    private $user;

    public function __construct($db)
    {
        $this->db = $db;
        $this->user = new User($db);
    }

    /**
     * Login user
     */
    public function login()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $user = $this->user->findByEmail($data['email']);

        if (!$user) {
            http_response_code(401);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if (!$this->user->validatePassword($data['password'], $user['password_hash'])) {
            http_response_code(401);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if ($user['is_active'] != 1) {
            http_response_code(403);
            return ['success' => false, 'message' => 'Account is inactive'];
        }

        // Generate JWT token
        $token = $this->generateToken($user);

        // Remove password from response
        unset($user['password_hash']);

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Register new user
     */
    public function register()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Required fields missing'];
        }

        // Check if email exists
        if ($this->user->findByEmail($data['email'])) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $user = $this->user->create($data);
        unset($user['password']);

        $token = $this->generateToken($user);

        return [
            'success' => true,
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Get current user
     */
    public function me()
    {
        $userId = $this->getAuthenticatedUserId();
        if (!$userId) {
            http_response_code(401);
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $user = $this->user->findById($userId);
        if (!$user) {
            http_response_code(404);
            return ['success' => false, 'message' => 'User not found'];
        }

        unset($user['password_hash']);
        return $user; // Return raw user object
    }

    /**
     * Logout (client-side token removal)
     */
    public function logout()
    {
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Generate JWT token
     */
    private function generateToken($user)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7) // 7 days
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $secret = 'your-secret-key-change-this-in-production';
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    /**
     * Verify JWT token and get user ID
     */
    public function getAuthenticatedUserId()
    {
        // Try getallheaders polyfill
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

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

        if (!$payload || !isset($payload['sub']) || !isset($payload['exp'])) {
            return null;
        }

        if ($payload['exp'] < time()) {
            return null;
        }

        return $payload['sub'];
    }
}
