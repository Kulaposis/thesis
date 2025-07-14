<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

// Start session and check admin auth
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize AdminManager for auth check
$adminManager = new AdminManager();



if (!isLoggedIn() || !$adminManager->isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

try {
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'GET') {
        // Get all users using AdminManager
        $users = $adminManager->getAllUsers();
        echo json_encode(['success' => true, 'users' => $users]);
        
    } elseif ($method === 'POST') {
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $result = $adminManager->createUser($input);
                echo json_encode($result);
                break;
                
            case 'update':
                $result = $adminManager->updateUser($input['user_id'], $input);
                echo json_encode($result);
                break;
                
            case 'delete':
                $result = $adminManager->deleteUser($input['user_id']);
                echo json_encode($result);
                break;
                
            case 'reset_password':
                $result = $adminManager->resetUserPassword($input['user_id']);
                echo json_encode($result);
                break;
                
            case 'bulk_reset_passwords':
                $result = $adminManager->bulkUserOperation($input['user_ids'], 'reset_password');
                echo json_encode($result);
                break;
                
            case 'bulk_delete':
                $result = $adminManager->bulkUserOperation($input['user_ids'], 'delete');
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 