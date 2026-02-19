<?php
require_once __DIR__ . '/BaseModel.php';

class Weather extends BaseModel
{
    protected $table = 'weather_data';

    public $id;
    public $farm_id;
    public $date;
    public $temperature;
    public $humidity;
    public $precipitation;
    public $wind_speed;
    public $conditions;
    public $data;

    /**
     * Create new weather record
     */
    public function create($data)
    {
        $id = $this->generateUUID();

        $sql = "INSERT INTO {$this->table} 
                (id, farm_id, date, temperature, humidity, precipitation, wind_speed, conditions, data) 
                VALUES (:id, :farm_id, :date, :temperature, :humidity, :precipitation, :wind_speed, :conditions, :data)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':farm_id' => $data['farm_id'],
            ':date' => $data['date'] ?? date('Y-m-d'),
            ':temperature' => $data['temperature'] ?? null,
            ':humidity' => $data['humidity'] ?? null,
            ':precipitation' => $data['precipitation'] ?? null,
            ':wind_speed' => $data['wind_speed'] ?? null,
            ':conditions' => $data['conditions'] ?? null,
            ':data' => isset($data['data']) ? json_encode($data['data']) : null
        ]);

        return $this->findById($id);
    }

    /**
     * Find weather by farm and date
     */
    public function findByFarmAndDate($farmId, $date)
    {
        return $this->findOne(['farm_id' => $farmId, 'date' => $date]);
    }

    /**
     * Find recent weather data
     */
    public function findRecentByFarm($farmId, $days = 7)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE farm_id = :farm_id AND date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                ORDER BY date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':farm_id' => $farmId, ':days' => $days]);
        return $stmt->fetchAll();
    }
}
