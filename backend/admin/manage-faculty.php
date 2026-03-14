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
    if (isset($_POST['add_faculty'])) {
        // First create user account
        $hashedPassword = password_hash('welcome123', PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("
            INSERT INTO users (first_name, last_name, email, password_hash, role)
            VALUES (?, ?, ?, ?, 'faculty')
        ");
        
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $hashedPassword
        ]);
        
        $userId = $db->lastInsertId();
        
        // Then create faculty profile
        $stmt = $db->prepare("
            INSERT INTO faculty (user_id, title, department, specialization, bio, research_interests, achievements)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $_POST['title'],
            $_POST['department'],
            $_POST['specialization'],
            $_POST['bio'],
            $_POST['research_interests'],
            $_POST['achievements']
        ]);
        
        $message = "Faculty member added successfully";
    } elseif (isset($_POST['delete_faculty'])) {
        // First delete faculty profile
        $stmt = $db->prepare("DELETE FROM faculty WHERE user_id = ?");
        $stmt->execute([$_POST['user_id']]);
        
        // Then delete user account
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        
        $message = "Faculty member deleted successfully";
    }
}

// Get all faculty
$stmt = $db->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.is_active,
           f.title, f.department
    FROM users u
    JOIN faculty f ON u.id = f.user_id
    ORDER BY u.last_name, u.first_name
");
$stmt->execute();
$faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique departments
$stmt = $db->prepare("SELECT DISTINCT department FROM faculty WHERE department IS NOT NULL");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-blue-800 text-white p-4">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-xl font-bold">Manage Faculty</h1>
                <a href="dashboard.php" class="hover:underline">Back to Dashboard</a>
            </div>
        </nav>
        
        <div class="container mx-auto p-4">
            <?php if (isset($message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Add Faculty Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Add New Faculty Member</h2>
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-gray-700 mb-2">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-gray-700 mb-2">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-gray-700 mb-2">Email *</label>
                            <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        </div>
                        
                        <div>
                            <label for="title" class="block text-gray-700 mb-2">Title</label>
                            <input type="text" id="title" name="title" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="e.g., Professor, Dr.">
                        </div>
                        
                        <div>
                            <label for="department" class="block text-gray-700 mb-2">Department</label>
                            <input type="text" id="department" name="department" class="w-full px-3 py-2 border border-gray-300 rounded-md" list="departments">
                            <datalist id="departments">
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div>
                            <label for="specialization" class="block text-gray-700 mb-2">Specialization</label>
                            <input type="text" id="specialization" name="specialization" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="bio" class="block text-gray-700 mb-2">Bio</label>
                            <textarea id="bio" name="bio" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="research_interests" class="block text-gray-700 mb-2">Research Interests</label>
                            <textarea id="research_interests" name="research_interests" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="achievements" class="block text-gray-700 mb-2">Achievements</label>
                            <textarea id="achievements" name="achievements" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_faculty" class="mt-4 bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition">
                        Add Faculty Member
                    </button>
                </form>
            </div>
            
            <!-- Faculty List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <h2 class="text-xl font-bold p-4 border-b">Faculty Members</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($faculty as $member): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($member['last_name'] . ', ' . $member['first_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($member['title'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($member['department'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $member['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                            <button type="submit" name="delete_faculty" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this faculty member?');">
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