<?php
require_once 'config/database.php';
require_once 'includes/thesis_functions.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();
$thesisManager = new ThesisManager();

// Check if there are any theses
$stmt = $db->query("SELECT COUNT(*) FROM theses");
$thesesCount = $stmt->fetchColumn();
echo "Total theses: $thesesCount<br>";

// Check if there are any chapters
$stmt = $db->query("SELECT COUNT(*) FROM chapters");
$chaptersCount = $stmt->fetchColumn();
echo "Total chapters: $chaptersCount<br>";

// Check if there are any file uploads
$stmt = $db->query("SELECT COUNT(*) FROM file_uploads");
$uploadsCount = $stmt->fetchColumn();
echo "Total file uploads: $uploadsCount<br>";

if ($uploadsCount > 0) {
    // Get all file uploads with chapter and thesis info
    $sql = "SELECT f.*, c.chapter_number, c.title as chapter_title, 
            t.title as thesis_title, u.full_name as student_name 
            FROM file_uploads f
            JOIN chapters c ON f.chapter_id = c.id
            JOIN theses t ON c.thesis_id = t.id
            JOIN users u ON t.student_id = u.id
            ORDER BY f.uploaded_at DESC";
    $stmt = $db->query($sql);
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>File Uploads Details:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Chapter</th><th>Thesis</th><th>Student</th><th>File</th><th>Uploaded</th></tr>";
    
    foreach ($uploads as $upload) {
        echo "<tr>";
        echo "<td>{$upload['id']}</td>";
        echo "<td>Chapter {$upload['chapter_number']}: {$upload['chapter_title']}</td>";
        echo "<td>{$upload['thesis_title']}</td>";
        echo "<td>{$upload['student_name']}</td>";
        echo "<td>{$upload['original_filename']}</td>";
        echo "<td>{$upload['uploaded_at']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No file uploads found.</p>";
}

// Check if there are any advisers
$stmt = $db->query("SELECT id, full_name FROM users WHERE role = 'adviser'");
$advisers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Advisers:</h3>";
echo "<ul>";
foreach ($advisers as $adviser) {
    $theses = $thesisManager->getAdviserTheses($adviser['id']);
    echo "<li>{$adviser['full_name']} (ID: {$adviser['id']}) - {$theses ? count($theses) : 0} theses assigned</li>";
    
    if (!empty($theses)) {
        echo "<ul>";
        foreach ($theses as $thesis) {
            echo "<li>Thesis: {$thesis['title']} (Student: {$thesis['student_name']})</li>";
            
            // Get chapters for this thesis
            $chapters = $thesisManager->getThesisChapters($thesis['id']);
            if (!empty($chapters)) {
                echo "<ul>";
                foreach ($chapters as $chapter) {
                    $files = $thesisManager->getChapterFiles($chapter['id']);
                    $hasFiles = !empty($files) ? " [" . count($files) . " files]" : " [no files]";
                    echo "<li>Chapter {$chapter['chapter_number']}: {$chapter['title']} - Status: {$chapter['status']}{$hasFiles}</li>";
                }
                echo "</ul>";
            } else {
                echo "<ul><li>No chapters found</li></ul>";
            }
        }
        echo "</ul>";
    }
}
echo "</ul>";
?> 