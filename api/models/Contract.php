<?php
require_once __DIR__ . '/BaseModel.php';

class Contract extends BaseModel
{
    protected $table = 'contracts';

    public $id;
    public $title;
    public $contract_type;
    public $party_name;
    public $farm_id;
    public $start_date;
    public $end_date;
    public $value;
    public $currency;
    public $status;
    public $terms;
    public $notes;

    /**
     * Create new contract
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, title, contract_type, party_name, farm_id, start_date, end_date, value, currency, status, terms, notes) 
                VALUES (:id, :title, :contract_type, :party_name, :farm_id, :start_date, :end_date, :value, :currency, :status, :terms, :notes)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':contract_type' => $data['contract_type'] ?? null,
            ':party_name' => $data['party_name'] ?? null,
            ':farm_id' => $data['farm_id'],
            ':start_date' => $data['start_date'] ?? null,
            ':end_date' => $data['end_date'] ?? null,
            ':value' => $data['value'] ?? 0,
            ':currency' => $data['currency'] ?? 'USD',
            ':status' => $data['status'] ?? 'active',
            ':terms' => $data['terms'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);

        return $this->findById($id);
    }

    /**
     * Update contract
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['title', 'contract_type', 'party_name', 'farm_id', 'start_date', 'end_date', 'value', 'currency', 'status', 'terms', 'notes'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    /**
     * Find contracts by farm ID
     */
    public function findByFarmId($farmId)
    {
        return $this->findAll(['farm_id' => $farmId]);
    }

    /**
     * Find active contracts
     */
    public function findActiveContracts($farmId = null)
    {
        $conditions = ['status' => 'active'];
        if ($farmId) {
            $conditions['farm_id'] = $farmId;
        }
        return $this->findAll($conditions);
    }
}
