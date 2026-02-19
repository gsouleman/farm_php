<?php
require_once __DIR__ . '/BaseModel.php';

class Activity extends BaseModel
{
    protected $table = 'activities';

    /**
     * Create a new activity
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, activity_type, description, field_id, crop_id, farm_id, infrastructure_id, 
                 activity_date, total_cost, duration_hours, notes, work_status) 
                VALUES (:id, :activity_type, :description, :field_id, :crop_id, :farm_id, :infrastructure_id, 
                        :activity_date, :total_cost, :duration_hours, :notes, :work_status)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':activity_type' => $data['activity_type'],
            ':description' => $data['description'] ?? null,
            ':field_id' => $data['field_id'] ?? null,
            ':crop_id' => $data['crop_id'] ?? null,
            ':farm_id' => $data['farm_id'] ?? null,
            ':infrastructure_id' => $data['infrastructure_id'] ?? null,
            ':activity_date' => $data['activity_date'] ?? date('Y-m-d'),
            ':total_cost' => $data['total_cost'] ?? $data['cost'] ?? 0,
            ':duration_hours' => $data['duration_hours'] ?? $data['labor_hours'] ?? 0,
            ':notes' => $data['notes'] ?? null,
            ':work_status' => $data['work_status'] ?? $data['status'] ?? 'completed',
            ':transaction_type' => $data['transaction_type'] ?? 'expense'
        ]);

        return $this->findById($id);
    }

    /**
     * Update activity
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'activity_type',
            'description',
            'field_id',
            'crop_id',
            'farm_id',
            'infrastructure_id',
            'activity_date',
            'total_cost',
            'duration_hours',
            'notes',
            'work_status',
            'labor_cost',
            'material_cost',
            'equipment_cost',
            'transaction_type'
        ];

        foreach ($allowedFields as $field) {
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
     * Find activities by farm ID
     */
    public function findByFarmId($farmId)
    {
        return $this->findAll(['farm_id' => $farmId]);
    }

    /**
     * Find activities by crop ID
     */
    public function findByCropId($cropId)
    {
        return $this->findAll(['crop_id' => $cropId]);
    }

    /**
     * Find activities by infrastructure ID
     */
    public function findByInfrastructureId($infrastructureId)
    {
        return $this->findAll(['infrastructure_id' => $infrastructureId]);
    }

    /**
     * Find duplicate activity
     * Checks for same type, date, and related entity (field/crop/infra) and similar cost
     */
    public function findDuplicate($data)
    {
        $sql = "SELECT id FROM {$this->table} 
                WHERE activity_type = :activity_type 
                AND activity_date = :activity_date";

        $params = [
            ':activity_type' => $data['activity_type'],
            ':activity_date' => $data['activity_date']
        ];

        if (!empty($data['crop_id'])) {
            $sql .= " AND crop_id = :crop_id";
            $params[':crop_id'] = $data['crop_id'];
        }

        if (!empty($data['field_id'])) {
            $sql .= " AND field_id = :field_id";
            $params[':field_id'] = $data['field_id'];
        }

        if (!empty($data['infrastructure_id'])) {
            $sql .= " AND infrastructure_id = :infrastructure_id";
            $params[':infrastructure_id'] = $data['infrastructure_id'];
        }

        // Check cost if provided (allow small floating point variance or exact match)
        if (isset($data['total_cost'])) {
            $sql .= " AND (total_cost = :total_cost OR labor_cost = :total_cost)";
            $params[':total_cost'] = $data['total_cost'];
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get activities with related data
     */
    public function findAllWithRelations($farmId = null)
    {
        $sql = "SELECT a.*, 
                f.name as field_name,
                c.crop_type as crop_name,
                c.variety as crop_variety,
                i.name as infrastructure_name
                FROM {$this->table} a
                LEFT JOIN fields f ON a.field_id = f.id
                LEFT JOIN crops c ON a.crop_id = c.id
                LEFT JOIN infrastructure i ON a.infrastructure_id = i.id";
        $params = [];

        if ($farmId) {
            $sql .= " WHERE a.farm_id = :farm_id";
            $params[':farm_id'] = $farmId;
        }

        $sql .= " ORDER BY a.activity_date DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
