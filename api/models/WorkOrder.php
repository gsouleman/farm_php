<?php
require_once __DIR__ . '/BaseModel.php';

class WorkOrder extends BaseModel
{
    protected $table = 'work_orders';

    public $id;
    public $farm_id;
    public $title;
    public $description;
    public $assigned_to; // user_id
    public $status; // todo, in_progress, review, done
    public $priority; // low, medium, high, urgent
    public $due_date;
    public $created_by;
    public $created_at;
    public $updated_at;

    /**
     * Create new work order
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        // Convert empty strings to NULL for optional foreign keys
        $field_id = !empty($data['field_id']) ? $data['field_id'] : null;
        $assigned_to = !empty($data['assigned_to']) ? $data['assigned_to'] : null;
        $created_by = !empty($data['created_by']) ? $data['created_by'] : null;

        $sql = "INSERT INTO {$this->table} 
                (id, farm_id, title, description, field_id, assigned_to, status, priority, due_date, created_by) 
                VALUES (:id, :farm_id, :title, :description, :field_id, :assigned_to, :status, :priority, :due_date, :created_by)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':farm_id' => $data['farm_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':field_id' => $field_id,
            ':assigned_to' => $assigned_to,
            ':status' => $data['status'] ?? 'todo',
            ':priority' => $data['priority'] ?? 'medium',
            ':due_date' => $data['due_date'] ?? null,
            ':created_by' => $created_by
        ]);

        return $this->findById($id);
    }

    /**
     * Update work order
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['title', 'description', 'field_id', 'assigned_to', 'status', 'priority', 'due_date'] as $field) {
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
     * Find by Farm with User details
     */
    public function findAllByFarm($farmId)
    {
        $sql = "SELECT w.*, 
                       u.first_name as assignee_first, u.last_name as assignee_last,
                       c.first_name as creator_first, c.last_name as creator_last,
                       f.name as field_name
                FROM {$this->table} w
                LEFT JOIN users u ON w.assigned_to = u.id
                LEFT JOIN users c ON w.created_by = c.id
                LEFT JOIN fields f ON w.field_id = f.id
                WHERE w.farm_id = :farm_id
                ORDER BY w.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':farm_id' => $farmId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
