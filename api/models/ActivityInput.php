<?php
require_once __DIR__ . '/BaseModel.php';

class ActivityInput extends BaseModel
{
    protected $table = 'activity_inputs';

    public $id;
    public $activity_id;
    public $input_id;
    public $quantity_used;
    public $unit;
    public $cost;

    /**
     * Create new activity-input relation
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, activity_id, input_id, quantity_used, unit, cost) 
                VALUES (:id, :activity_id, :input_id, :quantity_used, :unit, :cost)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':activity_id' => $data['activity_id'],
            ':input_id' => $data['input_id'],
            ':quantity_used' => $data['quantity_used'] ?? 0,
            ':unit' => $data['unit'] ?? 'units',
            ':cost' => $data['cost'] ?? 0
        ]);

        return $this->findById($id);
    }

    /**
     * Find by activity ID
     */
    public function findByActivityId($activityId)
    {
        $sql = "SELECT ai.*, i.name as input_name, i.category as input_category
                FROM {$this->table} ai
                JOIN inputs i ON ai.input_id = i.id
                WHERE ai.activity_id = :activity_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':activity_id' => $activityId]);
        return $stmt->fetchAll();
    }

    /**
     * Delete by activity ID
     */
    public function deleteByActivityId($activityId)
    {
        $sql = "DELETE FROM {$this->table} WHERE activity_id = :activity_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':activity_id' => $activityId]);
    }
}
