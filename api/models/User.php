<?php
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel
{
    protected $table = 'users';

    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $password_hash;
    public $phone;
    public $role;
    public $is_active;
    public $language;

    /**
     * Create a new user
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, first_name, last_name, email, password_hash, phone, role, is_active, language) 
                VALUES (:id, :first_name, :last_name, :email, :password_hash, :phone, :role, :is_active, :language)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':phone' => $data['phone'] ?? null,
            ':role' => $data['role'] ?? 'employee',
            ':is_active' => $data['is_active'] ?? 1,
            ':language' => $data['language'] ?? 'en'
        ]);

        return $this->findById($id);
    }

    /**
     * Update user
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        // Build dynamic update query
        foreach (['first_name', 'last_name', 'email', 'phone', 'role', 'is_active', 'language'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        // Handle password separately (needs hashing)
        if (!empty($data['password'])) {
            $fields[] = "password_hash = :password_hash";
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    /**
     * Find by email
     */
    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * Validate password
     */
    public function validatePassword($plainPassword, $hashedPassword)
    {
        return password_verify($plainPassword, $hashedPassword);
    }
}
