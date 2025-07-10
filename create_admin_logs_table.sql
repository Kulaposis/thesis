-- Admin Logs Table for Enhanced User Management
-- This table tracks all admin actions for auditing and monitoring

CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `admin_id` int(11) NOT NULL,
    `action` varchar(100) NOT NULL,
    `target_type` varchar(50) NOT NULL,
    `target_id` int(11) DEFAULT NULL,
    `details` text DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` varchar(500) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `admin_id` (`admin_id`),
    KEY `action` (`action`),
    KEY `target_type` (`target_type`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample admin log data for testing
INSERT INTO `admin_logs` (`admin_id`, `action`, `target_type`, `target_id`, `details`, `created_at`) 
SELECT 
    u.id,
    'system_access',
    'dashboard',
    NULL,
    'Admin accessed dashboard',
    NOW() - INTERVAL FLOOR(RAND() * 30) DAY
FROM users u 
WHERE u.role IN ('admin', 'super_admin')
LIMIT 5;

-- Add indexes for better performance
ALTER TABLE `admin_logs` ADD INDEX `idx_admin_action` (`admin_id`, `action`);
ALTER TABLE `admin_logs` ADD INDEX `idx_target` (`target_type`, `target_id`);
ALTER TABLE `admin_logs` ADD INDEX `idx_date_action` (`created_at`, `action`);

-- Update existing login_logs table if needed to ensure compatibility
ALTER TABLE `login_logs` 
ADD COLUMN IF NOT EXISTS `browser` varchar(200) DEFAULT NULL AFTER `ip_address`,
ADD COLUMN IF NOT EXISTS `device_type` varchar(50) DEFAULT NULL AFTER `browser`;

-- Create a view for easy admin log reporting
CREATE OR REPLACE VIEW `admin_activity_summary` AS
SELECT 
    al.id,
    u.full_name as admin_name,
    u.email as admin_email,
    al.action,
    al.target_type,
    al.target_id,
    al.details,
    al.created_at,
    DATE(al.created_at) as activity_date,
    COUNT(*) OVER (PARTITION BY al.admin_id, DATE(al.created_at)) as daily_actions
FROM admin_logs al
JOIN users u ON al.admin_id = u.id
ORDER BY al.created_at DESC;

-- Create a view for user management statistics
CREATE OR REPLACE VIEW `user_management_stats` AS
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_actions,
    COUNT(CASE WHEN action = 'create_user' THEN 1 END) as users_created,
    COUNT(CASE WHEN action = 'update_user' THEN 1 END) as users_updated,
    COUNT(CASE WHEN action = 'delete_user' THEN 1 END) as users_deleted,
    COUNT(CASE WHEN action = 'reset_password' THEN 1 END) as passwords_reset,
    COUNT(DISTINCT admin_id) as active_admins
FROM admin_logs 
WHERE target_type = 'user'
GROUP BY DATE(created_at)
ORDER BY date DESC; 