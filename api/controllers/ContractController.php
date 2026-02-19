<?php
require_once __DIR__ . '/../models/Contract.php';

class ContractController
{
    private $db;
    private $contract;

    public function __construct($db)
    {
        $this->db = $db;
        $this->contract = new Contract($db);
    }

    /**
     * Get all contracts
     */
    public function index($farmId = null)
    {
        if ($farmId) {
            $contracts = $this->contract->findByFarmId($farmId);
        } else {
            $contracts = $this->contract->findAll();
        }

        return ['success' => true, 'data' => $contracts];
    }

    /**
     * Get single contract
     */
    public function show($id)
    {
        $contract = $this->contract->findById($id);

        if (!$contract) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Contract not found'];
        }

        return ['success' => true, 'data' => $contract];
    }

    /**
     * Create new contract
     */
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['title']) || empty($data['farm_id'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Title and farm_id are required'];
        }

        $contract = $this->contract->create($data);

        http_response_code(201);
        return ['success' => true, 'message' => 'Contract created successfully', 'data' => $contract];
    }

    /**
     * Update contract
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->contract->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Contract not found'];
        }

        $contract = $this->contract->update($id, $data);

        return ['success' => true, 'message' => 'Contract updated successfully', 'data' => $contract];
    }

    /**
     * Delete contract
     */
    public function destroy($id)
    {
        $existing = $this->contract->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Contract not found'];
        }

        $this->contract->delete($id);

        return ['success' => true, 'message' => 'Contract deleted successfully'];
    }
}
