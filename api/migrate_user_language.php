<?php
// Migration script to add language column to users table
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== User Language Column Migration ===\n\n";

try {
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        echo "❌ Table 'users' does not exist. Please run core migrations first.\n";
        exit;
    }

    echo "✓ Table 'users' exists\n";

    // Check if language column already exists
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasLanguage = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'language') {
            $hasLanguage = true;
            break;
        }
    }

    if (!$hasLanguage) {
        echo "⚠ Missing 'language' column. Adding it now...\n";
        $db->exec("ALTER TABLE users ADD COLUMN language VARCHAR(5) DEFAULT 'en' AFTER is_active");
        echo "✓ Added 'language' column to users table\n";
    } else {
        echo "✓ 'language' column already exists\n";
    }

    echo "\n=== Migration Complete ===\n";
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
