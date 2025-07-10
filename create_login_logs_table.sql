-- Create login_logs table for tracking user login and logout activities
CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_role ENUM('admin', 'adviser', 'student') NOT NULL,
    action_type ENUM('login', 'logout', 'login_failed') NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    browser_info VARCHAR(255) NULL,
    login_time TIMESTAMP NULL,
    logout_time TIMESTAMP NULL,
    session_duration INT NULL COMMENT 'Duration in seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at),
    INDEX idx_user_role (user_role),
    INDEX idx_login_time (login_time)
);

-- Add some sample data (optional)
INSERT INTO login_logs (user_id, user_role, action_type, ip_address, user_agent, login_time) 
SELECT 
    id, 
    role, 
    'login', 
    '127.0.0.1', 
    'Mozilla/5.0 (Sample Browser)', 
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY)
FROM users 
WHERE role IN ('student', 'adviser') 
LIMIT 5; 