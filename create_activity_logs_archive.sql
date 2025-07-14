-- Activity Logs Archive System for Thesis Management System
-- This creates tables and functions for archiving cleared activity logs

USE `thesis_management`;

-- --------------------------------------------------------
-- Table structure for archived activity logs
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `archived_analytics_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) NOT NULL COMMENT 'Original ID from analytics_logs table',
  `event_type` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `entity_type` varchar(50) NOT NULL,
  `details` JSON DEFAULT NULL,
  `original_created_at` timestamp NOT NULL COMMENT 'Original creation timestamp',
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `archived_by` int(11) NOT NULL COMMENT 'User who archived this log',
  `archive_reason` varchar(100) DEFAULT 'manual_clear' COMMENT 'Reason for archiving',
  `archive_metadata` JSON DEFAULT NULL COMMENT 'Additional archive metadata',
  PRIMARY KEY (`id`),
  KEY `original_id` (`original_id`),
  KEY `user_id` (`user_id`),
  KEY `event_type` (`event_type`),
  KEY `entity_type` (`entity_type`),
  KEY `original_created_at` (`original_created_at`),
  KEY `archived_at` (`archived_at`),
  KEY `archived_by` (`archived_by`),
  CONSTRAINT `archived_analytics_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `archived_analytics_logs_ibfk_2` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table for archive management settings
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `archive_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default archive settings
-- --------------------------------------------------------

INSERT INTO `archive_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('auto_archive_days', '365', 'number', 'Automatically archive logs older than this many days'),
('archive_retention_days', '1095', 'number', 'Keep archived logs for this many days before permanent deletion (3 years default)'),
('max_archive_size_mb', '1000', 'number', 'Maximum archive size in MB before cleanup'),
('archive_compression', 'true', 'boolean', 'Enable compression for archived logs'),
('archive_notifications', 'true', 'boolean', 'Send notifications when archives are created or cleaned up');

-- --------------------------------------------------------
-- Create indexes for better performance
-- --------------------------------------------------------

ALTER TABLE `archived_analytics_logs` 
  ADD INDEX `idx_user_event` (`user_id`, `event_type`),
  ADD INDEX `idx_date_range` (`original_created_at`, `archived_at`),
  ADD INDEX `idx_archive_metadata` (`archive_reason`, `archived_by`);

-- --------------------------------------------------------
-- Create a view for easy archive statistics
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `archive_statistics` AS
SELECT 
  DATE(archived_at) as archive_date,
  COUNT(*) as logs_archived,
  COUNT(DISTINCT user_id) as unique_users,
  COUNT(DISTINCT event_type) as unique_event_types,
  archived_by,
  u.full_name as archived_by_name
FROM archived_analytics_logs aal
LEFT JOIN users u ON aal.archived_by = u.id
GROUP BY DATE(archived_at), archived_by
ORDER BY archive_date DESC;

-- --------------------------------------------------------
-- Create a view for archive summary by user
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `user_archive_summary` AS
SELECT 
  u.id as user_id,
  u.full_name,
  u.role,
  COUNT(aal.id) as total_archived_logs,
  MIN(aal.original_created_at) as earliest_log_date,
  MAX(aal.original_created_at) as latest_log_date,
  COUNT(DISTINCT aal.event_type) as unique_event_types
FROM users u
LEFT JOIN archived_analytics_logs aal ON u.id = aal.user_id
WHERE u.role = 'adviser'
GROUP BY u.id, u.full_name, u.role;

-- --------------------------------------------------------
-- Add a cleanup procedure for old archives
-- --------------------------------------------------------

DELIMITER //

CREATE PROCEDURE CleanupOldArchives()
BEGIN
  DECLARE retention_days INT DEFAULT 1095;
  
  -- Get retention setting
  SELECT CAST(setting_value AS SIGNED) INTO retention_days 
  FROM archive_settings 
  WHERE setting_key = 'archive_retention_days';
  
  -- Delete archives older than retention period
  DELETE FROM archived_analytics_logs 
  WHERE archived_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);
  
  -- Log the cleanup
  INSERT INTO archived_analytics_logs (
    original_id, event_type, user_id, entity_type, details, 
    original_created_at, archived_by, archive_reason, archive_metadata
  ) VALUES (
    0, 'archive_cleanup', 1, 'system', 
    JSON_OBJECT('cleanup_date', NOW(), 'retention_days', retention_days),
    NOW(), 1, 'automatic_cleanup', 
    JSON_OBJECT('procedure', 'CleanupOldArchives', 'automated', true)
  );
END //

DELIMITER ;

-- --------------------------------------------------------
-- Create an event scheduler to run cleanup monthly (optional)
-- --------------------------------------------------------

-- Note: Uncomment the following to enable automatic cleanup
-- SET GLOBAL event_scheduler = ON;
-- 
-- CREATE EVENT IF NOT EXISTS monthly_archive_cleanup
-- ON SCHEDULE EVERY 1 MONTH
-- STARTS CURRENT_TIMESTAMP
-- DO
--   CALL CleanupOldArchives();
