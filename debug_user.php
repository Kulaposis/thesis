<?php
session_start();
require_once 'config/database.php';

echo "<h1>User Debug Information</h1>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>No user logged in</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    exit();
}

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Session Information</h2>";
echo "<p><strong>User ID:</strong> " . $_SESSION['user_id'] . "</p>";
echo "<p><strong>Email:</strong> " . ($_SESSION['email'] ?? 'Not set') . "</p>";
echo "<p><strong>Full Name:</strong> " . ($_SESSION['full_name'] ?? 'Not set') . "</p>";
echo "<p><strong>Role:</strong> " . ($_SESSION['role'] ?? 'Not set') . "</p>";

echo "<h2>Database User Information</h2>";

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($user as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>User not found in database!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Admin Check</h2>";

require_once 'includes/admin_functions.php';
$adminManager = new AdminManager();

$isAdmin = $adminManager->isAdmin();
$isSuperAdmin = $adminManager->isSuperAdmin();

echo "<p><strong>Is Admin:</strong> " . ($isAdmin ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Is Super Admin:</strong> " . ($isSuperAdmin ? 'Yes' : 'No') . "</p>";

echo "<h2>Actions</h2>";
echo "<p><a href='setup_admin.php'>Setup Admin System</a></p>";
echo "<p><a href='admin_dashboard.php'>Try Admin Dashboard</a></p>";
echo "<p><a href='systemFunda.php'>Go to Adviser Dashboard</a></p>";
echo "<p><a href='studentDashboard.php'>Go to Student Dashboard</a></p>";
echo "<p><a href='logout.php'>Logout</a></p>";
?> 