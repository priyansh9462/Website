<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->connect();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_event'])) {
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$_POST['event_id']]);
        $message = "Event deleted successfully";
    } elseif (isset($_POST['add_event'])) {
        // Handle file upload
        $imagePath = null;
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/events/';
            $imageName = uniqid() . '_' . basename($_FILES['event_image']['name']);
            $targetPath = $uploadDir . $imageName;
            
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $targetPath)) {
                $imagePath = 'uploads/events/' . $imageName;
            }
        }
        
        $stmt = $db->prepare("
            INSERT INTO events (title, description, event_date, location, image_path, is_featured, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['event_date'],
            $_POST['location'],
            $imagePath,
            isset($_POST['is_featured']) ? 1 : 0,
            $_SESSION['admin_id']
        ]);
        
        $message = "Event added successfully";
    }
}

// Get all events
$stmt = $db->prepare("SELECT * FROM events ORDER BY event_date DESC");
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-blue-800 text-white p-4">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-xl font-bold">Manage Events</h1>
                <a href="dashboard.php" class="hover:underline">Back to Dashboard</a>
            </div>
        </nav>
        
        <div class="container mx-auto p-4">
            <?php if (isset($message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Add Event Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Add New Event</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-gray-700 mb-2">Event Title *</label>
                            <input type="text" id="title" name="title" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        </div>
                        
                        <div>
                            <label for="event_date" class="block text-gray-700 mb-2">Event Date *</label>
                            <input type="datetime-local" id="event_date" name="event_date" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        </div>
                        
                        <div>
                            <label for="location" class="block text-gray-700 mb-2">Location</label>
                            <input type="text" id="location" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        
                        <div>
                            <label for="event_image" class="block text-gray-700 mb-2">Event Image</label>
                            <input type="file" id="event_image" name="event_image" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="description" class="block text-gray-700 mb-2">Description</label>
                            <textarea id="description" name="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                        
                        <div>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="is_featured" class="rounded text-blue-600">
                                <span class="text-gray-700">Featured Event</span>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_event" class="mt-4 bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition">
                        Add Event
                    </button>
                </form>
            </div>
            
            <!-- Events List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <h2 class="text-xl font-bold p-4 border-b">Upcoming Events</h2>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($events as $event): ?>
                        <div class="p-4 flex flex-col md:flex-row md:items-center">
                            <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-4">
                                <?php if ($event['image_path']): ?>
                                    <img src="../<?php echo htmlspecialchars($event['image_path']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="w-32 h-32 object-cover rounded-md">
                                <?php else: ?>
                                    <div class="w-32 h-32 bg-gray-200 rounded-md flex items-center justify-center text-gray-400">
                                        No Image
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-grow">
                                <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p class="text-gray-600 mb-2"><?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?></p>
                                <p class="text-gray-700"><?php echo htmlspecialchars($event['description']); ?></p>
                            </div>
                            
                            <div class="mt-4 md:mt-0 md:ml-4">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" name="delete_event" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this event?');">
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