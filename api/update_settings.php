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

// Handle settings update
$action = $_POST['action'] ?? '';

if ($action !== 'update_settings') {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

$db = new Database();
$pdo = $db->getConnection();

try {
    // Create user_settings table if it doesn't exist
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS user_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_setting (user_id, setting_key),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableSql);
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Define settings to update
    $settings = [
        'email_notifications' => $_POST['email_notifications'] ?? '1',
        'submission_alerts' => $_POST['submission_alerts'] ?? '1',
        'weekly_reports' => $_POST['weekly_reports'] ?? '1',
        'show_profile' => $_POST['show_profile'] ?? '1',
        'activity_status' => $_POST['activity_status'] ?? '1',
        'theme' => $_POST['theme'] ?? 'light',
        'language' => $_POST['language'] ?? 'en',
        'items_per_page' => $_POST['items_per_page'] ?? '25',
        'timezone' => $_POST['timezone'] ?? 'Asia/Manila',
        'default_review_time' => $_POST['default_review_time'] ?? '7',
        'auto_reminders' => $_POST['auto_reminders'] ?? '3days'
    ];
    
    // Validate settings
    $validThemes = ['light', 'dark', 'auto'];
    $validLanguages = ['en', 'fil'];
    $validItemsPerPage = ['10', '25', '50', '100'];
    $validTimezones = ['Asia/Manila', 'UTC'];
    $validAutoReminders = ['none', '1day', '3days', '1week'];
    
    if (!in_array($settings['theme'], $validThemes)) {
        $settings['theme'] = 'light';
    }
    
    if (!in_array($settings['language'], $validLanguages)) {
        $settings['language'] = 'en';
    }
    
    if (!in_array($settings['items_per_page'], $validItemsPerPage)) {
        $settings['items_per_page'] = '25';
    }
    
    if (!in_array($settings['timezone'], $validTimezones)) {
        $settings['timezone'] = 'Asia/Manila';
    }
    
    if (!in_array($settings['auto_reminders'], $validAutoReminders)) {
        $settings['auto_reminders'] = '3days';
    }
    
    // Validate numeric settings
    $defaultReviewTime = intval($settings['default_review_time']);
    if ($defaultReviewTime < 1 || $defaultReviewTime > 30) {
        $defaultReviewTime = 7;
    }
    $settings['default_review_time'] = strval($defaultReviewTime);
    
    // Validate notification settings (all boolean 0 or 1)
    $booleanSettings = ['email_notifications', 'submission_alerts', 'weekly_reports', 'show_profile', 'activity_status'];
    foreach ($booleanSettings as $boolSetting) {
        $settings[$boolSetting] = in_array($settings[$boolSetting], ['0', '1']) ? $settings[$boolSetting] : '1';
    }
    
    // Prepare the SQL for INSERT ... ON DUPLICATE KEY UPDATE
    $sql = "INSERT INTO user_settings (user_id, setting_key, setting_value, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()";
    
    $stmt = $pdo->prepare($sql);
    
    // Update each setting
    foreach ($settings as $key => $value) {
        $result = $stmt->execute([$user['id'], $key, $value]);
        if (!$result) {
            throw new Exception("Failed to update setting: " . $key);
        }
    }
    
    // Store commonly used settings in session for quick access
    $_SESSION['user_theme'] = $settings['theme'];
    $_SESSION['user_language'] = $settings['language'];
    $_SESSION['user_timezone'] = $settings['timezone'];
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Settings saved successfully',
        'settings' => $settings
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollback();
    
    error_log("Settings update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to save settings: ' . $e->getMessage()]);
}
?> 