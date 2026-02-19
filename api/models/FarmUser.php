<?php
require_once __DIR__ . '/BaseModel.php';

class FarmUser extends BaseModel
{
    protected $table = 'farm_users';

    public $id;
    public $farm_id;
    public $user_id;
    public $role;
    public $permissions;

    /**
     * Create new farm-user association
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, farm_id, user_id, role, permissions) 
                VALUES (:id, :farm_id, :user_id, :role, :permissions)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':farm_id' => $data['farm_id'],
            ':user_id' => $data['user_id'],
            ':role' => $data['role'] ?? 'member',
            ':permissions' => isset($data['permissions']) ? json_encode($data['permissions']) : null
        ]);

        return $this->findById($id);
    }

    /**
     * Update farm-user association
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['farm_id', 'user_id', 'role'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (isset($data['permissions'])) {
            $fields[] = "permissions = :permissions";
            $params[':permissions'] = json_encode($data['permissions']);
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
     * Find by farm ID
     */
    public function findByFarmId($farmId)
    {
        $sql = "SELECT fu.*, u.first_name, u.last_name, u.email 
                FROM {$this->table} fu
                JOIN users u ON fu.user_id = u.id
                WHERE fu.farm_id = :farm_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':farm_id' => $farmId]);
        return $stmt->fetchAll();
    }

    /**
     * Find by farm and user
     */
    public function findByFarmAndUser($farmId, $userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE farm_id = :farm_id AND user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':farm_id' => $farmId, ':user_id' => $userId]);
        return $stmt->fetch();
    }
}
