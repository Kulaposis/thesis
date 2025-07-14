<?php
// Database configuration for XAMPP
class Database {
    private $host = "localhost";
    private $database_name = "thesis_management";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Database initialization
function initializeDatabase() {
    $host = "localhost";
    $username = "root";
    $password = "";
    $database_name = "thesis_management";
    
    try {
        // Create connection without database
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $sql = "CREATE DATABASE IF NOT EXISTS $database_name";
        $pdo->exec($sql);
        
        // Use the database
        $pdo->exec("USE $database_name");
        
        // Create tables
        createTables($pdo);
        
        echo "Database initialized successfully!";
        
    } catch(PDOException $e) {
        echo "Database initialization failed: " . $e->getMessage();
    }
}

function createTables($pdo) {
    // Users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('student', 'adviser') NOT NULL,
        student_id VARCHAR(20) NULL,
        faculty_id VARCHAR(20) NULL,
        program VARCHAR(50) NULL,
        department VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Theses table
    $sql = "CREATE TABLE IF NOT EXISTS theses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        adviser_id INT NULL,
        title VARCHAR(255) NOT NULL,
        abstract TEXT NULL,
        status ENUM('draft', 'in_progress', 'for_review', 'approved', 'rejected') DEFAULT 'draft',
        progress_percentage INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);

    // Chapters table
    $sql = "CREATE TABLE IF NOT EXISTS chapters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thesis_id INT NOT NULL,
        chapter_number INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content LONGTEXT NULL,
        file_path VARCHAR(255) NULL,
        status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
        submitted_at TIMESTAMP NULL,
        approved_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (thesis_id) REFERENCES theses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_chapter (thesis_id, chapter_number)
    )";
    $pdo->exec($sql);

    // Feedback table
    $sql = "CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chapter_id INT NOT NULL,
        adviser_id INT NOT NULL,
        feedback_text TEXT NOT NULL,
        feedback_type ENUM('comment', 'revision', 'approval') DEFAULT 'comment',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
        FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);

    // Timeline/milestones table
    $sql = "CREATE TABLE IF NOT EXISTS timeline (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thesis_id INT NOT NULL,
        milestone_name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        due_date DATE NOT NULL,
        completed_date DATE NULL,
        status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (thesis_id) REFERENCES theses(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);

    // Notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);

    // File uploads table
    $sql = "CREATE TABLE IF NOT EXISTS file_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chapter_id INT NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        stored_filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(50) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);

    // Document highlights table
    $sql = "CREATE TABLE IF NOT EXISTS document_highlights (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chapter_id INT NOT NULL,
        adviser_id INT NOT NULL,
        start_offset INT NOT NULL,
        end_offset INT NOT NULL,
        highlighted_text TEXT NOT NULL,
        highlight_color VARCHAR(20) DEFAULT '#ffeb3b',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
        FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);

    // Document comments table
    $sql = "CREATE TABLE IF NOT EXISTS document_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chapter_id INT NOT NULL,
        adviser_id INT NOT NULL,
        highlight_id INT NULL,
        comment_text TEXT NOT NULL,
        start_offset INT NULL,
        end_offset INT NULL,
        position_x FLOAT NULL,
        position_y FLOAT NULL,
        status ENUM('active', 'resolved') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
        FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (highlight_id) REFERENCES document_highlights(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);

    // User settings table
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

    // Insert sample data
    insertSampleData($pdo);
}

function insertSampleData($pdo) {
    // Insert sample adviser
    $sql = "INSERT IGNORE INTO users (email, password, full_name, role, faculty_id, department) 
            VALUES ('adviser@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Doe', 'adviser', 'FAC001', 'Computer Science')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Insert sample student
    $sql = "INSERT IGNORE INTO users (email, password, full_name, role, student_id, program) 
            VALUES ('student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Smith', 'student', 'STU001', 'Computer Science')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Insert additional sample users
    $sql = "INSERT IGNORE INTO users (email, password, full_name, role, student_id, program) 
            VALUES ('student2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Johnson', 'student', 'STU002', 'Information Technology')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $sql = "INSERT IGNORE INTO users (email, password, full_name, role, student_id, program) 
            VALUES ('student3@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carol Williams', 'student', 'STU003', 'Computer Science')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $sql = "INSERT IGNORE INTO users (email, password, full_name, role, faculty_id, department) 
            VALUES ('adviser2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Jane Smith', 'adviser', 'FAC002', 'Information Technology')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Get IDs for sample thesis
    $adviser = $pdo->query("SELECT id FROM users WHERE email = 'adviser@example.com'")->fetch();
    $student = $pdo->query("SELECT id FROM users WHERE email = 'student@example.com'")->fetch();

    if ($adviser && $student) {
        // Insert sample thesis
        $sql = "INSERT IGNORE INTO theses (student_id, adviser_id, title, abstract, status, progress_percentage) 
                VALUES (?, ?, 'AI-Based Recommendation System', 'This thesis explores the development of an AI-based recommendation system...', 'in_progress', 45)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student['id'], $adviser['id']]);

        $thesis_id = $pdo->lastInsertId();
        
        if ($thesis_id) {
            // Insert sample chapters
            $chapters = [
                [1, 'Introduction', 'submitted'],
                [2, 'Literature Review', 'approved'],
                [3, 'Methodology', 'draft']
            ];

            foreach ($chapters as $chapter) {
                $sql = "INSERT IGNORE INTO chapters (thesis_id, chapter_number, title, status) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$thesis_id, $chapter[0], $chapter[1], $chapter[2]]);
            }

            // Insert sample timeline
            $milestones = [
                ['Proposal Defense', '2024-03-15', 'completed'],
                ['Chapter 1-3 Submission', '2024-06-01', 'completed'],
                ['Chapter 4-5 Submission', '2024-08-15', 'in_progress'],
                ['Final Defense', '2024-10-30', 'pending']
            ];

            foreach ($milestones as $milestone) {
                $sql = "INSERT IGNORE INTO timeline (thesis_id, milestone_name, due_date, status) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$thesis_id, $milestone[0], $milestone[1], $milestone[2]]);
            }
        }
    }
}
?> 