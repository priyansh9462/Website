<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/auth.php';
require_once '../../config/jwt.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['email']) || empty($data['password'])) {
        throw new Exception('Email and password are required');
    }
    
    $auth = new Auth();
    $user = $auth->login($data['email'], $data['password']);
    
    // Generate JWT token
    $token = JWT_Auth::generateToken($user['id'], $user['role']);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => $user
    ]);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>