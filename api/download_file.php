<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';

// Require login
$auth = new Auth();
$auth->requireLogin();

// Get current user
$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();

// Check if file ID is provided
if (!isset($_GET['file_id']) || empty($_GET['file_id'])) {
    die('File ID is required');
}

$file_id = intval($_GET['file_id']);

// Get file information
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT f.*, c.thesis_id, t.student_id 
            FROM file_uploads f
            JOIN chapters c ON f.chapter_id = c.id
            JOIN theses t ON c.thesis_id = t.id
            WHERE f.id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id);
    $stmt->execute();
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        die('File not found');
    }
    
    // Check permissions
    $is_owner = ($user['role'] == 'student' && $file['student_id'] == $user['id']);
    
    // For advisers, check if they are assigned to this thesis
    $is_adviser = false;
    if ($user['role'] == 'adviser') {
        $sql = "SELECT id FROM theses WHERE id = :thesis_id AND adviser_id = :adviser_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':thesis_id', $file['thesis_id']);
        $stmt->bindParam(':adviser_id', $user['id']);
        $stmt->execute();
        $is_adviser = ($stmt->rowCount() > 0);
    }
    
    $is_admin = ($user['role'] == 'admin');
    
    if (!$is_owner && !$is_adviser && !$is_admin) {
        die('You do not have permission to download this file');
    }
    
    // Check if file exists
    if (!file_exists($file['file_path'])) {
        die('File not found on server');
    }
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $file['file_type']);
    header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file['file_path']));
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read file and output to browser
    readfile($file['file_path']);
    exit;
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
} 