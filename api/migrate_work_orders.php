<?php
// Migration script to create/update work_orders table
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Work Orders Table Migration ===\n\n";

try {
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'work_orders'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "✓ Table 'work_orders' exists\n";

        // Check table structure
        $stmt = $db->query("DESCRIBE work_orders");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "\nCurrent columns:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }

        // Check if field_id exists
        $hasFieldId = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'field_id') {
                $hasFieldId = true;
                break;
            }
        }

        if (!$hasFieldId) {
            echo "\n⚠ Missing 'field_id' column. Adding it now...\n";
            $db->exec("ALTER TABLE work_orders ADD COLUMN field_id VARCHAR(36) AFTER description");
            $db->exec("ALTER TABLE work_orders ADD FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL");
            echo "✓ Added 'field_id' column\n";
        } else {
            echo "\n✓ 'field_id' column exists\n";
        }
    } else {
        echo "⚠ Table 'work_orders' does not exist. Creating it now...\n\n";

        // Create table without foreign keys first to avoid constraint errors
        $createTable = "CREATE TABLE work_orders (
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
            INDEX idx_farm_id (farm_id),
            INDEX idx_field_id (field_id),
            INDEX idx_assigned_to (assigned_to),
            INDEX idx_created_by (created_by),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->exec($createTable);
        echo "✓ Table created successfully (without foreign keys for safety)!\n";

        // Try to add foreign keys if the referenced tables exist
        echo "\nAttempting to add foreign key constraints...\n";

        try {
            $db->exec("ALTER TABLE work_orders ADD CONSTRAINT fk_workorder_farm 
                      FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE");
            echo "✓ Added farm_id foreign key\n";
        } catch (PDOException $e) {
            echo "⚠ Could not add farm_id foreign key (farms table may not exist)\n";
        }

        try {
            $db->exec("ALTER TABLE work_orders ADD CONSTRAINT fk_workorder_field 
                      FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE SET NULL");
            echo "✓ Added field_id foreign key\n";
        } catch (PDOException $e) {
            echo "⚠ Could not add field_id foreign key (fields table may not exist)\n";
        }

        try {
            $db->exec("ALTER TABLE work_orders ADD CONSTRAINT fk_workorder_assignee 
                      FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL");
            echo "✓ Added assigned_to foreign key\n";
        } catch (PDOException $e) {
            echo "⚠ Could not add assigned_to foreign key (users table may not exist)\n";
        }

        try {
            $db->exec("ALTER TABLE work_orders ADD CONSTRAINT fk_workorder_creator 
                      FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
            echo "✓ Added created_by foreign key\n";
        } catch (PDOException $e) {
            echo "⚠ Could not add created_by foreign key (users table may not exist)\n";
        }
    }

    echo "\n=== Migration Complete ===\n";
    echo "The work_orders table is now ready for use.\n";
    echo "\nNote: Some foreign key constraints may be missing if referenced tables don't exist.\n";
    echo "This won't prevent work orders from functioning, but it's recommended to have them.\n";
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
