<?php
require_once 'config/database.php';
require_once 'includes/thesis_functions.php';

// Initialize ThesisManager
$thesisManager = new ThesisManager();

// Get all theses
$db = new Database();
$conn = $db->getConnection();

try {
    // Get all theses
    $stmt = $conn->query("SELECT id, title, student_id, adviser_id, progress_percentage FROM theses");
    $theses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Updating Thesis Progress</h1>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Title</th><th>Old Progress</th><th>New Progress</th><th>Status</th></tr>";
    
    foreach ($theses as $thesis) {
        // Get current progress
        $old_progress = $thesis['progress_percentage'];
        
        // Update progress
        $success = $thesisManager->updateProgress($thesis['id']);
        
        // Get new progress
        $stmt = $conn->prepare("SELECT progress_percentage FROM theses WHERE id = :id");
        $stmt->bindParam(':id', $thesis['id']);
        $stmt->execute();
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_progress = $updated['progress_percentage'];
        
        // Display results
        echo "<tr>";
        echo "<td>{$thesis['id']}</td>";
        echo "<td>{$thesis['title']}</td>";
        echo "<td>{$old_progress}%</td>";
        echo "<td>{$new_progress}%</td>";
        echo "<td>" . ($success ? "Updated" : "Failed") . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>Chapter Details</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Thesis ID</th><th>Chapter ID</th><th>Title</th><th>Status</th><th>Created</th><th>Submitted</th><th>Approved</th></tr>";
    
    // Get all chapters
    $stmt = $conn->query("SELECT id, thesis_id, title, status, created_at, submitted_at, approved_at FROM chapters ORDER BY thesis_id, chapter_number");
    $chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($chapters as $chapter) {
        echo "<tr>";
        echo "<td>{$chapter['thesis_id']}</td>";
        echo "<td>{$chapter['id']}</td>";
        echo "<td>{$chapter['title']}</td>";
        echo "<td>{$chapter['status']}</td>";
        echo "<td>{$chapter['created_at']}</td>";
        echo "<td>{$chapter['submitted_at']}</td>";
        echo "<td>{$chapter['approved_at']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p><a href='systemFunda.php'>Return to Adviser Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?> 