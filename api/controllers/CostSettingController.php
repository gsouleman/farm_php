<?php
require_once __DIR__ . '/../models/CostSetting.php';

class CostSettingController
{
    private $db;
    private $costSetting;

    public function __construct($db)
    {
        $this->db = $db;
        $this->costSetting = new CostSetting($db);
    }

    /**
     * Get all cost settings
     */
    public function index($farmId = null)
    {
        if ($farmId) {
            $settings = $this->costSetting->findByFarmId($farmId);
        } else {
            $settings = $this->costSetting->findAll();
        }

        return ['success' => true, 'data' => $settings];
    }

    /**
     * Get single cost setting
     */
    public function show($id)
    {
        $setting = $this->costSetting->findById($id);

        if (!$setting) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Cost setting not found'];
        }

        return ['success' => true, 'data' => $setting];
    }

    /**
     * Create new cost setting
     */
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['name'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Name is required'];
        }

        $setting = $this->costSetting->create($data);

        http_response_code(201);
        return ['success' => true, 'message' => 'Cost setting created successfully', 'data' => $setting];
    }

    /**
     * Update cost setting
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->costSetting->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Cost setting not found'];
        }

        $setting = $this->costSetting->update($id, $data);

        return ['success' => true, 'message' => 'Cost setting updated successfully', 'data' => $setting];
    }

    /**
     * Delete cost setting
     */
    public function destroy($id)
    {
        $existing = $this->costSetting->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Cost setting not found'];
        }

        $this->costSetting->delete($id);

        return ['success' => true, 'message' => 'Cost setting deleted successfully'];
    }

    /**
     * Get default cost settings
     */
    public function defaults()
    {
        $defaults = $this->costSetting->findDefaults();
        return ['success' => true, 'data' => $defaults];
    }
}
