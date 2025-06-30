<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Require adviser login
$auth = new Auth();
$auth->requireRole('adviser');

// Get current user
$user = $auth->getCurrentUser();

// Check if all required fields are present
if (!isset($_POST['student_id']) || !isset($_POST['edit_student_name']) || !isset($_POST['edit_program']) || !isset($_POST['edit_thesis_title'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Get form data
$studentId = $_POST['student_id'];
$studentName = $_POST['edit_student_name'];
$program = $_POST['edit_program'];
$thesisTitle = $_POST['edit_thesis_title'];

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update student information in users table
    $sql = "UPDATE users SET full_name = :name, program = :program WHERE id = :student_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $studentName,
        ':program' => $program,
        ':student_id' => $studentId
    ]);
    
    // Update thesis title
    $sql = "UPDATE theses SET title = :title WHERE student_id = :student_id AND adviser_id = :adviser_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $thesisTitle,
        ':student_id' => $studentId,
        ':adviser_id' => $user['id']
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Student information updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode(['success' => false, 'message' => 'Error updating student: ' . $e->getMessage()]);
} 