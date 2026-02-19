<?php
require_once __DIR__ . '/../models/Crop.php';

class CropController
{
    private $db;
    private $crop;

    public function __construct($db)
    {
        $this->db = $db;
        $this->crop = new Crop($db);
    }

    /**
     * Get all crops
     */
    public function index($farmId = null)
    {
        if ($farmId) {
            $crops = $this->crop->findByFarmId($farmId);
        } else {
            $crops = $this->crop->findAll();
        }

        // Parse JSON coordinates
        foreach ($crops as &$crop) {
            if (isset($crop['coordinates']) && is_string($crop['coordinates'])) {
                $crop['coordinates'] = json_decode($crop['coordinates'], true);
            }
        }

        return ['success' => true, 'data' => $crops];
    }

    /**
     * Get single crop
     */
    public function show($id)
    {
        $crop = $this->crop->findById($id);

        if (!$crop) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Crop not found'];
        }

        if (isset($crop['coordinates']) && is_string($crop['coordinates'])) {
            $crop['coordinates'] = json_decode($crop['coordinates'], true);
        }

        return ['success' => true, 'data' => $crop];
    }

    /**
     * Create new crop
     */
    public function store($farmId = null, $fieldId = null)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        // Prioritize farm_id or field_id from URL if provided
        if ($farmId) {
            $data['farm_id'] = $farmId;
        }
        if ($fieldId) {
            $data['field_id'] = $fieldId;
        }

        // Allow 'crop_type' to be used alias for 'name'
        if (empty($data['name']) && !empty($data['crop_type'])) {
            $data['name'] = $data['crop_type'];
        }

        if (empty($data['name']) || (empty($data['farm_id']) && empty($data['field_id']))) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Crop name (or crop_type) and farm_id or field_id are required'];
        }

        $crop = $this->crop->create($data);

        if (isset($crop['boundary']) && is_string($crop['boundary'])) {
            $crop['coordinates'] = json_decode($crop['boundary'], true);
            $crop['boundary'] = $crop['coordinates']; // Create consistent object
        } elseif (isset($crop['coordinates']) && is_string($crop['coordinates'])) {
            $crop['coordinates'] = json_decode($crop['coordinates'], true);
        }

        http_response_code(201);
        return ['success' => true, 'message' => 'Crop created successfully', 'data' => $crop];
    }

    /**
     * Update crop
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->crop->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Crop not found'];
        }

        $crop = $this->crop->update($id, $data);

        if (isset($crop['boundary']) && is_string($crop['boundary'])) {
            $crop['coordinates'] = json_decode($crop['boundary'], true);
            $crop['boundary'] = $crop['coordinates'];
        } elseif (isset($crop['coordinates']) && is_string($crop['coordinates'])) {
            $crop['coordinates'] = json_decode($crop['coordinates'], true);
        }

        return ['success' => true, 'message' => 'Crop updated successfully', 'data' => $crop];
    }

    /**
     * Delete crop
     */
    public function destroy($id)
    {
        $existing = $this->crop->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Crop not found'];
        }

        $this->crop->delete($id);

        return ['success' => true, 'message' => 'Crop deleted successfully'];
    }

    /**
     * Get active crops
     */
    public function active($farmId = null)
    {
        $crops = $this->crop->findActiveCrops($farmId);

        foreach ($crops as &$crop) {
            if (isset($crop['boundary']) && is_string($crop['boundary'])) {
                $crop['coordinates'] = json_decode($crop['boundary'], true);
                $crop['boundary'] = $crop['coordinates'];
            } elseif (isset($crop['coordinates']) && is_string($crop['coordinates'])) {
                $crop['coordinates'] = json_decode($crop['coordinates'], true);
            }
        }

        return ['success' => true, 'data' => $crops];
    }

    /**
     * Get crops by field ID
     */
    public function byField($fieldId)
    {
        $crops = $this->crop->findByFieldId($fieldId);

        foreach ($crops as &$crop) {
            if (isset($crop['boundary']) && is_string($crop['boundary'])) {
                $crop['coordinates'] = json_decode($crop['boundary'], true);
                $crop['boundary'] = $crop['coordinates'];
            } elseif (isset($crop['coordinates']) && is_string($crop['coordinates'])) {
                $crop['coordinates'] = json_decode($crop['coordinates'], true);
            }
        }

        return ['success' => true, 'data' => $crops];
    }
}
