<?php
/**
 * Migration script to add document review functionality
 * Run this script to add highlighting and commenting features to existing installations
 */

require_once 'config/database.php';

echo "<h2>Document Review Migration</h2>";
echo "<p>Adding document highlighting and commenting features...</p>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if tables already exist
    $checkHighlights = $pdo->query("SHOW TABLES LIKE 'document_highlights'")->rowCount();
    $checkComments = $pdo->query("SHOW TABLES LIKE 'document_comments'")->rowCount();
    
    if ($checkHighlights > 0 && $checkComments > 0) {
        echo "<p style='color: orange;'>✓ Document review tables already exist. No migration needed.</p>";
        exit;
    }
    
    // Create document highlights table
    if ($checkHighlights == 0) {
        $sql = "CREATE TABLE document_highlights (
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
        echo "<p style='color: green;'>✓ Created document_highlights table</p>";
    }
    
    // Create document comments table
    if ($checkComments == 0) {
        $sql = "CREATE TABLE document_comments (
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
        echo "<p style='color: green;'>✓ Created document_comments table</p>";
    }
    
    // Add sample content to existing chapters if they don't have any
    echo "<p>Adding sample content to chapters...</p>";
    
    $chapters = $pdo->query("SELECT id, title, content FROM chapters WHERE content IS NULL OR content = ''")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($chapters as $chapter) {
        $sampleContent = "
        <h2>Chapter: " . htmlspecialchars($chapter['title']) . "</h2>
        
        <h3>Introduction</h3>
        <p>This chapter introduces the main concepts and methodology that will be discussed throughout this section. The research presented here builds upon previous work in the field and aims to contribute new insights to the existing body of knowledge.</p>
        
        <h3>Background</h3>
        <p>The background of this study is rooted in the need to address current gaps in understanding. Previous research has shown that there are several areas where further investigation is warranted. This chapter will explore these areas in detail.</p>
        
        <h3>Methodology</h3>
        <p>The methodology employed in this research follows a systematic approach that ensures validity and reliability of the results. The following steps were taken:</p>
        <ul>
            <li>Literature review and analysis</li>
            <li>Data collection and processing</li>
            <li>Statistical analysis and interpretation</li>
            <li>Validation and verification of results</li>
        </ul>
        
        <h3>Key Findings</h3>
        <p>The preliminary findings suggest that there are significant correlations between the variables studied. These findings have important implications for future research and practical applications in the field.</p>
        
        <h3>Discussion</h3>
        <p>The discussion section will elaborate on the implications of these findings and how they contribute to the broader understanding of the topic. The results are consistent with theoretical expectations and provide valuable insights for practitioners.</p>
        
        <h3>Conclusion</h3>
        <p>In conclusion, this chapter has presented comprehensive analysis and findings that advance our understanding of the subject matter. The insights gained from this research provide a solid foundation for the subsequent chapters and overall thesis contribution.</p>
        ";
        
        $updateStmt = $pdo->prepare("UPDATE chapters SET content = ? WHERE id = ?");
        $updateStmt->execute([$sampleContent, $chapter['id']]);
    }
    
    echo "<p style='color: green;'>✓ Added sample content to " . count($chapters) . " chapters</p>";
    
    // Check for metadata column in document_comments table
    $checkMetadata = false;
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM document_comments LIKE 'metadata'")->fetchAll();
        $checkMetadata = count($columns) > 0;
    } catch (PDOException $e) {
        // Table might not exist yet
        $checkMetadata = false;
    }
    
    // Add metadata column if it doesn't exist
    if (!$checkMetadata && $checkComments > 0) {
        try {
            $sql = "ALTER TABLE document_comments ADD COLUMN metadata TEXT NULL";
            $pdo->exec($sql);
            echo "<p style='color: green;'>✓ Added metadata column to document_comments table</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Failed to add metadata column: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3 style='color: green;'>Migration completed successfully!</h3>";
    echo "<p><strong>What's new:</strong></p>";
    echo "<ul>";
    echo "<li>✓ Advisers can now highlight text in student documents</li>";
    echo "<li>✓ Advisers can add comments to specific text sections</li>";
    echo "<li>✓ Students can view adviser feedback with highlights and comments</li>";
    echo "<li>✓ Color-coded highlighting system with multiple colors</li>";
    echo "<li>✓ Structured feedback system for better communication</li>";
    echo "</ul>";
    
    echo "<p><strong>How to use:</strong></p>";
    echo "<ol>";
    echo "<li>Advisers: Go to the 'Document Review' tab in the adviser dashboard</li>";
    echo "<li>Select a chapter to review from the left panel</li>";
    echo "<li>Use the highlight tool to mark important text sections</li>";
    echo "<li>Add comments to provide specific feedback</li>";
    echo "<li>Students: Check the 'Document Review' tab to see adviser feedback</li>";
    echo "</ol>";
    
    echo "<p><a href='systemFunda.php' style='color: blue;'>→ Go to Adviser Dashboard</a> | <a href='studentDashboard.php' style='color: blue;'>→ Go to Student Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
}
?> 