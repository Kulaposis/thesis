<?php
// Simplified admin users API for testing
header('Content-Type: application/json');

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For testing purposes, simulate admin login if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    // Simulate admin session for testing
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Test Admin';
    $_SESSION['email'] = 'admin@test.com';
}

try {
    require_once '../config/database.php';
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all users directly from database
        $database = new Database();
        $conn = $database->getConnection();
        
        if (!$conn) {
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        
        // Simple query to get users
        $stmt = $conn->prepare("
            SELECT 
                id,
                full_name,
                email,
                role,
                student_id,
                faculty_id,
                program,
                department,
                COALESCE(created_at, NOW()) as created_at,
                COALESCE(is_active, 1) as is_active
            FROM users 
            ORDER BY created_at DESC
        ");
        
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format users
        foreach ($users as &$user) {
            $user['is_active'] = (bool)$user['is_active'];
            $user['display_name'] = $user['full_name'] ?: $user['email'];
            $user['status'] = 'offline'; // Default status
        }
        
        echo json_encode([
            'success' => true, 
            'users' => $users,
            'message' => 'Users loaded successfully',
            'count' => count($users)
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 