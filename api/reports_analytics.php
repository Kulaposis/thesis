<?php
/**
 * Reports and Analytics API for Thesis Management System
 * This file provides API endpoints for generating reports and analytics
 */

// Start session first
session_start();

// Set headers for JSON API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// For preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database and authentication files
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/analytics_functions.php';

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Check if user is authenticated
$user = authenticateUser($conn);

if (!$user) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Process based on request method
switch ($method) {
    case 'GET':
        handleGetRequest($conn, $user);
        break;
    case 'POST':
        handlePostRequest($conn, $user);
        break;
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

/**
 * Handle GET requests for reports and analytics
 * 
 * @param PDO $conn Database connection
 * @param array $user Authenticated user data
 */
function handleGetRequest($conn, $user) {
    // Get query parameters
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'templates':
            // Get available report templates
            $templates = getReportTemplates();
            echo json_encode(["templates" => $templates]);
            break;
            
        case 'saved_reports':
            // Get user's saved reports
            $savedReports = getSavedReports($user['id']);
            echo json_encode(["saved_reports" => $savedReports]);
            break;
            
        case 'adviser_metrics':
            // Check if user is an adviser or requesting their own metrics
            if ($user['role'] !== 'adviser' && !isset($_GET['adviser_id'])) {
                http_response_code(403);
                echo json_encode(["error" => "Access denied"]);
                break;
            }
            
            $adviserId = isset($_GET['adviser_id']) ? $_GET['adviser_id'] : $user['id'];
            $timePeriod = isset($_GET['time_period']) ? $_GET['time_period'] : 'monthly';
            
            // Get adviser metrics
            $metrics = getAdviserMetrics($conn, $adviserId, $timePeriod);
            echo json_encode(["adviser_metrics" => $metrics]);
            break;
            
        case 'student_metrics':
            // Check if user is requesting their own metrics or has permission
            if ($user['role'] === 'student' && isset($_GET['student_id']) && $_GET['student_id'] != $user['id']) {
                http_response_code(403);
                echo json_encode(["error" => "Access denied"]);
                break;
            }
            
            $studentId = isset($_GET['student_id']) ? $_GET['student_id'] : $user['id'];
            $timePeriod = isset($_GET['time_period']) ? $_GET['time_period'] : 'monthly';
            
            // Get student metrics
            $metrics = getStudentMetrics($conn, $studentId, $timePeriod);
            echo json_encode(["student_metrics" => $metrics]);
            break;
            
        case 'thesis_metrics':
            // Get thesis ID from query parameters
            if (!isset($_GET['thesis_id'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing thesis_id parameter"]);
                break;
            }
            
            $thesisId = $_GET['thesis_id'];
            
            // Check if user has access to this thesis
            if (!userHasAccessToThesis($conn, $user['id'], $thesisId)) {
                http_response_code(403);
                echo json_encode(["error" => "Access denied"]);
                break;
            }
            
            // Get thesis metrics
            $metrics = getThesisMetricsData($conn, $thesisId);
            echo json_encode(["thesis_metrics" => $metrics]);
            break;
            
        case 'department_metrics':
            // Get department from query parameters
            if (!isset($_GET['department'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing department parameter"]);
                break;
            }
            
            $department = $_GET['department'];
            $timePeriod = isset($_GET['time_period']) ? $_GET['time_period'] : 'monthly';
            
            // Get department metrics
            $metrics = getDepartmentMetricsData($conn, $department, $timePeriod);
            echo json_encode(["department_metrics" => $metrics]);
            break;
            
        case 'generate_report':
            // Get template ID from query parameters
            if (!isset($_GET['template_id'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing template_id parameter"]);
                break;
            }
            
            $templateId = $_GET['template_id'];
            $parameters = isset($_GET['parameters']) ? json_decode($_GET['parameters'], true) : [];
            
            // Generate report
            $report = generateReport($templateId, $parameters);
            echo json_encode(["report" => $report]);
            break;
            
        case 'chapter_submission_stats':
            // Get chapter submission statistics
            $status = isset($_GET['status']) ? $_GET['status'] : 'all';
            $stats = getChapterSubmissionStats($conn, $status);
            echo json_encode(["chapter_stats" => $stats]);
            break;
            
        case 'student_chapter_progress':
            // Get detailed student progress across chapters
            $progress = getStudentChapterProgress($conn);
            echo json_encode(["student_progress" => $progress]);
            break;
            
        case 'submission_timeline':
            // Get submission timeline analysis
            $period = isset($_GET['period']) ? $_GET['period'] : '12 months';
            $timeline = getSubmissionTimelineAnalysis($conn, $period);
            echo json_encode(["submission_timeline" => $timeline]);
            break;
            
        case 'adviser_chapter_metrics':
            // Get adviser performance with chapter-specific data
            $metrics = getAdviserChapterMetrics($conn);
            echo json_encode(["adviser_metrics" => $metrics]);
            break;
            
        case 'department_performance':
            // Get department performance comparison
            $performance = getDepartmentPerformanceComparison($conn);
            echo json_encode(["department_performance" => $performance]);
            break;
            
        case 'milestone_analysis':
            // Get thesis milestone analysis
            $milestones = getThesisMilestoneAnalysis($conn);
            echo json_encode(["milestone_analysis" => $milestones]);
            break;
            
        case 'feedback_analysis':
            // Get feedback analysis summary
            $feedback = getFeedbackAnalysisSummary($conn);
            echo json_encode(["feedback_analysis" => $feedback]);
            break;
            
        case 'recent_activity':
            $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
            
            $recent_activity = getRecentActivityAnalysis($conn, $days);
            echo json_encode(["recent_activity" => $recent_activity]);
            break;
            
        case 'comment_activity_logs':
            $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
            $userId = $user['id']; // Current adviser
            
            $commentLogs = getCommentActivityLogs($userId, $days);
            
            // Format the logs for display
            $formattedLogs = array_map(function($log) {
                $details = json_decode($log['details'], true);
                
                // Create a user-friendly description based on the action
                $description = '';
                if ($log['event_type'] === 'comment_activity') {
                    switch ($details['action']) {
                        case 'add_comment':
                            $description = "Added comment on {$details['chapter_title']} for {$details['student_name']}";
                            if (!empty($details['comment_text_preview'])) {
                                $description .= ": \"" . $details['comment_text_preview'] . "\"";
                                if ($details['comment_length'] > 100) {
                                    $description .= "...";
                                }
                            }
                            break;
                        case 'resolve_comment':
                            $description = "Resolved comment on {$details['chapter_title']} for {$details['student_name']}";
                            break;
                    }
                } elseif ($log['event_type'] === 'highlight_activity') {
                    switch ($details['action']) {
                        case 'add_highlight':
                            $description = "Added highlight on {$details['chapter_title']} for {$details['student_name']}";
                            if (!empty($details['highlighted_text_preview'])) {
                                $description .= ": \"" . $details['highlighted_text_preview'] . "\"";
                                if ($details['highlighted_text_length'] > 100) {
                                    $description .= "...";
                                }
                            }
                            break;
                        case 'remove_highlight':
                            $description = "Removed highlight on {$details['chapter_title']} for {$details['student_name']}";
                            break;
                    }
                }
                
                return [
                    'id' => $log['id'],
                    'activity_type' => ucfirst(str_replace('_', ' ', $log['event_type'])),
                    'description' => $description,
                    'chapter_title' => $details['chapter_title'] ?? '',
                    'student_name' => $details['student_name'] ?? '',
                    'activity_date' => $log['formatted_date'],
                    'details' => $details
                ];
            }, $commentLogs);
            
            echo json_encode(["comment_activity_logs" => $formattedLogs]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(["error" => "Invalid action"]);
            break;
    }
}

/**
 * Handle POST requests for reports and analytics
 * 
 * @param PDO $conn Database connection
 * @param array $user Authenticated user data
 */
function handlePostRequest($conn, $user) {
    // Get request body
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        error_log("Invalid JSON data received: " . file_get_contents("php://input"));
        http_response_code(400);
        echo json_encode(["error" => "Invalid request data: Unable to parse JSON"]);
        return;
    }
    
    // Get action from request body
    $action = isset($data['action']) ? $data['action'] : '';
    
    switch ($action) {
        case 'save_report':
            // Log received data for debugging
            error_log("Save report request data: " . json_encode($data));
            
            // Check required parameters with detailed error messages
            $missingParams = [];
            if (!isset($data['template_id'])) $missingParams[] = 'template_id';
            if (!isset($data['name'])) $missingParams[] = 'name';
            if (!isset($data['report_data'])) $missingParams[] = 'report_data';
            
            if (!empty($missingParams)) {
                http_response_code(400);
                echo json_encode([
                    "error" => "Missing required parameters: " . implode(', ', $missingParams)
                ]);
                break;
            }
            
            // Validate data types
            if (!is_numeric($data['template_id'])) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid template_id: must be a number"]);
                break;
            }
            
            if (!is_string($data['name']) || empty(trim($data['name']))) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid name: must be a non-empty string"]);
                break;
            }
            
            if (!is_array($data['report_data'])) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid report_data: must be an object/array"]);
                break;
            }
            
            $templateId = intval($data['template_id']);
            $name = trim($data['name']);
            $description = isset($data['description']) ? trim($data['description']) : '';
            $reportData = $data['report_data'];
            $parametersUsed = isset($data['parameters_used']) ? $data['parameters_used'] : [];
            
            // Save report
            $result = saveReport($user['id'], $templateId, $name, $description, $reportData, $parametersUsed);
            
            if ($result['success']) {
                echo json_encode([
                    "success" => true,
                    "report_id" => $result['report_id'],
                    "message" => $result['message']
                ]);
            } else {
                error_log("Error saving report: " . $result['error']);
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "error" => $result['error']
                ]);
            }
            break;
            
        case 'calculate_metrics':
            // Check if user has permission to calculate metrics
            if ($user['role'] !== 'adviser') {
                http_response_code(403);
                echo json_encode(["error" => "Access denied"]);
                break;
            }
            
            $metricType = isset($data['metric_type']) ? $data['metric_type'] : '';
            $entityId = isset($data['entity_id']) ? $data['entity_id'] : null;
            $timePeriod = isset($data['time_period']) ? $data['time_period'] : 'monthly';
            
            $success = false;
            
            switch ($metricType) {
                case 'adviser':
                    $success = calculateAdviserMetrics($entityId ?: $user['id'], $timePeriod);
                    break;
                case 'student':
                    $success = calculateStudentMetrics($entityId, $timePeriod);
                    break;
                case 'thesis':
                    $success = calculateThesisMetrics($entityId);
                    break;
                case 'department':
                    $department = isset($data['department']) ? $data['department'] : '';
                    $success = calculateDepartmentMetrics($department, $timePeriod);
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(["error" => "Invalid metric type"]);
                    return;
            }
            
            if ($success) {
                echo json_encode(["success" => true]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to calculate metrics"]);
            }
            break;
            
        case 'log_event':
            // Check required parameters
            if (!isset($data['event_type'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing event_type parameter"]);
                break;
            }
            
            $eventType = $data['event_type'];
            $relatedId = isset($data['related_id']) ? $data['related_id'] : null;
            $entityType = isset($data['entity_type']) ? $data['entity_type'] : '';
            $details = isset($data['details']) ? $data['details'] : null;
            
            // Log analytics event
            $success = logAnalyticsEvent($eventType, $user['id'], $relatedId, $entityType, $details);
            
            if ($success) {
                echo json_encode(["success" => true]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to log event"]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(["error" => "Invalid action"]);
            break;
    }
}

/**
 * Check if a user has access to a thesis
 * 
 * @param PDO $conn Database connection
 * @param int $userId User ID
 * @param int $thesisId Thesis ID
 * @return bool True if user has access, false otherwise
 */
function userHasAccessToThesis($conn, $userId, $thesisId) {
    // Get user role
    $query = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return false;
    }
    
    // Admin and faculty have access to all theses
    if ($user['role'] === 'admin' || $user['role'] === 'faculty') {
        return true;
    }
    
    // Check if user is the student or adviser for this thesis
    $query = "SELECT 1 FROM theses WHERE id = ? AND (student_id = ? OR adviser_id = ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$thesisId, $userId, $userId]);
    
    return $stmt->rowCount() > 0;
}

/**
 * Get adviser metrics data
 * 
 * @param PDO $conn Database connection
 * @param int $adviserId Adviser ID
 * @param string $timePeriod Time period
 * @return array Adviser metrics data
 */
function getAdviserMetrics($conn, $adviserId, $timePeriod) {
    $query = "SELECT metric_name, metric_value, time_period, start_date, end_date 
              FROM adviser_metrics 
              WHERE adviser_id = ? AND time_period = ? 
              ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$adviserId, $timePeriod]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format metrics as key-value pairs
    $metrics = [];
    foreach ($results as $row) {
        $metrics[$row['metric_name']] = [
            'value' => $row['metric_value'],
            'time_period' => $row['time_period'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date']
        ];
    }
    
    // If no metrics found, calculate them
    if (empty($metrics)) {
        calculateAdviserMetrics($adviserId, $timePeriod);
        return getAdviserMetrics($conn, $adviserId, $timePeriod);
    }
    
    return $metrics;
}

/**
 * Get student metrics data
 * 
 * @param PDO $conn Database connection
 * @param int $studentId Student ID
 * @param string $timePeriod Time period
 * @return array Student metrics data
 */
function getStudentMetrics($conn, $studentId, $timePeriod) {
    $query = "SELECT metric_name, metric_value, time_period, start_date, end_date 
              FROM student_metrics 
              WHERE student_id = ? AND time_period = ? 
              ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$studentId, $timePeriod]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format metrics as key-value pairs
    $metrics = [];
    foreach ($results as $row) {
        $metrics[$row['metric_name']] = [
            'value' => $row['metric_value'],
            'time_period' => $row['time_period'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date']
        ];
    }
    
    // If no metrics found, calculate them
    if (empty($metrics)) {
        calculateStudentMetrics($studentId, $timePeriod);
        return getStudentMetrics($conn, $studentId, $timePeriod);
    }
    
    return $metrics;
}

/**
 * Get thesis metrics data
 * 
 * @param PDO $conn Database connection
 * @param int $thesisId Thesis ID
 * @return array Thesis metrics data
 */
function getThesisMetricsData($conn, $thesisId) {
    $query = "SELECT metric_name, metric_value, calculated_at 
              FROM thesis_metrics 
              WHERE thesis_id = ? 
              ORDER BY calculated_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$thesisId]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format metrics as key-value pairs
    $metrics = [];
    foreach ($results as $row) {
        $metrics[$row['metric_name']] = [
            'value' => $row['metric_value'],
            'calculated_at' => $row['calculated_at']
        ];
    }
    
    // If no metrics found, calculate them
    if (empty($metrics)) {
        calculateThesisMetrics($thesisId);
        return getThesisMetricsData($conn, $thesisId);
    }
    
    return $metrics;
}

/**
 * Get department metrics data
 * 
 * @param PDO $conn Database connection
 * @param string $department Department name
 * @param string $timePeriod Time period
 * @return array Department metrics data
 */
function getDepartmentMetricsData($conn, $department, $timePeriod) {
    $query = "SELECT metric_name, metric_value, time_period, start_date, end_date 
              FROM department_metrics 
              WHERE department = ? AND time_period = ? 
              ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$department, $timePeriod]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format metrics as key-value pairs
    $metrics = [];
    foreach ($results as $row) {
        $metrics[$row['metric_name']] = [
            'value' => $row['metric_value'],
            'time_period' => $row['time_period'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date']
        ];
    }
    
    // If no metrics found, calculate them
    if (empty($metrics)) {
        calculateDepartmentMetrics($department, $timePeriod);
        return getDepartmentMetricsData($conn, $department, $timePeriod);
    }
    
    return $metrics;
}

/**
 * Authenticate user from API request
 * 
 * @param PDO $conn Database connection
 * @return array|bool User data or false if not authenticated
 */
function authenticateUser($conn) {
    // Check for session authentication
    if (isset($_SESSION['user_id'])) {
        $query = "SELECT id, role, full_name FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Check for token authentication
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        
        // For simplicity, we're using the session ID as the token
        // In a production environment, use a proper JWT or OAuth implementation
        session_id($token);
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            $query = "SELECT id, role, full_name FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    return false;
} 