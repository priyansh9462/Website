<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWT_Auth {
    private static $secretKey;
    private static $algorithm = 'HS256';
    
    public static function init() {
        self::$secretKey = getenv('JWT_SECRET') ?: 'your-secret-key-here';
    }
    
    public static function generateToken($userId, $role) {
        $issuedAt = time();
        $expirationTime = $issuedAt + (60 * 60); // 1 hour
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => $userId,
            'role' => $role
        ];
        
        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }
    
    public static function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            return (array) $decoded;
        } catch (Exception $e) {
            error_log('JWT validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function getAuthUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            return self::validateToken($token);
        }
        
        return false;
    }
}

JWT_Auth::init();
?>