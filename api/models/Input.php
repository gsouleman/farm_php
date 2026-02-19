<?php
require_once __DIR__ . '/BaseModel.php';

class Input extends BaseModel
{
    protected $table = 'inputs';

    public $id;
    public $name;
    public $category;
    public $quantity;
    public $unit;
    public $unit_cost;
    public $total_cost;
    public $supplier;
    public $purchase_date;
    public $expiry_date;
    public $farm_id;
    public $storage_location;
    public $notes;

    /**
     * Create new input
     */
    public function create($data)
    {
        $id = $this->generateUUID();
        $total_cost = ($data['quantity'] ?? 0) * ($data['unit_cost'] ?? 0);

        $sql = "INSERT INTO {$this->table} 
                (id, name, category, quantity, unit, unit_cost, total_cost, supplier, purchase_date, expiry_date, farm_id, storage_location, notes) 
                VALUES (:id, :name, :category, :quantity, :unit, :unit_cost, :total_cost, :supplier, :purchase_date, :expiry_date, :farm_id, :storage_location, :notes)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':category' => $data['category'] ?? null,
            ':quantity' => $data['quantity'] ?? 0,
            ':unit' => $data['unit'] ?? 'units',
            ':unit_cost' => $data['unit_cost'] ?? 0,
            ':total_cost' => $total_cost,
            ':supplier' => $data['supplier'] ?? null,
            ':purchase_date' => $data['purchase_date'] ?? null,
            ':expiry_date' => $data['expiry_date'] ?? null,
            ':farm_id' => $data['farm_id'],
            ':storage_location' => $data['storage_location'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);

        return $this->findById($id);
    }

    /**
     * Update input
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'category', 'quantity', 'unit', 'unit_cost', 'supplier', 'purchase_date', 'expiry_date', 'farm_id', 'storage_location', 'notes'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        // Recalculate total cost if quantity or unit_cost changed
        if (isset($data['quantity']) || isset($data['unit_cost'])) {
            $current = $this->findById($id);
            $quantity = $data['quantity'] ?? $current['quantity'];
            $unit_cost = $data['unit_cost'] ?? $current['unit_cost'];
            $fields[] = "total_cost = :total_cost";
            $params[':total_cost'] = $quantity * $unit_cost;
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
     * Find inputs by farm ID
     */
    public function findByFarmId($farmId)
    {
        return $this->findAll(['farm_id' => $farmId]);
    }

    /**
     * Find by category
     */
    public function findByCategory($category, $farmId = null)
    {
        $conditions = ['category' => $category];
        if ($farmId) {
            $conditions['farm_id'] = $farmId;
        }
        return $this->findAll($conditions);
    }
    /**
     * Adjust stock quantity
     * @param string $id Input ID
     * @param float $quantity Change amount (negative to deduct, positive to add)
     * @return boolean
     * @throws Exception if insufficient stock
     */
    public function adjustStock($id, $quantity)
    {
        $input = $this->findById($id);
        if (!$input) {
            throw new Exception("Input item not found.");
        }

        $newQuantity = $input['quantity'] + $quantity;

        // Prevent negative stock
        if ($newQuantity < 0) {
            throw new Exception("Insufficient stock for '{$input['name']}'. Available: {$input['quantity']}, Required: " . abs($quantity));
        }

        $sql = "UPDATE {$this->table} SET quantity = :quantity WHERE id = :id";
        $stmt = $this->conn->prepare($sql);

        // Also update total cost if unit cost exists (optional, but good for consistency)
        // Note: usage doesn't change unit_cost, just quantity.

        return $stmt->execute([
            ':quantity' => $newQuantity,
            ':id' => $id
        ]);
    }
}
