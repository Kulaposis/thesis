-- Fix Admin Dashboard Database Setup
-- Run this script to fix foreign key constraint issues

USE `thesis_management`;

-- --------------------------------------------------------
-- Step 1: Drop existing admin tables if they exist (to start fresh)
-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `admin_logs`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `system_health`;
DROP TABLE IF EXISTS `user_sessions`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Step 2: Update users table to add admin roles (if not already done)
-- --------------------------------------------------------

-- Check if role column needs to be updated
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('student','adviser','admin','super_admin') NOT NULL;

-- --------------------------------------------------------
-- Step 3: Create admin tables with proper structure
-- --------------------------------------------------------

-- System settings table
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin activity logs (create AFTER ensuring users table is ready)
CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System announcements
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `target_roles` json DEFAULT NULL,
  `target_departments` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `is_active` (`is_active`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System health monitoring
CREATE TABLE `system_health` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `metric_unit` varchar(20) DEFAULT NULL,
  `status` enum('good','warning','critical') DEFAULT 'good',
  `recorded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `metric_name` (`metric_name`),
  KEY `recorded_at` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions tracking
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `logout_time` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Step 4: Create/Update admin user FIRST before adding foreign keys
-- --------------------------------------------------------

-- Check if admin user already exists, if not create it
INSERT IGNORE INTO `users` (`email`, `password`, `full_name`, `role`, `faculty_id`, `department`) 
VALUES ('admin@thesis.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 'ADMIN001', 'Information Technology');

-- Update existing admin user if it exists but role is wrong
UPDATE `users` 
SET `role` = 'super_admin', `faculty_id` = 'ADMIN001', `department` = 'Information Technology'
WHERE `email` = 'admin@thesis.edu';

-- --------------------------------------------------------
-- Step 5: Add foreign key constraints AFTER ensuring data integrity
-- --------------------------------------------------------

-- First, let's make sure there are no orphaned records
DELETE FROM `admin_logs` WHERE `admin_id` NOT IN (SELECT `id` FROM `users`);
DELETE FROM `announcements` WHERE `created_by` NOT IN (SELECT `id` FROM `users`);
DELETE FROM `user_sessions` WHERE `user_id` NOT IN (SELECT `id` FROM `users`);

-- Now add foreign key constraints
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Step 6: Insert default system settings
-- --------------------------------------------------------

INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Thesis Management System', 'string', 'Name of the application'),
('max_file_size', '10485760', 'number', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', '["pdf","doc","docx"]', 'json', 'Allowed file types for uploads'),
('session_timeout', '3600', 'number', 'Session timeout in seconds (1 hour)'),
('backup_enabled', 'true', 'boolean', 'Enable automatic database backups'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('email_notifications', 'true', 'boolean', 'Enable email notifications'),
('registration_enabled', 'true', 'boolean', 'Allow new user registration'),
('default_thesis_chapters', '5', 'number', 'Default number of thesis chapters'),
('academic_year', '2024-2025', 'string', 'Current academic year');

-- --------------------------------------------------------
-- Step 7: Create indexes for better performance
-- --------------------------------------------------------

CREATE INDEX `idx_users_role_active` ON `users` (`role`, `created_at`);
CREATE INDEX `idx_theses_status_progress` ON `theses` (`status`, `progress_percentage`);
CREATE INDEX `idx_chapters_status_submitted` ON `chapters` (`status`, `submitted_at`);
CREATE INDEX `idx_admin_logs_admin_action` ON `admin_logs` (`admin_id`, `action`);
CREATE INDEX `idx_announcements_active_created` ON `announcements` (`is_active`, `created_at`);

-- --------------------------------------------------------
-- Step 8: Create views for admin analytics
-- --------------------------------------------------------

-- User statistics view
CREATE OR REPLACE VIEW `admin_user_stats` AS
SELECT 
    role,
    COUNT(*) as total_users,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_30d,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_7d
FROM users 
GROUP BY role;

-- Thesis progress overview
CREATE OR REPLACE VIEW `admin_thesis_overview` AS
SELECT 
    t.status,
    COUNT(*) as count,
    AVG(t.progress_percentage) as avg_progress,
    COUNT(CASE WHEN t.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_activity
FROM theses t
GROUP BY t.status;

-- Chapter submission statistics
CREATE OR REPLACE VIEW `admin_chapter_stats` AS
SELECT 
    c.status,
    COUNT(*) as total_chapters,
    COUNT(DISTINCT c.thesis_id) as unique_theses,
    AVG(DATEDIFF(c.submitted_at, c.created_at)) as avg_days_to_submit
FROM chapters c
WHERE c.submitted_at IS NOT NULL
GROUP BY c.status;

-- System activity summary
CREATE OR REPLACE VIEW `admin_activity_summary` AS
SELECT 
    DATE(created_at) as activity_date,
    COUNT(*) as total_activities,
    COUNT(DISTINCT user_id) as active_users
FROM notifications
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY activity_date DESC;

-- --------------------------------------------------------
-- Step 9: Verify setup
-- --------------------------------------------------------

-- Check if admin user was created successfully
SELECT 'Admin user check:' as status, email, role, full_name 
FROM users 
WHERE email = 'admin@thesis.edu';

-- Check if all admin tables exist
SELECT 'Tables created:' as status, 
       COUNT(*) as admin_tables_count
FROM information_schema.tables 
WHERE table_schema = 'thesis_management' 
AND table_name IN ('admin_logs', 'announcements', 'system_settings', 'system_health', 'user_sessions');

-- Check system settings
SELECT 'System settings:' as status, COUNT(*) as settings_count 
FROM system_settings;

-- --------------------------------------------------------

COMMIT;

-- --------------------------------------------------------
-- Setup completed successfully!
-- Login credentials: admin@thesis.edu / password123
-- --------------------------------------------------------