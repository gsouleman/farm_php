<?php

/**
 * System Controller
 * Handles system messages and notifications
 */

class SystemController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get system messages
     */
    public function messages()
    {
        // Return predefined system messages
        $messages = [
            'delete_confirmation' => [
                'title' => 'Confirm Deletion',
                'message' => 'Are you sure you want to delete this item? This action cannot be undone.',
                'confirm_text' => 'Delete',
                'cancel_text' => 'Cancel',
                'type' => 'danger'
            ],
            'save_confirmation' => [
                'title' => 'Unsaved Changes',
                'message' => 'You have unsaved changes. Do you want to save before leaving?',
                'confirm_text' => 'Save',
                'cancel_text' => 'Discard',
                'type' => 'warning'
            ],
            'logout_confirmation' => [
                'title' => 'Confirm Logout',
                'message' => 'Are you sure you want to log out?',
                'confirm_text' => 'Logout',
                'cancel_text' => 'Cancel',
                'type' => 'info'
            ],
            'session_expired' => [
                'title' => 'Session Expired',
                'message' => 'Your session has expired. Please log in again.',
                'confirm_text' => 'Login',
                'type' => 'warning'
            ],
            'idle_timeout' => [
                'title' => 'Inactivity Detected',
                'message' => 'You have been inactive for a while. Your session will expire soon.',
                'confirm_text' => 'Stay Logged In',
                'cancel_text' => 'Logout',
                'type' => 'warning'
            ],
            'network_error' => [
                'title' => 'Connection Error',
                'message' => 'Unable to connect to the server. Please check your internet connection.',
                'confirm_text' => 'Retry',
                'type' => 'error'
            ],
            'offline_mode' => [
                'title' => 'Offline Mode',
                'message' => 'You are currently offline. Changes will be synced when connection is restored.',
                'type' => 'info'
            ],
            'sync_complete' => [
                'title' => 'Sync Complete',
                'message' => 'All offline changes have been synchronized.',
                'type' => 'success'
            ]
        ];

        return ['success' => true, 'data' => $messages];
    }

    /**
     * Health check
     */
    public function health()
    {
        try {
            // Test database connection
            $stmt = $this->db->query("SELECT 1");
            $dbStatus = $stmt ? 'connected' : 'error';
        } catch (Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        return [
            'success' => true,
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $dbStatus,
            'php_version' => phpversion()
        ];
    }

    /**
     * Get app info
     */
    public function info()
    {
        return [
            'success' => true,
            'app' => [
                'name' => 'Farm Management System',
                'version' => '2.0.0',
                'api_version' => '1.0.0'
            ]
        ];
    }
}
