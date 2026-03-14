<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/auth.php';
require_once '../../config/jwt.php';

try {
    $authUser = JWT_Auth::getAuthUser();
    if (!$authUser) {
        throw new Exception('Unauthorized');
    }
    
    $auth = new Auth();
    $user = $auth->getUserById($authUser['sub']);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
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