<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/auth.php';
require_once '../../config/validation.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $auth = new Auth();
    $userId = $auth->register($data);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'userId' => $userId
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>