<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$response = [
    "success" => false,
    "data" => []
];

try {
    $isFeatured = isset($_GET['featured']) ? (bool)$_GET['featured'] : false;
    
    $query = "SELECT * FROM testimonials " . ($isFeatured ? "WHERE is_featured = TRUE " : "") . "ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response["data"][] = [
            "id" => $row['id'],
            "name" => $row['name'],
            "role" => $row['role'],
            "course" => $row['course'],
            "image" => APP_URL . "/uploads/testimonials/" . $row['image_path'],
            "content" => $row['content'],
            "rating" => $row['rating'],
            "achievement" => $row['achievement'],
            "is_featured" => (bool)$row['is_featured'],
            "created_at" => $row['created_at']
        ];
    }

    $response["success"] = true;
    http_response_code(200);
} catch (PDOException $e) {
    $response["message"] = "Database error: " . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
?>