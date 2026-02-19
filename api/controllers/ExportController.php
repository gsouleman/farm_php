<?php

/**
 * Export Controller
 * Handles data export functionality
 */

class ExportController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Export data as JSON
     */
    public function exportJson($table, $farmId = null)
    {
        $validTables = ['farms', 'fields', 'crops', 'activities', 'harvests', 'infrastructure', 'inputs'];

        if (!in_array($table, $validTables)) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Invalid table name'];
        }

        $sql = "SELECT * FROM $table";
        $params = [];

        if ($farmId && $table !== 'farms') {
            $sql .= " WHERE farm_id = :farm_id";
            $params[':farm_id'] = $farmId;
        } elseif ($farmId && $table === 'farms') {
            $sql .= " WHERE id = :farm_id";
            $params[':farm_id'] = $farmId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        return [
            'success' => true,
            'table' => $table,
            'count' => count($data),
            'data' => $data,
            'exported_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Export data as CSV
     */
    public function exportCsv($table, $farmId = null)
    {
        $result = $this->exportJson($table, $farmId);

        if (!$result['success']) {
            return $result;
        }

        if (empty($result['data'])) {
            return ['success' => true, 'csv' => '', 'message' => 'No data to export'];
        }

        // Generate CSV
        $output = fopen('php://temp', 'r+');

        // Headers
        fputcsv($output, array_keys($result['data'][0]));

        // Data rows
        foreach ($result['data'] as $row) {
            // Convert arrays/objects to JSON strings
            foreach ($row as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $row[$key] = json_encode($value);
                }
            }
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return [
            'success' => true,
            'csv' => $csv,
            'filename' => $table . '_export_' . date('Y-m-d') . '.csv'
        ];
    }

    /**
     * Export all farm data
     */
    public function exportAll($farmId)
    {
        $tables = ['farms', 'fields', 'crops', 'activities', 'harvests', 'infrastructure', 'inputs'];
        $export = [];

        foreach ($tables as $table) {
            $result = $this->exportJson($table, $farmId);
            if ($result['success']) {
                $export[$table] = $result['data'];
            }
        }

        return [
            'success' => true,
            'farm_id' => $farmId,
            'data' => $export,
            'exported_at' => date('Y-m-d H:i:s')
        ];
    }
}
