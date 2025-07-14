<?php
/**
 * Activity Logs Archive API for Thesis Management System
 * This file provides API endpoints for managing activity logs archives
 */

// Start session first
session_start();

// Set headers for JSON API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// For preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/analytics_functions.php';

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Authenticate user
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$user = $auth->getCurrentUser();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Process based on request method
switch ($method) {
    case 'GET':
        handleGetRequest($conn, $user, $action);
        break;
    case 'POST':
        handlePostRequest($conn, $user, $action);
        break;
    case 'DELETE':
        handleDeleteRequest($conn, $user, $action);
        break;
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}

/**
 * Handle GET requests for archive management
 */
function handleGetRequest($conn, $user, $action) {
    switch ($action) {
        case 'activity_logs':
            // Get activity logs with sorting, filtering, and pagination
            $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
            $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
            $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
            $eventType = isset($_GET['event_type']) ? $_GET['event_type'] : '';
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            
            $result = getActivityLogsWithPagination($conn, $user['id'], $days, $sortBy, $sortOrder, $eventType, $page, $limit);
            echo json_encode([
                "success" => true,
                "logs" => $result['logs'],
                "total_count" => $result['total_count'],
                "page" => $page,
                "total_pages" => ceil($result['total_count'] / $limit)
            ]);
            break;
            
        case 'archived_logs':
            // Get archived logs
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'archived_at';
            $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
            $eventType = isset($_GET['event_type']) ? $_GET['event_type'] : '';
            $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
            $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
            
            $result = getArchivedLogs($conn, $user['id'], $page, $limit, $sortBy, $sortOrder, $eventType, $dateFrom, $dateTo);
            echo json_encode([
                "success" => true,
                "logs" => $result['logs'],
                "total_count" => $result['total_count'],
                "page" => $page,
                "total_pages" => ceil($result['total_count'] / $limit)
            ]);
            break;
            
        case 'archive_statistics':
            // Get archive statistics
            $stats = getArchiveStatistics($conn, $user['id']);
            echo json_encode([
                "success" => true,
                "statistics" => $stats
            ]);
            break;
            
        case 'archive_settings':
            // Get archive settings
            $settings = getArchiveSettings($conn);
            echo json_encode([
                "success" => true,
                "settings" => $settings
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(["error" => "Invalid action"]);
            break;
    }
}

/**
 * Handle POST requests for archive operations
 */
function handlePostRequest($conn, $user, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'clear_logs':
            // Clear logs and move to archive
            $days = isset($input['days']) ? intval($input['days']) : null;
            $eventTypes = isset($input['event_types']) ? $input['event_types'] : [];
            $archiveReason = isset($input['reason']) ? $input['reason'] : 'manual_clear';
            
            $result = clearActivityLogs($conn, $user['id'], $days, $eventTypes, $archiveReason);
            
            if ($result['success']) {
                echo json_encode([
                    "success" => true,
                    "message" => "Successfully cleared {$result['count']} logs to archive",
                    "archived_count" => $result['count']
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "error" => "Failed to clear logs: " . $result['error']
                ]);
            }
            break;
            
        case 'restore_logs':
            // Restore logs from archive
            $logIds = isset($input['log_ids']) ? $input['log_ids'] : [];
            
            if (empty($logIds)) {
                http_response_code(400);
                echo json_encode(["error" => "No log IDs provided"]);
                break;
            }
            
            $result = restoreArchivedLogs($conn, $user['id'], $logIds);
            
            if ($result['success']) {
                echo json_encode([
                    "success" => true,
                    "message" => "Successfully restored {$result['count']} logs",
                    "restored_count" => $result['count']
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "error" => "Failed to restore logs: " . $result['error']
                ]);
            }
            break;
            
        case 'export_archive':
            // Export archived logs
            $format = isset($input['format']) ? $input['format'] : 'json';
            $dateFrom = isset($input['date_from']) ? $input['date_from'] : '';
            $dateTo = isset($input['date_to']) ? $input['date_to'] : '';
            
            $result = exportArchivedLogs($conn, $user['id'], $format, $dateFrom, $dateTo);
            
            if ($result['success']) {
                echo json_encode([
                    "success" => true,
                    "download_url" => $result['download_url'],
                    "file_size" => $result['file_size'],
                    "record_count" => $result['record_count']
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "error" => "Failed to export archive: " . $result['error']
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(["error" => "Invalid action"]);
            break;
    }
}

/**
 * Handle DELETE requests for permanent deletion
 */
function handleDeleteRequest($conn, $user, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'delete_archived_logs':
            // Permanently delete archived logs
            $logIds = isset($input['log_ids']) ? $input['log_ids'] : [];
            
            if (empty($logIds)) {
                http_response_code(400);
                echo json_encode(["error" => "No log IDs provided"]);
                break;
            }
            
            $result = deleteArchivedLogs($conn, $user['id'], $logIds);
            
            if ($result['success']) {
                echo json_encode([
                    "success" => true,
                    "message" => "Successfully deleted {$result['count']} archived logs permanently",
                    "deleted_count" => $result['count']
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "error" => "Failed to delete archived logs: " . $result['error']
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(["error" => "Invalid action"]);
            break;
    }
}

/**
 * Get activity logs with sorting and filtering
 */
function getActivityLogs($conn, $userId, $days = 30, $sortBy = 'created_at', $sortOrder = 'DESC', $eventType = '') {
    try {
        // Build WHERE conditions
        $whereConditions = ["al.user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if ($days > 0) {
            $whereConditions[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
            $params[':days'] = $days;
        }
        
        if (!empty($eventType)) {
            $whereConditions[] = "al.event_type = :event_type";
            $params[':event_type'] = $eventType;
        }
        
        // Validate sort parameters
        $allowedSortFields = ['created_at', 'event_type', 'entity_type'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'created_at';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $query = "SELECT 
                    al.*,
                    DATE_FORMAT(al.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date,
                    u.full_name as user_name
                  FROM analytics_logs al
                  LEFT JOIN users u ON al.user_id = u.id
                  WHERE {$whereClause}
                  ORDER BY al.{$sortBy} {$sortOrder}";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON details for better display
        foreach ($logs as &$log) {
            if ($log['details']) {
                $log['details_parsed'] = json_decode($log['details'], true);
            }
        }
        
        return $logs;
        
    } catch (Exception $e) {
        error_log("Error getting activity logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Get activity logs with pagination, sorting and filtering
 */
function getActivityLogsWithPagination($conn, $userId, $days = 30, $sortBy = 'created_at', $sortOrder = 'DESC', $eventType = '', $page = 1, $limit = 10) {
    try {
        // Build WHERE conditions
        $whereConditions = ["al.user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if ($days > 0) {
            $whereConditions[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
            $params[':days'] = $days;
        }
        
        if (!empty($eventType)) {
            $whereConditions[] = "al.event_type = :event_type";
            $params[':event_type'] = $eventType;
        }
        
        // Validate sort parameters
        $allowedSortFields = ['created_at', 'event_type', 'entity_type'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'created_at';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM analytics_logs al WHERE {$whereClause}";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Get paginated results
        $query = "SELECT 
                    al.*,
                    DATE_FORMAT(al.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date,
                    u.full_name as user_name
                  FROM analytics_logs al
                  LEFT JOIN users u ON al.user_id = u.id
                  WHERE {$whereClause}
                  ORDER BY al.{$sortBy} {$sortOrder}
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON details for better display
        foreach ($logs as &$log) {
            if ($log['details']) {
                $log['details_parsed'] = json_decode($log['details'], true);
            }
        }
        
        return [
            'logs' => $logs,
            'total_count' => $totalCount
        ];
        
    } catch (Exception $e) {
        error_log("Error getting activity logs with pagination: " . $e->getMessage());
        return [
            'logs' => [],
            'total_count' => 0
        ];
    }
}

/**
 * Get archived logs with pagination and filtering
 */
function getArchivedLogs($conn, $userId, $page = 1, $limit = 50, $sortBy = 'archived_at', $sortOrder = 'DESC', $eventType = '', $dateFrom = '', $dateTo = '') {
    try {
        // Build WHERE conditions
        $whereConditions = ["aal.user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if (!empty($eventType)) {
            $whereConditions[] = "aal.event_type = :event_type";
            $params[':event_type'] = $eventType;
        }
        
        if (!empty($dateFrom)) {
            $whereConditions[] = "DATE(aal.original_created_at) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        
        if (!empty($dateTo)) {
            $whereConditions[] = "DATE(aal.original_created_at) <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        // Validate sort parameters
        $allowedSortFields = ['archived_at', 'original_created_at', 'event_type', 'entity_type'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'archived_at';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM archived_analytics_logs aal WHERE {$whereClause}";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Get paginated results
        $query = "SELECT 
                    aal.*,
                    DATE_FORMAT(aal.original_created_at, '%Y-%m-%d %H:%i:%s') as formatted_original_date,
                    DATE_FORMAT(aal.archived_at, '%Y-%m-%d %H:%i:%s') as formatted_archived_date,
                    u.full_name as user_name,
                    ua.full_name as archived_by_name
                  FROM archived_analytics_logs aal
                  LEFT JOIN users u ON aal.user_id = u.id
                  LEFT JOIN users ua ON aal.archived_by = ua.id
                  WHERE {$whereClause}
                  ORDER BY aal.{$sortBy} {$sortOrder}
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON details for better display
        foreach ($logs as &$log) {
            if ($log['details']) {
                $log['details_parsed'] = json_decode($log['details'], true);
            }
            if ($log['archive_metadata']) {
                $log['archive_metadata_parsed'] = json_decode($log['archive_metadata'], true);
            }
        }
        
        return [
            'logs' => $logs,
            'total_count' => $totalCount
        ];
        
    } catch (Exception $e) {
        error_log("Error getting archived logs: " . $e->getMessage());
        return [
            'logs' => [],
            'total_count' => 0
        ];
    }
}

/**
 * Clear activity logs and move them to archive
 */
function clearActivityLogs($conn, $userId, $days = null, $eventTypes = [], $archiveReason = 'manual_clear') {
    try {
        $conn->beginTransaction();
        
        // Build WHERE conditions for logs to archive
        $whereConditions = ["user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if ($days !== null && $days > 0) {
            $whereConditions[] = "created_at <= DATE_SUB(NOW(), INTERVAL :days DAY)";
            $params[':days'] = $days;
        }
        
        if (!empty($eventTypes)) {
            $placeholders = [];
            foreach ($eventTypes as $index => $type) {
                $placeholder = ":event_type_{$index}";
                $placeholders[] = $placeholder;
                $params[$placeholder] = $type;
            }
            $whereConditions[] = "event_type IN (" . implode(',', $placeholders) . ")";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // First, get the logs to be archived
        $selectQuery = "SELECT * FROM analytics_logs WHERE {$whereClause}";
        $selectStmt = $conn->prepare($selectQuery);
        $selectStmt->execute($params);
        $logsToArchive = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($logsToArchive)) {
            $conn->rollback();
            return [
                'success' => true,
                'count' => 0,
                'message' => 'No logs found matching the criteria'
            ];
        }
        
        // Insert logs into archive table
        $archiveQuery = "INSERT INTO archived_analytics_logs 
                        (original_id, event_type, user_id, related_id, entity_type, details, 
                         original_created_at, archived_by, archive_reason, archive_metadata)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $archiveStmt = $conn->prepare($archiveQuery);
        
        foreach ($logsToArchive as $log) {
            $archiveMetadata = json_encode([
                'archived_from' => 'manual_clear',
                'criteria' => [
                    'days' => $days,
                    'event_types' => $eventTypes,
                    'archive_reason' => $archiveReason
                ],
                'original_log_id' => $log['id']
            ]);
            
            $archiveStmt->execute([
                $log['id'],
                $log['event_type'],
                $log['user_id'],
                $log['related_id'],
                $log['entity_type'],
                $log['details'],
                $log['created_at'],
                $userId, // archived_by is the current user
                $archiveReason,
                $archiveMetadata
            ]);
        }
        
        // Delete the original logs
        $deleteQuery = "DELETE FROM analytics_logs WHERE {$whereClause}";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->execute($params);
        
        $conn->commit();
        
        return [
            'success' => true,
            'count' => count($logsToArchive)
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error clearing activity logs: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Restore archived logs back to active logs
 */
function restoreArchivedLogs($conn, $userId, $logIds) {
    try {
        $conn->beginTransaction();
        
        // Get archived logs to restore
        $placeholders = str_repeat('?,', count($logIds) - 1) . '?';
        $selectQuery = "SELECT * FROM archived_analytics_logs 
                       WHERE id IN ({$placeholders}) AND user_id = ?";
        
        $selectStmt = $conn->prepare($selectQuery);
        $selectStmt->execute(array_merge($logIds, [$userId]));
        $logsToRestore = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($logsToRestore)) {
            $conn->rollback();
            return [
                'success' => false,
                'error' => 'No archived logs found or access denied'
            ];
        }
        
        // Insert logs back into analytics_logs table
        $restoreQuery = "INSERT INTO analytics_logs 
                        (event_type, user_id, related_id, entity_type, details, created_at)
                        VALUES (?, ?, ?, ?, ?, ?)";
        
        $restoreStmt = $conn->prepare($restoreQuery);
        
        foreach ($logsToRestore as $log) {
            $restoreStmt->execute([
                $log['event_type'],
                $log['user_id'],
                $log['related_id'],
                $log['entity_type'],
                $log['details'],
                $log['original_created_at']
            ]);
        }
        
        // Delete from archive
        $deleteQuery = "DELETE FROM archived_analytics_logs WHERE id IN ({$placeholders}) AND user_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->execute(array_merge($logIds, [$userId]));
        
        $conn->commit();
        
        return [
            'success' => true,
            'count' => count($logsToRestore)
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error restoring archived logs: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Permanently delete archived logs
 */
function deleteArchivedLogs($conn, $userId, $logIds) {
    try {
        $placeholders = str_repeat('?,', count($logIds) - 1) . '?';
        $deleteQuery = "DELETE FROM archived_analytics_logs 
                       WHERE id IN ({$placeholders}) AND user_id = ?";
        
        $deleteStmt = $conn->prepare($deleteQuery);
        $result = $deleteStmt->execute(array_merge($logIds, [$userId]));
        
        $deletedCount = $deleteStmt->rowCount();
        
        return [
            'success' => $result,
            'count' => $deletedCount
        ];
        
    } catch (Exception $e) {
        error_log("Error deleting archived logs: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get archive statistics
 */
function getArchiveStatistics($conn, $userId) {
    try {
        $stats = [];
        
        // Total archived logs
        $query = "SELECT COUNT(*) as total_archived FROM archived_analytics_logs WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $stats['total_archived'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_archived'];
        
        // Archives by event type
        $query = "SELECT event_type, COUNT(*) as count 
                  FROM archived_analytics_logs 
                  WHERE user_id = ? 
                  GROUP BY event_type 
                  ORDER BY count DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $stats['by_event_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent archive activity
        $query = "SELECT DATE(archived_at) as archive_date, COUNT(*) as count 
                  FROM archived_analytics_logs 
                  WHERE user_id = ? AND archived_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  GROUP BY DATE(archived_at) 
                  ORDER BY archive_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Oldest and newest archived logs
        $query = "SELECT 
                    MIN(original_created_at) as oldest_log,
                    MAX(original_created_at) as newest_log,
                    MIN(archived_at) as first_archive,
                    MAX(archived_at) as latest_archive
                  FROM archived_analytics_logs 
                  WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $dateStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats = array_merge($stats, $dateStats);
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting archive statistics: " . $e->getMessage());
        return [];
    }
}

/**
 * Get archive settings
 */
function getArchiveSettings($conn) {
    try {
        $query = "SELECT setting_key, setting_value, setting_type, description 
                  FROM archive_settings 
                  ORDER BY setting_key";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to associative array for easier access
        $settingsArray = [];
        foreach ($settings as $setting) {
            $value = $setting['setting_value'];
            
            // Convert based on type
            switch ($setting['setting_type']) {
                case 'number':
                    $value = (int)$value;
                    break;
                case 'boolean':
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
                // string is default, no conversion needed
            }
            
            $settingsArray[$setting['setting_key']] = [
                'value' => $value,
                'type' => $setting['setting_type'],
                'description' => $setting['description']
            ];
        }
        
        return $settingsArray;
        
    } catch (Exception $e) {
        error_log("Error getting archive settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Export archived logs to different formats
 */
function exportArchivedLogs($conn, $userId, $format = 'json', $dateFrom = '', $dateTo = '') {
    try {
        // Build WHERE conditions
        $whereConditions = ["user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if (!empty($dateFrom)) {
            $whereConditions[] = "DATE(original_created_at) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        
        if (!empty($dateTo)) {
            $whereConditions[] = "DATE(original_created_at) <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $query = "SELECT * FROM archived_analytics_logs WHERE {$whereClause} ORDER BY original_created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($logs)) {
            return [
                'success' => false,
                'error' => 'No archived logs found for export'
            ];
        }
        
        // Create exports directory if it doesn't exist
        $exportDir = '../exports/';
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $filename = "archived_logs_" . $userId . "_" . date('Y-m-d_H-i-s');
        $filepath = $exportDir . $filename;
        
        switch (strtolower($format)) {
            case 'csv':
                $filepath .= '.csv';
                $result = exportToCSV($logs, $filepath);
                break;
            case 'json':
            default:
                $filepath .= '.json';
                $result = exportToJSON($logs, $filepath);
                break;
        }
        
        if ($result) {
            return [
                'success' => true,
                'download_url' => str_replace('../', '', $filepath),
                'file_size' => filesize($filepath),
                'record_count' => count($logs)
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to create export file'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error exporting archived logs: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Export logs to JSON format
 */
function exportToJSON($logs, $filepath) {
    $jsonData = json_encode($logs, JSON_PRETTY_PRINT);
    return file_put_contents($filepath, $jsonData) !== false;
}

/**
 * Export logs to CSV format
 */
function exportToCSV($logs, $filepath) {
    $file = fopen($filepath, 'w');
    
    if (!$file) {
        return false;
    }
    
    // Write CSV header
    $headers = ['ID', 'Original ID', 'Event Type', 'User ID', 'Related ID', 'Entity Type', 'Details', 'Original Created At', 'Archived At', 'Archived By', 'Archive Reason'];
    fputcsv($file, $headers);
    
    // Write data rows
    foreach ($logs as $log) {
        $row = [
            $log['id'],
            $log['original_id'],
            $log['event_type'],
            $log['user_id'],
            $log['related_id'],
            $log['entity_type'],
            $log['details'],
            $log['original_created_at'],
            $log['archived_at'],
            $log['archived_by'],
            $log['archive_reason']
        ];
        fputcsv($file, $row);
    }
    
    fclose($file);
    return true;
}
?> 