<?php
require_once 'config/database.php';

echo "<h2>Initializing Thesis Management Database...</h2>";

try {
    // Initialize the database
    initializeDatabase();
    echo "<p style='color: green;'>✓ Database and tables created successfully!</p>";
    echo "<p style='color: blue;'>Sample data has been inserted:</p>";
    echo "<ul>";
    echo "<li><strong>Sample Adviser:</strong> adviser@example.com (password: password123)</li>";
    echo "<li><strong>Sample Student:</strong> student@example.com (password: password123)</li>";
    echo "</ul>";
    echo "<p><a href='login.php' style='color: blue; text-decoration: underline;'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Initialization</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2 { color: #333; }
        ul { background: #f5f5f5; padding: 15px; border-radius: 5px; }
        li { margin: 5px 0; }
    </style>
</head>
<body>
    <h2>Database Setup Instructions for XAMPP:</h2>
    <ol>
        <li>Start XAMPP Control Panel</li>
        <li>Start Apache and MySQL services</li>
        <li>Open this file in your browser to initialize the database</li>
        <li>Use the sample credentials to test the system</li>
    </ol>
</body>
</html> 