<?php
require_once __DIR__ . '/../models/Harvest.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Crop.php';

class HarvestController
{
    private $db;
    private $harvest;
    private $activity;
    private $crop;

    public function __construct($db)
    {
        $this->db = $db;
        $this->harvest = new Harvest($db);
        $this->activity = new Activity($db);
        $this->crop = new Crop($db);
    }

    /**
     * Get all harvests
     */
    public function index($farmId = null)
    {
        if ($farmId) {
            $harvests = $this->harvest->findAllWithCrops($farmId);
        } else {
            $harvests = $this->harvest->findAllWithCrops();
        }

        return ['success' => true, 'data' => $harvests];
    }

    /**
     * Get single harvest
     */
    public function show($id)
    {
        $harvest = $this->harvest->findById($id);

        if (!$harvest) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Harvest not found'];
        }

        return ['success' => true, 'data' => $harvest];
    }

    /**
     * Create new harvest
     */
    public function store($cropId = null)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        // Prioritize crop_id from URL if provided
        if ($cropId) {
            $data['crop_id'] = $cropId;
        }

        if (empty($data['farm_id']) && empty($data['crop_id'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Farm ID or Crop ID is required'];
        }

        // 1. Create Harvest Record
        $harvest = $this->harvest->create($data);

        // 2. Auto-Create Farm Journal Activity (Income) is requested
        try {
            // Fetch crop details for description and farm_id fallback
            $cropDetails = null;
            if (!empty($data['crop_id'])) {
                $cropDetails = $this->crop->findById($data['crop_id']);
            }

            // Determine Farm ID
            $farmId = $data['farm_id'] ?? ($cropDetails['farm_id'] ?? null);

            // Determine Amount (Revenue)
            $amount = $data['total_revenue'] ?? 0;
            if (!$amount && !empty($data['quantity']) && !empty($data['price_per_unit'])) {
                $amount = $data['quantity'] * $data['price_per_unit'];
            }

            // Description
            $cropName = $cropDetails['crop_type'] ?? 'Crop';
            $qty = $data['quantity'] ?? 0;
            $unit = $data['unit'] ?? 'units';
            $desc = "Harvested $qty $unit of $cropName";

            if ($farmId) {
                $activityData = [
                    'activity_type' => 'harvesting',
                    'transaction_type' => 'income', // Force Income as per requirement
                    'activity_date' => $data['harvest_date'] ?? date('Y-m-d'),
                    'farm_id' => $farmId,
                    'crop_id' => $data['crop_id'] ?? null,
                    'field_id' => $cropDetails['field_id'] ?? ($data['field_id'] ?? null),
                    'description' => $desc,
                    'total_cost' => $amount, // For income, total_cost stores the positive amount
                    'labor_cost' => $amount, // Also store in labor_cost as legacy backup/base
                    'work_status' => 'completed',
                    'notes' => 'Auto-generated from Harvest Ledger: ' . ($data['notes'] ?? '')
                ];

                // Check for duplicates (Harvest <-> Journal)
                $existingActivity = $this->activity->findDuplicate($activityData);
                if (!$existingActivity) {
                    $this->activity->create($activityData);
                } else {
                    error_log("Skipping auto-sync: Activity already exists for harvest.");
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the harvest creation
            error_log("Failed to sync harvest to activity: " . $e->getMessage());
        }

        http_response_code(201);
        return ['success' => true, 'message' => 'Harvest recorded successfully', 'data' => $harvest];
    }

    /**
     * Update harvest
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->harvest->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Harvest not found'];
        }

        $harvest = $this->harvest->update($id, $data);

        return ['success' => true, 'message' => 'Harvest updated successfully', 'data' => $harvest];
    }

    /**
     * Delete harvest
     */
    public function destroy($id)
    {
        $existing = $this->harvest->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Harvest not found'];
        }

        $this->harvest->delete($id);

        return ['success' => true, 'message' => 'Harvest deleted successfully'];
    }

    /**
     * Get harvests by crop
     */
    public function byCrop($cropId)
    {
        $harvests = $this->harvest->findByCropId($cropId);
        return ['success' => true, 'data' => $harvests];
    }
}
