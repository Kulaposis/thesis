<?php
/**
 * Analytics Functions for Thesis Management System
 * This file contains functions for tracking, analyzing, and reporting on thesis management data
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Log an analytics event
 * 
 * @param string $eventType Type of event (e.g., 'login', 'submission', 'feedback')
 * @param int $userId User ID who performed the action
 * @param int|null $relatedId Related entity ID (e.g., thesis_id, chapter_id)
 * @param string $entityType Type of related entity (e.g., 'thesis', 'chapter', 'feedback')
 * @param array|null $details Additional details about the event
 * @return bool Success status
 */
function logAnalyticsEvent($eventType, $userId, $relatedId = null, $entityType = '', $details = null) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "INSERT INTO analytics_logs 
              (event_type, user_id, related_id, entity_type, details) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    
    $detailsJson = $details ? json_encode($details) : null;
    
    return $stmt->execute([$eventType, $userId, $relatedId, $entityType, $detailsJson]);
}

/**
 * Calculate and store adviser metrics
 * 
 * @param int $adviserId Adviser ID
 * @param string $timePeriod Time period ('daily', 'weekly', 'monthly', 'yearly')
 * @return bool Success status
 */
function calculateAdviserMetrics($adviserId, $timePeriod = 'monthly') {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Define date ranges based on time period
    $dates = getDateRangeForPeriod($timePeriod);
    $startDate = $dates['start_date'];
    $endDate = $dates['end_date'];
    
    // Calculate metrics
    $metrics = [
        // Number of students assigned
        'student_count' => getAdviserStudentCount($conn, $adviserId),
        
        // Average response time for feedback (in hours)
        'avg_response_time' => getAdviserResponseTime($conn, $adviserId, $startDate, $endDate),
        
        // Number of chapters reviewed
        'chapters_reviewed' => getAdviserChaptersReviewed($conn, $adviserId, $startDate, $endDate),
        
        // Feedback quality (based on feedback length and detail)
        'feedback_quality' => getAdviserFeedbackQuality($conn, $adviserId, $startDate, $endDate)
    ];
    
    // Store each metric
    foreach ($metrics as $metricName => $metricValue) {
        if ($metricValue !== null) {
            $query = "INSERT INTO adviser_metrics 
                      (adviser_id, metric_name, metric_value, time_period, start_date, end_date) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$adviserId, $metricName, $metricValue, $timePeriod, $startDate, $endDate]);
        }
    }
    
    return true;
}

/**
 * Calculate and store student metrics
 * 
 * @param int $studentId Student ID
 * @param string $timePeriod Time period ('daily', 'weekly', 'monthly', 'yearly')
 * @return bool Success status
 */
function calculateStudentMetrics($studentId, $timePeriod = 'monthly') {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Define date ranges based on time period
    $dates = getDateRangeForPeriod($timePeriod);
    $startDate = $dates['start_date'];
    $endDate = $dates['end_date'];
    
    // Calculate metrics
    $metrics = [
        // Submission timeliness (percentage of on-time submissions)
        'submission_timeliness' => getStudentSubmissionTimeliness($conn, $studentId, $startDate, $endDate),
        
        // Revision frequency (average number of revisions per chapter)
        'revision_frequency' => getStudentRevisionFrequency($conn, $studentId, $startDate, $endDate),
        
        // Response time to feedback (average days between feedback and resubmission)
        'feedback_response_time' => getStudentFeedbackResponseTime($conn, $studentId, $startDate, $endDate),
        
        // Progress rate (percentage progress increase per month)
        'progress_rate' => getStudentProgressRate($conn, $studentId, $startDate, $endDate)
    ];
    
    // Store each metric
    foreach ($metrics as $metricName => $metricValue) {
        if ($metricValue !== null) {
            $query = "INSERT INTO student_metrics 
                      (student_id, metric_name, metric_value, time_period, start_date, end_date) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$studentId, $metricName, $metricValue, $timePeriod, $startDate, $endDate]);
        }
    }
    
    return true;
}

/**
 * Calculate and store thesis metrics
 * 
 * @param int $thesisId Thesis ID
 * @return bool Success status
 */
function calculateThesisMetrics($thesisId) {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Calculate metrics
    $metrics = [
        // Completion percentage
        'completion_percentage' => getThesisCompletionPercentage($conn, $thesisId),
        
        // Average days per milestone
        'avg_days_per_milestone' => getThesisAvgDaysPerMilestone($conn, $thesisId),
        
        // Feedback density (average feedback items per chapter)
        'feedback_density' => getThesisFeedbackDensity($conn, $thesisId),
        
        // Revision count (total number of revisions across all chapters)
        'revision_count' => getThesisRevisionCount($conn, $thesisId)
    ];
    
    // Store each metric
    foreach ($metrics as $metricName => $metricValue) {
        if ($metricValue !== null) {
            $query = "INSERT INTO thesis_metrics 
                      (thesis_id, metric_name, metric_value) 
                      VALUES (?, ?, ?)
                      ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value)";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$thesisId, $metricName, $metricValue]);
        }
    }
    
    return true;
}

