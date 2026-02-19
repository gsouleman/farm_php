<?php
require_once __DIR__ . '/../models/Farm.php';

class FarmController
{
    private $db;
    private $farm;

    public function __construct($db)
    {
        $this->db = $db;
        $this->farm = new Farm($db);
    }

    /**
     * Get all farms
     */
    public function index($userId = null)
    {
        if ($userId) {
            $farms = $this->farm->findByUserId($userId);
        } else {
            $farms = $this->farm->findAll();
        }

        // Parse JSON coordinates
        foreach ($farms as &$farm) {
            if (isset($farm['coordinates']) && is_string($farm['coordinates'])) {
                $farm['coordinates'] = json_decode($farm['coordinates'], true);
            }
        }

        return ['success' => true, 'data' => $farms];
    }

    /**
     * Get single farm
     */
    public function show($id)
    {
        $farm = $this->farm->findByIdWithDetails($id);

        if (!$farm) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Farm not found'];
        }

        if (isset($farm['coordinates']) && is_string($farm['coordinates'])) {
            $farm['coordinates'] = json_decode($farm['coordinates'], true);
        }

        return ['success' => true, 'data' => $farm];
    }

    /**
     * Create new farm
     */
    public function store()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['name'])) {
                http_response_code(400);
                return ['success' => false, 'message' => 'Farm name is required'];
            }

            // Extract authenticated user ID
            require_once __DIR__ . '/AuthController.php';
            $auth = new AuthController($this->db);
            $userId = $auth->getAuthenticatedUserId();

            if ($userId) {
                $data['owner_id'] = $userId;
            }

            $farm = $this->farm->create($data);

            if ($farm && isset($farm['coordinates']) && is_string($farm['coordinates'])) {
                $farm['coordinates'] = json_decode($farm['coordinates'], true);
            }

            http_response_code(201);
            return [
                'success' => true,
                'message' => 'Farm created successfully',
                'data' => $farm,
                'notification' => [
                    'message' => 'Enterprise Profile "' . $data['name'] . '" registered successfully.',
                    'type' => 'success'
                ]
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'success' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * Update farm
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->farm->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Farm not found'];
        }

        $farm = $this->farm->update($id, $data);

        if (isset($farm['coordinates']) && is_string($farm['coordinates'])) {
            $farm['coordinates'] = json_decode($farm['coordinates'], true);
        }

        return ['success' => true, 'message' => 'Farm updated successfully', 'data' => $farm];
    }

    /**
     * Delete farm
     */
    public function destroy($id)
    {
        $existing = $this->farm->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Farm not found'];
        }

        $this->farm->delete($id);

        return ['success' => true, 'message' => 'Farm deleted successfully'];
    }
}
