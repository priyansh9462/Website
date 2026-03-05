<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../config/jwt.php';
require_once '../../config/validation.php';

$database = new Database();
$db = $database->connect();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get materials for a course
            $courseId = $_GET['course_id'] ?? null;
            
            if (!$courseId) {
                throw new Exception('Course ID is required');
            }
            
            $stmt = $db->prepare("
                SELECT cm.*, c.title as course_title, 
                       CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name
                FROM course_materials cm
                JOIN courses c ON cm.course_id = c.id
                JOIN users u ON cm.uploaded_by = u.id
                WHERE cm.course_id = ?
                ORDER BY cm.upload_date DESC
            ");
            $stmt->execute([$courseId]);
            $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $materials]);
            break;
            
        case 'POST':
            // Verify JWT token
            $authUser = JWT_Auth::getAuthUser();
            if (!$authUser || $authUser['role'] === 'student') {
                throw new Exception('Unauthorized');
            }
            
            // Validate file upload
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload failed');
            }
            
            $allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'application/zip'
            ];
            
            Validator::validateFile($_FILES['file'], $allowedTypes, 10 * 1024 * 1024); // 10MB max
            
            // Handle file upload
            $uploadDir = '../../uploads/course-materials/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['file']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                throw new Exception('Failed to save file');
            }
            
            $data = json_decode($_POST['data'], true);
            
            if (empty($data['course_id']) || empty($data['title'])) {
                throw new Exception('Course ID and title are required');
            }
            
            $stmt = $db->prepare("
                INSERT INTO course_materials (
                    course_id, title, description, file_path, 
                    file_type, file_size, uploaded_by
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $fileSize = $_FILES['file']['size'];
            $fileType = $_FILES['file']['type'];
            
            $success = $stmt->execute([
                $data['course_id'],
                Validator::sanitizeInput($data['title']),
                isset($data['description']) ? Validator::sanitizeInput($data['description']) : null,
                'uploads/course-materials/' . $fileName,
                $fileType,
                $fileSize,
                $authUser['sub']
            ]);
            
            if (!$success) {
                throw new Exception('Failed to add course material');
            }
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'id' => $db->lastInsertId()
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>