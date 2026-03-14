<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../config/auth.php';

$database = new Database();
$db = $database->connect();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all events (or single event if ID provided)
            $id = $_GET['id'] ?? null;
            $upcoming = isset($_GET['upcoming']) ? true : false;
            
            if ($id) {
                $stmt = $db->prepare("
                    SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name
                    FROM events e
                    LEFT JOIN users u ON e.created_by = u.id
                    WHERE e.id = ?
                ");
                $stmt->execute([$id]);
                $event = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($event) {
                    echo json_encode(['success' => true, 'data' => $event]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Event not found']);
                }
            } else {
                $query = "
                    SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name
                    FROM events e
                    LEFT JOIN users u ON e.created_by = u.id
                ";
                
                if ($upcoming) {
                    $query .= " WHERE e.event_date >= NOW() ORDER BY e.event_date ASC";
                } else {
                    $query .= " ORDER BY e.event_date DESC";
                }
                
                $stmt = $db->prepare($query);
                $stmt->execute();
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $events]);
            }
            break;
            
        case 'POST':
            // Create new event (admin/faculty only)
            session_start();
            if (!isset($_SESSION['user']) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            
            $user = $_SESSION['user'];
            if ($user['role'] === 'student') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate input
            if (empty($data['title']) || empty($data['event_date'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Title and event date are required']);
                exit;
            }
            
            $stmt = $db->prepare("
                INSERT INTO events (title, description, event_date, location, image_path, is_featured, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $data['title'],
                $data['description'] ?? null,
                $data['event_date'],
                $data['location'] ?? null,
                $data['image_path'] ?? null,
                $data['is_featured'] ?? false,
                $user['id']
            ]);
            
            if ($success) {
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to create event']);
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