<?php
session_start();
header('Content-Type: application/json');

// Enable error logging to a specific file
ini_set('log_errors', 1);
ini_set('error_log', '../debug.log');
error_log("=== Feedback Management API Called ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("Raw POST data: " . file_get_contents('php://input'));
error_log("POST array: " . json_encode($_POST));
error_log("GET array: " . json_encode($_GET));

// Debug session information
error_log("Session ID: " . session_id());
error_log("Session save path: " . session_save_path());
error_log("Session name: " . session_name());
error_log("Session status: " . session_status());
error_log("Session data: " . json_encode($_SESSION));
error_log("Session cookie params: " . json_encode(session_get_cookie_params()));
error_log("Cookies received: " . json_encode($_COOKIE));

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';

// Handle JSON requests
if (empty($_POST) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    error_log("Raw JSON input: " . $raw);
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $_POST = array_merge($_POST, $json);
        error_log("Parsed JSON data into POST: " . json_encode($_POST));
    }
}

// Handle application/x-www-form-urlencoded
if (empty($_POST) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/x-www-form-urlencoded') !== false) {
    $raw = file_get_contents('php://input');
    error_log("Raw form input: " . $raw);
    parse_str($raw, $data);
    if (is_array($data)) {
        $_POST = array_merge($_POST, $data);
        error_log("Parsed form data into POST: " . json_encode($_POST));
    }
}

// Additional fallback: try to parse raw input even if $_POST is not empty but action is missing
if (!isset($_POST['action']) && !empty($_SERVER['CONTENT_TYPE'])) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        error_log("Fallback: trying to parse raw input: " . $raw);
        if (strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false) {
            parse_str($raw, $data);
            if (is_array($data) && isset($data['action'])) {
                $_POST = array_merge($_POST, $data);
                error_log("Fallback: successfully parsed form data: " . json_encode($data));
            }
        }
    }
}

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    error_log("Authentication failed - user not logged in");
    error_log("Session data: " . json_encode($_SESSION));
    error_log("Session ID: " . session_id());
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();

// Get action from POST or GET with fallback
$action = '';
if (isset($_POST['action'])) {
    $action = trim($_POST['action']);
    error_log("Action from POST: '$action'");
} elseif (isset($_GET['action'])) {
    $action = trim($_GET['action']);
    error_log("Action from GET: '$action'");
}

error_log("User: " . json_encode($user));
error_log("Raw action from POST: " . ($_POST['action'] ?? 'not set'));
error_log("Raw action from GET: " . ($_GET['action'] ?? 'not set'));
error_log("Final action variable: '$action'");
error_log("Action length: " . strlen($action));
error_log("Action is empty: " . (empty($action) ? 'yes' : 'no'));
error_log("POST data: " . json_encode($_POST));
error_log("GET data: " . json_encode($_GET));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

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
            error_log("=== get_adviser_feedback for student_id: $student_id ===");
            
            // Get all theses for this adviser to verify the student is assigned to them
            $adviserTheses = $thesisManager->getAdviserTheses($user['id']);
            error_log("Found " . count($adviserTheses) . " theses for adviser " . $user['id']);
            
            $isStudentAssigned = false;
            $matchingThesis = null;
            
            foreach ($adviserTheses as $thesis) {
                error_log("Checking thesis: student_user_id=" . $thesis['student_user_id'] . ", student_id_field=" . ($thesis['student_id'] ?? 'not_set') . ", title=" . $thesis['title']);
                if ($thesis['student_user_id'] == $student_id) {
                    $isStudentAssigned = true;
                    $matchingThesis = $thesis;
                    error_log("Found matching thesis for student_user_id: $student_id");
                    break;
                }
            }
            
            if (!$isStudentAssigned) {
                error_log("Student ID $student_id not found in adviser theses for adviser " . $user['id']);
                error_log("Available theses: " . json_encode($adviserTheses));
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            // Get feedback for this specific student - use student_user_id instead of student_id
            error_log("Calling getStudentAllFeedback with student_user_id: " . $matchingThesis['student_user_id']);
            $feedback = $thesisManager->getStudentAllFeedback($matchingThesis['student_user_id']);
            error_log("Retrieved " . count($feedback) . " feedback items");
            if (count($feedback) > 0) {
                error_log("Sample feedback item: " . json_encode($feedback[0]));
            }
        } else {
            error_log("=== get_adviser_feedback for all students ===");
            // Get all feedback given by this adviser
            $feedback = $thesisManager->getAdviserAllFeedback($user['id']);
            error_log("Retrieved " . count($feedback) . " total feedback items for adviser");
        }
        
        echo json_encode(['success' => true, 'feedback' => $feedback]);
        break;
        
    case 'add_feedback':
        // Only advisers can add feedback
        error_log("=== ADD_FEEDBACK CASE STARTED ===");
        error_log("User role: " . $user['role']);
        
        if ($user['role'] !== 'adviser') {
            error_log("Access denied: User is not an adviser");
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        error_log("POST data for add_feedback: " . json_encode($_POST));
        
        if (!isset($_POST['chapter_id']) || !isset($_POST['feedback_text'])) {
            error_log("Missing required fields: chapter_id or feedback_text");
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
            error_log("Feedback added successfully");
            echo json_encode(['success' => true, 'message' => 'Feedback added successfully']);
        } else {
            error_log("Failed to add feedback to chapter $chapter_id by adviser {$user['id']}");
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add feedback: Database error']);
        }
        error_log("=== ADD_FEEDBACK CASE ENDED ===");
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
        error_log("SWITCH DEFAULT CASE: Unknown action '$action'");
        error_log("Available actions: get_student_feedback, get_adviser_feedback, add_feedback, delete_feedback");
        error_log("Action length: " . strlen($action));
        error_log("Action trimmed: '" . trim($action) . "'");
        error_log("Action comparison with 'add_feedback': " . ($action === 'add_feedback' ? 'true' : 'false'));
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        break;
}
?> 