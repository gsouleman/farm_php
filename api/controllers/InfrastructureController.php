<?php
require_once __DIR__ . '/../models/Infrastructure.php';

class InfrastructureController
{
    private $db;
    private $infrastructure;

    public function __construct($db)
    {
        $this->db = $db;
        $this->infrastructure = new Infrastructure($db);
    }

    /**
     * Get all infrastructure
     */
    public function index($farmId = null)
    {
        if ($farmId) {
            $items = $this->infrastructure->findByFarmId($farmId);
        } else {
            $items = $this->infrastructure->findAll();
        }

        // Parse JSON coordinates
        foreach ($items as &$item) {
            if (isset($item['boundary']) && is_string($item['boundary'])) {
                $item['coordinates'] = json_decode($item['boundary'], true);
                $item['boundary'] = $item['coordinates'];
            } elseif (isset($item['coordinates']) && is_string($item['coordinates'])) {
                $item['coordinates'] = json_decode($item['coordinates'], true);
            }
        }

        return ['success' => true, 'data' => $items];
    }

    /**
     * Get single infrastructure
     */
    public function show($id)
    {
        $item = $this->infrastructure->findById($id);

        if (!$item) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Infrastructure not found'];
        }

        if (isset($item['boundary']) && is_string($item['boundary'])) {
            $item['coordinates'] = json_decode($item['boundary'], true);
            $item['boundary'] = $item['coordinates'];
        } elseif (isset($item['coordinates']) && is_string($item['coordinates'])) {
            $item['coordinates'] = json_decode($item['coordinates'], true);
        }

        return ['success' => true, 'data' => $item];
    }

    /**
     * Create new infrastructure
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

        $item = $this->infrastructure->create($data);

        if (isset($item['boundary']) && is_string($item['boundary'])) {
            $item['coordinates'] = json_decode($item['boundary'], true);
            $item['boundary'] = $item['coordinates'];
        } elseif (isset($item['coordinates']) && is_string($item['coordinates'])) {
            $item['coordinates'] = json_decode($item['coordinates'], true);
        }

        http_response_code(201);
        return ['success' => true, 'message' => 'Infrastructure created successfully', 'data' => $item];
    }

    /**
     * Update infrastructure
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->infrastructure->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Infrastructure not found'];
        }

        $item = $this->infrastructure->update($id, $data);

        if (isset($item['boundary']) && is_string($item['boundary'])) {
            $item['coordinates'] = json_decode($item['boundary'], true);
            $item['boundary'] = $item['coordinates'];
        } elseif (isset($item['coordinates']) && is_string($item['coordinates'])) {
            $item['coordinates'] = json_decode($item['coordinates'], true);
        }

        return ['success' => true, 'message' => 'Infrastructure updated successfully', 'data' => $item];
    }

    /**
     * Delete infrastructure
     */
    public function destroy($id)
    {
        $existing = $this->infrastructure->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Infrastructure not found'];
        }

        $this->infrastructure->delete($id);

        return ['success' => true, 'message' => 'Infrastructure deleted successfully'];
    }
}
