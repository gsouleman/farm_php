<?php
require_once __DIR__ . '/BaseController.php';

class ActivitiesController extends BaseController {

    public function all() {
        $this->requireAuth();
        try {
            // Filter by farm_id if provided, otherwise by user's farms
            $farm_id = $_GET['farm_id'] ?? null;
            if ($farm_id) {
                $stmt = $this->db->prepare("SELECT * FROM activities WHERE farm_id = ?");
                $stmt->execute([$farm_id]);
            } else {
                $stmt = $this->db->prepare("SELECT a.* FROM activities a JOIN farms f ON a.farm_id = f.id WHERE f.owner_id = ? ORDER BY a.created_at DESC");
                $stmt->execute([$this->userId]);
            }
            echo json_encode($stmt->fetchAll());
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function update($id, $data) {
        $this->requireAuth();
        try {
            $stmt = $this->db->prepare("UPDATE activities SET 
                activity_type = ?, 
                activity_date = ?, 
                status = ?, 
                description = ?, 
                notes = ?, 
                cost = ?,
                updated_at = NOW() 
                WHERE id = ?");
            
            $stmt->execute([
                $data['activity_type'],
                $data['activity_date'],
                $data['status'] ?? 'planned',
                $data['description'] ?? null,
                $data['notes'] ?? null,
                $data['cost'] ?? 0,
                $id
            ]);

            echo json_encode(['message' => 'Activity updated successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function delete($id) {
        $this->requireAuth();
        try {
            $stmt = $this->db->prepare("DELETE FROM activities WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['message' => 'Activity deleted successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function create($data) {
        $this->requireAuth();
        try {
            $id = $this->generateUUID();
            $stmt = $this->db->prepare("INSERT INTO activities (id, farm_id, crop_id, activity_type, activity_date, status, description, notes, cost, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            
            $stmt->execute([
                $id,
                $data['farm_id'],
                $data['crop_id'] ?? null,
                $data['activity_type'],
                $data['activity_date'],
                $data['status'] ?? 'planned',
                $data['description'] ?? null,
                $data['notes'] ?? null,
                $data['cost'] ?? 0
            ]);

            echo json_encode(['message' => 'Activity created', 'id' => $id]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
