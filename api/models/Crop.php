<?php
require_once __DIR__ . '/BaseModel.php';

class Crop extends BaseModel
{
    protected $table = 'crops';

    /**
     * Create a new crop
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, field_id, crop_type, variety, planting_date, expected_harvest_date, 
                 planted_area, planting_rate, row_spacing, status, season, year, 
                 estimated_cost, boundary, notes) 
                VALUES (:id, :field_id, :crop_type, :variety, :planting_date, :expected_harvest_date, 
                        :planted_area, :planting_rate, :row_spacing, :status, :season, :year, 
                        :estimated_cost, :boundary, :notes)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':field_id' => $data['field_id'] ?? null,
            ':crop_type' => $data['crop_type'] ?? $data['name'] ?? null,
            ':variety' => $data['variety'] ?? null,
            ':planting_date' => $data['planting_date'] ?? null,
            ':expected_harvest_date' => $data['expected_harvest_date'] ?? $data['expected_harvest'] ?? null,
            ':planted_area' => $data['planted_area'] ?? $data['area_allocated'] ?? null,
            ':planting_rate' => $data['planting_rate'] ?? null,
            ':row_spacing' => $data['row_spacing'] ?? null,
            ':status' => $data['status'] ?? 'active',
            ':season' => $data['season'] ?? null,
            ':year' => $data['year'] ?? date('Y'),
            ':estimated_cost' => $data['estimated_cost'] ?? null,
            ':boundary' => isset($data['boundary']) ? json_encode($data['boundary']) : (isset($data['coordinates']) ? json_encode($data['coordinates']) : (isset($data['boundary_coordinates']) ? json_encode($data['boundary_coordinates']) : null)),
            ':notes' => $data['notes'] ?? null
        ]);

        return $this->findById($id);
    }

    /**
     * Update crop
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'field_id',
            'crop_type',
            'variety',
            'planting_date',
            'expected_harvest_date',
            'actual_harvest_date',
            'planted_area',
            'planting_rate',
            'row_spacing',
            'status',
            'season',
            'year',
            'estimated_cost',
            'notes'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        // Handle JSON boundary field (support boundary, coordinates, or boundary_coordinates)
        $boundaryData = $data['boundary'] ?? $data['coordinates'] ?? $data['boundary_coordinates'] ?? null;
        if ($boundaryData) {
            $fields[] = "boundary = :boundary";
            $params[':boundary'] = json_encode($boundaryData);
        }

        // Handle legacy field names
        if (isset($data['name']) && !isset($data['crop_type'])) {
            $fields[] = "crop_type = :crop_type";
            $params[':crop_type'] = $data['name'];
        }
        if (isset($data['expected_harvest']) && !isset($data['expected_harvest_date'])) {
            $fields[] = "expected_harvest_date = :expected_harvest_date";
            $params[':expected_harvest_date'] = $data['expected_harvest'];
        }
        if (isset($data['area_allocated']) && !isset($data['planted_area'])) {
            $fields[] = "planted_area = :planted_area";
            $params[':planted_area'] = $data['area_allocated'];
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
     * Find crops by field ID
     */
    public function findByFieldId($fieldId)
    {
        return $this->findAll(['field_id' => $fieldId]);
    }

    /**
     * Find crops by farm ID (through fields)
     */
    public function findByFarmId($farmId)
    {
        $sql = "SELECT c.*, f.name as field_name 
                FROM {$this->table} c
                INNER JOIN fields f ON c.field_id = f.id
                WHERE f.farm_id = :farm_id
                ORDER BY c.planting_date DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':farm_id' => $farmId]);
        return $stmt->fetchAll();
    }

    /**
     * Find active crops
     */
    public function findActive($farmId = null)
    {
        if ($farmId) {
            $sql = "SELECT c.*, f.name as field_name 
                    FROM {$this->table} c
                    INNER JOIN fields f ON c.field_id = f.id
                    WHERE f.farm_id = :farm_id AND c.status = 'active'
                    ORDER BY c.planting_date DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':farm_id' => $farmId]);
        } else {
            $sql = "SELECT c.*, f.name as field_name 
                    FROM {$this->table} c
                    LEFT JOIN fields f ON c.field_id = f.id
                    WHERE c.status = 'active'
                    ORDER BY c.planting_date DESC";
            $stmt = $this->conn->query($sql);
        }
        return $stmt->fetchAll();
    }

    /**
     * Get all crops with field info
     */
    public function findAllWithFields()
    {
        $sql = "SELECT c.*, f.name as field_name, f.farm_id
                FROM {$this->table} c
                LEFT JOIN fields f ON c.field_id = f.id
                ORDER BY c.created_at DESC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
}
