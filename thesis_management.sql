-- Thesis Management System Database
-- Created for XAMPP MySQL
-- Version: 1.0
-- Date: 2024

-- Create database
CREATE DATABASE IF NOT EXISTS `thesis_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `thesis_management`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('student','adviser') NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `faculty_id` varchar(20) DEFAULT NULL,
  `program` varchar(50) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `theses`
--

CREATE TABLE `theses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `abstract` text,
  `status` enum('draft','in_progress','for_review','approved','rejected') NOT NULL DEFAULT 'draft',
  `progress_percentage` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `adviser_id` (`adviser_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chapters`
--

CREATE TABLE `chapters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thesis_id` int(11) NOT NULL,
  `chapter_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `thesis_id` (`thesis_id`),
  UNIQUE KEY `unique_chapter` (`thesis_id`,`chapter_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chapter_id` int(11) NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `feedback_text` text NOT NULL,
  `feedback_type` enum('comment','revision','approval') NOT NULL DEFAULT 'comment',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chapter_id` (`chapter_id`),
  KEY `adviser_id` (`adviser_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timeline`
--

CREATE TABLE `timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thesis_id` int(11) NOT NULL,
  `milestone_name` varchar(100) NOT NULL,
  `description` text,
  `due_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed','overdue') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `thesis_id` (`thesis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_uploads`
--

CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chapter_id` int(11) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chapter_id` (`chapter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Add foreign key constraints
--

ALTER TABLE `theses`
  ADD CONSTRAINT `theses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `theses_ibfk_2` FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `chapters`
  ADD CONSTRAINT `chapters_ibfk_1` FOREIGN KEY (`thesis_id`) REFERENCES `theses` (`id`) ON DELETE CASCADE;

ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `timeline`
  ADD CONSTRAINT `timeline_ibfk_1` FOREIGN KEY (`thesis_id`) REFERENCES `theses` (`id`) ON DELETE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `file_uploads`
  ADD CONSTRAINT `file_uploads_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Insert sample data
--

-- Insert sample adviser
INSERT INTO `users` (`email`, `password`, `full_name`, `role`, `faculty_id`, `department`) VALUES
('adviser@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Doe', 'adviser', 'FAC001', 'Computer Science');

-- Insert sample student
INSERT INTO `users` (`email`, `password`, `full_name`, `role`, `student_id`, `program`) VALUES
('student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Smith', 'student', 'STU001', 'Computer Science');

-- Insert additional sample users for testing
INSERT INTO `users` (`email`, `password`, `full_name`, `role`, `student_id`, `program`) VALUES
('student2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Johnson', 'student', 'STU002', 'Information Technology'),
('student3@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carol Williams', 'student', 'STU003', 'Computer Science');

INSERT INTO `users` (`email`, `password`, `full_name`, `role`, `faculty_id`, `department`) VALUES
('adviser2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Jane Smith', 'adviser', 'FAC002', 'Information Technology');

-- Insert sample thesis
INSERT INTO `theses` (`student_id`, `adviser_id`, `title`, `abstract`, `status`, `progress_percentage`) VALUES
(2, 1, 'AI-Based Recommendation System', 'This thesis explores the development of an AI-based recommendation system for e-commerce platforms. The system utilizes machine learning algorithms to analyze user behavior and provide personalized product recommendations.', 'in_progress', 45),
(3, 1, 'Blockchain Technology in Supply Chain Management', 'An investigation into the application of blockchain technology for improving transparency and traceability in supply chain management systems.', 'in_progress', 30),
(4, 1, 'Mobile App Development for Educational Purposes', 'Development of a mobile application designed to enhance learning experiences in higher education through interactive features and gamification.', 'draft', 15);

-- Insert sample chapters for the first thesis
INSERT INTO `chapters` (`thesis_id`, `chapter_number`, `title`, `status`, `submitted_at`) VALUES
(1, 1, 'Introduction', 'approved', '2024-01-15 10:00:00'),
(1, 2, 'Literature Review', 'approved', '2024-02-20 14:30:00'),
(1, 3, 'Methodology', 'submitted', '2024-03-10 09:15:00'),
(1, 4, 'Implementation', 'draft', NULL),
(1, 5, 'Results and Analysis', 'draft', NULL);

-- Insert sample chapters for the second thesis
INSERT INTO `chapters` (`thesis_id`, `chapter_number`, `title`, `status`, `submitted_at`) VALUES
(2, 1, 'Introduction', 'approved', '2024-01-20 11:00:00'),
(2, 2, 'Background Study', 'submitted', '2024-03-05 15:45:00'),
(2, 3, 'System Design', 'draft', NULL);

-- Insert sample chapters for the third thesis
INSERT INTO `chapters` (`thesis_id`, `chapter_number`, `title`, `status`) VALUES
(3, 1, 'Project Overview', 'draft'),
(3, 2, 'Technical Requirements', 'draft');

-- Insert sample feedback
INSERT INTO `feedback` (`chapter_id`, `adviser_id`, `feedback_text`, `feedback_type`) VALUES
(1, 1, 'Excellent introduction. The problem statement is clear and well-defined. Consider adding more recent statistics about the current market trends.', 'approval'),
(2, 1, 'Good literature review overall. Please include more recent research papers from 2023-2024. The theoretical framework section needs more detail.', 'comment'),
(3, 1, 'The methodology section needs revision. Please clarify the data collection methods and add more details about the experimental setup.', 'revision'),
(6, 1, 'Strong background research. The blockchain concepts are well explained. Consider adding a comparison table of different blockchain platforms.', 'approval'),
(7, 1, 'The system design is comprehensive but needs more technical details. Please include system architecture diagrams and database schema.', 'revision');

-- Insert sample timeline milestones
INSERT INTO `timeline` (`thesis_id`, `milestone_name`, `due_date`, `status`, `completed_date`) VALUES
(1, 'Proposal Defense', '2024-01-10', 'completed', '2024-01-10'),
(1, 'Chapter 1-3 Submission', '2024-03-15', 'completed', '2024-03-10'),
(1, 'Chapter 4-5 Submission', '2024-05-20', 'in_progress', NULL),
(1, 'First Draft Completion', '2024-07-15', 'pending', NULL),
(1, 'Final Defense', '2024-09-30', 'pending', NULL),

(2, 'Proposal Defense', '2024-01-15', 'completed', '2024-01-15'),
(2, 'Chapter 1-2 Submission', '2024-03-20', 'in_progress', NULL),
(2, 'System Implementation', '2024-06-30', 'pending', NULL),
(2, 'Final Defense', '2024-10-15', 'pending', NULL),

(3, 'Proposal Defense', '2024-02-01', 'pending', NULL),
(3, 'Requirements Analysis', '2024-04-15', 'pending', NULL),
(3, 'Prototype Development', '2024-07-30', 'pending', NULL),
(3, 'Final Defense', '2024-11-30', 'pending', NULL);

-- Insert sample notifications
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`) VALUES
(2, 'Chapter Approved', 'Your Chapter 1: Introduction has been approved by Dr. John Doe', 'success'),
(2, 'Feedback Available', 'New feedback is available for Chapter 3: Methodology', 'info'),
(2, 'Deadline Reminder', 'Chapter 4-5 submission deadline is approaching (Due: May 20, 2024)', 'warning'),
(3, 'Chapter Approved', 'Your Chapter 1: Introduction has been approved', 'success'),
(3, 'Revision Required', 'Chapter 2: Background Study requires revision', 'warning'),
(1, 'New Submission', 'Alice Smith has submitted Chapter 3: Methodology for review', 'info'),
(1, 'Student Progress', 'Bob Johnson has updated his thesis progress', 'info');

-- --------------------------------------------------------

--
-- Update AUTO_INCREMENT values
--

ALTER TABLE `users` AUTO_INCREMENT = 6;
ALTER TABLE `theses` AUTO_INCREMENT = 4;
ALTER TABLE `chapters` AUTO_INCREMENT = 10;
ALTER TABLE `feedback` AUTO_INCREMENT = 6;
ALTER TABLE `timeline` AUTO_INCREMENT = 13;
ALTER TABLE `notifications` AUTO_INCREMENT = 8;
ALTER TABLE `file_uploads` AUTO_INCREMENT = 1;

-- --------------------------------------------------------

-- Create uploads directory (Note: This needs to be done manually on the file system)
-- CREATE DIRECTORY: uploads/ with write permissions

-- --------------------------------------------------------

--
-- Additional indexes for better performance
--

CREATE INDEX `idx_users_role` ON `users` (`role`);
CREATE INDEX `idx_theses_status` ON `theses` (`status`);
CREATE INDEX `idx_chapters_status` ON `chapters` (`status`);
CREATE INDEX `idx_feedback_type` ON `feedback` (`feedback_type`);
CREATE INDEX `idx_timeline_status` ON `timeline` (`status`);
CREATE INDEX `idx_notifications_read` ON `notifications` (`is_read`);

-- --------------------------------------------------------

COMMIT;

-- End of SQL file
-- 
-- Note: Default password for all sample accounts is 'password123'
-- The password hash used is: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
--
-- Sample login credentials:
-- Adviser: adviser@example.com / password123
-- Student: student@example.com / password123
-- Additional students: student2@example.com, student3@example.com / password123
-- Additional adviser: adviser2@example.com / password123 