<?php
/**
 * Database Configuration for InfinityFree
 * Update these values with your InfinityFree MySQL credentials
 */
class Database
{
    // InfinityFree MySQL credentials - UPDATE THESE VALUES
    private $host = 'sql112.infinityfree.com';      // Your MySQL host from InfinityFree
    private $db_name = 'if0_41077803_farm';      // Your database name
    private $username = 'if0_41077803';              // Your username
    private $password = 'AJkTbv7Btng4';                          // YOUR PASSWORD HERE - Get from InfinityFree panel
    public $conn;
    // PUBLIC constructor - this must be public!
    public function __construct()
    {
        // Constructor can be empty
    }
    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        return $this->conn;
    }
}