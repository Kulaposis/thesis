<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Not logged in']);
    exit;
}

// Get current user
$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_chapter':
        if (!isset($_GET['chapter_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chapter ID required']);
            exit;
        }
        
        // Get the chapter
        $chapter = $thesisManager->getChapterForReview($_GET['chapter_id']);
        if (!$chapter) {
            http_response_code(404);
            echo json_encode(['error' => 'Chapter not found']);
            exit;
        }
        
        // Check if the user has access to this chapter
        $hasAccess = false;
        
        if ($user['role'] === 'student') {
            // For students, check if the chapter belongs to their thesis
            $student_thesis = $thesisManager->getStudentThesis($user['id']);
            if ($student_thesis && $chapter['thesis_id'] == $student_thesis['id']) {
                $hasAccess = true;
            }
        } else if ($user['role'] === 'adviser') {
            // For advisers, check if the chapter belongs to a thesis they advise
            $thesis = $thesisManager->getThesisById($chapter['thesis_id']);
            if ($thesis && $thesis['adviser_id'] == $user['id']) {
                $hasAccess = true;
            }
        }
        
        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied - You do not have permission to view this chapter']);
            exit;
        }
        
        echo json_encode(['success' => true, 'chapter' => $chapter]);
        break;

    case 'get_files':
        if (!isset($_GET['chapter_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chapter ID required']);
            exit;
        }
        
        $chapter_id = $_GET['chapter_id'];
        $hasAccess = false;
        
        if ($user['role'] === 'student') {
            // For students, check if the chapter belongs to their thesis
            $student_thesis = $thesisManager->getStudentThesis($user['id']);
            if ($student_thesis) {
                $chapters = $thesisManager->getThesisChapters($student_thesis['id']);
                foreach ($chapters as $chapter) {
                    if ($chapter['id'] == $chapter_id) {
                        $hasAccess = true;
                        break;
                    }
                }
            }
        } else if ($user['role'] === 'adviser') {
            // For advisers, check if the chapter belongs to a thesis they advise
            $chapter = $thesisManager->getChapterById($chapter_id);
            if ($chapter) {
                $thesis = $thesisManager->getThesisById($chapter['thesis_id']);
                if ($thesis && $thesis['adviser_id'] == $user['id']) {
                    $hasAccess = true;
                }
            }
        }
        
        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        $files = $thesisManager->getChapterFiles($chapter_id);
        echo json_encode(['success' => true, 'files' => $files]);
        break;

    case 'get_highlights':
        if (!isset($_GET['chapter_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chapter ID required']);
            exit;
        }
        
        $chapter_id = $_GET['chapter_id'];
        $hasAccess = false;
        
        if ($user['role'] === 'student') {
            // For students, check if the chapter belongs to their thesis
            $student_thesis = $thesisManager->getStudentThesis($user['id']);
            if ($student_thesis) {
                $chapters = $thesisManager->getThesisChapters($student_thesis['id']);
                foreach ($chapters as $chapter) {
                    if ($chapter['id'] == $chapter_id) {
                        $hasAccess = true;
                        break;
                    }
                }
            }
        } else if ($user['role'] === 'adviser') {
            // For advisers, check if the chapter belongs to a thesis they advise
            $chapter = $thesisManager->getChapterById($chapter_id);
            if ($chapter) {
                $thesis = $thesisManager->getThesisById($chapter['thesis_id']);
                if ($thesis && $thesis['adviser_id'] == $user['id']) {
                    $hasAccess = true;
                }
            }
        }
        
        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        $highlights = $thesisManager->getChapterHighlights($chapter_id);
        echo json_encode(['success' => true, 'highlights' => $highlights]);
        break;

    case 'get_comments':
        if (!isset($_GET['chapter_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chapter ID required']);
            exit;
        }
        
        $chapter_id = $_GET['chapter_id'];
        $hasAccess = false;
        
        if ($user['role'] === 'student') {
            // For students, check if the chapter belongs to their thesis
            $student_thesis = $thesisManager->getStudentThesis($user['id']);
            if ($student_thesis) {
                $chapters = $thesisManager->getThesisChapters($student_thesis['id']);
                foreach ($chapters as $chapter) {
                    if ($chapter['id'] == $chapter_id) {
                        $hasAccess = true;
                        break;
                    }
                }
            }
        } else if ($user['role'] === 'adviser') {
            // For advisers, check if the chapter belongs to a thesis they advise
            $chapter = $thesisManager->getChapterById($chapter_id);
            if ($chapter) {
                $thesis = $thesisManager->getThesisById($chapter['thesis_id']);
                if ($thesis && $thesis['adviser_id'] == $user['id']) {
                    $hasAccess = true;
                }
            }
        }
        
        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        $comments = $thesisManager->getChapterComments($chapter_id);
        echo json_encode(['success' => true, 'comments' => $comments]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?> 