-- Simplified Activity Logs Archive System Tables
-- This creates the essential tables for the archive functionality

-- Table structure for archived activity logs
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
  KEY `idx_user_event` (`user_id`, `event_type`),
  KEY `idx_date_range` (`original_created_at`, `archived_at`),
  KEY `idx_archive_metadata` (`archive_reason`, `archived_by`),
  CONSTRAINT `archived_analytics_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `archived_analytics_logs_ibfk_2` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for archive management settings
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

-- Insert default archive settings
INSERT IGNORE INTO `archive_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('auto_archive_days', '365', 'number', 'Automatically archive logs older than this many days'),
('archive_retention_days', '1095', 'number', 'Keep archived logs for this many days before permanent deletion (3 years default)'),
('max_archive_size_mb', '1000', 'number', 'Maximum archive size in MB before cleanup'),
('archive_compression', 'true', 'boolean', 'Enable compression for archived logs'),
('archive_notifications', 'true', 'boolean', 'Send notifications when archives are created or cleaned up'); 