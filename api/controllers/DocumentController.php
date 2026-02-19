<?php
require_once __DIR__ . '/../models/Document.php';

class DocumentController
{
    private $db;
    private $document;
    private $uploadDir;

    public function __construct($db)
    {
        $this->db = $db;
        $this->document = new Document($db);
        $this->uploadDir = __DIR__ . '/../../uploads/documents/';

        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Get all documents
     */
    public function index($farmId = null)
    {
        if ($farmId) {
            $documents = $this->document->findByFarmId($farmId);
        } else {
            $documents = $this->document->findAll();
        }

        return ['success' => true, 'data' => $documents];
    }

    /**
     * Get single document
     */
    public function show($id)
    {
        $document = $this->document->findById($id);

        if (!$document) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Document not found'];
        }

        return ['success' => true, 'data' => $document];
    }

    /**
     * Upload new document
     */
    public function store()
    {
        // Handle file upload
        if (!isset($_FILES['file'])) {
            // Try JSON input for metadata-only creation
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['title'])) {
                http_response_code(400);
                return ['success' => false, 'message' => 'Title is required'];
            }

            $document = $this->document->create($data);

            http_response_code(201);
            return ['success' => true, 'message' => 'Document created successfully', 'data' => $document];
        }

        $file = $_FILES['file'];
        $title = $_POST['title'] ?? $file['name'];
        $farmId = $_POST['farm_id'] ?? null;
        $documentType = $_POST['document_type'] ?? null;

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            return ['success' => false, 'message' => 'File upload failed'];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $this->uploadDir . $newFileName;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            http_response_code(500);
            return ['success' => false, 'message' => 'Failed to save file'];
        }

        // Create document record
        $document = $this->document->create([
            'title' => $title,
            'document_type' => $documentType,
            'file_path' => 'uploads/documents/' . $newFileName,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'farm_id' => $farmId,
            'notes' => $_POST['notes'] ?? null
        ]);

        http_response_code(201);
        return ['success' => true, 'message' => 'Document uploaded successfully', 'data' => $document];
    }

    /**
     * Update document metadata
     */
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $existing = $this->document->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Document not found'];
        }

        $document = $this->document->update($id, $data);

        return ['success' => true, 'message' => 'Document updated successfully', 'data' => $document];
    }

    /**
     * Delete document
     */
    public function destroy($id)
    {
        $existing = $this->document->findById($id);
        if (!$existing) {
            http_response_code(404);
            return ['success' => false, 'message' => 'Document not found'];
        }

        // Delete file if exists
        if (!empty($existing['file_path'])) {
            $filePath = __DIR__ . '/../../' . $existing['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $this->document->delete($id);

        return ['success' => true, 'message' => 'Document deleted successfully'];
    }
}
