<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->connect();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_image'])) {
        // First get image path to delete file
        $stmt = $db->prepare("SELECT image_path FROM gallery WHERE id = ?");
        $stmt->execute([$_POST['image_id']]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image && file_exists('../' . $image['image_path'])) {
            unlink('../' . $image['image_path']);
        }
        
        // Then delete from database
        $stmt = $db->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$_POST['image_id']]);
        $message = "Image deleted successfully";
    } elseif (isset($_POST['add_image'])) {
        // Handle file upload
        if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/gallery/';
            $imageName = uniqid() . '_' . basename($_FILES['gallery_image']['name']);
            $targetPath = $uploadDir . $imageName;
            
            if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $targetPath)) {
                $stmt = $db->prepare("
                    INSERT INTO gallery (title, description, image_path, category, uploaded_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $_POST['title'],
                    $_POST['description'],
                    'uploads/gallery/' . $imageName,
                    $_POST['category'],
                    $_SESSION['admin_id']
                ]);
                
                $message = "Image added to gallery";
            } else {
                $error = "Failed to upload image";
            }
        } else {
            $error = "Please select an image to upload";
        }
    }
}

// Get all gallery items
$stmt = $db->prepare("SELECT * FROM gallery ORDER BY upload_date DESC");
$stmt->execute();
$galleryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique categories
$stmt = $db->prepare("SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-blue-800 text-white p-4">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-xl font-bold">Manage Gallery</h1>
                <a href="dashboard.php" class="hover:underline">Back to Dashboard</a>
            </div>
        </nav>
        
        <div class="container mx-auto p-4">
            <?php if (isset($message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Add Image Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Add New Image</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-gray-700 mb-2">Title *</label>
                            <input type="text" id="title" name="title" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        </div>
                        
                        <div>
                            <label for="category" class="block text-gray-700 mb-2">Category</label>
                            <input type="text" id="category" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md" list="categories">
                            <datalist id="categories">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="description" class="block text-gray-700 mb-2">Description</label>
                            <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="gallery_image" class="block text-gray-700 mb-2">Image *</label>
                            <input type="file" id="gallery_image" name="gallery_image" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept="image/*" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_image" class="mt-4 bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition">
                        Upload Image
                    </button>
                </form>
            </div>
            
            <!-- Gallery Grid -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="text-xl font-bold mb-4">Gallery Items</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($galleryItems as $item): ?>
                        <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition">
                            <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="w-full h-48 object-cover">
                            
                            <div class="p-3">
                                <h3 class="font-semibold"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <?php if ($item['category']): ?>
                                    <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded mt-1">
                                        <?php echo htmlspecialchars($item['category']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <form method="POST" class="mt-2">
                                    <input type="hidden" name="image_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="delete_image" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Are you sure you want to delete this image?');">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>