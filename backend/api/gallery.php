<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$database = new Database();
$db = $database->connect();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get gallery items (filter by category if provided)
            $category = $_GET['category'] ?? null;
            
            $query = "SELECT * FROM gallery";
            $params = [];
            
            if ($category) {
                $query .= " WHERE category = ?";
                $params[] = $category;
            }
            
            $query .= " ORDER BY upload_date DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $galleryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $galleryItems]);
            break;
            
        case 'POST':
            // Upload new gallery item (admin only)
            session_start();
            if (!isset($_SESSION['admin_logged_in'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            
            // Handle file upload
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Image upload failed']);
                exit;
            }
            
            $uploadDir = '../../uploads/gallery/';
            $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $imageName;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to save image']);
                exit;
            }
            
            $data = json_decode($_POST['data'], true);
            
            $stmt = $db->prepare("
                INSERT INTO gallery (title, description, image_path, category, uploaded_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $data['title'],
                $data['description'] ?? null,
                'uploads/gallery/' . $imageName,
                $data['category'] ?? null,
                $_SESSION['admin_id']
            ]);
            
            if ($success) {
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to add gallery item']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>