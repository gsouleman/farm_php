<?php
require_once __DIR__ . '/BaseModel.php';

class InfrastructureDefinition extends BaseModel
{
    protected $table = 'infrastructure_definitions';

    public $id;
    public $name;
    public $category;
    public $type;
    public $description;
    public $is_default;

    /**
     * Create new infrastructure definition
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, name, category, type, description, is_default) 
                VALUES (:id, :name, :category, :type, :description, :is_default)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':category' => $data['category'] ?? null,
            ':type' => $data['type'] ?? null,
            ':description' => $data['description'] ?? null,
            ':is_default' => $data['is_default'] ?? 1
        ]);

        return $this->findById($id);
    }

    /**
     * Update infrastructure definition
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'category', 'type', 'description', 'is_default'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
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
     * Find by category
     */
    public function findByCategory($category)
    {
        return $this->findAll(['category' => $category]);
    }
}
