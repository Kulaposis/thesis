<?php
session_start();
header('Content-Type: application/json');

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', '../debug.log');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting feedback test...\n<br>";

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo "ERROR: Not logged in\n<br>";
    exit;
}

$user = $auth->getCurrentUser();
echo "User: " . json_encode($user) . "\n<br>";

if ($user['role'] !== 'adviser') {
    echo "ERROR: User is not an adviser\n<br>";
    exit;
}

$thesisManager = new ThesisManager();

// Get adviser's theses
$theses = $thesisManager->getAdviserTheses($user['id']);
echo "Adviser has " . count($theses) . " theses\n<br>";

if (empty($theses)) {
    echo "ERROR: No theses found for this adviser\n<br>";
    exit;
}

$firstThesis = $theses[0];
echo "Using thesis: " . json_encode($firstThesis) . "\n<br>";

// Get chapters
$chapters = $thesisManager->getThesisChapters($firstThesis['id']);
echo "Thesis has " . count($chapters) . " chapters\n<br>";

if (empty($chapters)) {
    echo "ERROR: No chapters found\n<br>";
    exit;
}

$firstChapter = $chapters[0];
echo "Using chapter: " . json_encode($firstChapter) . "\n<br>";

// Test the addFeedback method directly
$testFeedback = "Test feedback from simple test - " . date('Y-m-d H:i:s');
echo "Attempting to add feedback...\n<br>";
echo "Chapter ID: {$firstChapter['id']}\n<br>";
echo "Adviser ID: {$user['id']}\n<br>";
echo "Feedback: $testFeedback\n<br>";

$result = $thesisManager->addFeedback($firstChapter['id'], $user['id'], $testFeedback, 'comment');

if ($result) {
    echo "SUCCESS: Feedback added!\n<br>";
    
    // Verify by getting the feedback
    $feedback = $thesisManager->getChapterFeedback($firstChapter['id']);
    echo "Chapter now has " . count($feedback) . " feedback entries\n<br>";
    
    foreach ($feedback as $f) {
        if (strpos($f['feedback_text'], 'Test feedback from simple test') !== false) {
            echo "SUCCESS: Test feedback found in database!\n<br>";
            break;
        }
    }
} else {
    echo "ERROR: Failed to add feedback\n<br>";
}

echo "Test complete.\n<br>";
?> 