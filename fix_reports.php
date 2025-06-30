<?php
/**
 * Fix script for Reports templates
 * This script updates the existing report templates with corrected SQL queries
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Fixing Reports templates...\n";
    
    // Clear existing templates first
    $conn->exec("DELETE FROM report_templates");
    echo "✓ Cleared existing report templates\n";
    
    // Get a user ID for created_by (use the first adviser or create with ID 1)
    $stmt = $conn->query("SELECT id FROM users WHERE role = 'adviser' LIMIT 1");
    $adviser = $stmt->fetch(PDO::FETCH_ASSOC);
    $created_by = $adviser ? $adviser['id'] : 1;
    
    // Insert corrected report templates
    $templates = [
        [
            'name' => 'Thesis Progress Overview',
            'description' => 'Shows the progress of all theses currently in the system',
            'query' => 'SELECT CONCAT(u.full_name, " - ", LEFT(t.title, 30), "...") AS thesis_info, t.progress_percentage FROM theses t JOIN users u ON t.student_id = u.id ORDER BY t.progress_percentage DESC',
            'parameters' => null,
            'chart_type' => 'bar'
        ],
        [
            'name' => 'Adviser Workload',
            'description' => 'Displays the number of students assigned to each adviser',
            'query' => 'SELECT u.full_name AS adviser_name, COUNT(t.id) AS student_count FROM users u LEFT JOIN theses t ON u.id = t.adviser_id WHERE u.role = "adviser" GROUP BY u.id, u.full_name ORDER BY student_count DESC',
            'parameters' => null,
            'chart_type' => 'bar'
        ],
        [
            'name' => 'Thesis Status Distribution',
            'description' => 'Shows the distribution of thesis statuses',
            'query' => 'SELECT status, COUNT(*) AS count FROM theses GROUP BY status ORDER BY count DESC',
            'parameters' => null,
            'chart_type' => 'pie'
        ],
        [
            'name' => 'Student Progress Summary',
            'description' => 'Summary of student progress across all theses',
            'query' => 'SELECT u.full_name AS student_name, t.progress_percentage FROM theses t JOIN users u ON t.student_id = u.id ORDER BY t.progress_percentage DESC',
            'parameters' => null,
            'chart_type' => 'bar'
        ],
        [
            'name' => 'Chapter Submission Timeline',
            'description' => 'Shows chapter submission patterns over time',
            'query' => 'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS submission_count FROM chapters WHERE created_at IS NOT NULL GROUP BY month ORDER BY month DESC LIMIT 12',
            'parameters' => null,
            'chart_type' => 'line'
        ],
        [
            'name' => 'Active Students Count',
            'description' => 'Shows count of students by thesis status',
            'query' => 'SELECT t.status AS thesis_status, COUNT(DISTINCT t.student_id) AS student_count FROM theses t GROUP BY t.status ORDER BY student_count DESC',
            'parameters' => null,
            'chart_type' => 'pie'
        ]
    ];
    
    $stmt = $conn->prepare("INSERT INTO report_templates (name, description, query, parameters, chart_type, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($templates as $template) {
        $stmt->execute([
            $template['name'],
            $template['description'],
            $template['query'],
            $template['parameters'],
            $template['chart_type'],
            $created_by
        ]);
        echo "  ✓ Inserted corrected template: " . $template['name'] . "\n";
    }
    
    echo "\n🎉 Report templates fixed successfully!\n";
    echo "The 'Thesis Progress Overview' should now work properly.\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 