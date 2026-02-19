<?php
require_once __DIR__ . '/BaseController.php';

class CropsController extends BaseController {

    public function all() {
        $this->requireAuth();
        try {
            $stmt = $this->db->prepare("SELECT c.id, c.field_id, c.crop_type, c.variety, c.planting_date, c.expected_harvest_date, c.actual_harvest_date, c.planted_area, c.planting_rate, c.row_spacing, c.status, c.season, c.year, c.notes, c.created_at, c.updated_at, c.estimated_cost, ST_AsGeoJSON(c.boundary) as boundary FROM crops c JOIN fields f ON c.field_id = f.id JOIN farms fr ON f.farm_id = fr.id WHERE fr.owner_id = ? ORDER BY c.created_at DESC");
            $stmt->execute([$this->userId]);
            $results = $stmt->fetchAll();
            foreach ($results as &$crop) {
                $this->formatGeometry($crop);
            }
            echo json_encode($results ?: []);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getAll($id = null) {
        if ($id && $this->is_uuid($id)) {
             // Single crop fetch
             $this->requireAuth();
             $stmt = $this->db->prepare("SELECT c.id, c.field_id, c.crop_type, c.variety, c.planting_date, c.expected_harvest_date, c.actual_harvest_date, c.planted_area, c.planting_rate, c.row_spacing, c.status, c.season, c.year, c.notes, c.created_at, c.updated_at, c.estimated_cost, ST_AsGeoJSON(c.boundary) as boundary FROM crops c JOIN fields f ON c.field_id = f.id JOIN farms fr ON f.farm_id = fr.id WHERE c.id = ? AND fr.owner_id = ?");
             $stmt->execute([$id, $this->userId]);
             $crop = $stmt->fetch();
             $this->formatGeometry($crop);
             echo json_encode($crop ?: (object)[]);
        } else {
            $this->all();
        }
    }

    public function create($data) {
        $this->requireAuth();
        try {
            $id = $this->generateUUID();
            $stmt = $this->db->prepare("INSERT INTO crops (id, field_id, crop_type, variety, planting_date, expected_harvest_date, planted_area, status, season, year, notes, created_at, updated_at, boundary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ST_GeomFromGeoJSON(?))");
            
            $boundary = isset($data['boundary']) ? json_encode($data['boundary']) : null;

            $stmt->execute([
                $id,
                $data['field_id'],
                $data['crop_type'],
                $data['variety'] ?? null,
                $data['planting_date'] ?? null,
                $data['expected_harvest_date'] ?? null,
                $data['planted_area'] ?? 0,
                $data['status'] ?? 'planted',
                $data['season'] ?? null,
                $data['year'] ?? date('Y'),
                $data['notes'] ?? null,
                $boundary
            ]);

            // Return the created crop
            $stmt = $this->db->prepare("SELECT c.*, ST_AsGeoJSON(c.boundary) as boundary FROM crops c WHERE id = ?");
            $stmt->execute([$id]);
            $crop = $stmt->fetch();
            $this->formatGeometry($crop);
            echo json_encode($crop);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function update($id, $data) {
        $this->requireAuth();
        try {
            $stmt = $this->db->prepare("UPDATE crops SET 
                crop_type = ?, 
                variety = ?, 
                planting_date = ?, 
                expected_harvest_date = ?, 
                actual_harvest_date = ?,
                planted_area = ?, 
                status = ?, 
                season = ?, 
                year = ?, 
                notes = ?, 
                updated_at = NOW(),
                boundary = ST_GeomFromGeoJSON(?)
                WHERE id = ?");
            
            $boundary = isset($data['boundary']) ? json_encode($data['boundary']) : null;

            $stmt->execute([
                $data['crop_type'],
                $data['variety'] ?? null,
                $data['planting_date'] ?? null,
                $data['expected_harvest_date'] ?? null,
                $data['actual_harvest_date'] ?? null,
                $data['planted_area'] ?? 0,
                $data['status'] ?? 'planted',
                $data['season'] ?? null,
                $data['year'] ?? date('Y'),
                $data['notes'] ?? null,
                $boundary,
                $id
            ]);

            echo json_encode(['message' => 'Crop updated successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function delete($id) {
        $this->requireAuth();
        try {
            $stmt = $this->db->prepare("DELETE FROM crops WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['message' => 'Crop deleted successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function post_activities($crop_id, $data) {
        $this->requireAuth();
        try {
            // Get farm_id from crop
            $stmt = $this->db->prepare("SELECT f.farm_id FROM crops c JOIN fields f ON c.field_id = f.id WHERE c.id = ?");
            $stmt->execute([$crop_id]);
            $crop = $stmt->fetch();
            if (!$crop) throw new Exception("Crop not found");
            $farm_id = $crop['farm_id'];

            $id = $this->generateUUID();
            $stmt = $this->db->prepare("INSERT INTO activities (id, farm_id, crop_id, activity_type, activity_date, status, description, notes, cost, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            
            $stmt->execute([
                $id,
                $farm_id,
                $crop_id,
                $data['activity_type'],
                $data['activity_date'],
                $data['status'] ?? 'planned',
                $data['description'] ?? null,
                $data['notes'] ?? null,
                $data['cost'] ?? 0
            ]);

            echo json_encode(['message' => 'Activity created for crop', 'id' => $id]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function get_activities($crop_id) {
        $this->requireAuth();
        try {
            $stmt = $this->db->prepare("SELECT * FROM activities WHERE crop_id = ? ORDER BY activity_date DESC");
            $stmt->execute([$crop_id]);
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function is_uuid($str) {
        return preg_match('/^[a-f\d]{8}-(?:[a-f\d]{4}-){3}[a-f\d]{12}$/i', $str);
    }
}
