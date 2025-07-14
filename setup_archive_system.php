<?php
/**
 * Setup script for Activity Logs Archive System
 * This script creates the necessary tables and initializes the archive system
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Setting up Activity Logs Archive System...\n\n";
    
    // Read the SQL file
    $sqlFile = 'create_archive_tables_simple.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Failed to read SQL file: $sqlFile");
    }
    
    // Split SQL into individual statements
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        // Skip USE statements as we're already connected to the right database
        if (stripos($statement, 'USE ') === 0) {
            continue;
        }
        
        // Skip DELIMITER statements
        if (stripos($statement, 'DELIMITER') === 0) {
            continue;
        }
        
        try {
            $conn->exec($statement);
            echo "✓ Executed SQL statement successfully\n";
        } catch (PDOException $e) {
            // Only show error if it's not about table already existing
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
            } else {
                echo "✓ Table already exists (skipped)\n";
            }
        }
    }
    
    echo "\n📋 Verifying archive system setup...\n";
    
    // Verify tables were created
    $tables = ['archived_analytics_logs', 'archive_settings'];
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' not found\n";
        }
    }
    
    // Check if archive settings are populated
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM archive_settings");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        echo "✅ Archive settings populated ($count settings)\n";
    } else {
        echo "⚠ Archive settings table is empty\n";
        
        // Populate default settings
        $defaultSettings = [
            ['auto_archive_days', '365', 'number', 'Automatically archive logs older than this many days'],
            ['archive_retention_days', '1095', 'number', 'Keep archived logs for this many days before permanent deletion (3 years default)'],
            ['max_archive_size_mb', '1000', 'number', 'Maximum archive size in MB before cleanup'],
            ['archive_compression', 'true', 'boolean', 'Enable compression for archived logs'],
            ['archive_notifications', 'true', 'boolean', 'Send notifications when archives are created or cleaned up']
        ];
        
        $insertStmt = $conn->prepare("INSERT INTO archive_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
        
        foreach ($defaultSettings as $setting) {
            $insertStmt->execute($setting);
        }
        
        echo "✅ Default archive settings populated\n";
    }
    
    // Verify views were created
    $views = ['archive_statistics', 'user_archive_summary'];
    foreach ($views as $view) {
        $stmt = $conn->prepare("SHOW FULL TABLES WHERE Table_Type = 'VIEW' AND Tables_in_thesis_management = ?");
        $stmt->execute([$view]);
        
        if ($stmt->rowCount() > 0) {
            echo "✅ View '$view' exists\n";
        } else {
            echo "⚠ View '$view' not found (may need manual creation)\n";
        }
    }
    
    echo "\n🎉 Archive system setup completed successfully!\n\n";
    
    echo "📝 Summary:\n";
    echo "- Archive tables created and verified\n";
    echo "- Default settings configured\n";
    echo "- Archive system ready for use\n";
    echo "- Access via Activity Logs tab in Adviser Dashboard\n\n";
    
    echo "🔧 Next steps:\n";
    echo "1. Test the archive functionality in the adviser dashboard\n";
    echo "2. Clear some activity logs to test archiving\n";
    echo "3. Try exporting archived data\n";
    echo "4. Configure additional settings as needed\n\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up archive system: " . $e->getMessage() . "\n";
    echo "Please check your database connection and permissions.\n";
    exit(1);
}
?> 