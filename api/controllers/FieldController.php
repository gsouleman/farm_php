<?php
require_once __DIR__ . '/../models/Field.php';

class FieldController
{
    private $db;
    private $field;

    public function __construct($db)
    {
        $this->db = $db;
        $this->field = new Field($db);
    }

    /**
     * Get all fields
     */
    public function index($farmId = null)
    {
        if ($farmId) {
            $fields = $this->field->findByFarmId($farmId);
        } else {
            $fields = $this->field->findAll();
        }

        // Parse JSON boundary
        foreach ($fields as &$field) {
            if (isset($field['boundary']) && is_string($field['boundary'])) {
                $field['boundary'] = json_decode($field['boundary'], true);
            }
        }

        return ['success' => true, 'data' => $fields];
    }

    /**
     * Get single field
     */
    public function show($id)
    {
        $field = $this->field->findByIdWithCrops($id);

        if (!$field) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Field not found'];
        }

        if (isset($field['boundary']) && is_string($field['boundary'])) {
            $field['boundary'] = json_decode($field['boundary'], true);
        }

        return ['success' => true, 'data' => $field];
    }

    /**
     * Create new field/parcel
     */
    public function store($farmId = null)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        // Prioritize farm_id from URL if provided
        if ($farmId) {
            $data['farm_id'] = $farmId;
        }

        if (empty($data['name']) || empty($data['farm_id'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Field name and farm_id are required'];
        }

        $field = $this->field->create($data);

        if (isset($field['boundary']) && is_string($field['boundary'])) {
            $field['boundary'] = json_decode($field['boundary'], true);
        }

        http_response_code(201);
        return ['success' => true, 'message' => 'Field created successfully', 'data' => $field];
    }

    /**
     * Update field
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->field->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Field not found'];
        }

        $field = $this->field->update($id, $data);

        if (isset($field['boundary']) && is_string($field['boundary'])) {
            $field['boundary'] = json_decode($field['boundary'], true);
        }

        return ['success' => true, 'message' => 'Field updated successfully', 'data' => $field];
    }

    /**
     * Delete field
     */
    public function destroy($id)
    {
        $existing = $this->field->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Field not found'];
        }

        $this->field->delete($id);

        return ['success' => true, 'message' => 'Field deleted successfully'];
    }
}
