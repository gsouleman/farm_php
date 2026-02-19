<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

header('Content-Type: text/plain');

try {
    $db = (new Database())->getConnection();
    $userModel = new User($db);

    $email = 'admin@farmer.local';
    $password = 'admin123'; // Default secure password

    $existingUser = $userModel->findByEmail($email);

    if ($existingUser) {
        echo "User found. Updating role to 'admin'...\n";
        $userModel->update($existingUser['id'], [
            'role' => 'admin',
            'is_active' => 1
        ]);
        echo "SUCCESS: User '{$email}' is now a System Administrator.\n";
    } else {
        echo "User not found. Creating new administrator...\n";
        $userData = [
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => $email,
            'password' => $password,
            'phone' => '+237 Admin',
            'role' => 'admin',
            'is_active' => 1
        ];

        $newUser = $userModel->create($userData);
        echo "SUCCESS: Created new administrator account.\n";
        echo "Email: {$email}\n";
        echo "Password: {$password}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