/**
 * Calculate and store department metrics
 * 
 * @param string $department Department name
 * @param string $timePeriod Time period ('daily', 'weekly', 'monthly', 'yearly')
 * @return bool Success status
 */
function calculateDepartmentMetrics($department, $timePeriod = 'monthly') {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Define date ranges based on time period
    $dates = getDateRangeForPeriod($timePeriod);
    $startDate = $dates['start_date'];
    $endDate = $dates['end_date'];
    
    // Calculate metrics
    $metrics = [
        // Thesis completion rate
        'completion_rate' => getDepartmentCompletionRate($conn, $department, $startDate, $endDate),
        
        // Average thesis progress
        'avg_thesis_progress' => getDepartmentAvgThesisProgress($conn, $department),
        
        // Average time to completion (in days)
        'avg_completion_time' => getDepartmentAvgCompletionTime($conn, $department, $startDate, $endDate),
        
        // Student-adviser ratio
        'student_adviser_ratio' => getDepartmentStudentAdviserRatio($conn, $department)
    ];
    
    // Store each metric
    foreach ($metrics as $metricName => $metricValue) {
        if ($metricValue !== null) {
            $query = "INSERT INTO department_metrics 
                      (department, metric_name, metric_value, time_period, start_date, end_date) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$department, $metricName, $metricValue, $timePeriod, $startDate, $endDate]);
        }
    }
    
    return true;
}

/**
 * Analyze feedback sentiment and categories
 * 
 * @param int $feedbackId Feedback ID
 * @return bool Success status
 */
function analyzeFeedback($feedbackId) {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get feedback text
    $query = "SELECT feedback_text FROM feedback WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$feedbackId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return false;
    }
    
    $feedbackText = $result['feedback_text'];
    
    // Simple sentiment analysis (more sophisticated analysis could be implemented)
    $sentimentScore = calculateSentiment($feedbackText);
    
    // Simple categorization (more sophisticated categorization could be implemented)
    $category = categorizeFeedback($feedbackText);
    
    // Extract keywords
    $keywords = extractKeywords($feedbackText);
    
    // Store analysis results
    $query = "INSERT INTO feedback_analysis 
              (feedback_id, sentiment_score, category, keywords) 
              VALUES (?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE 
              sentiment_score = VALUES(sentiment_score), 
              category = VALUES(category), 
              keywords = VALUES(keywords)";
    
    $stmt = $conn->prepare($query);
    return $stmt->execute([$feedbackId, $sentimentScore, $category, json_encode($keywords)]);
}

/**
 * Get all available report templates
 * 
 * @return array Report templates
 */
function getReportTemplates() {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT id, name, description, chart_type FROM report_templates ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Generate a report based on a template
 * 
 * @param int $templateId Template ID
 * @param array $parameters Parameters for the report query
 * @return array|bool Report data or false on failure
 */
function generateReport($templateId, $parameters = []) {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get template details
    $query = "SELECT * FROM report_templates WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        return false;
    }
    
    // Prepare and execute the report query
    $reportQuery = $template['query'];
    $stmt = $conn->prepare($reportQuery);
    
    // Bind parameters if provided
    if (!empty($parameters) && isset($template['parameters'])) {
        $templateParams = json_decode($template['parameters'], true);
        if (isset($templateParams['params']) && is_array($templateParams['params'])) {
            foreach ($templateParams['params'] as $index => $param) {
                if (isset($parameters[$index])) {
                    $stmt->bindValue($index + 1, $parameters[$index]);
                } else {
                    $stmt->bindValue($index + 1, $param);
                }
            }
        }
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'template' => $template,
        'data' => $data,
        'parameters_used' => $parameters
    ];
}

/**
 * Save a report for future reference
 * 
 * @param int $userId User ID
 * @param int $templateId Template ID
 * @param string $name Report name
 * @param string $description Report description
 * @param array $reportData Report data
 * @param array $parametersUsed Parameters used for the report
 * @return array Success status and message/error
 */
