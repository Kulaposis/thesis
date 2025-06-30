<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_student_feedback':
        // For students to get their feedback
        if ($user['role'] !== 'student') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        $thesis = $thesisManager->getStudentThesis($user['id']);
        if (!$thesis) {
            echo json_encode(['success' => true, 'feedback' => []]);
            exit;
        }
        
        $feedback = $thesisManager->getStudentAllFeedback($user['id']);
        echo json_encode(['success' => true, 'feedback' => $feedback]);
        break;
        
    case 'get_adviser_feedback':
        // For advisers to get feedback they've given
        if ($user['role'] !== 'adviser') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        if (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            
            // Get all theses for this adviser to verify the student is assigned to them
            $adviserTheses = $thesisManager->getAdviserTheses($user['id']);
            $isStudentAssigned = false;
            
            foreach ($adviserTheses as $thesis) {
                if ($thesis['student_user_id'] == $student_id) {
                    $isStudentAssigned = true;
                    break;
                }
            }
            
            if (!$isStudentAssigned) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            // Use the student_id from the thesis object
            $feedback = $thesisManager->getStudentAllFeedback($thesis['student_id']);
        } else {
            // Get all feedback given by this adviser
            $feedback = $thesisManager->getAdviserAllFeedback($user['id']);
        }
        
        echo json_encode(['success' => true, 'feedback' => $feedback]);
        break;
        
    case 'add_feedback':
        // Only advisers can add feedback
        if ($user['role'] !== 'adviser') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        if (!isset($_POST['chapter_id']) || !isset($_POST['feedback_text'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chapter ID and feedback text required']);
            exit;
        }
        
        $chapter_id = $_POST['chapter_id'];
        $feedback_text = $_POST['feedback_text'];
        $feedback_type = $_POST['feedback_type'] ?? 'comment';
        
        // Debug logging
        error_log("Adding feedback for chapter ID: $chapter_id");
        
        // Verify the chapter belongs to a student assigned to this adviser
        $chapter = $thesisManager->getChapterById($chapter_id);
        if (!$chapter) {
            error_log("Chapter not found: $chapter_id");
            http_response_code(404);
            echo json_encode(['error' => 'Chapter not found']);
            exit;
        }
        
        error_log("Chapter found: " . json_encode($chapter));
        
        // Get all theses for this adviser
        $adviserTheses = $thesisManager->getAdviserTheses($user['id']);
        $isChapterValid = false;
        
        foreach ($adviserTheses as $thesis) {
            if ($thesis['id'] == $chapter['thesis_id']) {
                $isChapterValid = true;
                break;
            }
        }
        
        if (!$isChapterValid) {
            error_log("Access denied: Chapter " . $chapter_id . " does not belong to any thesis assigned to adviser " . $user['id']);
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        // We've already verified that the chapter belongs to a thesis assigned to this adviser
        
        $success = $thesisManager->addFeedback($chapter_id, $user['id'], $feedback_text, $feedback_type);
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Feedback added successfully']);
        } else {
            error_log("Failed to add feedback to chapter $chapter_id by adviser {$user['id']}");
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add feedback: Database error']);
        }
        break;
        
    case 'delete_feedback':
        // Only advisers can delete their own feedback
        if ($user['role'] !== 'adviser') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        if (!isset($_POST['feedback_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Feedback ID required']);
            exit;
        }
        
        $feedback_id = $_POST['feedback_id'];
        
        // Verify the feedback belongs to this adviser
        $feedback = $thesisManager->getFeedbackById($feedback_id);
        if (!$feedback || $feedback['adviser_id'] != $user['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        $success = $thesisManager->deleteFeedback($feedback_id);
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Feedback deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete feedback']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?> 