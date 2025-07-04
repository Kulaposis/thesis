<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';
require_once '../includes/analytics_functions.php';

// Require adviser login
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SESSION['role'] !== 'adviser') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not an adviser']);
    exit;
}

$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Debug logging
error_log("comments.php: Action received: " . $action);
error_log("comments.php: POST data: " . json_encode($_POST));
error_log("comments.php: GET data: " . json_encode($_GET));

switch ($action) {
    case 'add_comment':
        if (!isset($_POST['chapter_id']) || !isset($_POST['comment_text'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chapter ID and comment text required']);
            exit;
        }
        
        // Check if this is a paragraph comment
        $paragraph_id = $_POST['paragraph_id'] ?? null;
        $metadata = null;
        
        if ($paragraph_id) {
            // Store paragraph ID in metadata
            $metadata = json_encode(['paragraph_id' => $paragraph_id]);
        }
        
        $comment_id = $thesisManager->addDocumentComment(
            $_POST['chapter_id'],
            $user['id'],
            $_POST['comment_text'],
            $_POST['highlight_id'] ?? null,
            $_POST['start_offset'] ?? null,
            $_POST['end_offset'] ?? null,
            $_POST['position_x'] ?? null,
            $_POST['position_y'] ?? null,
            $metadata
        );
        
        if ($comment_id) {
            // Log the comment activity
            $logDetails = [
                'comment_text_preview' => substr($_POST['comment_text'], 0, 100),
                'comment_length' => strlen($_POST['comment_text']),
                'has_highlight' => !empty($_POST['highlight_id']),
                'has_paragraph' => !empty($paragraph_id),
                'start_offset' => $_POST['start_offset'] ?? null,
                'end_offset' => $_POST['end_offset'] ?? null,
                'position_x' => $_POST['position_x'] ?? null,
                'position_y' => $_POST['position_y'] ?? null
            ];
            
            logCommentActivity('add_comment', $user['id'], $comment_id, $_POST['chapter_id'], $logDetails);
            
            echo json_encode(['success' => true, 'comment_id' => $comment_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to add comment']);
        }
        break;

    case 'get_comments':
        if (!isset($_GET['chapter_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chapter ID required']);
            exit;
        }
        
        $comments = $thesisManager->getChapterComments($_GET['chapter_id']);
        echo json_encode(['success' => true, 'comments' => $comments]);
        break;

    case 'resolve_comment':
        if (!isset($_POST['comment_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Comment ID required']);
            exit;
        }
        
        $success = $thesisManager->resolveComment($_POST['comment_id'], $user['id']);
        if ($success) {
            // Get comment details for logging
            $db = new Database();
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("SELECT chapter_id FROM document_comments WHERE id = ?");
            $stmt->execute([$_POST['comment_id']]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($comment) {
                logCommentActivity('resolve_comment', $user['id'], $_POST['comment_id'], $comment['chapter_id']);
            }
            
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to resolve comment']);
        }
        break;

    case 'add_highlight':
        $required = ['chapter_id', 'start_offset', 'end_offset', 'highlighted_text'];
        foreach ($required as $field) {
            if (!isset($_POST[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Field $field is required"]);
                exit;
            }
        }
        
        $highlight_color = $_POST['highlight_color'] ?? '#ffeb3b';
        $highlight_id = $thesisManager->addHighlight(
            $_POST['chapter_id'],
            $user['id'],
            $_POST['start_offset'],
            $_POST['end_offset'],
            $_POST['highlighted_text'],
            $highlight_color
        );
        
        if ($highlight_id) {
            // Log the highlight activity
            $logDetails = [
                'highlighted_text_preview' => substr($_POST['highlighted_text'], 0, 100),
                'highlighted_text_length' => strlen($_POST['highlighted_text']),
                'highlight_color' => $highlight_color,
                'start_offset' => $_POST['start_offset'],
                'end_offset' => $_POST['end_offset']
            ];
            
            logHighlightActivity('add_highlight', $user['id'], $highlight_id, $_POST['chapter_id'], $logDetails);
            
            echo json_encode(['success' => true, 'highlight_id' => $highlight_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to add highlight']);
        }
        break;

    case 'remove_highlight':
        if (!isset($_POST['highlight_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Highlight ID required']);
            exit;
        }
        
        $success = $thesisManager->removeHighlight($_POST['highlight_id'], $user['id']);
        if ($success) {
            // Get highlight details for logging
            $db = new Database();
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("SELECT chapter_id, highlighted_text, highlight_color FROM document_highlights WHERE id = ?");
            $stmt->execute([$_POST['highlight_id']]);
            $highlight = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($highlight) {
                $logDetails = [
                    'highlighted_text_preview' => substr($highlight['highlighted_text'], 0, 100),
                    'highlight_color' => $highlight['highlight_color']
                ];
                logHighlightActivity('remove_highlight', $user['id'], $_POST['highlight_id'], $highlight['chapter_id'], $logDetails);
            }
            
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to remove highlight']);
        }
        break;

    case 'get_highlights':
        if (!isset($_GET['chapter_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chapter ID required']);
            exit;
        }
        
        $highlights = $thesisManager->getChapterHighlights($_GET['chapter_id']);
        echo json_encode(['success' => true, 'highlights' => $highlights]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
        break;
}
?> 