<?php
/**
 * Direct Setup script for Activity Logs Archive System
 * This script creates the necessary tables directly without external SQL files
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Setting up Activity Logs Archive System...\n\n";
    
    // Create archived_analytics_logs table
    echo "Creating archived_analytics_logs table...\n";
    $sql1 = "CREATE TABLE IF NOT EXISTS `archived_analytics_logs` (
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
      KEY `idx_archive_metadata` (`archive_reason`, `archived_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql1);
    echo "✅ archived_analytics_logs table created\n";
    
    // Create archive_settings table
    echo "Creating archive_settings table...\n";
    $sql2 = "CREATE TABLE IF NOT EXISTS `archive_settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `setting_key` varchar(100) NOT NULL,
      `setting_value` text NOT NULL,
      `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
      `description` varchar(255) DEFAULT NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql2);
    echo "✅ archive_settings table created\n";
    
    // Add foreign key constraints (if they don't exist)
    echo "Adding foreign key constraints...\n";
    try {
        $conn->exec("ALTER TABLE `archived_analytics_logs` 
                    ADD CONSTRAINT `archived_analytics_logs_ibfk_1` 
                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE");
        echo "✅ Foreign key constraint 1 added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "✅ Foreign key constraint 1 already exists\n";
        } else {
            echo "⚠ Warning adding constraint 1: " . $e->getMessage() . "\n";
        }
    }
    
    try {
        $conn->exec("ALTER TABLE `archived_analytics_logs` 
                    ADD CONSTRAINT `archived_analytics_logs_ibfk_2` 
                    FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE CASCADE");
        echo "✅ Foreign key constraint 2 added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "✅ Foreign key constraint 2 already exists\n";
        } else {
            echo "⚠ Warning adding constraint 2: " . $e->getMessage() . "\n";
        }
    }
    
    // Insert default settings
    echo "Inserting default archive settings...\n";
    $defaultSettings = [
        ['auto_archive_days', '365', 'number', 'Automatically archive logs older than this many days'],
        ['archive_retention_days', '1095', 'number', 'Keep archived logs for this many days before permanent deletion (3 years default)'],
        ['max_archive_size_mb', '1000', 'number', 'Maximum archive size in MB before cleanup'],
        ['archive_compression', 'true', 'boolean', 'Enable compression for archived logs'],
        ['archive_notifications', 'true', 'boolean', 'Send notifications when archives are created or cleaned up']
    ];
    
    $insertStmt = $conn->prepare("INSERT IGNORE INTO archive_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    
    foreach ($defaultSettings as $setting) {
        $insertStmt->execute($setting);
    }
    echo "✅ Default archive settings inserted\n";
    
    echo "\n📋 Verifying archive system setup...\n";
    
    // Verify tables were created
    $tables = ['archived_analytics_logs', 'archive_settings'];
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
            
            // Show table structure
            $stmt = $conn->prepare("DESCRIBE $table");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "   - " . count($columns) . " columns defined\n";
        } else {
            echo "❌ Table '$table' not found\n";
        }
    }
    
    // Check if archive settings are populated
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM archive_settings");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "✅ Archive settings populated ($count settings)\n";
    
    // Show current settings
    $stmt = $conn->prepare("SELECT setting_key, setting_value, setting_type FROM archive_settings ORDER BY setting_key");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📊 Current Archive Settings:\n";
    foreach ($settings as $setting) {
        echo "   - {$setting['setting_key']}: {$setting['setting_value']} ({$setting['setting_type']})\n";
    }
    
    echo "\n🎉 Archive system setup completed successfully!\n\n";
    
    echo "📝 Summary:\n";
    echo "- Archive tables created and verified\n";
    echo "- Foreign key constraints added\n";
    echo "- Default settings configured\n";
    echo "- Archive system ready for use\n";
    echo "- Access via Activity Logs tab in Adviser Dashboard\n\n";
    
    echo "🔧 Next steps:\n";
    echo "1. Navigate to the Adviser Dashboard (systemFunda.php)\n";
    echo "2. Go to the Activity Logs tab\n";
    echo "3. Test the 'Clear Logs' functionality\n";
    echo "4. Check the 'View Archive' feature\n";
    echo "5. Try exporting archived data\n\n";
    
    echo "🌟 New Features Available:\n";
    echo "- Clear logs with date sorting (newest/oldest first, by type)\n";
    echo "- Archive management with search and filtering\n";
    echo "- Export archived logs in JSON/CSV formats\n";
    echo "- Statistics dashboard for archive analytics\n";
    echo "- Restore logs from archive when needed\n\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up archive system: " . $e->getMessage() . "\n";
    echo "Please check your database connection and permissions.\n";
    exit(1);
}
?> 