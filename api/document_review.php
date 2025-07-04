<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';

// Require adviser login
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in', 'session_status' => session_status(), 'session_id' => session_id()]);
    exit;
}

if ($_SESSION['role'] !== 'adviser') {
    http_response_code(401);
    echo json_encode(['error' => 'Not an adviser', 'role' => $_SESSION['role'] ?? 'none']);
    exit;
}

$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_all_students':
        // Get all students assigned to this adviser
        $students = $thesisManager->getAdviserStudents($user['id']);
        
        if (empty($students)) {
            echo json_encode([
                'success' => true,
                'students' => [],
                'message' => 'No students assigned'
            ]);
            exit;
        }
        
        // For each student, get their thesis and chapters
        $studentsWithChapters = [];
        foreach ($students as $student) {
            // Determine if this is a real thesis or just a placeholder
            $isPlaceholder = ($student['thesis_title'] === 'Untitled Thesis' || 
                            $student['thesis_title'] === null || 
                            $student['thesis_title'] === '');
            
            $studentData = [
                'id' => $student['id'],
                'full_name' => $student['full_name'],
                'student_id' => $student['student_id'],
                'program' => $student['program'],
                'thesis_id' => $student['thesis_id'],
                'thesis_title' => $isPlaceholder ? 'No thesis topic selected yet' : $student['thesis_title'],
                'thesis_status' => $student['thesis_status'] ?: 'not_started',
                'progress_percentage' => $student['progress_percentage'] ?: 0,
                'is_placeholder' => $isPlaceholder,
                'chapters' => []
            ];
            
            // Get chapters if thesis exists and it's not a placeholder
            if ($student['thesis_id'] && !$isPlaceholder) {
                $chapters = $thesisManager->getThesisChapters($student['thesis_id']);
                foreach ($chapters as $chapter) {
                    // Get files for this chapter
                    $files = $thesisManager->getChapterFiles($chapter['id']);
                    $chapter['files'] = $files;
                    $chapter['has_files'] = !empty($files);
                    $studentData['chapters'][] = $chapter;
                }
            }
            
            $studentsWithChapters[] = $studentData;
        }
        
        echo json_encode([
            'success' => true,
            'students' => $studentsWithChapters
        ]);
        break;

    case 'get_chapters':
        if (isset($_GET['thesis_id'])) {
            $thesis_id = $_GET['thesis_id'];
            
            // Verify the thesis is assigned to this adviser
            $thesis = $thesisManager->getThesisById($thesis_id);
            if (!$thesis || $thesis['adviser_id'] != $user['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            $chapters = $thesisManager->getThesisChapters($thesis_id);
            
            // Add debug information
            error_log("Returning chapters for thesis ID: $thesis_id");
            error_log("Chapters: " . json_encode($chapters));
            
            echo json_encode([
                'success' => true, 
                'chapters' => $chapters,
                'thesis' => $thesis
            ]);
        } else if (isset($_GET['student_id'])) {
            $student_id = $_GET['student_id'];
            
            // Get all theses for this adviser to verify the student is assigned to them
            $adviserTheses = $thesisManager->getAdviserTheses($user['id']);
            $thesis = null;
            
            foreach ($adviserTheses as $t) {
                if ($t['student_user_id'] == $student_id) {
                    $thesis = $t;
                    break;
                }
            }
            
            if (!$thesis) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            $chapters = $thesisManager->getThesisChapters($thesis['id']);
            echo json_encode(['success' => true, 'chapters' => $chapters]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Thesis ID or Student ID required']);
        }
        break;
        
    case 'get_chapter':
        if (!isset($_GET['chapter_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chapter ID required']);
            exit;
        }
        
        $chapter = $thesisManager->getChapterForReview($_GET['chapter_id']);
        if ($chapter) {
            echo json_encode(['success' => true, 'chapter' => $chapter]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Chapter not found']);
        }
        break;

    case 'add_highlight':
        $required = ['chapter_id', 'start_offset', 'end_offset', 'highlighted_text'];
        foreach ($required as $field) {
            if (!isset($_POST[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field $field is required"]);
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
            echo json_encode(['success' => true, 'highlight_id' => $highlight_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add highlight']);
        }
        break;

    case 'add_comment':
        if (!isset($_POST['chapter_id']) || !isset($_POST['comment_text'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chapter ID and comment text required']);
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
            echo json_encode(['success' => true, 'comment_id' => $comment_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add comment']);
        }
        break;

    case 'remove_highlight':
        if (!isset($_POST['highlight_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Highlight ID required']);
            exit;
        }
        
        $success = $thesisManager->removeHighlight($_POST['highlight_id'], $user['id']);
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove highlight']);
        }
        break;

    case 'resolve_comment':
        if (!isset($_POST['comment_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Comment ID required']);
            exit;
        }
        
        $success = $thesisManager->resolveComment($_POST['comment_id'], $user['id']);
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to resolve comment']);
        }
        break;

    case 'get_highlights':
        if (!isset($_GET['chapter_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chapter ID required']);
            exit;
        }
        
        $highlights = $thesisManager->getChapterHighlights($_GET['chapter_id']);
        echo json_encode(['success' => true, 'highlights' => $highlights]);
        break;

    case 'get_comments':
        if (!isset($_GET['chapter_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Chapter ID required']);
            exit;
        }
        
        $comments = $thesisManager->getChapterComments($_GET['chapter_id']);
        echo json_encode(['success' => true, 'comments' => $comments]);
        break;

    case 'debug_file':
        if (!isset($_GET['file_id'])) {
            echo json_encode(['success' => false, 'error' => 'File ID required']);
            break;
        }
        
        $file_id = intval($_GET['file_id']);
        
        // Get database connection
        $database = new Database();
        $pdo = $database->getConnection();
        
        $sql = "SELECT f.*, c.title as chapter_title, t.title as thesis_title, u.full_name as student_name
                FROM file_uploads f
                JOIN chapters c ON f.chapter_id = c.id
                JOIN theses t ON c.thesis_id = t.id
                JOIN users u ON t.student_id = u.id
                WHERE f.id = :file_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':file_id', $file_id);
        $stmt->execute();
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file) {
            $debug_info = [
                'file_id' => $file['id'],
                'original_filename' => $file['original_filename'],
                'file_type' => $file['file_type'],
                'file_path' => $file['file_path'],
                'file_exists' => file_exists($file['file_path']),
                'file_size' => file_exists($file['file_path']) ? filesize($file['file_path']) : 'N/A',
                'chapter_title' => $file['chapter_title'],
                'thesis_title' => $file['thesis_title'],
                'student_name' => $file['student_name'],
                'uploaded_at' => $file['uploaded_at']
            ];
            echo json_encode(['success' => true, 'debug_info' => $debug_info]);
        } else {
            echo json_encode(['success' => false, 'error' => 'File not found']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?> 