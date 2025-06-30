-- Reports and Analytics Module for Thesis Management System
-- Version: 1.0
-- Date: 2024

USE `thesis_management`;

-- --------------------------------------------------------

--
-- Table structure for table `analytics_logs`
--

CREATE TABLE `analytics_logs` (
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
  KEY `created_at` (`created_at`),
  CONSTRAINT `analytics_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adviser_metrics`
--

CREATE TABLE `adviser_metrics` (
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
  KEY `time_period` (`time_period`),
  CONSTRAINT `adviser_metrics_ibfk_1` FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_metrics`
--

CREATE TABLE `student_metrics` (
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
  KEY `time_period` (`time_period`),
  CONSTRAINT `student_metrics_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `thesis_metrics`
--

CREATE TABLE `thesis_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thesis_id` int(11) NOT NULL,
  `metric_name` varchar(50) NOT NULL,
  `metric_value` float NOT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `thesis_id` (`thesis_id`),
  KEY `metric_name` (`metric_name`),
  CONSTRAINT `thesis_metrics_ibfk_1` FOREIGN KEY (`thesis_id`) REFERENCES `theses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_metrics`
--

CREATE TABLE `department_metrics` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_analysis`
--

CREATE TABLE `feedback_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feedback_id` int(11) NOT NULL,
  `sentiment_score` float DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `keywords` JSON DEFAULT NULL,
  `analyzed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `feedback_id` (`feedback_id`),
  KEY `sentiment_score` (`sentiment_score`),
  KEY `category` (`category`),
  CONSTRAINT `feedback_analysis_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_templates`
--

CREATE TABLE `report_templates` (
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
  KEY `created_by` (`created_by`),
  CONSTRAINT `report_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_reports`
--

CREATE TABLE `saved_reports` (
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
  KEY `template_id` (`template_id`),
  CONSTRAINT `saved_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_reports_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Insert sample report templates
--

INSERT INTO `report_templates` (`name`, `description`, `query`, `parameters`, `chart_type`, `created_by`) VALUES
('Thesis Progress Overview', 'Shows the progress of all theses currently in the system', 'SELECT t.title, t.progress_percentage, u.full_name AS student_name, u2.full_name AS adviser_name FROM theses t JOIN users u ON t.student_id = u.id LEFT JOIN users u2 ON t.adviser_id = u2.id WHERE t.status = ?', '{"params": ["in_progress"]}', 'bar', 1),
('Adviser Workload', 'Displays the number of students assigned to each adviser', 'SELECT u.full_name AS adviser_name, COUNT(t.id) AS student_count FROM users u LEFT JOIN theses t ON u.id = t.adviser_id WHERE u.role = "adviser" GROUP BY u.id ORDER BY student_count DESC', NULL, 'bar', 1),
('Submission Timeline', 'Shows the submission patterns over time', 'SELECT DATE_FORMAT(submitted_at, "%Y-%m") AS month, COUNT(*) AS submission_count FROM chapters WHERE submitted_at IS NOT NULL GROUP BY month ORDER BY month', NULL, 'line', 1),
('Department Completion Rates', 'Compares thesis completion rates across different departments', 'SELECT u.department, COUNT(CASE WHEN t.status = "approved" THEN 1 END) / COUNT(*) * 100 AS completion_rate FROM theses t JOIN users u ON t.student_id = u.id GROUP BY u.department ORDER BY completion_rate DESC', NULL, 'pie', 1),
('Feedback Analysis', 'Analyzes the types of feedback given by advisers', 'SELECT feedback_type, COUNT(*) AS count FROM feedback GROUP BY feedback_type', NULL, 'pie', 1); 