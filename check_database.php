<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get all tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Available tables: " . implode(', ', $tables) . "\n\n";
    
    // Check if document_comments table exists
    if (in_array('document_comments', $tables)) {
        echo "document_comments table EXISTS!\n\n";
        
        // Show table structure
        echo "Table structure:\n";
        $structure = $pdo->query('DESCRIBE document_comments')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($structure as $col) {
            echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . $col['Null'] . ' - ' . $col['Key'] . "\n";
        }
        
        // Check if there are any comments
        $count = $pdo->query('SELECT COUNT(*) FROM document_comments')->fetchColumn();
        echo "\nNumber of comments in table: " . $count . "\n";
        
    } else {
        echo "document_comments table does NOT exist!\n";
        echo "Need to create the table.\n";
    }
    
    // Also check for document_highlights table
    if (in_array('document_highlights', $tables)) {
        echo "\ndocument_highlights table EXISTS!\n";
    } else {
        echo "\ndocument_highlights table does NOT exist!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 