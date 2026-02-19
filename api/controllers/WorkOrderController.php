<?php
require_once __DIR__ . '/../models/WorkOrder.php';

class WorkOrderController
{
    private $db;
    private $workOrder;

    public function __construct($db)
    {
        $this->db = $db;
        $this->workOrder = new WorkOrder($db);
    }

    public function index($farmId = null)
    {
        if (!$farmId) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Farm ID is required'];
        }

        $orders = $this->workOrder->findAllByFarm($farmId);
        return ['success' => true, 'data' => $orders];
    }

    public function store()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            error_log("Work Order Create Request: " . json_encode($data));

            if (empty($data['farm_id']) || empty($data['title'])) {
                http_response_code(400);
                return ['success' => false, 'message' => 'Farm ID and Title are required'];
            }

            // Validate field_id if provided
            if (!empty($data['field_id'])) {
                $stmt = $this->db->prepare("SELECT id FROM fields WHERE id = :field_id");
                $stmt->execute([':field_id' => $data['field_id']]);
                if ($stmt->rowCount() === 0) {
                    error_log("Invalid field_id provided: " . $data['field_id']);
                    http_response_code(400);
                    return ['success' => false, 'message' => 'Invalid field_id: Field does not exist'];
                }
            }

            // Validate assigned_to if provided
            if (!empty($data['assigned_to'])) {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :user_id");
                $stmt->execute([':user_id' => $data['assigned_to']]);
                if ($stmt->rowCount() === 0) {
                    error_log("Invalid assigned_to user_id: " . $data['assigned_to']);
                    http_response_code(400);
                    return ['success' => false, 'message' => 'Invalid assigned_to: User does not exist'];
                }
            }

            $order = $this->workOrder->create($data);
            http_response_code(201);
            return ['success' => true, 'data' => $order];
        } catch (PDOException $e) {
            error_log("Work Order Creation Error (PDO): " . $e->getMessage());
            http_response_code(500);

            // Parse common database errors
            $message = 'Database error occurred';
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $message = 'Invalid reference: One or more related records do not exist';
            } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = 'Duplicate entry: Work order already exists';
            }

            return ['success' => false, 'message' => $message, 'error' => $e->getMessage()];
        } catch (Exception $e) {
            error_log("Work Order Creation Error: " . $e->getMessage());
            http_response_code(500);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $order = $this->workOrder->update($id, $data);
        return ['success' => true, 'data' => $order];
    }

    public function delete($id)
    {
        $this->workOrder->delete($id);
        return ['success' => true, 'message' => 'Work Order deleted'];
    }
}
