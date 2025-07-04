<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/thesis_functions.php';

// Check if user is logged in and is an adviser
$auth = new Auth();
if (!$auth->isLoggedIn() || $_SESSION['role'] !== 'adviser') {
    die('Access denied. Please login as an adviser.');
}

$user = $auth->getCurrentUser();

try {
    // Test database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>Database Setup and Testing</h2>";
    
    // Check if document_highlights table exists
    $sql = "SHOW TABLES LIKE 'document_highlights'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $highlights_table_exists = $stmt->fetch();
    
    echo "<p>document_highlights table exists: " . ($highlights_table_exists ? 'YES' : 'NO') . "</p>";
    
    if (!$highlights_table_exists) {
        // Create the table
        $create_sql = "CREATE TABLE document_highlights (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chapter_id INT NOT NULL,
            adviser_id INT NOT NULL,
            start_offset INT NOT NULL,
            end_offset INT NOT NULL,
            highlighted_text TEXT NOT NULL,
            highlight_color VARCHAR(10) DEFAULT '#ffeb3b',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_chapter_id (chapter_id),
            INDEX idx_adviser_id (adviser_id),
            FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
            FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        if ($pdo->exec($create_sql)) {
            echo "<p style='color: green;'>Created document_highlights table successfully!</p>";
        } else {
            echo "<p style='color: red;'>Failed to create document_highlights table</p>";
        }
    }
    
    // Check if document_comments table exists
    $sql = "SHOW TABLES LIKE 'document_comments'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $comments_table_exists = $stmt->fetch();
    
    echo "<p>document_comments table exists: " . ($comments_table_exists ? 'YES' : 'NO') . "</p>";
    
    if (!$comments_table_exists) {
        // Create the table
        $create_sql = "CREATE TABLE document_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chapter_id INT NOT NULL,
            adviser_id INT NOT NULL,
            comment_text TEXT NOT NULL,
            highlight_id INT NULL,
            start_offset INT NULL,
            end_offset INT NULL,
            position_x INT NULL,
            position_y INT NULL,
            metadata TEXT NULL,
            status ENUM('active', 'resolved') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_chapter_id (chapter_id),
            INDEX idx_adviser_id (adviser_id),
            FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
            FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (highlight_id) REFERENCES document_highlights(id) ON DELETE SET NULL
        )";
        
        if ($pdo->exec($create_sql)) {
            echo "<p style='color: green;'>Created document_comments table successfully!</p>";
        } else {
            echo "<p style='color: red;'>Failed to create document_comments table</p>";
            echo "<p>Error: " . implode(', ', $pdo->errorInfo()) . "</p>";
        }
    }
    
    // Test the comment functionality
    echo "<h3>Testing Comment Functionality</h3>";
    
    $thesisManager = new ThesisManager();
    
    // Get a test chapter ID
    $sql = "SELECT id, title FROM chapters LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $chapter = $stmt->fetch();
    
    if ($chapter) {
        echo "<p>Test chapter: ID " . $chapter['id'] . " - " . htmlspecialchars($chapter['title']) . "</p>";
        
        // Try to add a test comment
        $comment_id = $thesisManager->addDocumentComment(
            $chapter['id'],
            $user['id'],
            'Test comment for debugging',
            null, // highlight_id
            null, // start_offset
            null, // end_offset
            null, // position_x
            null, // position_y
            json_encode(['test' => true, 'debug' => true]) // metadata
        );
        
        if ($comment_id) {
            echo "<p style='color: green;'>Test comment added successfully with ID: " . $comment_id . "</p>";
            
            // Retrieve the comment
            $comments = $thesisManager->getChapterComments($chapter['id']);
            echo "<p>Retrieved " . count($comments) . " comments for this chapter</p>";
            
            if (!empty($comments)) {
                echo "<ul>";
                foreach ($comments as $comment) {
                    echo "<li>Comment ID: " . $comment['id'] . " - " . htmlspecialchars($comment['comment_text']) . "</li>";
                }
                echo "</ul>";
            }
            
            // Delete the test comment
            $sql = "DELETE FROM document_comments WHERE id = :comment_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':comment_id', $comment_id);
            if ($stmt->execute()) {
                echo "<p style='color: blue;'>Test comment deleted successfully</p>";
            }
        } else {
            echo "<p style='color: red;'>Failed to add test comment</p>";
            $errorInfo = $pdo->errorInfo();
            echo "<p>Database Error: " . implode(', ', $errorInfo) . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>No chapters found in database</p>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p>You can now test the comment functionality in the Document Review section.</p>";
    echo "<p><a href='systemFunda.php?tab=document-review'>Go to Document Review</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?> 