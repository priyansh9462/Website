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
            // Get all faculty or single faculty member
            $id = $_GET['id'] ?? null;
            $department = $_GET['department'] ?? null;
            
            if ($id) {
                $stmt = $db->prepare("
                    SELECT u.id, u.first_name, u.last_name, u.email, u.phone, 
                           f.title, f.department, f.specialization, f.bio, 
                           f.research_interests, f.achievements
                    FROM users u
                    JOIN faculty f ON u.id = f.user_id
                    WHERE u.id = ? AND u.role = 'faculty'
                ");
                $stmt->execute([$id]);
                $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($faculty) {
                    echo json_encode(['success' => true, 'data' => $faculty]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Faculty not found']);
                }
            } else {
                $query = "
                    SELECT u.id, u.first_name, u.last_name, u.email, 
                           f.title, f.department, f.specialization
                    FROM users u
                    JOIN faculty f ON u.id = f.user_id
                    WHERE u.role = 'faculty'
                ";
                
                $params = [];
                
                if ($department) {
                    $query .= " AND f.department = ?";
                    $params[] = $department;
                }
                
                $query .= " ORDER BY u.last_name, u.first_name";
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $faculty]);
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