<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>User Check</h2>";

try {
    $stmt = $conn->query("SELECT id, email, full_name, role FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($users) {
        echo "<h3>All Users:</h3>";
        foreach ($users as $user) {
            $adminBadge = in_array($user['role'], ['admin', 'super_admin']) ? ' <span style="color: red; font-weight: bold;">[ADMIN]</span>' : '';
            echo "- ID: {$user['id']}, Name: {$user['full_name']}, Email: {$user['email']}, Role: {$user['role']}{$adminBadge}<br>";
        }
    } else {
        echo "No users found<br>";
    }
    
    // Check for admin users specifically
    $stmt = $conn->prepare("SELECT id, email, full_name, role FROM users WHERE role IN ('admin', 'super_admin')");
    $stmt->execute();
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($adminUsers) {
        echo "<h3>Admin Users:</h3>";
        foreach ($adminUsers as $user) {
            echo "- ID: {$user['id']}, Name: {$user['full_name']}, Email: {$user['email']}, Role: {$user['role']}<br>";
        }
    } else {
        echo "<h3>No Admin Users Found</h3>";
        echo "Creating an admin user...<br>";
        
        // Create an admin user
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role, department) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin@thesis.edu', $hashedPassword, 'System Administrator', 'super_admin', 'Information Technology']);
        
        echo "✅ Admin user created: admin@thesis.edu / admin123<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?> 