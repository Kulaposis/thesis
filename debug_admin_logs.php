<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';

// Force admin login for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['logged_in'] = true;
$_SESSION['email'] = 'admin@example.com';
$_SESSION['full_name'] = 'Test Admin';

$adminManager = new AdminManager();

echo "<h2>Debug: Admin Login Logs</h2>";

// Test if session is working
echo "<h3>Session Info:</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
echo "Logged in: " . ($_SESSION['logged_in'] ? 'Yes' : 'No') . "<br>";

// Test authentication
$auth = new Auth();
echo "<h3>Authentication Test:</h3>";
echo "Is logged in: " . ($auth->isLoggedIn() ? 'Yes' : 'No') . "<br>";

// Test admin manager
echo "<h3>Admin Manager Test:</h3>";
$currentUser = $adminManager->getCurrentUser();
echo "Current user: " . ($currentUser ? $currentUser['full_name'] : 'Not found') . "<br>";

// Test getLoginLogs directly
echo "<h3>Direct getLoginLogs Test:</h3>";
$logs = $adminManager->getLoginLogs(10, []);
if ($logs !== false) {
    echo "✅ getLoginLogs works: " . count($logs) . " logs found<br>";
    
    // Show role breakdown
    $roleStats = [];
    foreach ($logs as $log) {
        $role = $log['user_role'];
        if (!isset($roleStats[$role])) {
            $roleStats[$role] = 0;
        }
        $roleStats[$role]++;
    }
    
    echo "Role breakdown:<br>";
    foreach ($roleStats as $role => $count) {
        echo "- $role: $count logs<br>";
    }
    
    echo "<h4>Recent logs:</h4>";
    foreach (array_slice($logs, 0, 5) as $log) {
        echo "- {$log['full_name']} ({$log['user_role']}) {$log['action_type']} at {$log['created_at']}<br>";
    }
} else {
    echo "❌ getLoginLogs failed<br>";
}

// Test AJAX endpoint simulation
echo "<h3>AJAX Endpoint Test:</h3>";
$_POST['action'] = 'get_login_logs';
$_POST['limit'] = 100;

// Simulate the admin dashboard processing
$filters = [
    'user_role' => $_POST['user_role'] ?? '',
    'action_type' => $_POST['action_type'] ?? '',
    'date_from' => $_POST['date_from'] ?? '',
    'date_to' => $_POST['date_to'] ?? '',
    'user_search' => $_POST['user_search'] ?? ''
];
$limit = $_POST['limit'] ?? 100;
$logs = $adminManager->getLoginLogs($limit, $filters);
$response = ['success' => true, 'logs' => $logs];

echo "AJAX simulation result:<br>";
echo "Success: " . ($response['success'] ? 'Yes' : 'No') . "<br>";
echo "Log count: " . count($response['logs']) . "<br>";
echo "JSON size: " . strlen(json_encode($response)) . " bytes<br>";

// Output a sample of the JSON
echo "<h4>Sample JSON Response (first 2 logs):</h4>";
$sampleResponse = ['success' => true, 'logs' => array_slice($response['logs'], 0, 2)];
echo "<pre>" . json_encode($sampleResponse, JSON_PRETTY_PRINT) . "</pre>";

echo "<br><a href='admin_dashboard.php'>Go to Admin Dashboard</a>";
?> 