<?php
require_once __DIR__ . '/BaseModel.php';

class Field extends BaseModel
{
    protected $table = 'fields';

    /**
     * Create a new field
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, farm_id, name, field_number, status, crop_id, boundary, area, perimeter, 
                 area_unit, soil_type, drainage, slope, irrigation, notes, is_active) 
                VALUES (:id, :farm_id, :name, :field_number, :status, :crop_id, :boundary, :area, :perimeter, 
                        :area_unit, :soil_type, :drainage, :slope, :irrigation, :notes, :is_active)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':farm_id' => $data['farm_id'],
            ':name' => $data['name'],
            ':field_number' => $data['field_number'] ?? null,
            ':status' => $data['status'] ?? 'active',
            ':crop_id' => $data['crop_id'] ?? null,
            ':boundary' => isset($data['boundary']) ? json_encode($data['boundary']) : (isset($data['coordinates']) ? json_encode($data['coordinates']) : null),
            ':area' => $data['area'] ?? null,
            ':perimeter' => $data['perimeter'] ?? null,
            ':area_unit' => $data['area_unit'] ?? 'hectares',
            ':soil_type' => $data['soil_type'] ?? null,
            ':drainage' => $data['drainage'] ?? null,
            ':slope' => $data['slope'] ?? null,
            ':irrigation' => $data['irrigation'] ?? $data['irrigation_type'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':is_active' => $data['is_active'] ?? 1
        ]);

        return $this->findById($id);
    }

    /**
     * Update field
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'name',
            'field_number',
            'status',
            'crop_id',
            'area',
            'perimeter',
            'area_unit',
            'soil_type',
            'drainage',
            'slope',
            'irrigation',
            'notes',
            'is_active',
            'carbon_sequestration',
            'water_efficiency'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        // Handle JSON boundary field
        if (isset($data['boundary'])) {
            $fields[] = "boundary = :boundary";
            $params[':boundary'] = json_encode($data['boundary']);
        }
        if (isset($data['coordinates']) && !isset($data['boundary'])) {
            $fields[] = "boundary = :boundary";
            $params[':boundary'] = json_encode($data['coordinates']);
        }

        // Handle legacy field names
        if (isset($data['irrigation_type']) && !isset($data['irrigation'])) {
            $fields[] = "irrigation = :irrigation";
            $params[':irrigation'] = $data['irrigation_type'];
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
     * Find fields by farm ID
     */
    public function findByFarmId($farmId)
    {
        return $this->findAll(['farm_id' => $farmId]);
    }

    /**
     * Get fields with current crop info
     */
    public function findAllWithCrops($farmId = null)
    {
        $sql = "SELECT f.*, c.crop_type as current_crop_type, c.variety as current_crop_variety
                FROM {$this->table} f
                LEFT JOIN crops c ON f.crop_id = c.id";
        $params = [];

        if ($farmId) {
            $sql .= " WHERE f.farm_id = :farm_id";
            $params[':farm_id'] = $farmId;
        }

        $sql .= " ORDER BY f.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find field by ID with crops info
     */
    public function findByIdWithCrops($id)
    {
        $sql = "SELECT f.*, c.crop_type as current_crop_type, c.variety as current_crop_variety
                FROM {$this->table} f
                LEFT JOIN crops c ON f.crop_id = c.id
                WHERE f.id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
