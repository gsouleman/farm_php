<?php
require_once __DIR__ . '/../models/User.php';

class UserController
{
    private $db;
    private $user;

    public function __construct($db)
    {
        $this->db = $db;
        $this->user = new User($db);
    }

    /**
     * Get all users
     */
    public function index()
    {
        $users = $this->user->findAll();

        // Remove passwords from response
        foreach ($users as &$user) {
            unset($user['password']);
        }

        return ['success' => true, 'data' => $users];
    }

    /**
     * Get single user
     */
    public function show($id)
    {
        $user = $this->user->findById($id);

        if (!$user) {
            http_response_code(404);
            return ['success' => false, 'message' => 'User not found'];
        }

        unset($user['password']);

        return ['success' => true, 'data' => $user];
    }

    /**
     * Create new user
     */
    public function store()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
            http_response_code(400);
            return ['success' => false, 'message' => 'Required fields missing'];
        }

        // Check if email exists
        if ($this->user->findByEmail($data['email'])) {
            http_response_code(409);
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $user = $this->user->create($data);
        unset($user['password']);

        http_response_code(201);
        return ['success' => true, 'message' => 'User created successfully', 'data' => $user];
    }

    /**
     * Update user
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->user->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'User not found'];
        }

        // Check if changing email to existing email
        if (!empty($data['email']) && $data['email'] !== $existing['email']) {
            if ($this->user->findByEmail($data['email'])) {
                http_response_code(409);
                return ['success' => false, 'message' => 'Email already exists'];
            }
        }

        $user = $this->user->update($id, $data);
        unset($user['password']);

        return ['success' => true, 'message' => 'User updated successfully', 'data' => $user];
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        $existing = $this->user->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'User not found'];
        }

        $this->user->delete($id);

        return ['success' => true, 'message' => 'User deleted successfully'];
    }
}
