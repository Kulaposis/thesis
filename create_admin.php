<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h1>Create Admin User</h1>";

// Admin user details
$email = 'admins@edu.ph';
$password = '12345678';
$fullName = 'System Administrator';
$role = 'super_admin';
$facultyId = 'ADMIN002';
$department = 'Information Technology';

try {
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id, email, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "<p style='color: orange;'>⚠️ User already exists:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $existingUser['id'] . "</li>";
        echo "<li><strong>Email:</strong> " . $existingUser['email'] . "</li>";
        echo "<li><strong>Current Role:</strong> " . $existingUser['role'] . "</li>";
        echo "</ul>";
        
        // Update the existing user to admin role
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, role = ?, faculty_id = ?, department = ?, updated_at = NOW() WHERE email = ?");
        $stmt->execute([$hashedPassword, $role, $facultyId, $department, $email]);
        
        echo "<p style='color: green;'>✅ User updated to admin role successfully!</p>";
    } else {
        // Create new admin user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role, faculty_id, department, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$email, $hashedPassword, $fullName, $role, $facultyId, $department]);
        
        $userId = $conn->lastInsertId();
        echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
        echo "<p><strong>User ID:</strong> " . $userId . "</p>";
    }
    
    // Verify the user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>User Details:</h2>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach ($user as $key => $value) {
        if ($key === 'password') {
            echo "<tr><td><strong>$key</strong></td><td>[HIDDEN]</td></tr>";
        } else {
            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
    }
    echo "</table>";
    
    echo "<h2>Login Credentials:</h2>";
    echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>Role:</strong> $role</p>";
    echo "</div>";
    
    echo "<h2>Next Steps:</h2>";
    echo "<ul>";
    echo "<li><a href='login.php'>Go to Login Page</a></li>";
    echo "<li><a href='admin_dashboard.php'>Go to Admin Dashboard</a></li>";
    echo "<li><a href='debug_user.php'>Debug User (if logged in)</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    
    // Check if admin tables exist
    $stmt = $conn->query("SHOW TABLES LIKE 'admin_logs'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: orange;'>⚠️ Admin tables might not exist. Please run the admin setup first:</p>";
        echo "<p><a href='setup_admin.php'>Setup Admin System</a></p>";
    }
}

// Show all admin users
echo "<h2>All Admin Users:</h2>";
try {
    $stmt = $conn->query("SELECT id, email, full_name, role, created_at FROM users WHERE role IN ('admin', 'super_admin') ORDER BY created_at DESC");
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($adminUsers) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Created</th></tr>";
        foreach ($adminUsers as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No admin users found.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching admin users: " . $e->getMessage() . "</p>";
}
?> 