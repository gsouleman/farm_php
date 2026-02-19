<?php
require_once __DIR__ . '/BaseModel.php';

class Document extends BaseModel
{
    protected $table = 'documents';

    public $id;
    public $title;
    public $document_type;
    public $file_path;
    public $file_name;
    public $file_size;
    public $mime_type;
    public $farm_id;
    public $uploaded_by;
    public $notes;

    /**
     * Create new document
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, title, document_type, file_path, file_name, file_size, mime_type, farm_id, uploaded_by, notes) 
                VALUES (:id, :title, :document_type, :file_path, :file_name, :file_size, :mime_type, :farm_id, :uploaded_by, :notes)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':document_type' => $data['document_type'] ?? null,
            ':file_path' => $data['file_path'] ?? null,
            ':file_name' => $data['file_name'] ?? null,
            ':file_size' => $data['file_size'] ?? null,
            ':mime_type' => $data['mime_type'] ?? null,
            ':farm_id' => $data['farm_id'] ?? null,
            ':uploaded_by' => $data['uploaded_by'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);

        return $this->findById($id);
    }

    /**
     * Update document
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['title', 'document_type', 'file_path', 'file_name', 'file_size', 'mime_type', 'farm_id', 'notes'] as $field) {
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
     * Find documents by farm ID
     */
    public function findByFarmId($farmId)
    {
        return $this->findAll(['farm_id' => $farmId]);
    }
}
