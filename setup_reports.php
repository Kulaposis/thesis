<?php
/**
 * Setup script for Reports & Analytics module
 * This script creates the necessary tables and populates them with sample data
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Setting up Reports & Analytics module...\n";
    
    // Create analytics_logs table
    $sql = "CREATE TABLE IF NOT EXISTS `analytics_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `event_type` varchar(50) NOT NULL,
        `user_id` int(11) NOT NULL,
        `related_id` int(11) DEFAULT NULL,
        `entity_type` varchar(50) NOT NULL,
        `details` JSON DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `event_type` (`event_type`),
        KEY `entity_type` (`entity_type`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Created analytics_logs table\n";
    
    // Create adviser_metrics table
    $sql = "CREATE TABLE IF NOT EXISTS `adviser_metrics` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `adviser_id` int(11) NOT NULL,
        `metric_name` varchar(50) NOT NULL,
        `metric_value` float NOT NULL,
        `time_period` varchar(20) NOT NULL,
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `adviser_id` (`adviser_id`),
        KEY `metric_name` (`metric_name`),
        KEY `time_period` (`time_period`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Created adviser_metrics table\n";
    
    // Create student_metrics table
    $sql = "CREATE TABLE IF NOT EXISTS `student_metrics` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `metric_name` varchar(50) NOT NULL,
        `metric_value` float NOT NULL,
        `time_period` varchar(20) NOT NULL,
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `student_id` (`student_id`),
        KEY `metric_name` (`metric_name`),
        KEY `time_period` (`time_period`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Created student_metrics table\n";
    
    // Create thesis_metrics table
    $sql = "CREATE TABLE IF NOT EXISTS `thesis_metrics` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `thesis_id` int(11) NOT NULL,
        `metric_name` varchar(50) NOT NULL,
        `metric_value` float NOT NULL,
        `calculated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `thesis_id` (`thesis_id`),
        KEY `metric_name` (`metric_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Created thesis_metrics table\n";
    
    // Create department_metrics table
    $sql = "CREATE TABLE IF NOT EXISTS `department_metrics` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `department` varchar(50) NOT NULL,
        `metric_name` varchar(50) NOT NULL,
        `metric_value` float NOT NULL,
        `time_period` varchar(20) NOT NULL,
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `department` (`department`),
        KEY `metric_name` (`metric_name`),
        KEY `time_period` (`time_period`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Created department_metrics table\n";
    
    // Create feedback_analysis table
    $sql = "CREATE TABLE IF NOT EXISTS `feedback_analysis` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `feedback_id` int(11) NOT NULL,
        `sentiment_score` float DEFAULT NULL,
        `category` varchar(50) DEFAULT NULL,
        `keywords` JSON DEFAULT NULL,
        `analyzed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `feedback_id` (`feedback_id`),
        KEY `sentiment_score` (`sentiment_score`),
        KEY `category` (`category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Created feedback_analysis table\n";
    
    // Create report_templates table
    $sql = "CREATE TABLE IF NOT EXISTS `report_templates` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `description` text,
        `query` text NOT NULL,
        `parameters` JSON DEFAULT NULL,
        `chart_type` varchar(50) DEFAULT NULL,
        `created_by` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Created report_templates table\n";
    
    // Create saved_reports table
    $sql = "CREATE TABLE IF NOT EXISTS `saved_reports` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `template_id` int(11) DEFAULT NULL,
        `name` varchar(100) NOT NULL,
        `description` text,
        `report_data` JSON NOT NULL,
        `parameters_used` JSON DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `template_id` (`template_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Created saved_reports table\n";
    
    // Check if report templates exist
    $stmt = $conn->query("SELECT COUNT(*) as count FROM report_templates");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo "Inserting sample report templates...\n";
        
        // Get a user ID for created_by (use the first adviser or create with ID 1)
        $stmt = $conn->query("SELECT id FROM users WHERE role = 'adviser' LIMIT 1");
        $adviser = $stmt->fetch(PDO::FETCH_ASSOC);
        $created_by = $adviser ? $adviser['id'] : 1;
        
        // Insert sample report templates
        $templates = [
            [
                'name' => 'Thesis Progress Overview',
                'description' => 'Shows the progress of all theses currently in the system',
                'query' => 'SELECT CONCAT(u.full_name, " - ", LEFT(t.title, 30), "...") AS thesis_info, t.progress_percentage FROM theses t JOIN users u ON t.student_id = u.id ORDER BY t.progress_percentage DESC',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Adviser Workload',
                'description' => 'Displays the number of students assigned to each adviser',
                'query' => 'SELECT COALESCE(u.full_name, "Unassigned") AS adviser_name, COUNT(t.id) AS student_count FROM users u LEFT JOIN theses t ON u.id = t.adviser_id WHERE u.role = "adviser" GROUP BY u.id, u.full_name ORDER BY student_count DESC',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Thesis Status Distribution',
                'description' => 'Shows the distribution of thesis statuses',
                'query' => 'SELECT status, COUNT(*) AS count FROM theses GROUP BY status ORDER BY count DESC',
                'parameters' => null,
                'chart_type' => 'pie'
            ],
            [
                'name' => 'Student Progress Summary',
                'description' => 'Summary of student progress across all theses',
                'query' => 'SELECT u.full_name AS student_name, t.progress_percentage FROM theses t JOIN users u ON t.student_id = u.id ORDER BY t.progress_percentage DESC',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Chapter Submission Timeline',
                'description' => 'Shows chapter submission patterns over time',
                'query' => 'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS submission_count FROM chapters WHERE created_at IS NOT NULL GROUP BY month ORDER BY month DESC LIMIT 12',
                'parameters' => null,
                'chart_type' => 'line'
            ],
            [
                'name' => 'Chapter Submission Statistics',
                'description' => 'Shows how many students have submitted each chapter (1, 2, 3, etc.)',
                'query' => 'SELECT CONCAT("Chapter ", c.chapter_number) AS chapter_name, COUNT(CASE WHEN c.status IN ("submitted", "approved") THEN 1 END) AS submitted_count, COUNT(c.id) AS total_chapters FROM chapters c GROUP BY c.chapter_number ORDER BY c.chapter_number',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Chapter Approval Rates',
                'description' => 'Shows approval rates for each chapter',
                'query' => 'SELECT CONCAT("Chapter ", c.chapter_number) AS chapter_name, ROUND((COUNT(CASE WHEN c.status = "approved" THEN 1 END) / COUNT(c.id)) * 100, 1) AS approval_rate FROM chapters c WHERE c.status IN ("submitted", "approved", "rejected") GROUP BY c.chapter_number HAVING COUNT(c.id) > 0 ORDER BY c.chapter_number',
                'parameters' => null,
                'chart_type' => 'line'
            ],
            [
                'name' => 'Student Performance by Chapter',
                'description' => 'Detailed breakdown of student submissions by chapter',
                'query' => 'SELECT u.full_name AS student_name, COUNT(CASE WHEN c.chapter_number = 1 AND c.status IN ("submitted", "approved") THEN 1 END) AS chapter_1, COUNT(CASE WHEN c.chapter_number = 2 AND c.status IN ("submitted", "approved") THEN 1 END) AS chapter_2, COUNT(CASE WHEN c.chapter_number = 3 AND c.status IN ("submitted", "approved") THEN 1 END) AS chapter_3, COUNT(CASE WHEN c.chapter_number = 4 AND c.status IN ("submitted", "approved") THEN 1 END) AS chapter_4, COUNT(CASE WHEN c.chapter_number = 5 AND c.status IN ("submitted", "approved") THEN 1 END) AS chapter_5 FROM users u JOIN theses t ON u.id = t.student_id LEFT JOIN chapters c ON t.id = c.thesis_id WHERE u.role = "student" GROUP BY u.id, u.full_name ORDER BY u.full_name',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Adviser Performance Metrics',
                'description' => 'Shows completion rates and average progress by adviser',
                'query' => 'SELECT COALESCE(u.full_name, "Unassigned") AS adviser_name, COUNT(t.id) AS total_students, COUNT(CASE WHEN t.status = "approved" THEN 1 END) AS completed_theses, ROUND(AVG(t.progress_percentage), 1) AS avg_progress, ROUND((COUNT(CASE WHEN t.status = "approved" THEN 1 END) / COUNT(t.id)) * 100, 1) AS completion_rate FROM theses t LEFT JOIN users u ON t.adviser_id = u.id GROUP BY t.adviser_id, u.full_name HAVING total_students > 0 ORDER BY completion_rate DESC, avg_progress DESC',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Chapter Status Distribution',
                'description' => 'Shows the distribution of chapter statuses across all chapters',
                'query' => 'SELECT CASE WHEN c.status = "draft" THEN "Draft" WHEN c.status = "submitted" THEN "Submitted" WHEN c.status = "approved" THEN "Approved" WHEN c.status = "rejected" THEN "Rejected" ELSE "Unknown" END AS status_name, COUNT(c.id) AS count FROM chapters c GROUP BY c.status ORDER BY count DESC',
                'parameters' => null,
                'chart_type' => 'pie'
            ],
            [
                'name' => 'Top Performing Students',
                'description' => 'Students with highest thesis progress and completion rates',
                'query' => 'SELECT u.full_name AS student_name, t.progress_percentage, t.status, COUNT(CASE WHEN c.status = "approved" THEN 1 END) AS approved_chapters FROM users u JOIN theses t ON u.id = t.student_id LEFT JOIN chapters c ON t.id = c.thesis_id WHERE u.role = "student" GROUP BY u.id, u.full_name, t.progress_percentage, t.status ORDER BY t.progress_percentage DESC, approved_chapters DESC LIMIT 15',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Chapter Submission Summary by Chapter Number',
                'description' => 'Shows detailed breakdown of how many students have submitted each chapter (1, 2, 3, etc.) with submission and approval rates',
                'query' => 'SELECT 
                            CONCAT("Chapter ", c.chapter_number) AS chapter_name,
                            COUNT(DISTINCT t.student_id) as total_students,
                            COUNT(DISTINCT CASE WHEN c.status IN ("submitted", "approved") THEN c.id END) as submitted_count,
                            COUNT(DISTINCT CASE WHEN c.status = "approved" THEN c.id END) as approved_count,
                            COUNT(DISTINCT CASE WHEN c.status = "rejected" THEN c.id END) as rejected_count,
                            ROUND((COUNT(DISTINCT CASE WHEN c.status IN ("submitted", "approved") THEN c.id END) / COUNT(DISTINCT t.student_id)) * 100, 1) as submission_percentage,
                            ROUND((COUNT(DISTINCT CASE WHEN c.status = "approved" THEN c.id END) / NULLIF(COUNT(DISTINCT CASE WHEN c.status IN ("submitted", "approved") THEN c.id END), 0)) * 100, 1) as approval_rate
                        FROM chapters c
                        JOIN theses t ON c.thesis_id = t.id
                        GROUP BY c.chapter_number
                        ORDER BY c.chapter_number',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Student Chapter Progress Matrix',
                'description' => 'Detailed view of each student\'s progress across all chapters (which chapters they have submitted)',
                'query' => 'SELECT 
                            u.full_name as student_name,
                            u.student_id,
                            COUNT(DISTINCT CASE WHEN c.chapter_number = 1 AND c.status IN ("submitted", "approved") THEN c.id END) as chapter_1,
                            COUNT(DISTINCT CASE WHEN c.chapter_number = 2 AND c.status IN ("submitted", "approved") THEN c.id END) as chapter_2,
                            COUNT(DISTINCT CASE WHEN c.chapter_number = 3 AND c.status IN ("submitted", "approved") THEN c.id END) as chapter_3,
                            COUNT(DISTINCT CASE WHEN c.chapter_number = 4 AND c.status IN ("submitted", "approved") THEN c.id END) as chapter_4,
                            COUNT(DISTINCT CASE WHEN c.chapter_number = 5 AND c.status IN ("submitted", "approved") THEN c.id END) as chapter_5,
                            COUNT(DISTINCT CASE WHEN c.status IN ("submitted", "approved") THEN c.id END) as total_submitted,
                            t.progress_percentage
                        FROM users u
                        JOIN theses t ON u.id = t.student_id
                        LEFT JOIN chapters c ON t.id = c.thesis_id
                        WHERE u.role = "student"
                        GROUP BY u.id, u.full_name, u.student_id, t.progress_percentage
                        ORDER BY total_submitted DESC, u.full_name',
                'parameters' => null,
                'chart_type' => 'table'
            ],
            [
                'name' => 'Chapter Submission Timeline Analysis',
                'description' => 'Shows submission patterns over time by month for all chapters',
                'query' => 'SELECT 
                            DATE_FORMAT(c.submitted_at, "%Y-%m") AS submission_month,
                            COUNT(*) as total_submissions,
                            COUNT(DISTINCT c.chapter_number) as unique_chapters_submitted,
                            COUNT(DISTINCT t.student_id) as active_students
                        FROM chapters c
                        JOIN theses t ON c.thesis_id = t.id
                        WHERE c.submitted_at IS NOT NULL
                        AND c.submitted_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(c.submitted_at, "%Y-%m")
                        ORDER BY submission_month DESC',
                'parameters' => null,
                'chart_type' => 'line'
            ],
            [
                'name' => 'Chapter Approval Rates by Chapter Number',
                'description' => 'Compares approval rates across different chapters to identify which chapters students struggle with most',
                'query' => 'SELECT 
                            CONCAT("Chapter ", c.chapter_number) AS chapter_name,
                            COUNT(CASE WHEN c.status IN ("submitted", "approved", "rejected") THEN 1 END) as total_reviewed,
                            COUNT(CASE WHEN c.status = "approved" THEN 1 END) as approved_count,
                            COUNT(CASE WHEN c.status = "rejected" THEN 1 END) as rejected_count,
                            ROUND((COUNT(CASE WHEN c.status = "approved" THEN 1 END) / COUNT(CASE WHEN c.status IN ("submitted", "approved", "rejected") THEN 1 END)) * 100, 1) as approval_rate
                        FROM chapters c
                        WHERE c.status IN ("submitted", "approved", "rejected")
                        GROUP BY c.chapter_number
                        HAVING total_reviewed > 0
                        ORDER BY c.chapter_number',
                'parameters' => null,
                'chart_type' => 'line'
            ],
            [
                'name' => 'Student Performance Rankings',
                'description' => 'Ranks students by their overall progress and chapter completion rates',
                'query' => 'SELECT 
                            u.full_name as student_name,
                            u.student_id,
                            COALESCE(ua.full_name, "Not Assigned") as adviser_name,
                            COUNT(DISTINCT CASE WHEN c.status IN ("submitted", "approved") THEN c.id END) as chapters_submitted,
                            COUNT(DISTINCT CASE WHEN c.status = "approved" THEN c.id END) as chapters_approved,
                            t.progress_percentage,
                            t.status as thesis_status,
                            ROUND((COUNT(DISTINCT CASE WHEN c.status = "approved" THEN c.id END) / NULLIF(COUNT(DISTINCT CASE WHEN c.status IN ("submitted", "approved") THEN c.id END), 0)) * 100, 1) as approval_rate
                        FROM users u
                        JOIN theses t ON u.id = t.student_id
                        LEFT JOIN chapters c ON t.id = c.thesis_id
                        LEFT JOIN users ua ON t.adviser_id = ua.id
                        WHERE u.role = "student"
                        GROUP BY u.id, u.full_name, u.student_id, ua.full_name, t.progress_percentage, t.status
                        ORDER BY t.progress_percentage DESC, chapters_approved DESC',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Adviser Performance Dashboard',
                'description' => 'Comprehensive view of adviser performance including response times, approval rates, and student progress',
                'query' => 'SELECT 
                            COALESCE(ua.full_name, "Unassigned") as adviser_name,
                            ua.faculty_id,
                            COUNT(DISTINCT t.student_id) as total_students,
                            COUNT(DISTINCT c.id) as total_chapters_reviewed,
                            COUNT(DISTINCT CASE WHEN c.status = "approved" THEN c.id END) as chapters_approved,
                            COUNT(DISTINCT f.id) as total_feedback_given,
                            ROUND(AVG(t.progress_percentage), 1) as avg_student_progress,
                            ROUND((COUNT(DISTINCT CASE WHEN c.status = "approved" THEN c.id END) / NULLIF(COUNT(DISTINCT c.id), 0)) * 100, 1) as chapter_approval_rate
                        FROM users ua
                        LEFT JOIN theses t ON ua.id = t.adviser_id
                        LEFT JOIN chapters c ON t.id = c.thesis_id AND c.status IN ("submitted", "approved", "rejected")
                        LEFT JOIN feedback f ON c.id = f.chapter_id
                        WHERE ua.role = "adviser"
                        GROUP BY ua.id, ua.full_name, ua.faculty_id
                        HAVING total_students > 0
                        ORDER BY total_students DESC, chapter_approval_rate DESC',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Department Performance Comparison',
                'description' => 'Compares performance across different departments/programs',
                'query' => 'SELECT 
                            COALESCE(u.department, u.program, "Not Specified") as department_program,
                            COUNT(DISTINCT u.id) as total_students,
                            COUNT(DISTINCT t.id) as total_theses,
                            COUNT(DISTINCT CASE WHEN t.status = "approved" THEN t.id END) as completed_theses,
                            ROUND(AVG(t.progress_percentage), 1) as avg_progress,
                            COUNT(DISTINCT c.id) as total_chapters,
                            COUNT(DISTINCT CASE WHEN c.status = "approved" THEN c.id END) as approved_chapters,
                            ROUND((COUNT(DISTINCT CASE WHEN t.status = "approved" THEN t.id END) / COUNT(DISTINCT t.id)) * 100, 1) as completion_rate
                        FROM users u
                        JOIN theses t ON u.id = t.student_id
                        LEFT JOIN chapters c ON t.id = c.thesis_id
                        WHERE u.role = "student"
                        GROUP BY COALESCE(u.department, u.program, "Not Specified")
                        HAVING total_students > 0
                        ORDER BY completion_rate DESC, avg_progress DESC',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Thesis Milestone Progress Tracking',
                'description' => 'Tracks progress on thesis milestones and identifies bottlenecks',
                'query' => 'SELECT 
                            tm.milestone_name,
                            COUNT(*) as total_milestones,
                            COUNT(CASE WHEN tm.status = "completed" THEN 1 END) as completed_count,
                            COUNT(CASE WHEN tm.status = "overdue" THEN 1 END) as overdue_count,
                            COUNT(CASE WHEN tm.status = "in_progress" THEN 1 END) as in_progress_count,
                            ROUND((COUNT(CASE WHEN tm.status = "completed" THEN 1 END) / COUNT(*)) * 100, 1) as completion_rate
                        FROM timeline tm
                        JOIN theses t ON tm.thesis_id = t.id
                        GROUP BY tm.milestone_name
                        ORDER BY completion_rate DESC',
                'parameters' => null,
                'chart_type' => 'pie'
            ],
            [
                'name' => 'Feedback Analysis Summary',
                'description' => 'Analyzes feedback patterns, types, and volumes across the system',
                'query' => 'SELECT 
                            f.feedback_type,
                            COUNT(*) as feedback_count,
                            ROUND(AVG(LENGTH(f.feedback_text)), 0) as avg_feedback_length,
                            COUNT(DISTINCT c.chapter_number) as chapters_with_feedback,
                            COUNT(DISTINCT t.student_id) as students_receiving_feedback
                        FROM feedback f
                        JOIN chapters c ON f.chapter_id = c.id
                        JOIN theses t ON c.thesis_id = t.id
                        GROUP BY f.feedback_type
                        ORDER BY feedback_count DESC',
                'parameters' => null,
                'chart_type' => 'pie'
            ],
            [
                'name' => 'Recent System Activity (Last 30 Days)',
                'description' => 'Shows recent activity trends including submissions, feedback, and approvals',
                'query' => 'SELECT 
                            DATE(c.submitted_at) as activity_date,
                            COUNT(*) as daily_submissions,
                            COUNT(DISTINCT t.student_id) as active_students,
                            COUNT(DISTINCT c.chapter_number) as chapters_submitted
                        FROM chapters c
                        JOIN theses t ON c.thesis_id = t.id
                        WHERE c.submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY DATE(c.submitted_at)
                        ORDER BY activity_date DESC',
                'parameters' => null,
                'chart_type' => 'line'
            ],
            [
                'name' => 'Chapter Status Distribution Overview',
                'description' => 'Overview of all chapter statuses across the system',
                'query' => 'SELECT 
                            CASE 
                                WHEN c.status = "draft" THEN "Draft"
                                WHEN c.status = "submitted" THEN "Submitted (Pending Review)"
                                WHEN c.status = "approved" THEN "Approved"
                                WHEN c.status = "rejected" THEN "Needs Revision"
                                ELSE "Unknown"
                            END as status_name,
                            COUNT(*) as chapter_count,
                            COUNT(DISTINCT t.student_id) as students_affected,
                            ROUND((COUNT(*) / (SELECT COUNT(*) FROM chapters)) * 100, 1) as percentage
                        FROM chapters c
                        JOIN theses t ON c.thesis_id = t.id
                        GROUP BY c.status
                        ORDER BY chapter_count DESC',
                'parameters' => null,
                'chart_type' => 'pie'
            ],
            [
                'name' => 'Chapter Submissions by Number',
                'description' => 'Simple breakdown showing how many students submitted Chapter 1, Chapter 2, Chapter 3, etc.',
                'query' => 'SELECT 
                            CONCAT("Chapter ", c.chapter_number) AS chapter,
                            c.chapter_number as chapter_num,
                            COUNT(DISTINCT CASE WHEN c.status IN ("submitted", "approved") THEN t.student_id END) as students_submitted,
                            COUNT(DISTINCT t.student_id) as total_students_with_chapter,
                            ROUND((COUNT(DISTINCT CASE WHEN c.status IN ("submitted", "approved") THEN t.student_id END) / COUNT(DISTINCT t.student_id)) * 100, 1) as submission_rate
                        FROM chapters c
                        JOIN theses t ON c.thesis_id = t.id
                        JOIN users u ON t.student_id = u.id
                        WHERE u.role = "student"
                        GROUP BY c.chapter_number
                        ORDER BY c.chapter_number',
                'parameters' => null,
                'chart_type' => 'bar'
            ],
            [
                'name' => 'Students per Chapter - Quick Summary',
                'description' => 'Quick overview: Chapter 1 = X students, Chapter 2 = Y students, etc.',
                'query' => 'SELECT 
                            CASE c.chapter_number
                                WHEN 1 THEN "Chapter 1"
                                WHEN 2 THEN "Chapter 2" 
                                WHEN 3 THEN "Chapter 3"
                                WHEN 4 THEN "Chapter 4"
                                WHEN 5 THEN "Chapter 5"
                                ELSE CONCAT("Chapter ", c.chapter_number)
                            END as chapter_name,
                            COUNT(DISTINCT t.student_id) as number_of_students
                        FROM chapters c
                        JOIN theses t ON c.thesis_id = t.id
                        JOIN users u ON t.student_id = u.id
                        WHERE u.role = "student" 
                        AND c.status IN ("submitted", "approved")
                        GROUP BY c.chapter_number
                        ORDER BY c.chapter_number',
                'parameters' => null,
                'chart_type' => 'bar'
            ]
        ];
        
        $stmt = $conn->prepare("INSERT INTO report_templates (name, description, query, parameters, chart_type, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($templates as $template) {
            $stmt->execute([
                $template['name'],
                $template['description'],
                $template['query'],
                $template['parameters'],
                $template['chart_type'],
                $created_by
            ]);
            echo "  ✓ Inserted template: " . $template['name'] . "\n";
        }
    } else {
        echo "✓ Report templates already exist (" . $result['count'] . " templates found)\n";
    }
    
    echo "\n🎉 Reports & Analytics module setup completed successfully!\n";
    echo "You can now use the reports functionality in your dashboard.\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 