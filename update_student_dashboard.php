<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/thesis_functions.php';

// This script updates the student dashboard to show recent feedback
echo "Updating student dashboard with recent feedback...\n";

// Ensure the student dashboard has the feedback tab
$studentDashboardFile = file_get_contents('studentDashboard.php');

// Check if the feedback tab is already functional
if (strpos($studentDashboardFile, '$thesisManager->getStudentAllFeedback') !== false) {
    echo "Student dashboard already has functional feedback. No changes needed.\n";
    exit;
}

// Update the feedback tab in studentDashboard.php
$feedbackTabPlaceholder = '<div id="feedback-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Adviser Feedback</h3>
          <p class="text-gray-500">Feedback system coming soon!</p>
        </div>
      </div>';

$feedbackTabReplacement = '<div id="feedback-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Adviser Feedback</h3>
          
          <?php 
          $feedback = [];
          if ($thesis) {
            $feedback = $thesisManager->getStudentAllFeedback($user[\'id\']);
          }
          
          if (empty($feedback)): 
          ?>
            <div class="text-center py-8 text-gray-500">
              <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
              <p>No feedback available yet</p>
              <p class="text-sm mt-2">Your adviser will provide feedback as you submit chapters for review</p>
            </div>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach($feedback as $item): ?>
                <div class="border rounded-lg p-4 <?php echo $item[\'feedback_type\'] === \'approval\' ? \'border-green-200 bg-green-50\' : ($item[\'feedback_type\'] === \'revision\' ? \'border-amber-200 bg-amber-50\' : \'border-blue-200 bg-blue-50\'); ?>">
                  <div class="flex justify-between items-start mb-2">
                    <div>
                      <span class="font-medium">Chapter <?php echo $item[\'chapter_number\']; ?>: <?php echo htmlspecialchars($item[\'chapter_title\']); ?></span>
                      <div class="text-sm text-gray-600">
                        <?php echo htmlspecialchars($item[\'adviser_name\']); ?> • <?php echo date(\'M j, Y g:i A\', strtotime($item[\'created_at\'])); ?>
                      </div>
                    </div>
                    <span class="text-xs px-2 py-1 rounded
                      <?php echo $item[\'feedback_type\'] === \'comment\' ? \'bg-blue-100 text-blue-800\' : 
                        ($item[\'feedback_type\'] === \'approval\' ? \'bg-green-100 text-green-800\' : \'bg-amber-100 text-amber-800\'); ?>">
                      <?php echo ucfirst($item[\'feedback_type\']); ?>
                    </span>
                  </div>
                  <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($item[\'feedback_text\'])); ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>';

$updatedStudentDashboard = str_replace($feedbackTabPlaceholder, $feedbackTabReplacement, $studentDashboardFile);

// Write the updated file
if ($updatedStudentDashboard !== $studentDashboardFile) {
    file_put_contents('studentDashboard.php', $updatedStudentDashboard);
    echo "Updated studentDashboard.php with functional feedback tab.\n";
} else {
    echo "Could not update studentDashboard.php. The feedback tab placeholder was not found.\n";
}

echo "Update complete.\n";
?> 