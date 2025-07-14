<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Initialize auth and get current user
$auth = new Auth();
$user = $auth->getCurrentUser();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$db = new Database();
$pdo = $db->getConnection();

try {
    // Get user settings from database
    $sql = "SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user['id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array
    $settings = [];
    foreach ($results as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Define default values
    $defaults = [
        'email_notifications' => '1',
        'submission_alerts' => '1',
        'weekly_reports' => '1',
        'show_profile' => '1',
        'activity_status' => '1',
        'theme' => 'light',
        'language' => 'en',
        'items_per_page' => '25',
        'timezone' => 'Asia/Manila',
        'default_review_time' => '7',
        'auto_reminders' => '3days'
    ];
    
    // Merge with defaults to ensure all settings have values
    $allSettings = array_merge($defaults, $settings);
    
    echo json_encode([
        'success' => true,
        'settings' => $allSettings
    ]);
    
} catch (Exception $e) {
    error_log("Get settings error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to load settings']);
}
?> 