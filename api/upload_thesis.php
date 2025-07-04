<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';

// Require student login
$auth = new Auth();
$auth->requireRole('student');

// Get current user
$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();

// Check if thesis exists for this student
$thesis = $thesisManager->getStudentThesis($user['id']);

if (!$thesis) {
    echo json_encode(['success' => false, 'message' => 'No thesis found for this student']);
    exit;
}

$response = ['success' => false, 'message' => 'No file uploaded'];

// Check if file was uploaded
if (isset($_FILES['thesis_document']) && $_FILES['thesis_document']['error'] == 0) {
    $file = $_FILES['thesis_document'];
    $chapter_id = isset($_POST['chapter_id']) ? intval($_POST['chapter_id']) : 0;
    
    // Validate chapter belongs to student's thesis
    if ($chapter_id > 0) {
        $chapters = $thesisManager->getThesisChapters($thesis['id']);
        $valid_chapter = false;
        foreach ($chapters as $chapter) {
            if ($chapter['id'] == $chapter_id) {
                $valid_chapter = true;
                break;
            }
        }
        
        if (!$valid_chapter) {
            echo json_encode(['success' => false, 'message' => 'Invalid chapter']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Chapter ID is required']);
        exit;
    }
    
    // Check file type
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only PDF and Word documents are allowed']);
        exit;
    }
    
    // Check file size (max 10MB)
    $max_size = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds the limit of 10MB']);
        exit;
    }
    
    // Upload the file
    $result = $thesisManager->uploadFile($chapter_id, $file);
    
    if ($result['success']) {
        // Update the chapter's file path
        $thesisManager->updateChapterFilePath($chapter_id, $result['file_id']);
        
        // Get complete file information for response
        $fileInfo = $thesisManager->getFileById($result['file_id']);
        
        $response = [
            'success' => true, 
            'message' => 'File uploaded successfully',
            'file_info' => $fileInfo
        ];
    } else {
        $response = ['success' => false, 'message' => 'Failed to upload file'];
    }
}

echo json_encode($response); 