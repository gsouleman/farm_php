<?php
require_once __DIR__ . '/BaseModel.php';

class Farm extends BaseModel
{
    protected $table = 'farms';

    /**
     * Create a new farm
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, owner_id, name, address, city, state, country, postal_code, 
                 coordinates, boundary, total_area, area_unit, farm_type, is_active) 
                VALUES (:id, :owner_id, :name, :address, :city, :state, :country, :postal_code, 
                        :coordinates, :boundary, :total_area, :area_unit, :farm_type, :is_active)";

        // Create structured coordinates if latitude/longitude provided
        $coordinates = $data['coordinates'] ?? null;
        if (!$coordinates && isset($data['latitude']) && isset($data['longitude'])) {
            $coordinates = [
                'type' => 'Point',
                'coordinates' => [
                    (float)$data['longitude'],
                    (float)$data['latitude']
                ]
            ];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':owner_id' => $data['owner_id'] ?? $data['user_id'] ?? null,
            ':name' => $data['name'],
            ':address' => $data['address'] ?? $data['location'] ?? null,
            ':city' => $data['city'] ?? null,
            ':state' => $data['state'] ?? null,
            ':country' => $data['country'] ?? null,
            ':postal_code' => $data['postal_code'] ?? null,
            ':coordinates' => $coordinates ? json_encode($coordinates) : null,
            ':boundary' => isset($data['boundary']) ? json_encode($data['boundary']) : null,
            ':total_area' => $data['total_area'] ?? null,
            ':area_unit' => $data['area_unit'] ?? 'hectares',
            ':farm_type' => $data['farm_type'] ?? null,
            ':is_active' => $data['is_active'] ?? 1
        ]);

        return $this->findById($id);
    }

    /**
     * Update farm
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'name',
            'address',
            'city',
            'state',
            'country',
            'postal_code',
            'total_area',
            'area_unit',
            'farm_type',
            'is_active'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        // Handle JSON fields
        if (isset($data['coordinates'])) {
            $fields[] = "coordinates = :coordinates";
            $params[':coordinates'] = json_encode($data['coordinates']);
        }
        if (isset($data['boundary'])) {
            $fields[] = "boundary = :boundary";
            $params[':boundary'] = json_encode($data['boundary']);
        }

        // Handle legacy field names
        if (isset($data['location']) && !isset($data['address'])) {
            $fields[] = "address = :address";
            $params[':address'] = $data['location'];
        }
        if (isset($data['user_id']) && !isset($data['owner_id'])) {
            $fields[] = "owner_id = :owner_id";
            $params[':owner_id'] = $data['user_id'];
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
     * Find farms by owner/user ID
     */
    public function findByUserId($userId)
    {
        return $this->findAll(['owner_id' => $userId]);
    }

    /**
     * Get farm with fields count
     */
    public function findAllWithStats()
    {
        $sql = "SELECT f.*, 
                (SELECT COUNT(*) FROM fields WHERE farm_id = f.id) as field_count,
                (SELECT COUNT(*) FROM crops c INNER JOIN fields fd ON c.field_id = fd.id WHERE fd.farm_id = f.id) as crop_count
                FROM {$this->table} f
                ORDER BY f.created_at DESC";

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }
}
