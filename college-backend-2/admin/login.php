<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("
            SELECT id, username, password_hash 
            FROM admin_users 
            WHERE username = ? AND is_active = TRUE
        ");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Invalid credentials');
        }
        
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($password, $admin['password_hash'])) {
            throw new Exception('Invalid credentials');
        }
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        
        header('Location: dashboard.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <h1 class="text-2xl font-bold mb-6 text-center">Admin Login</h1>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 mb-2">Username</label>
                    <input type="text" id="username" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                    Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>