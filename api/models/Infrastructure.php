<?php
require_once __DIR__ . '/BaseModel.php';

class Infrastructure extends BaseModel
{
    protected $table = 'infrastructure';

    /**
     * Create new infrastructure
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, farm_id, field_id, name, type, sub_type, status, `condition`, material, 
                 construction_date, cost, area_sqm, capacity_unit, quantity, unit_price, 
                 acquisition_cost, perimeter, boundary, notes) 
                VALUES (:id, :farm_id, :field_id, :name, :type, :sub_type, :status, :condition, :material, 
                        :construction_date, :cost, :area_sqm, :capacity_unit, :quantity, :unit_price, 
                        :acquisition_cost, :perimeter, :boundary, :notes)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':farm_id' => $data['farm_id'],
            ':field_id' => $data['field_id'] ?? null,
            ':name' => $data['name'],
            ':type' => $data['type'] ?? null,
            ':sub_type' => $data['sub_type'] ?? null,
            ':status' => $data['status'] ?? 'operational',
            ':condition' => $data['condition'] ?? null,
            ':material' => $data['material'] ?? null,
            ':construction_date' => $data['construction_date'] ?? null,
            ':cost' => $data['cost'] ?? null,
            ':area_sqm' => $data['area_sqm'] ?? null,
            ':capacity_unit' => $data['capacity_unit'] ?? $data['capacity'] ?? null,
            ':quantity' => $data['quantity'] ?? null,
            ':unit_price' => $data['unit_price'] ?? null,
            ':acquisition_cost' => $data['acquisition_cost'] ?? null,
            ':perimeter' => $data['perimeter'] ?? null,
            ':boundary' => isset($data['boundary']) ? json_encode($data['boundary']) : (isset($data['coordinates']) ? json_encode($data['coordinates']) : (isset($data['boundary_coordinates']) ? json_encode($data['boundary_coordinates']) : null)),
            ':notes' => $data['notes'] ?? null
        ]);

        return $this->findById($id);
    }

    /**
     * Update infrastructure
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'name',
            'type',
            'sub_type',
            'farm_id',
            'field_id',
            'status',
            'condition',
            'material',
            'construction_date',
            'cost',
            'area_sqm',
            'capacity_unit',
            'quantity',
            'unit_price',
            'acquisition_cost',
            'perimeter',
            'notes'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                // Escape reserved words like 'condition'
                $colName = $field === 'condition' ? '`condition`' : $field;
                $fields[] = "$colName = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (isset($data['boundary'])) {
            $fields[] = "boundary = :boundary";
            $params[':boundary'] = json_encode($data['boundary']);
        }
        if (isset($data['coordinates']) && !isset($data['boundary'])) {
            $fields[] = "boundary = :boundary";
            $params[':boundary'] = json_encode($data['coordinates']);
        }
        if (isset($data['boundary_coordinates']) && !isset($data['boundary']) && !isset($data['coordinates'])) {
            $fields[] = "boundary = :boundary";
            $params[':boundary'] = json_encode($data['boundary_coordinates']);
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
     * Find infrastructure by farm ID
     */
    public function findByFarmId($farmId)
    {
        return $this->findAll(['farm_id' => $farmId]);
    }

    /**
     * Find by type
     */
    public function findByType($type, $farmId = null)
    {
        $conditions = ['type' => $type];
        if ($farmId) {
            $conditions['farm_id'] = $farmId;
        }
        return $this->findAll($conditions);
    }

    /**
     * Find by field
     */
    public function findByFieldId($fieldId)
    {
        return $this->findAll(['field_id' => $fieldId]);
    }
}
