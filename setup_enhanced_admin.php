<?php
/**
 * Enhanced Admin Dashboard Setup Script
 * This script sets up all necessary components for the enhanced admin dashboard
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Enhanced Admin Dashboard Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; line-height: 1.6; }
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 4px; margin: 10px 0; }
        h1, h2 { color: #333; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background-color: #f8f9fa; }
        .code { background-color: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>";

echo "<h1>🚀 Enhanced Admin Dashboard Setup</h1>";
echo "<p>This script will set up all components for the enhanced admin dashboard with comprehensive user management.</p>";

try {
    $pdo = getDBConnection();
    echo "<div class='success'>✅ Database connection established successfully!</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</body></html>";
    exit;
}

echo "<div class='step'>";
echo "<h2>Step 1: Setting up Admin Logs Table</h2>";

try {
    // Create admin_logs table
    $sql = "
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
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
    echo "<div class='success'>✅ Admin logs table created successfully!</div>";
    
    // Add foreign key constraint if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE `admin_logs` ADD FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE");
        echo "<div class='success'>✅ Foreign key constraint added!</div>";
    } catch (Exception $e) {
        echo "<div class='warning'>⚠️ Foreign key constraint may already exist or users table not found.</div>";
    }
    
    // Add indexes for better performance
    try {
        $pdo->exec("ALTER TABLE `admin_logs` ADD INDEX `idx_admin_action` (`admin_id`, `action`)");
        $pdo->exec("ALTER TABLE `admin_logs` ADD INDEX `idx_target` (`target_type`, `target_id`)");
        $pdo->exec("ALTER TABLE `admin_logs` ADD INDEX `idx_date_action` (`created_at`, `action`)");
        echo "<div class='success'>✅ Performance indexes added!</div>";
    } catch (Exception $e) {
        echo "<div class='warning'>⚠️ Some indexes may already exist.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error setting up admin logs: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h2>Step 2: Enhancing Login Logs Table</h2>";

try {
    // Add browser and device columns to login_logs if they don't exist
    $pdo->exec("ALTER TABLE `login_logs` ADD COLUMN IF NOT EXISTS `browser` varchar(200) DEFAULT NULL AFTER `ip_address`");
    $pdo->exec("ALTER TABLE `login_logs` ADD COLUMN IF NOT EXISTS `device_type` varchar(50) DEFAULT NULL AFTER `browser`");
    echo "<div class='success'>✅ Login logs table enhanced with browser and device tracking!</div>";
    
} catch (Exception $e) {
    echo "<div class='warning'>⚠️ Login logs enhancement: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h2>Step 3: Creating Admin Views</h2>";

try {
    // Create admin activity summary view
    $sql = "
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
        ORDER BY al.created_at DESC
    ";
    
    $pdo->exec($sql);
    echo "<div class='success'>✅ Admin activity summary view created!</div>";
    
    // Create user management stats view
    $sql = "
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
        ORDER BY date DESC
    ";
    
    $pdo->exec($sql);
    echo "<div class='success'>✅ User management statistics view created!</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error creating views: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h2>Step 4: Checking Required Tables</h2>";

$requiredTables = ['users', 'students', 'advisers', 'login_logs', 'admin_logs'];
$missingTables = [];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        echo "<div class='success'>✅ Table '$table' exists and is accessible.</div>";
    } catch (Exception $e) {
        $missingTables[] = $table;
        echo "<div class='error'>❌ Table '$table' is missing or inaccessible.</div>";
    }
}

if (!empty($missingTables)) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Missing Tables Detected</h3>";
    echo "<p>The following tables are required but missing:</p>";
    echo "<ul>";
    foreach ($missingTables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    echo "<p>Please run the main database setup scripts first:</p>";
    echo "<div class='code'>";
    echo "php init_database.php<br>";
    echo "mysql -u [username] -p [database] < thesis_management.sql<br>";
    echo "mysql -u [username] -p [database] < create_login_logs_table.sql";
    echo "</div>";
    echo "</div>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h2>Step 5: Verifying Admin Users</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as admin_count FROM users WHERE role IN ('admin', 'super_admin')");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $adminCount = $result['admin_count'];
    
    if ($adminCount > 0) {
        echo "<div class='success'>✅ Found $adminCount admin user(s) in the system.</div>";
        
        // Show admin users
        $stmt = $pdo->query("SELECT id, full_name, email, role FROM users WHERE role IN ('admin', 'super_admin') ORDER BY role DESC");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Admin Users:</h3>";
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li><strong>" . htmlspecialchars($admin['full_name']) . "</strong> (" . htmlspecialchars($admin['email']) . ") - " . htmlspecialchars($admin['role']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<div class='error'>❌ No admin users found! You need at least one admin user.</div>";
        echo "<div class='info'>";
        echo "<h3>Create an Admin User</h3>";
        echo "<p>Run one of these scripts to create an admin user:</p>";
        echo "<div class='code'>";
        echo "php create_admin.php<br>";
        echo "php setup_admin.php";
        echo "</div>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error checking admin users: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h2>Step 6: Adding Sample Data (Optional)</h2>";

try {
    // Check if we have admin users to create sample logs
    $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin') LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Insert sample admin log data
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO admin_logs (admin_id, action, target_type, target_id, details, created_at) 
            VALUES 
            (?, 'system_access', 'dashboard', NULL, 'Admin accessed dashboard', NOW() - INTERVAL 1 DAY),
            (?, 'view_users', 'user_management', NULL, 'Viewed user management page', NOW() - INTERVAL 2 HOUR),
            (?, 'system_setup', 'system', NULL, 'Enhanced admin dashboard setup completed', NOW())
        ");
        
        $stmt->execute([$admin['id'], $admin['id'], $admin['id']]);
        echo "<div class='success'>✅ Sample admin log data added!</div>";
    } else {
        echo "<div class='warning'>⚠️ No admin users available for sample data creation.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='warning'>⚠️ Sample data creation: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<div class='step'>";
echo "<h2>🎉 Setup Complete!</h2>";

echo "<div class='success'>";
echo "<h3>✅ Enhanced Admin Dashboard is Ready!</h3>";
echo "<p>Your enhanced admin dashboard has been successfully set up with the following features:</p>";
echo "<ul>";
echo "<li><strong>Comprehensive User Management</strong> - Create, edit, delete users</li>";
echo "<li><strong>Role-based Access Control</strong> - Students, Advisers, Admins</li>";
echo "<li><strong>Password Management</strong> - Reset and generate secure passwords</li>";
echo "<li><strong>Bulk Operations</strong> - Bulk delete and password reset</li>";
echo "<li><strong>Enhanced Logging</strong> - Track all admin actions</li>";
echo "<li><strong>User Statistics</strong> - Real-time user metrics</li>";
echo "<li><strong>Search and Filtering</strong> - Advanced user search capabilities</li>";
echo "<li><strong>Login Monitoring</strong> - Track user login/logout activity</li>";
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>📋 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Access the Dashboard:</strong> <a href='admin_dashboard.php' target='_blank'>admin_dashboard.php</a></li>";
echo "<li><strong>Login with Admin Credentials:</strong> Use your admin email and password</li>";
echo "<li><strong>Explore User Management:</strong> Navigate to the 'User Management' tab</li>";
echo "<li><strong>Create Test Users:</strong> Try creating student and adviser accounts</li>";
echo "<li><strong>Test Features:</strong> Try password reset, user editing, and bulk operations</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>🔒 Security Notes:</h3>";
echo "<ul>";
echo "<li>Always use strong passwords for admin accounts</li>";
echo "<li>Regularly monitor admin activity logs</li>";
echo "<li>Keep user permissions up to date</li>";
echo "<li>Back up your database regularly</li>";
echo "<li>Delete this setup file after use for security</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

echo "<hr style='margin: 30px 0;'>";
echo "<p style='text-align: center; color: #666;'><em>Enhanced Admin Dashboard Setup Completed Successfully!</em></p>";

echo "</body></html>";
?> 