function saveReport($userId, $templateId, $name, $description, $reportData, $parametersUsed = []) {
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Validate required fields
        if (!$userId || !$name || !$reportData) {
            return [
                'success' => false,
                'error' => 'Missing required fields'
            ];
        }
        
        // Validate template exists if template_id is provided
        if ($templateId) {
            $stmt = $conn->prepare("SELECT id FROM report_templates WHERE id = ?");
            $stmt->execute([$templateId]);
            if (!$stmt->fetch()) {
                return [
                    'success' => false,
                    'error' => 'Invalid template ID'
                ];
            }
        }
        
        // Validate user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            return [
                'success' => false,
                'error' => 'Invalid user ID'
            ];
        }
        
        // Validate and encode JSON data
        $reportDataJson = json_encode($reportData);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid report data format: ' . json_last_error_msg()
            ];
        }
        
        $parametersUsedJson = json_encode($parametersUsed);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid parameters format: ' . json_last_error_msg()
            ];
        }
        
        // Insert the report
        $query = "INSERT INTO saved_reports 
                  (user_id, template_id, name, description, report_data, parameters_used) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            $userId,
            $templateId,
            $name,
            $description,
            $reportDataJson,
            $parametersUsedJson
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'report_id' => $conn->lastInsertId(),
                'message' => 'Report saved successfully'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to save report'
            ];
        }
    } catch (PDOException $e) {
        error_log('Error saving report: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log('Error saving report: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Unexpected error: ' . $e->getMessage()
        ];
    }
}

/**
 * Get saved reports for a user
 * 
 * @param int $userId User ID
 * @return array Saved reports
 */
function getSavedReports($userId) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT id, name, description, report_data, created_at FROM saved_reports WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper functions

/**
 * Get date range for a given time period
 * 
 * @param string $timePeriod Time period ('daily', 'weekly', 'monthly', 'yearly')
 * @return array Start and end dates
 */
function getDateRangeForPeriod($timePeriod) {
    $endDate = date('Y-m-d');
    $startDate = '';
    
    switch ($timePeriod) {
        case 'daily':
            $startDate = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'weekly':
            $startDate = date('Y-m-d', strtotime('-1 week'));
            break;
        case 'monthly':
            $startDate = date('Y-m-d', strtotime('-1 month'));
            break;
        case 'yearly':
            $startDate = date('Y-m-d', strtotime('-1 year'));
            break;
        default:
            $startDate = date('Y-m-d', strtotime('-1 month'));
    }
    
    return [
        'start_date' => $startDate,
        'end_date' => $endDate
    ];
}

// Adviser metric calculation functions

function getAdviserStudentCount($conn, $adviserId) {
    $query = "SELECT COUNT(DISTINCT student_id) as count 
              FROM theses 
              WHERE adviser_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$adviserId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['count'] : 0;
}

function getAdviserResponseTime($conn, $adviserId, $startDate, $endDate) {
    // Calculate average time between chapter submission and feedback
    $query = "SELECT AVG(TIMESTAMPDIFF(HOUR, c.submitted_at, f.created_at)) as avg_hours 
              FROM feedback f 
              JOIN chapters c ON f.chapter_id = c.id 
              WHERE f.adviser_id = ? 
              AND c.submitted_at IS NOT NULL 
              AND f.created_at BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$adviserId, $startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['avg_hours'] !== null ? $result['avg_hours'] : null;
}

function getAdviserChaptersReviewed($conn, $adviserId, $startDate, $endDate) {
    $query = "SELECT COUNT(DISTINCT chapter_id) as count 
              FROM feedback 
              WHERE adviser_id = ? 
              AND created_at BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$adviserId, $startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['count'] : 0;
}

function getAdviserFeedbackQuality($conn, $adviserId, $startDate, $endDate) {
    // Simple quality metric based on feedback length and detail
    $query = "SELECT AVG(LENGTH(feedback_text)) as avg_length 
              FROM feedback 
              WHERE adviser_id = ? 
              AND created_at BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$adviserId, $startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Normalize to a 0-10 scale (assuming average feedback length between 0-1000 characters)
    if ($result && $result['avg_length'] !== null) {
        return min(10, $result['avg_length'] / 100);
    }
    
    return null;
}

// Student metric calculation functions

function getStudentSubmissionTimeliness($conn, $studentId, $startDate, $endDate) {
    // Calculate percentage of on-time submissions
    $query = "SELECT 
                COUNT(*) as total_milestones,
                SUM(CASE WHEN completed_date <= due_date THEN 1 ELSE 0 END) as on_time_count
              FROM timeline t
              JOIN theses th ON t.thesis_id = th.id
              WHERE th.student_id = ?
              AND t.completed_date IS NOT NULL
              AND t.completed_date BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$studentId, $startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['total_milestones'] > 0) {
        return ($result['on_time_count'] / $result['total_milestones']) * 100;
    }
    
    return null;
}

