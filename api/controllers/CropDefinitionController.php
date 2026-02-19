<?php
require_once __DIR__ . '/../models/CropDefinition.php';

class CropDefinitionController
{
    private $db;
    private $cropDefinition;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cropDefinition = new CropDefinition($db);
    }

    /**
     * Get all crop definitions
     */
    public function index()
    {
        $definitions = $this->cropDefinition->findAll();
        return ['success' => true, 'data' => $definitions];
    }

    /**
     * Get single crop definition
     */
    public function show($id)
    {
        $definition = $this->cropDefinition->findById($id);

        if (!$definition) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Crop definition not found'];
        }

        return ['success' => true, 'data' => $definition];
    }

    /**
     * Create new crop definition
     */
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['name'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Name is required'];
        }

        $definition = $this->cropDefinition->create($data);

        http_response_code(201);
        return ['success' => true, 'message' => 'Crop definition created successfully', 'data' => $definition];
    }

    /**
     * Update crop definition
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->cropDefinition->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Crop definition not found'];
        }

        $definition = $this->cropDefinition->update($id, $data);

        return ['success' => true, 'message' => 'Crop definition updated successfully', 'data' => $definition];
    }

    /**
     * Delete crop definition
     */
    public function destroy($id)
    {
        $existing = $this->cropDefinition->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Crop definition not found'];
        }

        $this->cropDefinition->delete($id);

        return ['success' => true, 'message' => 'Crop definition deleted successfully'];
    }

    /**
     * Get definitions by category
     */
    public function byCategory($category)
    {
        $definitions = $this->cropDefinition->findByCategory($category);
        return ['success' => true, 'data' => $definitions];
    }
}
