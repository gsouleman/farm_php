<?php
// Debug script for work order creation issues
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Work Orders Debug Analysis ===\n\n";

// 1. Check if work_orders table exists
echo "1. Checking work_orders table...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'work_orders'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Table exists\n\n";

        // Show table structure
        echo "2. Table structure:\n";
        $stmt = $db->query("DESCRIBE work_orders");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
        }
        echo "\n";

        // Check foreign keys
        echo "3. Foreign key constraints:\n";
        $stmt = $db->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'work_orders'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($fks) > 0) {
            foreach ($fks as $fk) {
                echo "   - {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
            }
        } else {
            echo "   ⚠ No foreign keys found\n";
        }
        echo "\n";
    } else {
        echo "   ❌ Table does not exist!\n";
        echo "   Run migrate_work_orders.php to create it.\n";
        exit;
    }
} catch (PDOException $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit;
}

// 4. Test data from the error log
echo "4. Testing with the submitted data:\n";
$testData = [
    'farm_id' => null, // Will be replaced with actual farm_id
    'title' => 'test',
    'description' => 'test',
    'field_id' => '6c7809f5-42ca-4f46-8a31-2d3e4ca9db82',
    'priority' => 'medium',
    'due_date' => '2026-02-20',
    'assigned_to' => null,
    'status' => 'todo',
    'created_by' => null
];

// Get a valid farm_id
try {
    $stmt = $db->query("SELECT id FROM farms LIMIT 1");
    $farm = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($farm) {
        $testData['farm_id'] = $farm['id'];
        echo "   Using farm_id: {$testData['farm_id']}\n";
    } else {
        echo "   ⚠ No farms found in database\n";
    }
} catch (Exception $e) {
    echo "   ⚠ Could not get farm_id: " . $e->getMessage() . "\n";
}

// Check if the field_id exists
echo "\n5. Checking if field_id exists in fields table:\n";
try {
    $stmt = $db->prepare("SELECT id, name FROM fields WHERE id = :field_id");
    $stmt->execute([':field_id' => $testData['field_id']]);
    $field = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($field) {
        echo "   ✓ Field found: {$field['id']} - {$field['name']}\n";
    } else {
        echo "   ❌ Field ID '{$testData['field_id']}' does NOT exist in fields table!\n";
        echo "   This would cause a foreign key constraint failure.\n";

        // Show available fields
        echo "\n   Available fields:\n";
        $stmt = $db->query("SELECT id, name FROM fields LIMIT 5");
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fields as $f) {
            echo "   - {$f['id']} - {$f['name']}\n";
        }
    }
} catch (PDOException $e) {
    echo "   ❌ Error checking field: " . $e->getMessage() . "\n";
}

// Try to create a test work order
echo "\n6. Attempting to create a test work order:\n";
if ($testData['farm_id']) {
    try {
        // Generate UUID
        $id = bin2hex(random_bytes(16));
        $id = substr($id, 0, 8) . '-' . substr($id, 8, 4) . '-' . substr($id, 12, 4) . '-' . substr($id, 16, 4) . '-' . substr($id, 20);

        $sql = "INSERT INTO work_orders 
                (id, farm_id, title, description, field_id, assigned_to, status, priority, due_date, created_by) 
                VALUES (:id, :farm_id, :title, :description, :field_id, :assigned_to, :status, :priority, :due_date, :created_by)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':farm_id' => $testData['farm_id'],
            ':title' => $testData['title'],
            ':description' => $testData['description'],
            ':field_id' => $testData['field_id'],
            ':assigned_to' => $testData['assigned_to'],
            ':status' => $testData['status'],
            ':priority' => $testData['priority'],
            ':due_date' => $testData['due_date'],
            ':created_by' => $testData['created_by']
        ]);

        echo "   ✓ Test work order created successfully with ID: $id\n";

        // Clean up - delete the test record
        $db->prepare("DELETE FROM work_orders WHERE id = :id")->execute([':id' => $id]);
        echo "   ✓ Test work order deleted (cleanup)\n";
    } catch (PDOException $e) {
        echo "   ❌ FAILED to create work order!\n";
        echo "   Error: " . $e->getMessage() . "\n";
        echo "   Error Code: " . $e->getCode() . "\n";

        // Parse the error for common issues
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            echo "\n   ⚠ This is a FOREIGN KEY CONSTRAINT error!\n";
            echo "   The field_id value does not exist in the fields table.\n";
        } elseif (strpos($e->getMessage(), "doesn't exist") !== false) {
            echo "\n   ⚠ The work_orders table doesn't exist!\n";
            echo "   Run: php api/migrate_work_orders.php\n";
        }
    }
}

echo "\n=== Analysis Complete ===\n";
