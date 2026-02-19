<?php
// Base Controller to share common functionality
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/AuthHelper.php';

class BaseController {
    protected $db;
    protected $userId;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->userId = AuthHelper::getUserId();
    }

    protected function requireAuth() {
        if (!$this->userId) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            exit;
        }
    }

    protected function formatGeometry(&$item, $fields = ['boundary', 'coordinates']) {
        if (!$item) return;
        foreach ($fields as $field) {
            if (isset($item[$field]) && is_string($item[$field])) {
                $decoded = json_decode($item[$field], true);
                if ($decoded) {
                    $item[$field] = $decoded;
                }
            }
        }
    }

    protected function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
