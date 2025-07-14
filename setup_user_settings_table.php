<?php
require_once 'config/database.php';

echo "Setting up user_settings table...\n";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Create user_settings table
    $sql = "CREATE TABLE IF NOT EXISTS user_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        setting_key VARCHAR(100) NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_setting (user_id, setting_key),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✅ user_settings table created successfully!\n";
    
    // Verify the table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_settings'");
    if ($stmt->rowCount() > 0) {
        echo "✅ user_settings table verification successful!\n";
    } else {
        echo "❌ user_settings table verification failed!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 