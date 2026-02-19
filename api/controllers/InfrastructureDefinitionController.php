<?php
require_once __DIR__ . '/../models/InfrastructureDefinition.php';

class InfrastructureDefinitionController
{
    private $db;
    private $infraDefinition;

    public function __construct($db)
    {
        $this->db = $db;
        $this->infraDefinition = new InfrastructureDefinition($db);
    }

    /**
     * Get all infrastructure definitions
     */
    public function index()
    {
        $definitions = $this->infraDefinition->findAll();
        return ['success' => true, 'data' => $definitions];
    }

    /**
     * Get single infrastructure definition
     */
    public function show($id)
    {
        $definition = $this->infraDefinition->findById($id);

        if (!$definition) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Infrastructure definition not found'];
        }

        return ['success' => true, 'data' => $definition];
    }

    /**
     * Create new infrastructure definition
     */
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['name'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Name is required'];
        }

        $definition = $this->infraDefinition->create($data);

        http_response_code(201);
        return ['success' => true, 'message' => 'Infrastructure definition created successfully', 'data' => $definition];
    }

    /**
     * Update infrastructure definition
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->infraDefinition->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Infrastructure definition not found'];
        }

        $definition = $this->infraDefinition->update($id, $data);

        return ['success' => true, 'message' => 'Infrastructure definition updated successfully', 'data' => $definition];
    }

    /**
     * Delete infrastructure definition
     */
    public function destroy($id)
    {
        $existing = $this->infraDefinition->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Infrastructure definition not found'];
        }

        $this->infraDefinition->delete($id);

        return ['success' => true, 'message' => 'Infrastructure definition deleted successfully'];
    }

    /**
     * Get definitions by category
     */
    public function byCategory($category)
    {
        $definitions = $this->infraDefinition->findByCategory($category);
        return ['success' => true, 'data' => $definitions];
    }
}
