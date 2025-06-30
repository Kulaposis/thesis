<?php
/**
 * Test script for Reports functionality
 */

session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/analytics_functions.php';

// Simulate being logged in as an adviser (you should replace this with your actual user ID)
if (!isset($_SESSION['user_id'])) {
    // For testing, let's get the first adviser
    $database = new Database();
    $conn = $database->getConnection();
    $stmt = $conn->query("SELECT id FROM users WHERE role = 'adviser' LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = 'adviser';
    } else {
        die("No adviser found in the database. Please create an adviser user first.");
    }
}

echo "<h1>Reports Functionality Test</h1>";

// Test 1: Check if analytics_functions.php is loaded
echo "<h2>1. Testing analytics functions</h2>";
if (function_exists('getReportTemplates')) {
    echo "✓ analytics_functions.php loaded successfully<br>";
} else {
    echo "❌ analytics_functions.php not loaded or functions missing<br>";
}

// Test 2: Test database connection
echo "<h2>2. Testing database connection</h2>";
try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "✓ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 3: Check if report_templates table exists and has data
echo "<h2>3. Testing report templates table</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM report_templates");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ report_templates table exists<br>";
    echo "✓ Found " . $result['count'] . " report templates<br>";
    
    if ($result['count'] == 0) {
        echo "<p style='color: orange;'>⚠️ No report templates found. Please run setup_reports.php first.</p>";
    }
} catch (Exception $e) {
    echo "❌ Error accessing report_templates table: " . $e->getMessage() . "<br>";
}

// Test 4: Test getReportTemplates function
echo "<h2>4. Testing getReportTemplates function</h2>";
try {
    $templates = getReportTemplates();
    echo "✓ getReportTemplates() executed successfully<br>";
    echo "✓ Retrieved " . count($templates) . " templates<br>";
    
    if (count($templates) > 0) {
        echo "<h3>Available templates:</h3>";
        echo "<ul>";
        foreach ($templates as $template) {
            echo "<li>ID: {$template['id']}, Name: {$template['name']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "❌ Error in getReportTemplates(): " . $e->getMessage() . "<br>";
}

// Test 5: Test generateReport function with first template
if (count($templates) > 0) {
    echo "<h2>5. Testing generateReport function</h2>";
    try {
        $firstTemplate = $templates[0];
        echo "Testing with template: " . $firstTemplate['name'] . " (ID: " . $firstTemplate['id'] . ")<br>";
        
        $report = generateReport($firstTemplate['id']);
        
        if ($report) {
            echo "✓ generateReport() executed successfully<br>";
            echo "✓ Generated report with " . count($report['data']) . " data rows<br>";
            
            if (count($report['data']) > 0) {
                echo "<h3>Sample data (first 3 rows):</h3>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                $headers = array_keys($report['data'][0]);
                echo "<tr>";
                foreach ($headers as $header) {
                    echo "<th style='padding: 5px;'>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                
                for ($i = 0; $i < min(3, count($report['data'])); $i++) {
                    echo "<tr>";
                    foreach ($headers as $header) {
                        echo "<td style='padding: 5px;'>" . htmlspecialchars($report['data'][$i][$header]) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: orange;'>⚠️ Report generated but no data returned. This might be because there are no theses in the database.</p>";
            }
        } else {
            echo "❌ generateReport() returned false<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error in generateReport(): " . $e->getMessage() . "<br>";
    }
}

// Test 6: Check if there are any theses in the database
echo "<h2>6. Checking database content</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM theses");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Theses in database: " . $result['count'] . "<br>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Students in database: " . $result['count'] . "<br>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'adviser'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Advisers in database: " . $result['count'] . "<br>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM chapters");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Chapters in database: " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error checking database content: " . $e->getMessage() . "<br>";
}

echo "<h2>Conclusion</h2>";
echo "<p>If you see errors above, those need to be fixed for the reports to work properly.</p>";
echo "<p>If all tests pass but you still have issues, check the browser console for JavaScript errors.</p>";

?> 