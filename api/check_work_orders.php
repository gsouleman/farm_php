<?php
// Quick script to check work_orders table structure
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Check if work_orders table exists
    $stmt = $db->query("SHOW CREATE TABLE work_orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Table structure:\n";
    echo $result['Create Table'];
    echo "\n\n";

    // Check if we can insert a test record
    echo "Database connection successful!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";

    // If table doesn't exist, create it
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "\nCreating work_orders table...\n";
        $createTable = "CREATE TABLE IF NOT EXISTS work_orders (
            id VARCHAR(36) PRIMARY KEY,
            farm_id VARCHAR(36) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            field_id VARCHAR(36),
            assigned_to VARCHAR(36),
            status ENUM('todo', 'in_progress', 'review', 'done') DEFAULT 'todo',
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            due_date DATE,
            created_by VARCHAR(36),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
            FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->exec($createTable);
        echo "Table created successfully!\n";
    }
}
