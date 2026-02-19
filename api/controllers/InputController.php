<?php
require_once __DIR__ . '/../models/Input.php';

class InputController
{
    private $db;
    private $input;

    public function __construct($db)
    {
        $this->db = $db;
        $this->input = new Input($db);
    }

    /**
     * Get all inputs
     */
    public function index($farmId = null)
    {
        if ($farmId) {
            $inputs = $this->input->findByFarmId($farmId);
        } else {
            $inputs = $this->input->findAll();
        }

        return ['success' => true, 'data' => $inputs];
    }

    /**
     * Get single input
     */
    public function show($id)
    {
        $input = $this->input->findById($id);

        if (!$input) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Input not found'];
        }

        return ['success' => true, 'data' => $input];
    }

    /**
     * Create new input
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
            return ['success' => false, 'message' => 'Name and farm_id are required'];
        }

        $input = $this->input->create($data);

        http_response_code(201);
        return ['success' => true, 'message' => 'Input created successfully', 'data' => $input];
    }

    /**
     * Update input
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->input->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Input not found'];
        }

        $input = $this->input->update($id, $data);

        return ['success' => true, 'message' => 'Input updated successfully', 'data' => $input];
    }

    /**
     * Delete input
     */
    public function destroy($id)
    {
        $existing = $this->input->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Input not found'];
        }

        $this->input->delete($id);

        return ['success' => true, 'message' => 'Input deleted successfully'];
    }
}
