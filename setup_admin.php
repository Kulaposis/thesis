<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();
$message = '';
$error = '';

// Check if admin tables exist
$stmt = $conn->query("SHOW TABLES LIKE 'admin_logs'");
$adminTablesExist = $stmt->rowCount() > 0;

// Check for admin users
$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'super_admin')");
$adminCount = $stmt->fetch()['count'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['setup_admin'])) {
        try {
            // Read and execute the admin setup SQL
            $sql = file_get_contents('admin_database_setup.sql');
            $conn->exec($sql);
            $message = "Admin setup completed successfully!";
            
            // Refresh the page to show updated status
            header("Location: setup_admin.php?success=1");
            exit();
            
        } catch (Exception $e) {
            $error = "Error setting up admin: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['create_admin_user'])) {
        try {
            $email = $_POST['email'];
            $password = $_POST['password'];
            $fullName = $_POST['full_name'];
            $role = $_POST['role'];
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email already exists!";
            } else {
                // Create admin user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role, faculty_id, department) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$email, $hashedPassword, $fullName, $role, 'ADMIN001', 'Information Technology']);
                
                $message = "Admin user created successfully! You can now login with: " . $email;
                
                // Auto-login the newly created admin user
                $_SESSION['user_id'] = $conn->lastInsertId();
                $_SESSION['email'] = $email;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['role'] = $role;
                $_SESSION['logged_in'] = true;
            }
        } catch (Exception $e) {
            $error = "Error creating admin user: " . $e->getMessage();
        }
    }
}

// Check status again after potential changes
$stmt = $conn->query("SHOW TABLES LIKE 'admin_logs'");
$adminTablesExist = $stmt->rowCount() > 0;

$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'super_admin')");
$adminCount = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - Thesis Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Admin Setup</h1>
                <p class="text-gray-600">Thesis Management System</p>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Status Section -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-3">System Status</h2>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span>Admin Tables:</span>
                        <span class="<?php echo $adminTablesExist ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $adminTablesExist ? '✓ Installed' : '✗ Missing'; ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span>Admin Users:</span>
                        <span class="<?php echo $adminCount > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $adminCount > 0 ? "✓ $adminCount found" : '✗ None found'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Setup Admin Tables -->
            <?php if (!$adminTablesExist): ?>
                <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                    <h3 class="font-semibold text-yellow-800 mb-2">Step 1: Setup Admin Tables</h3>
                    <p class="text-yellow-700 text-sm mb-3">Admin tables need to be created in the database.</p>
                    <form method="post">
                        <button type="submit" name="setup_admin" class="w-full bg-yellow-600 text-white py-2 px-4 rounded hover:bg-yellow-700">
                            Setup Admin Tables
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Create Admin User -->
            <?php if ($adminTablesExist && $adminCount == 0): ?>
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                    <h3 class="font-semibold text-blue-800 mb-2">Step 2: Create Admin User</h3>
                    <p class="text-blue-700 text-sm mb-3">Create your first admin user account.</p>
                    
                    <form method="post" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" value="admin@thesis.edu" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" value="admin123" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="full_name" value="System Administrator" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select name="role" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="create_admin_user" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                            Create Admin User
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if ($adminTablesExist && $adminCount > 0): ?>
                <div class="p-4 bg-green-50 border border-green-200 rounded">
                    <h3 class="font-semibold text-green-800 mb-2">✓ Setup Complete!</h3>
                    <p class="text-green-700 text-sm mb-3">Admin system is ready. You can now access the admin dashboard.</p>
                    <a href="admin_dashboard.php" class="block w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 text-center">
                        Go to Admin Dashboard
                    </a>
                </div>
            <?php endif; ?>

            <!-- Navigation -->
            <div class="mt-6 text-center">
                <a href="login.php" class="text-blue-600 hover:text-blue-800">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html> 