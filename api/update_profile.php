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

// Handle different actions
$action = $_POST['action'] ?? '';

$db = new Database();
$pdo = $db->getConnection();

try {
    switch ($action) {
        case 'update_profile':
            // Validate required fields
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (empty($full_name) || empty($email)) {
                echo json_encode(['success' => false, 'error' => 'Full name and email are required']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Invalid email format']);
                exit;
            }
            
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Email address is already taken']);
                exit;
            }
            
            // Prepare update data
            $faculty_id = trim($_POST['faculty_id'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $specialization = trim($_POST['specialization'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            
            // Check if specialization and bio columns exist
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'specialization'");
            $hasSpecialization = $stmt->rowCount() > 0;
            
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'bio'");
            $hasBio = $stmt->rowCount() > 0;
            
            // Build SQL query based on available columns
            if ($hasSpecialization && $hasBio) {
                $sql = "UPDATE users SET 
                            full_name = ?, 
                            email = ?, 
                            faculty_id = ?, 
                            department = ?, 
                            specialization = ?, 
                            bio = ?
                        WHERE id = ?";
                $params = [$full_name, $email, $faculty_id, $department, $specialization, $bio, $user['id']];
            } else {
                $sql = "UPDATE users SET 
                            full_name = ?, 
                            email = ?, 
                            faculty_id = ?, 
                            department = ?
                        WHERE id = ?";
                $params = [$full_name, $email, $faculty_id, $department, $user['id']];
            }
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Update session data
                $_SESSION['user_full_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
            }
            break;
            
        case 'change_password':
            // Validate required fields
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                echo json_encode(['success' => false, 'error' => 'All password fields are required']);
                exit;
            }
            
            if ($new_password !== $confirm_password) {
                echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
                exit;
            }
            
            if (strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters']);
                exit;
            }
            
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
                exit;
            }
            
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$hashed_password, $user['id']]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update password']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
}
?> 