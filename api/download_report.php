<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/analytics_functions.php';

// Require login
$auth = new Auth();
$auth->requireLogin();

// Get current user
$user = $auth->getCurrentUser();

// Check if report ID is provided
if (!isset($_GET['report_id']) || empty($_GET['report_id'])) {
    die('Report ID is required');
}

$report_id = intval($_GET['report_id']);

// Get report information
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get report data
    $sql = "SELECT * FROM saved_reports WHERE id = :report_id AND user_id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        die('Report not found or access denied');
    }
    
    // Decode report data
    $reportData = json_decode($report['report_data'], true);
    
    if (!$reportData || !isset($reportData['data']) || empty($reportData['data'])) {
        die('Invalid report data');
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $report['name'] . '_' . date('Y-m-d') . '.csv"');
    
    // Create a file pointer connected to PHP output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add report name and description as first rows
    fputcsv($output, ['Report: ' . $report['name']]);
    if (!empty($report['description'])) {
        fputcsv($output, ['Description: ' . $report['description']]);
    }
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty line for spacing
    
    // Output the column headers
    fputcsv($output, array_keys($reportData['data'][0]));
    
    // Output the data rows
    foreach ($reportData['data'] as $row) {
        fputcsv($output, $row);
    }
    
    // Close the file pointer
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
} 