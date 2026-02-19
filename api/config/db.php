<?php
// Database configuration
// Enable error reporting for setup debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
// For InfinityFree, these details will be provided in your hosting control panel

define('DB_HOST', 'sql112.infinityfree.com'); // Replace with your host
define('DB_NAME', 'if0_41077803_farm');       // Replace with your DB name
define('DB_USER', 'if0_41077803');            // Replace with your username
define('DB_PASS', 'AJkTbv7Btng4');           // Replace with your password

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}
