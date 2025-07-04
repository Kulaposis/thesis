<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';

// Require login
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();

// Get file ID from request
$file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;

if ($file_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid file ID required']);
    exit;
}

// Get file information
$file = $thesisManager->getFileById($file_id);

if (!$file) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

// Security check - verify user has access to this file
$chapter = $thesisManager->getChapterById($file['chapter_id']);
if (!$chapter) {
    http_response_code(404);
    echo json_encode(['error' => 'Chapter not found']);
    exit;
}

$thesis = $thesisManager->getThesisById($chapter['thesis_id']);
if (!$thesis) {
    http_response_code(404);
    echo json_encode(['error' => 'Thesis not found']);
    exit;
}

// Check if user has permission to access this file
$hasAccess = false;

if ($user['role'] === 'student' && $thesis['student_id'] == $user['id']) {
    // Student can access their own files
    $hasAccess = true;
} elseif ($user['role'] === 'adviser' && $thesis['adviser_id'] == $user['id']) {
    // Adviser can access files of their assigned students
    $hasAccess = true;
} elseif ($user['role'] === 'admin') {
    // Admin can access all files
    $hasAccess = true;
}

if (!$hasAccess) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Check if file exists on disk
$file_path = '../' . $file['file_path'];
if (!file_exists($file_path)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found on disk']);
    exit;
}

// Set headers for file download
$file_size = filesize($file_path);
$file_name = $file['original_filename'];

// Determine content type
$content_type = 'application/octet-stream';
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

switch ($file_ext) {
    case 'pdf':
        $content_type = 'application/pdf';
        break;
    case 'doc':
        $content_type = 'application/msword';
        break;
    case 'docx':
        $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    case 'txt':
        $content_type = 'text/plain';
        break;
    case 'jpg':
    case 'jpeg':
        $content_type = 'image/jpeg';
        break;
    case 'png':
        $content_type = 'image/png';
        break;
}

// Clear output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers
header('Content-Type: ' . $content_type);
header('Content-Length: ' . $file_size);
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Cache-Control: private');
header('Pragma: private');
header('Expires: 0');

// Output file
readfile($file_path);
exit; 