function getStudentRevisionFrequency($conn, $studentId, $startDate, $endDate) {
    // Count file uploads per chapter
    $query = "SELECT AVG(upload_count) as avg_revisions
              FROM (
                SELECT c.id, COUNT(f.id) as upload_count
                FROM chapters c
                JOIN theses t ON c.thesis_id = t.id
                LEFT JOIN file_uploads f ON c.id = f.chapter_id
                WHERE t.student_id = ?
                AND f.uploaded_at BETWEEN ? AND ?
                GROUP BY c.id
              ) as chapter_uploads";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$studentId, $startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['avg_revisions'] !== null ? $result['avg_revisions'] : null;
}

function getStudentFeedbackResponseTime($conn, $studentId, $startDate, $endDate) {
    // Calculate average days between feedback and next submission
    // This is a complex metric that would require tracking feedback and subsequent uploads
    // Simplified version:
    return null; // Placeholder for more complex implementation
}

function getStudentProgressRate($conn, $studentId, $startDate, $endDate) {
    // Calculate progress percentage change over the period
    $query = "SELECT 
                MAX(progress_percentage) - MIN(progress_percentage) as progress_change
              FROM theses
              WHERE student_id = ?
              AND updated_at BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$studentId, $startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['progress_change'] !== null ? $result['progress_change'] : 0;
}

// Thesis metric calculation functions

function getThesisCompletionPercentage($conn, $thesisId) {
    $query = "SELECT progress_percentage FROM theses WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$thesisId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['progress_percentage'] : null;
}

function getThesisAvgDaysPerMilestone($conn, $thesisId) {
    $query = "SELECT 
                AVG(DATEDIFF(completed_date, due_date)) as avg_days
              FROM timeline
              WHERE thesis_id = ?
              AND completed_date IS NOT NULL";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$thesisId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['avg_days'] !== null ? $result['avg_days'] : null;
}

function getThesisFeedbackDensity($conn, $thesisId) {
    $query = "SELECT 
                COUNT(f.id) / COUNT(DISTINCT c.id) as feedback_density
              FROM chapters c
              LEFT JOIN feedback f ON c.id = f.chapter_id
              WHERE c.thesis_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$thesisId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['feedback_density'] !== null ? $result['feedback_density'] : null;
}

function getThesisRevisionCount($conn, $thesisId) {
    $query = "SELECT 
                COUNT(f.id) as revision_count
              FROM chapters c
              JOIN file_uploads f ON c.id = f.chapter_id
              WHERE c.thesis_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$thesisId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['revision_count'] : 0;
}

// Department metric calculation functions

function getDepartmentCompletionRate($conn, $department, $startDate, $endDate) {
    $query = "SELECT 
                COUNT(CASE WHEN t.status = 'approved' THEN 1 END) / COUNT(*) * 100 as completion_rate
              FROM theses t
              JOIN users u ON t.student_id = u.id
              WHERE u.department = ?
              AND t.updated_at BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$department, $startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['completion_rate'] !== null ? $result['completion_rate'] : null;
}

function getDepartmentAvgThesisProgress($conn, $department) {
    $query = "SELECT 
                AVG(t.progress_percentage) as avg_progress
              FROM theses t
              JOIN users u ON t.student_id = u.id
              WHERE u.department = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$department]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['avg_progress'] !== null ? $result['avg_progress'] : null;
}

function getDepartmentAvgCompletionTime($conn, $department, $startDate, $endDate) {
    // Calculate average days from thesis creation to approval
    $query = "SELECT 
                AVG(DATEDIFF(t.updated_at, t.created_at)) as avg_days
              FROM theses t
              JOIN users u ON t.student_id = u.id
              WHERE u.department = ?
              AND t.status = 'approved'
              AND t.updated_at BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$department, $startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['avg_days'] !== null ? $result['avg_days'] : null;
}

function getDepartmentStudentAdviserRatio($conn, $department) {
    $query = "SELECT 
                (SELECT COUNT(*) FROM users WHERE department = ? AND role = 'student') /
                NULLIF((SELECT COUNT(*) FROM users WHERE department = ? AND role = 'adviser'), 0) as ratio";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$department, $department]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['ratio'] !== null ? $result['ratio'] : null;
}

// Text analysis helper functions

