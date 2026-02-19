<?php
require_once __DIR__ . '/BaseModel.php';

class CostSetting extends BaseModel
{
    protected $table = 'cost_settings';

    public $id;
    public $name;
    public $category;
    public $amount;
    public $unit;
    public $farm_id;
    public $is_default;
    public $notes;

    /**
     * Create new cost setting
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, name, category, amount, unit, farm_id, is_default, notes) 
                VALUES (:id, :name, :category, :amount, :unit, :farm_id, :is_default, :notes)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':category' => $data['category'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':unit' => $data['unit'] ?? 'per_unit',
            ':farm_id' => $data['farm_id'] ?? null,
            ':is_default' => $data['is_default'] ?? 0,
            ':notes' => $data['notes'] ?? null
        ]);

        return $this->findById($id);
    }

    /**
     * Update cost setting
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'category', 'amount', 'unit', 'farm_id', 'is_default', 'notes'] as $field) {
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
     * Find cost settings by farm ID
     */
    public function findByFarmId($farmId)
    {
        return $this->findAll(['farm_id' => $farmId]);
    }

    /**
     * Find default cost settings
     */
    public function findDefaults()
    {
        return $this->findAll(['is_default' => 1]);
    }
}
