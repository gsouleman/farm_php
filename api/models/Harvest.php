<?php
require_once __DIR__ . '/BaseModel.php';

class Harvest extends BaseModel
{
    protected $table = 'harvests';

    /**
     * Create a new harvest
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, crop_id, harvest_date, area_harvested, quantity, unit, yield_per_area, 
                 quality_grade, moisture_content, storage_location, destination, 
                 price_per_unit, total_revenue, notes) 
                VALUES (:id, :crop_id, :harvest_date, :area_harvested, :quantity, :unit, :yield_per_area, 
                        :quality_grade, :moisture_content, :storage_location, :destination, 
                        :price_per_unit, :total_revenue, :notes)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':crop_id' => $data['crop_id'] ?? null,
            ':harvest_date' => $data['harvest_date'] ?? date('Y-m-d'),
            ':area_harvested' => $data['area_harvested'] ?? null,
            ':quantity' => $data['quantity'] ?? 0,
            ':unit' => $data['unit'] ?? 'kg',
            ':yield_per_area' => $data['yield_per_area'] ?? null,
            ':quality_grade' => $data['quality_grade'] ?? null,
            ':moisture_content' => $data['moisture_content'] ?? null,
            ':storage_location' => $data['storage_location'] ?? null,
            ':destination' => $data['destination'] ?? null,
            ':price_per_unit' => $data['price_per_unit'] ?? null,
            ':total_revenue' => $data['total_revenue'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);

        return $this->findByIdWithCrop($id);
    }

    /**
     * Update harvest
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'crop_id',
            'harvest_date',
            'area_harvested',
            'quantity',
            'unit',
            'yield_per_area',
            'quality_grade',
            'moisture_content',
            'storage_location',
            'destination',
            'price_per_unit',
            'total_revenue',
            'notes'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return $this->findByIdWithCrop($id);
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $this->findByIdWithCrop($id);
    }

    /**
     * Find harvests by crop ID
     */
    public function findByCropId($cropId)
    {
        return $this->findAll(['crop_id' => $cropId]);
    }

    /**
     * Find harvests by farm ID (through crops)
     */
    /**
     * Find harvests by farm ID (through crops)
     * Reuses findAllWithCrops to ensure consistent data structure
     */
    public function findByFarmId($farmId)
    {
        return $this->findAllWithCrops($farmId);
    }

    /**
     * Find single harvest with full crop details
     */
    public function findByIdWithCrop($id)
    {
        $sql = "SELECT h.*, c.crop_type as crop_name, c.variety as crop_variety,
                       f.name as field_name, f.farm_id, c.field_id
                FROM {$this->table} h
                LEFT JOIN crops c ON h.crop_id = c.id
                LEFT JOIN fields f ON c.field_id = f.id
                WHERE h.id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        // Format result (same logic as findAllWithCrops)
        $harvest = $row;
        if (!empty($row['crop_id'])) {
            $harvest['Crop'] = [
                'id' => $row['crop_id'],
                'crop_type' => $row['crop_name'] ?? null,
                'variety' => $row['crop_variety'] ?? null,
                'status' => 'active'
            ];

            if (!empty($row['field_name'])) {
                $harvest['Crop']['Field'] = [
                    'id' => $row['field_id'] ?? null,
                    'name' => $row['field_name'],
                    'farm_id' => $row['farm_id']
                ];
            }
        }

        return $harvest;
    }

    /**
     * Get harvests with crop info
     */
    public function findAllWithCrops($farmId = null)
    {
        $sql = "SELECT h.*, c.crop_type as crop_name, c.variety as crop_variety,
                       f.name as field_name, f.farm_id, c.field_id
                FROM {$this->table} h
                LEFT JOIN crops c ON h.crop_id = c.id
                LEFT JOIN fields f ON c.field_id = f.id";
        $params = [];

        if ($farmId) {
            $sql .= " WHERE f.farm_id = :farm_id";
            $params[':farm_id'] = $farmId;
        }

        $sql .= " ORDER BY h.harvest_date DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format results to nest Crop and Field objects expected by Frontend
        return array_map(function ($row) {
            $harvest = $row;

            // Build nested Crop object
            if (!empty($row['crop_id'])) {
                $harvest['Crop'] = [
                    'id' => $row['crop_id'],
                    'crop_type' => $row['crop_name'] ?? null,
                    'variety' => $row['crop_variety'] ?? null,
                    'status' => 'active' // presumed if joined
                ];

                // Add Field info to Crop if available
                if (!empty($row['field_name'])) {
                    $harvest['Crop']['Field'] = [
                        'id' => $row['field_id'] ?? null, // field_id wasn't selected in SQL, need to add it
                        'name' => $row['field_name'],
                        'farm_id' => $row['farm_id']
                    ];
                }
            }

            // Cleanup flat fields if desired, or keep them for backward compat
            // unset($harvest['crop_name'], $harvest['crop_variety'], $harvest['field_name']);

            return $harvest;
        }, $results);
    }
}