function calculateSentiment($text) {
    // Simple sentiment analysis based on positive and negative word counts
    // In a real implementation, you would use a more sophisticated NLP library
    
    $positiveWords = ['good', 'great', 'excellent', 'well', 'clear', 'concise', 'impressive', 'strong', 'thorough'];
    $negativeWords = ['poor', 'bad', 'unclear', 'weak', 'confusing', 'insufficient', 'inadequate', 'revise', 'incorrect'];
    
    $text = strtolower($text);
    $positiveCount = 0;
    $negativeCount = 0;
    
    foreach ($positiveWords as $word) {
        $positiveCount += substr_count($text, $word);
    }
    
    foreach ($negativeWords as $word) {
        $negativeCount += substr_count($text, $word);
    }
    
    $totalWords = str_word_count($text);
    
    if ($totalWords > 0) {
        // Calculate score from -1 to 1
        return ($positiveCount - $negativeCount) / ($positiveCount + $negativeCount + 0.001);
    }
    
    return 0;
}

function categorizeFeedback($text) {
    // Simple categorization based on keyword presence
    // In a real implementation, you would use a more sophisticated NLP library
    
    $text = strtolower($text);
    
    $categories = [
        'structure' => ['structure', 'organization', 'flow', 'layout', 'format'],
        'content' => ['content', 'substance', 'argument', 'evidence', 'data', 'analysis'],
        'language' => ['grammar', 'spelling', 'language', 'writing', 'sentence', 'paragraph'],
        'methodology' => ['methodology', 'method', 'approach', 'design', 'experiment', 'sample'],
        'references' => ['reference', 'citation', 'source', 'bibliography', 'literature']
    ];
    
    $categoryScores = [];
    
    foreach ($categories as $category => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            $score += substr_count($text, $keyword);
        }
        $categoryScores[$category] = $score;
    }
    
    // Find category with highest score
    $maxScore = 0;
    $mainCategory = 'general';
    
    foreach ($categoryScores as $category => $score) {
        if ($score > $maxScore) {
            $maxScore = $score;
            $mainCategory = $category;
        }
    }
    
    return $mainCategory;
}

function extractKeywords($text) {
    // Simple keyword extraction based on word frequency
    // In a real implementation, you would use a more sophisticated NLP library
    
    $text = strtolower($text);
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
    
    $stopWords = ['the', 'and', 'a', 'to', 'of', 'in', 'is', 'that', 'it', 'with', 'as', 'for', 'on', 'was', 'be', 'this', 'have', 'are', 'you', 'your'];
    $words = str_word_count($text, 1);
    $wordFreq = array_count_values($words);
    
    // Remove stop words
    foreach ($stopWords as $stopWord) {
        if (isset($wordFreq[$stopWord])) {
            unset($wordFreq[$stopWord]);
        }
    }
    
    // Sort by frequency
    arsort($wordFreq);
    
    // Take top 5 keywords
    return array_slice(array_keys($wordFreq), 0, 5);
}

/**
 * Get chapter submission statistics by chapter number
 * Shows how many students have submitted each chapter
 */
