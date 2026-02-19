<?php
require_once __DIR__ . '/../models/FarmUser.php';

class TeamController
{
    private $db;
    private $farmUser;

    public function __construct($db)
    {
        $this->db = $db;
        $this->farmUser = new FarmUser($db);
    }

    /**
     * Get all team members for a farm
     */
    public function index($farmId = null)
    {
        if ($farmId) {
            $members = $this->farmUser->findByFarmId($farmId);
        } else {
            $members = $this->farmUser->findAll();
        }

        // Parse JSON permissions
        foreach ($members as &$member) {
            if (isset($member['permissions']) && is_string($member['permissions'])) {
                $member['permissions'] = json_decode($member['permissions'], true);
            }
        }

        return ['success' => true, 'data' => $members];
    }

    /**
     * Get single team member
     */
    public function show($id)
    {
        $member = $this->farmUser->findById($id);

        if (!$member) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Team member not found'];
        }

        if (isset($member['permissions']) && is_string($member['permissions'])) {
            $member['permissions'] = json_decode($member['permissions'], true);
        }

        return ['success' => true, 'data' => $member];
    }

    /**
     * Add team member to farm
     */
    public function store($farmId = null)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        // Ensure we have a farm_id
        $farm_id = $farmId ?: ($data['farm_id'] ?? null);
        if (!$farm_id) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Farm ID is required'];
        }

        if (empty($data['user_id'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'User ID is required'];
        }

        // Check if user is already in this farm
        $existing = $this->farmUser->findByFarmAndUser($farm_id, $data['user_id']);
        if ($existing) {
            http_response_code(409);
            return ['success' => false, 'message' => 'User is already a member of this farm'];
        }

        $data['farm_id'] = $farm_id;
        $member = $this->farmUser->create($data);

        if (isset($member['permissions']) && is_string($member['permissions'])) {
            $member['permissions'] = json_decode($member['permissions'], true);
        }

        http_response_code(201);
        return ['success' => true, 'message' => 'Team member added successfully', 'data' => $member];
    }

    /**
     * Update team member
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->farmUser->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Team member not found'];
        }

        $member = $this->farmUser->update($id, $data);

        if (isset($member['permissions']) && is_string($member['permissions'])) {
            $member['permissions'] = json_decode($member['permissions'], true);
        }

        return ['success' => true, 'message' => 'Team member updated successfully', 'data' => $member];
    }

    /**
     * Remove team member
     */
    public function destroy($id)
    {
        $existing = $this->farmUser->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Team member not found'];
        }

        $this->farmUser->delete($id);

        return ['success' => true, 'message' => 'Team member removed successfully'];
    }
}
