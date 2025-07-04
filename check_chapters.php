<?php
// Script to check which chapters belong to theses assigned to adviser 7
require_once 'config/database.php';

echo "=== Checking Chapters for Adviser 7 ===\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // First, let's see what theses are assigned to adviser 7
    $stmt = $pdo->prepare("SELECT * FROM theses WHERE adviser_id = ?");
    $stmt->execute([7]);
    $theses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Theses assigned to adviser 7:\n";
    foreach ($theses as $thesis) {
        echo "- Thesis ID: {$thesis['id']}, Title: {$thesis['title']}, Student ID: {$thesis['student_id']}\n";
        
        // Get chapters for this thesis
        $stmt2 = $pdo->prepare("SELECT * FROM chapters WHERE thesis_id = ?");
        $stmt2->execute([$thesis['id']]);
        $chapters = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        echo "  Chapters:\n";
        foreach ($chapters as $chapter) {
            echo "  - Chapter ID: {$chapter['id']}, Title: {$chapter['title']}, Status: {$chapter['status']}\n";
        }
        echo "\n";
    }
    
    // If no theses found, let's see all theses and their advisers
    if (empty($theses)) {
        echo "No theses assigned to adviser 7. Let's see all theses:\n";
        $stmt = $pdo->query("SELECT t.*, u.full_name as adviser_name FROM theses t LEFT JOIN users u ON t.adviser_id = u.id");
        $all_theses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($all_theses as $thesis) {
            echo "- Thesis ID: {$thesis['id']}, Title: {$thesis['title']}, Student ID: {$thesis['student_id']}, Adviser: {$thesis['adviser_name']} (ID: {$thesis['adviser_id']})\n";
        }
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 