function getChapterSubmissionStats($conn, $status = 'all') {
    $statusCondition = '';
    if ($status !== 'all') {
        $statusCondition = "AND c.status = '$status'";
    }
    
    $query = "SELECT 
                c.chapter_number,
                COUNT(DISTINCT c.id) as total_chapters,
                COUNT(DISTINCT CASE WHEN c.status IN ('submitted', 'approved') THEN c.id END) as submitted_count,
                COUNT(DISTINCT CASE WHEN c.status = 'approved' THEN c.id END) as approved_count,
                COUNT(DISTINCT CASE WHEN c.status = 'rejected' THEN c.id END) as rejected_count,
                COUNT(DISTINCT t.student_id) as total_students,
                ROUND((COUNT(DISTINCT CASE WHEN c.status IN ('submitted', 'approved') THEN c.id END) / COUNT(DISTINCT t.student_id)) * 100, 1) as submission_percentage,
                ROUND((COUNT(DISTINCT CASE WHEN c.status = 'approved' THEN c.id END) / COUNT(DISTINCT CASE WHEN c.status IN ('submitted', 'approved') THEN c.id END)) * 100, 1) as approval_rate
              FROM chapters c
              JOIN theses t ON c.thesis_id = t.id
              $statusCondition
              GROUP BY c.chapter_number
              ORDER BY c.chapter_number";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get detailed student progress across all chapters
 */
function getStudentChapterProgress($conn) {
    $query = "SELECT 
                u.full_name as student_name,
                u.student_id,
                COUNT(DISTINCT c.id) as total_chapters_created,
                COUNT(DISTINCT CASE WHEN c.status IN ('submitted', 'approved') THEN c.id END) as chapters_submitted,
                COUNT(DISTINCT CASE WHEN c.status = 'approved' THEN c.id END) as chapters_approved,
                COUNT(DISTINCT CASE WHEN c.chapter_number = 1 AND c.status IN ('submitted', 'approved') THEN c.id END) as chapter_1_status,
                COUNT(DISTINCT CASE WHEN c.chapter_number = 2 AND c.status IN ('submitted', 'approved') THEN c.id END) as chapter_2_status,
                COUNT(DISTINCT CASE WHEN c.chapter_number = 3 AND c.status IN ('submitted', 'approved') THEN c.id END) as chapter_3_status,
                COUNT(DISTINCT CASE WHEN c.chapter_number = 4 AND c.status IN ('submitted', 'approved') THEN c.id END) as chapter_4_status,
                COUNT(DISTINCT CASE WHEN c.chapter_number = 5 AND c.status IN ('submitted', 'approved') THEN c.id END) as chapter_5_status,
                t.progress_percentage,
                t.status as thesis_status,
                COALESCE(ua.full_name, 'Not Assigned') as adviser_name
              FROM users u
              JOIN theses t ON u.id = t.student_id
              LEFT JOIN chapters c ON t.id = c.thesis_id
              LEFT JOIN users ua ON t.adviser_id = ua.id
              WHERE u.role = 'student'
              GROUP BY u.id, u.full_name, u.student_id, t.progress_percentage, t.status, ua.full_name
              ORDER BY u.full_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get submission timeline analysis
 */
function getSubmissionTimelineAnalysis($conn, $period = '12 months') {
    $query = "SELECT 
                DATE_FORMAT(c.submitted_at, '%Y-%m') as submission_month,
                COUNT(*) as total_submissions,
                COUNT(DISTINCT c.chapter_number) as unique_chapters,
                COUNT(DISTINCT t.student_id) as active_students,
                c.chapter_number,
                COUNT(*) as chapter_submissions
              FROM chapters c
              JOIN theses t ON c.thesis_id = t.id
              WHERE c.submitted_at IS NOT NULL
              AND c.submitted_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(c.submitted_at, '%Y-%m'), c.chapter_number
              ORDER BY submission_month DESC, c.chapter_number";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get adviser performance metrics with chapter-specific data
 */
function getAdviserChapterMetrics($conn) {
    $query = "SELECT 
                COALESCE(ua.full_name, 'Unassigned') as adviser_name,
                ua.faculty_id,
                COUNT(DISTINCT t.student_id) as total_students,
                COUNT(DISTINCT c.id) as total_chapters_reviewed,
                COUNT(DISTINCT CASE WHEN c.status = 'approved' THEN c.id END) as chapters_approved,
                COUNT(DISTINCT f.id) as total_feedback_given,
                ROUND(AVG(LENGTH(f.feedback_text)), 0) as avg_feedback_length,
                ROUND(AVG(TIMESTAMPDIFF(HOUR, c.submitted_at, f.created_at)), 1) as avg_response_time_hours,
                ROUND((COUNT(DISTINCT CASE WHEN c.status = 'approved' THEN c.id END) / COUNT(DISTINCT c.id)) * 100, 1) as approval_rate,
                COUNT(DISTINCT CASE WHEN t.status = 'approved' THEN t.id END) as completed_theses,
                ROUND(AVG(t.progress_percentage), 1) as avg_student_progress
              FROM users ua
              LEFT JOIN theses t ON ua.id = t.adviser_id
              LEFT JOIN chapters c ON t.id = c.thesis_id AND c.status IN ('submitted', 'approved', 'rejected')
              LEFT JOIN feedback f ON c.id = f.chapter_id
              WHERE ua.role = 'adviser'
              GROUP BY ua.id, ua.full_name, ua.faculty_id
              HAVING total_students > 0
              ORDER BY total_students DESC, approval_rate DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get department/program performance comparison
 */
function getDepartmentPerformanceComparison($conn) {
    $query = "SELECT 
                COALESCE(u.department, u.program, 'Not Specified') as department_program,
                COUNT(DISTINCT u.id) as total_students,
                COUNT(DISTINCT t.id) as total_theses,
                COUNT(DISTINCT CASE WHEN t.status = 'approved' THEN t.id END) as completed_theses,
                ROUND(AVG(t.progress_percentage), 1) as avg_progress,
                COUNT(DISTINCT c.id) as total_chapters,
                COUNT(DISTINCT CASE WHEN c.status = 'approved' THEN c.id END) as approved_chapters,
                ROUND((COUNT(DISTINCT CASE WHEN t.status = 'approved' THEN t.id END) / COUNT(DISTINCT t.id)) * 100, 1) as completion_rate,
                ROUND((COUNT(DISTINCT CASE WHEN c.status = 'approved' THEN c.id END) / COUNT(DISTINCT c.id)) * 100, 1) as chapter_approval_rate
              FROM users u
              JOIN theses t ON u.id = t.student_id
              LEFT JOIN chapters c ON t.id = c.thesis_id
              WHERE u.role = 'student'
              GROUP BY COALESCE(u.department, u.program, 'Not Specified')
              HAVING total_students > 0
              ORDER BY completion_rate DESC, avg_progress DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get thesis milestone analysis
 */
function getThesisMilestoneAnalysis($conn) {
    $query = "SELECT 
                tm.milestone_name,
                COUNT(*) as total_milestones,
                COUNT(CASE WHEN tm.status = 'completed' THEN 1 END) as completed_count,
                COUNT(CASE WHEN tm.status = 'overdue' THEN 1 END) as overdue_count,
                COUNT(CASE WHEN tm.status = 'in_progress' THEN 1 END) as in_progress_count,
                ROUND((COUNT(CASE WHEN tm.status = 'completed' THEN 1 END) / COUNT(*)) * 100, 1) as completion_rate,
                ROUND(AVG(CASE WHEN tm.completed_date IS NOT NULL THEN DATEDIFF(tm.completed_date, tm.due_date) END), 1) as avg_delay_days
              FROM timeline tm
              JOIN theses t ON tm.thesis_id = t.id
              GROUP BY tm.milestone_name
              ORDER BY completion_rate DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get feedback analysis by category and sentiment
 */
function getFeedbackAnalysisSummary($conn) {
    $query = "SELECT 
                f.feedback_type,
                COUNT(*) as feedback_count,
                ROUND(AVG(LENGTH(f.feedback_text)), 0) as avg_length,
                COUNT(DISTINCT c.chapter_number) as chapters_affected,
                COUNT(DISTINCT t.student_id) as students_affected,
                ROUND(AVG(CASE WHEN fa.sentiment_score IS NOT NULL THEN fa.sentiment_score END), 2) as avg_sentiment_score
              FROM feedback f
              JOIN chapters c ON f.chapter_id = c.id
              JOIN theses t ON c.thesis_id = t.id
              LEFT JOIN feedback_analysis fa ON f.id = fa.feedback_id
              GROUP BY f.feedback_type
              ORDER BY feedback_count DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get recent activity dashboard data
 */
function getRecentActivityAnalysis($conn, $days = 30) {
    $query = "SELECT 
                'Chapter Submission' as activity_type,
                DATE(c.submitted_at) as activity_date,
                COUNT(*) as activity_count,
                GROUP_CONCAT(DISTINCT CONCAT(u.full_name, ' (Ch. ', c.chapter_number, ')') SEPARATOR ', ') as details
              FROM chapters c
              JOIN theses t ON c.thesis_id = t.id
              JOIN users u ON t.student_id = u.id
              WHERE c.submitted_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              GROUP BY DATE(c.submitted_at)
              
              UNION ALL
              
              SELECT 
                'Feedback Given' as activity_type,
                DATE(f.created_at) as activity_date,
                COUNT(*) as activity_count,
                GROUP_CONCAT(DISTINCT ua.full_name SEPARATOR ', ') as details
              FROM feedback f
              JOIN chapters c ON f.chapter_id = c.id
              JOIN theses t ON c.thesis_id = t.id
              JOIN users ua ON f.adviser_id = ua.id
              WHERE f.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              GROUP BY DATE(f.created_at)
              
              UNION ALL
              
              SELECT 
                'Comment Activity' as activity_type,
                DATE(al.created_at) as activity_date,
                COUNT(*) as activity_count,
                GROUP_CONCAT(DISTINCT CONCAT(
                  ua.full_name, ' - ', 
                  JSON_UNQUOTE(JSON_EXTRACT(al.details, '$.action')),
                  ' on Ch. ',
                  JSON_UNQUOTE(JSON_EXTRACT(al.details, '$.chapter_number'))
                ) SEPARATOR ', ') as details
              FROM analytics_logs al
              JOIN users ua ON al.user_id = ua.id
              WHERE al.event_type IN ('comment_activity', 'highlight_activity')
                AND al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              GROUP BY DATE(al.created_at)
              
              ORDER BY activity_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$days, $days, $days]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Log comment-related activities
 * 
 * @param string $commentAction Type of comment action (add_comment, resolve_comment, edit_comment, delete_comment)
 * @param int $userId User ID who performed the action
 * @param int $commentId Comment ID
 * @param int $chapterId Chapter ID where comment was made
 * @param array|null $details Additional details about the comment action
 * @return bool Success status
 */
function logCommentActivity($commentAction, $userId, $commentId, $chapterId, $details = null) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get additional context information
        $contextQuery = "SELECT 
                            c.title as chapter_title,
                            c.chapter_number,
                            t.title as thesis_title,
                            t.student_id,
                            us.full_name as student_name,
                            ua.full_name as adviser_name
                        FROM chapters c
                        JOIN theses t ON c.thesis_id = t.id
                        JOIN users us ON t.student_id = us.id
                        JOIN users ua ON ua.id = ?
                        WHERE c.id = ?";
        
        $contextStmt = $conn->prepare($contextQuery);
        $contextStmt->execute([$userId, $chapterId]);
        $context = $contextStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$context) {
            error_log("Failed to get context for comment logging");
            return false;
        }
        
        // Prepare log details
        $logDetails = [
            'comment_id' => $commentId,
            'chapter_id' => $chapterId,
            'chapter_title' => $context['chapter_title'],
            'chapter_number' => $context['chapter_number'],
            'thesis_title' => $context['thesis_title'],
            'student_id' => $context['student_id'],
            'student_name' => $context['student_name'],
            'adviser_name' => $context['adviser_name'],
            'action' => $commentAction
        ];
        
        // Merge with additional details if provided
        if ($details) {
            $logDetails = array_merge($logDetails, $details);
        }
        
        // Log the event
        $query = "INSERT INTO analytics_logs 
                  (event_type, user_id, related_id, entity_type, details) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $detailsJson = json_encode($logDetails);
        
        $result = $stmt->execute([
            'comment_activity',
            $userId,
            $commentId,
            'comment',
            $detailsJson
        ]);
        
        if ($result) {
            error_log("Comment activity logged: $commentAction for comment ID $commentId by user $userId");
            return true;
        } else {
            error_log("Failed to log comment activity: " . implode(", ", $stmt->errorInfo()));
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error logging comment activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Log highlight-related activities
 * 
 * @param string $highlightAction Type of highlight action (add_highlight, remove_highlight)
 * @param int $userId User ID who performed the action
 * @param int $highlightId Highlight ID
 * @param int $chapterId Chapter ID where highlight was made
 * @param array|null $details Additional details about the highlight action
 * @return bool Success status
 */
function logHighlightActivity($highlightAction, $userId, $highlightId, $chapterId, $details = null) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get additional context information
        $contextQuery = "SELECT 
                            c.title as chapter_title,
                            c.chapter_number,
                            t.title as thesis_title,
                            t.student_id,
                            us.full_name as student_name,
                            ua.full_name as adviser_name
                        FROM chapters c
                        JOIN theses t ON c.thesis_id = t.id
                        JOIN users us ON t.student_id = us.id
                        JOIN users ua ON ua.id = ?
                        WHERE c.id = ?";
        
        $contextStmt = $conn->prepare($contextQuery);
        $contextStmt->execute([$userId, $chapterId]);
        $context = $contextStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$context) {
            error_log("Failed to get context for highlight logging");
            return false;
        }
        
        // Prepare log details
        $logDetails = [
            'highlight_id' => $highlightId,
            'chapter_id' => $chapterId,
            'chapter_title' => $context['chapter_title'],
            'chapter_number' => $context['chapter_number'],
            'thesis_title' => $context['thesis_title'],
            'student_id' => $context['student_id'],
            'student_name' => $context['student_name'],
            'adviser_name' => $context['adviser_name'],
            'action' => $highlightAction
        ];
        
        // Merge with additional details if provided
        if ($details) {
            $logDetails = array_merge($logDetails, $details);
        }
        
        // Log the event
        $query = "INSERT INTO analytics_logs 
                  (event_type, user_id, related_id, entity_type, details) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $detailsJson = json_encode($logDetails);
        
        $result = $stmt->execute([
            'highlight_activity',
            $userId,
            $highlightId,
            'highlight',
            $detailsJson
        ]);
        
        if ($result) {
            error_log("Highlight activity logged: $highlightAction for highlight ID $highlightId by user $userId");
            return true;
        } else {
            error_log("Failed to log highlight activity: " . implode(", ", $stmt->errorInfo()));
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error logging highlight activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get comment and highlight activity logs
 * 
 * @param int $userId User ID (adviser)
 * @param int $days Number of days to look back
 * @return array Activity logs
 */
function getCommentActivityLogs($userId, $days = 30) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT 
                    al.*,
                    DATE_FORMAT(al.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date
                  FROM analytics_logs al
                  WHERE al.user_id = ? 
                    AND al.event_type IN ('comment_activity', 'highlight_activity')
                    AND al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  ORDER BY al.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId, $days]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse the JSON details for better display
        foreach ($logs as &$log) {
            if ($log['details']) {
                $log['details_parsed'] = json_decode($log['details'], true);
            }
        }
        
        return $logs;
        
    } catch (Exception $e) {
        error_log("Error getting comment activity logs: " . $e->getMessage());
        return [];
    }
}