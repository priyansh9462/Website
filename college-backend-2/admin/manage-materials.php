<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->connect();

// Get all courses for dropdown
$stmt = $db->prepare("SELECT id, title FROM courses ORDER BY title");
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_material'])) {
        try {
            // First get file path to delete file
            $stmt = $db->prepare("SELECT file_path FROM course_materials WHERE id = ?");
            $stmt->execute([$_POST['material_id']]);
            $material = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($material && file_exists('../' . $material['file_path'])) {
                unlink('../' . $material['file_path']);
            }
            
            // Then delete from database
            $stmt = $db->prepare("DELETE FROM course_materials WHERE id = ?");
            $stmt->execute([$_POST['material_id']]);
            
            $_SESSION['success_message'] = "Material deleted successfully";
            header('Location: manage-materials.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Failed to delete material: " . $e->getMessage();
        }
    } elseif (isset($_POST['add_material'])) {
        try {
            // Validate input
            if (empty($_POST['course_id']) || empty($_POST['title'])) {
                throw new Exception("Course and title are required");
            }
            
            if (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Please select a valid file to upload");
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
            
            Validator::validateFile($_FILES['material_file'], $allowedTypes, 10 * 1024 * 1024); // 10MB max
            
            // Handle file upload
            $uploadDir = '../../uploads/course-materials/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['material_file']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (!move_uploaded_file($_FILES['material_file']['tmp_name'], $targetPath)) {
                throw new Exception("Failed to upload file");
            }
            
            $stmt = $db->prepare("
                INSERT INTO course_materials (
                    course_id, title, description, file_path, 
                    file_type, file_size, uploaded_by
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $fileSize = $_FILES['material_file']['size'];
            $fileType = $_FILES['material_file']['type'];
            
            $stmt->execute([
                $_POST['course_id'],
                Validator::sanitizeInput($_POST['title']),
                isset($_POST['description']) ? Validator::sanitizeInput($_POST['description']) : null,
                'uploads/course-materials/' . $fileName,
                $fileType,
                $fileSize,
                $_SESSION['admin_id']
            ]);
            
            $_SESSION['success_message'] = "Course material added successfully";
            header('Location: manage-materials.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
}

// Get all materials
$stmt = $db->prepare("
    SELECT cm.*, c.title as course_title
    FROM course_materials cm
    JOIN courses c ON cm.course_id = c.id
    ORDER BY cm.upload_date DESC
");
$stmt->execute();
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course Materials</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-blue-800 text-white p-4">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-xl font-bold">Manage Course Materials</h1>
                <a href="dashboard.php" class="hover:underline">Back to Dashboard</a>
            </div>
        </nav>
        
        <div class="container mx-auto p-4">
            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Add Material Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Add New Course Material</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="course_id" class="block text-gray-700 mb-2">Course *</label>
                            <select id="course_id" name="course_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                                <option value="">Select a course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="title" class="block text-gray-700 mb-2">Title *</label>
                            <input type="text" id="title" name="title" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="description" class="block text-gray-700 mb-2">Description</label>
                            <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="material_file" class="block text-gray-700 mb-2">File * (Max 10MB)</label>
                            <input type="file" id="material_file" name="material_file" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <p class="text-sm text-gray-500 mt-1">Allowed types: PDF, Word, Excel, PowerPoint, Text, ZIP</p>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_material" class="mt-4 bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition">
                        Upload Material
                    </button>
                </form>
            </div>
            
            <!-- Materials List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <h2 class="text-xl font-bold p-4 border-b">Course Materials</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($material['course_title']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($material['title']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="../<?php echo htmlspecialchars($material['file_path']); ?>" class="text-blue-600 hover:text-blue-800" download>
                                            Download
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M j, Y', strtotime($material['upload_date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo round($material['file_size'] / 1024); ?> KB</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="material_id" value="<?php echo $material['id']; ?>">
                                            <button type="submit" name="delete_material" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this material?');">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>