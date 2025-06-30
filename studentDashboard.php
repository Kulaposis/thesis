<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/thesis_functions.php';

// Require student login
$auth = new Auth();
$auth->requireRole('student');

// Get current user
$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();

// Get student's thesis and statistics
$thesis = $thesisManager->getStudentThesis($user['id']);
$stats = $thesisManager->getStudentStats($user['id']);
$chapters = [];
$timeline = [];

if ($thesis) {
    $chapters = $thesisManager->getThesisChapters($thesis['id']);
    $timeline = $thesisManager->getThesisTimeline($thesis['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Thesis Dashboard</title>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .active-tab {
      @apply text-blue-600 font-semibold bg-blue-50 rounded-md transition-colors duration-200;
    }
    .progress-bar {
      transition: width 0.6s ease;
    }
    .card-hover {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-hover:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
  </style>
</head>
<body class="bg-gray-50 font-sans text-sm antialiased">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md p-4 hidden md:block">
      <div class="flex items-center mb-6">
        <div class="bg-blue-100 p-2 rounded-lg mr-3">
          <i data-lucide="book-open" class="w-6 h-6 text-blue-600"></i>
        </div>
        <h1 class="text-blue-700 font-bold text-lg leading-tight">
          THESIS/CAPSTONE<br>STUDENT PORTAL
        </h1>
      </div>
      <nav class="space-y-1 text-gray-700 font-medium">
        <a href="#" data-tab="dashboard" class="flex items-center gap-3 p-3 sidebar-item active-tab hover:bg-blue-50 rounded-lg">
          <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
        </a>
        <a href="#" data-tab="thesis" class="flex items-center gap-3 p-3 sidebar-item hover:bg-gray-50 rounded-lg">
          <i data-lucide="file-text" class="w-5 h-5"></i> My Thesis
        </a>
        <a href="#" data-tab="feedback" class="flex items-center gap-3 p-3 sidebar-item hover:bg-gray-50 rounded-lg">
          <i data-lucide="message-circle" class="w-5 h-5"></i> Adviser Feedback
        </a>
        <a href="#" data-tab="review-feedback" class="flex items-center gap-3 p-3 sidebar-item hover:bg-gray-50 rounded-lg">
          <i data-lucide="eye" class="w-5 h-5"></i> Document Review
        </a>
        <a href="#" data-tab="timeline" class="flex items-center gap-3 p-3 sidebar-item hover:bg-gray-50 rounded-lg">
          <i data-lucide="calendar" class="w-5 h-5"></i> Timeline
        </a>
      </nav>
      <div class="mt-auto pt-6">
        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
          <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold truncate"><?php echo htmlspecialchars($user['full_name']); ?></p>
            <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($user['program'] ?? 'Student'); ?></p>
          </div>
          <a href="logout.php" class="text-gray-400 hover:text-gray-600">
            <i data-lucide="log-out" class="w-4 h-4"></i>
          </a>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-4 md:p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h2 class="text-xl font-semibold">Thesis Dashboard</h2>
          <p class="text-gray-500 text-sm">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?></p>
        </div>
        <div>
          <button id="new-student-btn" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Register as New Student</span>
          </button>
        </div>
      </div>

      <!-- Dashboard Tab Content -->
      <div id="dashboard-content" class="tab-content">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
          <div class="bg-white p-4 rounded-lg shadow flex items-center card-hover">
            <div class="bg-blue-100 p-3 rounded-full mr-4">
              <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
            </div>
            <div>
              <p class="font-bold text-2xl"><?php echo $stats['submitted_chapters']; ?></p>
              <p class="text-gray-500 text-sm">Chapters Submitted</p>
            </div>
          </div>
          <div class="bg-white p-4 rounded-lg shadow flex items-center card-hover">
            <div class="bg-green-100 p-3 rounded-full mr-4">
              <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
            </div>
            <div>
              <p class="font-bold text-2xl"><?php echo $stats['approved_chapters']; ?></p>
              <p class="text-gray-500 text-sm">Chapters Approved</p>
            </div>
          </div>
          <div class="bg-white p-4 rounded-lg shadow flex items-center card-hover">
            <div class="bg-amber-100 p-3 rounded-full mr-4">
              <i data-lucide="alert-circle" class="w-5 h-5 text-amber-600"></i>
            </div>
            <div>
              <p class="font-bold text-2xl"><?php echo $stats['rejected_chapters']; ?></p>
              <p class="text-gray-500 text-sm">Need Revision</p>
            </div>
          </div>
        </div>

        <!-- Thesis Progress -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
          <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-semibold">Thesis Progress</h3>
            <div class="text-sm text-gray-500">Overall: <?php echo $stats['progress_percentage']; ?>% complete</div>
          </div>
          <div class="p-4">
            <div class="w-full bg-gray-200 h-3 rounded-full mb-2">
              <div class="bg-blue-600 h-3 rounded-full progress-bar" style="width: <?php echo $stats['progress_percentage']; ?>%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500">
              <span>Started</span>
              <span>Final Defense</span>
            </div>
          </div>
        </div>

        <!-- Chapters Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Chapters List -->
          <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow overflow-hidden">
              <div class="p-4 border-b flex justify-between items-center">
                <h3 class="font-semibold">My Chapters</h3>
              </div>
              <div class="p-4">
                <?php if (!$thesis): ?>
                  <div class="text-center py-8">
                    <i data-lucide="book-open" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Thesis Found</h3>
                    <p class="text-gray-500 mb-4">You have sample data available from the database</p>
                  </div>
                <?php elseif (empty($chapters)): ?>
                  <div class="text-center py-8">
                    <i data-lucide="file-plus" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Chapters Yet</h3>
                    <p class="text-gray-500 mb-4">Start by adding your first chapter</p>
                  </div>
                <?php else: ?>
                  <div class="space-y-3">
                    <?php foreach ($chapters as $chapter): ?>
                    <div class="border rounded-lg p-3 hover:bg-gray-50">
                      <div class="flex justify-between items-start">
                        <div class="flex-1">
                          <h4 class="font-medium">Chapter <?php echo $chapter['chapter_number']; ?>: <?php echo htmlspecialchars($chapter['title']); ?></h4>
                          <p class="text-sm text-gray-600 mt-1">
                            <?php if ($chapter['submitted_at']): ?>
                              Submitted: <?php echo date('M j, Y', strtotime($chapter['submitted_at'])); ?>
                            <?php else: ?>
                              Last updated: <?php echo date('M j, Y', strtotime($chapter['updated_at'])); ?>
                            <?php endif; ?>
                          </p>
                          <?php 
                          // Get uploaded files for this chapter
                          $files = $thesisManager->getChapterFiles($chapter['id']);
                          if (!empty($files)): 
                          ?>
                          <div class="mt-2">
                            <p class="text-xs text-gray-500">Uploaded files:</p>
                            <ul class="text-xs text-blue-600">
                              <?php foreach ($files as $file): ?>
                                <li class="mt-1">
                                  <a href="api/download_file.php?file_id=<?php echo $file['id']; ?>" class="flex items-center">
                                    <i data-lucide="file-text" class="w-3 h-3 mr-1"></i>
                                    <?php echo htmlspecialchars($file['original_filename']); ?>
                                  </a>
                                </li>
                              <?php endforeach; ?>
                            </ul>
                          </div>
                          <?php endif; ?>
                        </div>
                        <div class="ml-4">
                          <?php 
                          $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-800',
                            'submitted' => 'bg-blue-100 text-blue-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800'
                          ];
                          $statusColor = $statusColors[$chapter['status']] ?? 'bg-gray-100 text-gray-800';
                          ?>
                          <span class="px-2 py-1 text-xs rounded-full <?php echo $statusColor; ?>">
                            <?php echo ucfirst($chapter['status']); ?>
                          </span>
                          <button class="upload-btn mt-2 px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600" 
                                  data-chapter-id="<?php echo $chapter['id']; ?>"
                                  data-chapter-title="<?php echo htmlspecialchars($chapter['title']); ?>">
                            Upload Document
                          </button>
                        </div>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="space-y-6">
            <!-- Thesis Info -->
            <?php if ($thesis): ?>
            <div class="bg-white p-4 rounded-lg shadow">
              <h3 class="font-semibold mb-3">Thesis Information</h3>
              <div class="space-y-2">
                <div>
                  <p class="text-sm font-medium text-gray-700">Title:</p>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($thesis['title']); ?></p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-700">Status:</p>
                  <span class="text-sm px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                    <?php echo ucfirst($thesis['status']); ?>
                  </span>
                </div>
                <?php if ($thesis['adviser_name']): ?>
                <div>
                  <p class="text-sm font-medium text-gray-700">Adviser:</p>
                  <p class="text-sm text-gray-600"><?php echo htmlspecialchars($thesis['adviser_name']); ?></p>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Recent Activity -->
            <div class="bg-white p-4 rounded-lg shadow">
              <h3 class="font-semibold mb-3">Recent Activity</h3>
              <div class="space-y-3">
                <?php 
                $recent_chapters = array_slice($chapters, -3);
                foreach ($recent_chapters as $chapter): 
                ?>
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <div class="flex-1">
                    <p class="text-sm"><?php echo htmlspecialchars($chapter['title']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo date('M j', strtotime($chapter['updated_at'])); ?></p>
                  </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($chapters)): ?>
                <p class="text-sm text-gray-500">No recent activity</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Timeline Content -->
      <div id="timeline-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Timeline & Milestones</h3>
          <?php if (!empty($timeline)): ?>
          <div class="space-y-4">
            <?php foreach ($timeline as $milestone): ?>
            <div class="flex items-center gap-4 p-4 border rounded-lg">
              <div class="w-3 h-3 rounded-full <?php echo $milestone['status'] === 'completed' ? 'bg-green-500' : ($milestone['status'] === 'in_progress' ? 'bg-blue-500' : 'bg-gray-300'); ?>"></div>
              <div class="flex-1">
                <h4 class="font-medium"><?php echo htmlspecialchars($milestone['milestone_name']); ?></h4>
                <p class="text-sm text-gray-600">Due: <?php echo date('M j, Y', strtotime($milestone['due_date'])); ?></p>
              </div>
              <span class="text-sm px-2 py-1 rounded-full <?php echo $milestone['status'] === 'completed' ? 'bg-green-100 text-green-800' : ($milestone['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?>">
                <?php echo ucfirst(str_replace('_', ' ', $milestone['status'])); ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-gray-500">No timeline milestones available.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Other tabs -->
      <div id="thesis-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6 mb-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Thesis Information</h3>
            <?php if ($thesis): ?>
            <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $thesis['status'] === 'approved' ? 'green' : 'blue'; ?>-100 text-<?php echo $thesis['status'] === 'approved' ? 'green' : 'blue'; ?>-800">
              <?php echo ucfirst($thesis['status']); ?>
            </span>
            <?php endif; ?>
          </div>
          
          <?php if (!$thesis): ?>
          <div class="text-center py-8">
            <i data-lucide="file-text" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Thesis Found</h3>
            <p class="text-gray-500 mb-4">Please contact your adviser to register your thesis.</p>
          </div>
          <?php else: ?>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
              <p class="text-gray-900 p-2 bg-gray-50 rounded-md"><?php echo htmlspecialchars($thesis['title']); ?></p>
            </div>
            
            <?php if (!empty($thesis['abstract'])): ?>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Abstract</label>
              <div class="text-gray-900 p-2 bg-gray-50 rounded-md">
                <?php echo nl2br(htmlspecialchars($thesis['abstract'])); ?>
              </div>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adviser</label>
                <p class="text-gray-900 p-2 bg-gray-50 rounded-md"><?php echo htmlspecialchars($thesis['adviser_name'] ?? 'Not assigned'); ?></p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Progress</label>
                <div class="p-2 bg-gray-50 rounded-md">
                  <div class="w-full bg-gray-200 h-2 rounded-full">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $thesis['progress_percentage']; ?>%"></div>
                  </div>
                  <p class="text-sm text-right mt-1"><?php echo $thesis['progress_percentage']; ?>% complete</p>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
        
        <?php if ($thesis): ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-semibold">My Chapters</h3>
          </div>
          <div class="p-4">
            <?php if (empty($chapters)): ?>
            <div class="text-center py-8">
              <i data-lucide="file-plus" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
              <h3 class="text-lg font-medium text-gray-900 mb-2">No Chapters Yet</h3>
              <p class="text-gray-500 mb-4">Your adviser will add chapters to your thesis</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chapter</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <?php foreach ($chapters as $chapter): ?>
                  <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Chapter <?php echo $chapter['chapter_number']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($chapter['title']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <?php 
                      $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-800',
                        'submitted' => 'bg-blue-100 text-blue-800',
                        'approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800'
                      ];
                      $statusColor = $statusColors[$chapter['status']] ?? 'bg-gray-100 text-gray-800';
                      ?>
                      <span class="px-2 py-1 text-xs rounded-full <?php echo $statusColor; ?>">
                        <?php echo ucfirst($chapter['status']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <?php echo date('M j, Y', strtotime($chapter['updated_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <?php 
                      $files = $thesisManager->getChapterFiles($chapter['id']);
                      echo count($files);
                      ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <button class="upload-btn px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600" 
                              data-chapter-id="<?php echo $chapter['id']; ?>"
                              data-chapter-title="<?php echo htmlspecialchars($chapter['title']); ?>">
                        Upload Document
                      </button>
                      <?php if (!empty($files)): ?>
                      <button class="view-files-btn ml-2 px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded hover:bg-gray-200"
                              data-chapter-id="<?php echo $chapter['id']; ?>"
                              data-chapter-title="<?php echo htmlspecialchars($chapter['title']); ?>">
                        View Files
                      </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div id="feedback-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Adviser Feedback</h3>
          
          <?php 
          $feedback = [];
          if ($thesis) {
            $feedback = $thesisManager->getStudentAllFeedback($user['id']);
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
                <div class="border rounded-lg p-4 <?php echo $item['feedback_type'] === 'approval' ? 'border-green-200 bg-green-50' : ($item['feedback_type'] === 'revision' ? 'border-amber-200 bg-amber-50' : 'border-blue-200 bg-blue-50'); ?>">
                  <div class="flex justify-between items-start mb-2">
                    <div>
                      <span class="font-medium">Chapter <?php echo $item['chapter_number']; ?>: <?php echo htmlspecialchars($item['chapter_title']); ?></span>
                      <div class="text-sm text-gray-600">
                        <?php echo htmlspecialchars($item['adviser_name']); ?> • <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?>
                      </div>
                    </div>
                    <span class="text-xs px-2 py-1 rounded
                      <?php echo $item['feedback_type'] === 'comment' ? 'bg-blue-100 text-blue-800' : 
                        ($item['feedback_type'] === 'approval' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'); ?>">
                      <?php echo ucfirst($item['feedback_type']); ?>
                    </span>
                  </div>
                  <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($item['feedback_text'])); ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Document Review Feedback Tab Content -->
      <div id="review-feedback-content" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
          <!-- Chapter Selection Panel -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-4">
              <h3 class="font-semibold mb-4">My Chapters</h3>
              <div id="student-chapter-list" class="space-y-2">
                <?php if (!empty($chapters)): ?>
                  <?php foreach ($chapters as $chapter): ?>
                    <button 
                      class="w-full text-left px-3 py-2 border rounded-lg hover:bg-blue-50 student-chapter-item"
                      data-chapter-id="<?php echo $chapter['id']; ?>"
                      data-chapter-title="<?php echo htmlspecialchars($chapter['title']); ?>">
                      <div class="flex justify-between items-center">
                        <div>
                          <span class="font-medium text-sm">Ch. <?php echo $chapter['chapter_number']; ?>: <?php echo htmlspecialchars($chapter['title']); ?></span>
                          <div class="text-xs text-gray-500 mt-1">
                            <?php 
                            // Count comments for this chapter
                            $comments_count = 0;
                            if ($thesis) {
                              $chapter_comments = $thesisManager->getChapterComments($chapter['id']);
                              $comments_count = count($chapter_comments);
                            }
                            ?>
                            <?php echo $comments_count; ?> adviser comment(s)
                          </div>
                        </div>
                        <span class="text-xs px-1 py-1 rounded <?php echo $chapter['status'] === 'submitted' ? 'bg-yellow-100 text-yellow-800' : ($chapter['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'); ?>">
                          <?php echo ucfirst($chapter['status']); ?>
                        </span>
                      </div>
                    </button>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-center py-8 text-gray-500">
                    <i data-lucide="file-text" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                    <p class="text-sm">No chapters available</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Document Viewer Panel -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow h-full">
              <div class="p-4 border-b">
                <h3 class="font-semibold" id="student-document-title">Select a chapter to view feedback</h3>
                <p class="text-sm text-gray-500" id="student-document-info"></p>
              </div>
              <div class="p-4" style="height: calc(100vh - 200px); overflow-y: auto;">
                <div id="student-document-content" class="prose max-w-none">
                  <div class="text-center py-12 text-gray-500">
                    <i data-lucide="file-text" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                    <p>Select a chapter from the left panel to view adviser feedback</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Adviser Comments Panel -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow h-full">
              <div class="p-4 border-b">
                <h3 class="font-semibold">Adviser Comments</h3>
              </div>
              <div class="p-4" style="height: calc(100vh - 200px); overflow-y: auto;">
                <div id="student-comments-list" class="space-y-3">
                  <div class="text-center py-8 text-gray-500">
                    <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                    <p class="text-sm">No adviser comments yet</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Tab switching functionality
    document.querySelectorAll('.sidebar-item').forEach(item => {
      item.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        document.querySelectorAll('.sidebar-item').forEach(tab => {
          tab.classList.remove('active-tab');
          tab.classList.add('hover:bg-gray-50');
        });
        
        // Add active class to clicked tab
        this.classList.add('active-tab');
        this.classList.remove('hover:bg-gray-50');
        
        // Hide all content
        document.querySelectorAll('.tab-content').forEach(content => {
          content.classList.add('hidden');
        });
        
        // Show selected content
        const tabName = this.getAttribute('data-tab');
        const content = document.getElementById(tabName + '-content');
        if (content) {
          content.classList.remove('hidden');
        }
      });
    });

    // Student Document Review Functionality
    let studentCurrentChapterId = null;

    // Chapter selection for student review
    document.addEventListener('click', function(e) {
      if (e.target.closest('.student-chapter-item')) {
        const chapterItem = e.target.closest('.student-chapter-item');
        const chapterId = chapterItem.dataset.chapterId;
        const chapterTitle = chapterItem.dataset.chapterTitle;
        
        // Remove active class from all chapters
        document.querySelectorAll('.student-chapter-item').forEach(item => {
          item.classList.remove('bg-blue-100');
        });
        
        // Add active class to selected chapter
        chapterItem.classList.add('bg-blue-100');
        
        loadStudentChapter(chapterId, chapterTitle);
      }
    });

    // Load chapter content for student review
    function loadStudentChapter(chapterId, chapterTitle) {
      studentCurrentChapterId = chapterId;
      
      // Update document title
      document.getElementById('student-document-title').textContent = chapterTitle;
      document.getElementById('student-document-info').textContent = 'Loading chapter content...';
      
      // Load chapter data
      fetch(`api/student_review.php?action=get_chapter&chapter_id=${chapterId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const chapter = data.chapter;
            document.getElementById('student-document-info').textContent = 
              `Thesis: ${chapter.thesis_title}`;
            
            // Display chapter content with highlights
            let content = chapter.content || '';
            
            if (!content) {
              // Check if there are uploaded files
              const files = chapter.files || [];
              if (files.length > 0) {
                document.getElementById('student-document-content').innerHTML = `
                  <div class="text-center py-8">
                    <i data-lucide="file-text" class="w-16 h-16 mx-auto mb-4 text-blue-300"></i>
                    <p class="text-lg font-medium mb-2">Document Uploaded</p>
                    <p class="text-gray-500 mb-4">This chapter has uploaded documents but no text content.</p>
                    <div class="space-y-2 max-w-md mx-auto">
                      ${files.map(file => `
                        <div class="border rounded-lg p-3 hover:bg-blue-50 flex justify-between items-center">
                          <div class="flex items-center">
                            <i data-lucide="file-text" class="w-5 h-5 text-blue-500 mr-2"></i>
                            <span>${file.original_filename}</span>
                          </div>
                          <a href="api/download_file.php?file_id=${file.id}" class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">
                            Download
                          </a>
                        </div>
                      `).join('')}
                    </div>
                  </div>
                `;
                lucide.createIcons();
              } else {
                document.getElementById('student-document-content').innerHTML = `
                  <div class="text-center py-12 text-gray-500">
                    <i data-lucide="file-x" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                    <p>No content available for this chapter</p>
                  </div>
                `;
                lucide.createIcons();
              }
            } else {
              // If we have content, display it with any highlights
              document.getElementById('student-document-content').innerHTML = 
                `<div class="student-chapter-content prose max-w-none" data-chapter-id="${chapterId}">${content}</div>`;
              
              // Apply existing highlights
              applyStudentHighlights(chapter.highlights || []);
            }
            
            // Load comments
            displayStudentComments(chapter.comments || []);
            
          } else {
            showStudentError('Failed to load chapter: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Error loading chapter:', error);
          showStudentError('Failed to load chapter');
        });
    }

    // Apply highlights to content for students (read-only)
    function applyStudentHighlights(highlights) {
      const contentElement = document.querySelector('.student-chapter-content');
      if (!contentElement || !highlights.length) return;
      
      // For a simple implementation, we'll add visual indicators for highlights
      // In a real implementation, you'd need more sophisticated text range handling
      highlights.forEach(highlight => {
        // Create a visual indicator that the text has been highlighted
        const highlightNote = document.createElement('div');
        highlightNote.className = 'highlight-note mb-2 p-2 border-l-4 border-yellow-400 bg-yellow-50';
        highlightNote.innerHTML = `
          <div class="text-xs text-gray-600 mb-1">Highlighted by ${highlight.adviser_name}</div>
          <div class="text-sm font-medium" style="background-color: ${highlight.highlight_color}; padding: 2px 4px; border-radius: 3px;">
            "${highlight.highlighted_text}"
          </div>
        `;
        
        // Insert at the beginning of content
        contentElement.insertBefore(highlightNote, contentElement.firstChild);
      });
    }

    // Display comments for students
    function displayStudentComments(comments) {
      const commentsList = document.getElementById('student-comments-list');
      
      if (comments.length === 0) {
        commentsList.innerHTML = `
          <div class="text-center py-8 text-gray-500">
            <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
            <p class="text-sm">No adviser comments yet</p>
          </div>
        `;
        lucide.createIcons();
        return;
      }
      
      commentsList.innerHTML = comments.map(comment => `
        <div class="comment-item border rounded-lg p-3 ${comment.status === 'resolved' ? 'bg-green-50' : 'bg-blue-50'}">
          <div class="flex justify-between items-start mb-2">
            <span class="font-medium text-sm ${comment.status === 'resolved' ? 'text-green-800' : 'text-blue-800'}">${comment.adviser_name}</span>
            <span class="text-xs text-gray-500">${new Date(comment.created_at).toLocaleDateString()}</span>
          </div>
          ${comment.highlighted_text ? `
            <div class="bg-yellow-100 p-2 rounded text-xs mb-2 border-l-2 border-yellow-400">
              <strong>About:</strong> "${comment.highlighted_text}"
            </div>
          ` : ''}
          <div class="bg-white p-2 rounded border">
            <p class="text-sm">${comment.comment_text}</p>
          </div>
          <div class="mt-2 flex justify-between items-center">
            <span class="text-xs ${comment.status === 'resolved' ? 'text-green-600' : 'text-blue-600'}">
              <i data-lucide="${comment.status === 'resolved' ? 'check-circle' : 'arrow-right'}" class="w-3 h-3 inline mr-1"></i>
              ${comment.status === 'resolved' ? 'Resolved' : 'Action needed: Please review and make necessary revisions'}
            </span>
          </div>
        </div>
      `).join('');
      
      // Re-initialize icons
      lucide.createIcons();
    }

    // Error display function for student view
    function showStudentError(message) {
      alert(message);
    }
    
    // File Upload Modal Functionality
    const uploadModal = document.createElement('div');
    uploadModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden';
    uploadModal.id = 'upload-modal';
    uploadModal.innerHTML = `
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-semibold text-lg" id="upload-modal-title">Upload Thesis Document</h3>
          <button id="close-upload-modal" class="text-gray-500 hover:text-gray-700">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <form id="upload-thesis-form" enctype="multipart/form-data">
          <input type="hidden" id="upload-chapter-id" name="chapter_id">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Document</label>
            <input type="file" name="thesis_document" id="thesis-document" 
                   class="w-full border rounded-lg p-2 text-sm"
                   accept=".pdf,.doc,.docx">
            <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, DOC, DOCX (Max 10MB)</p>
          </div>
          <div class="flex justify-end space-x-2 mt-6">
            <button type="button" id="cancel-upload" class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              Upload Document
            </button>
          </div>
        </form>
        <div id="upload-progress" class="mt-4 hidden">
          <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
          </div>
          <p class="text-xs text-center mt-1">Uploading... <span id="upload-percentage">0%</span></p>
        </div>
      </div>
    `;
    document.body.appendChild(uploadModal);
    
    // File View Modal
    const filesModal = document.createElement('div');
    filesModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden';
    filesModal.id = 'files-modal';
    filesModal.innerHTML = `
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-semibold text-lg" id="files-modal-title">Chapter Files</h3>
          <button id="close-files-modal" class="text-gray-500 hover:text-gray-700">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <div id="files-list" class="max-h-96 overflow-y-auto">
          <div class="text-center py-8">
            <i data-lucide="loader" class="w-8 h-8 mx-auto mb-3 text-gray-400 animate-spin"></i>
            <p class="text-gray-500">Loading files...</p>
          </div>
        </div>
        <div class="flex justify-end mt-6">
          <button id="close-files-btn" class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50">
            Close
          </button>
        </div>
      </div>
    `;
    document.body.appendChild(filesModal);
    
    // Show upload modal when upload button is clicked
    document.addEventListener('click', function(e) {
      if (e.target.closest('.upload-btn')) {
        const btn = e.target.closest('.upload-btn');
        const chapterId = btn.dataset.chapterId;
        const chapterTitle = btn.dataset.chapterTitle;
        
        document.getElementById('upload-chapter-id').value = chapterId;
        document.getElementById('upload-modal-title').textContent = `Upload Document for: ${chapterTitle}`;
        document.getElementById('upload-modal').classList.remove('hidden');
      }
      
      // View files button click
      if (e.target.closest('.view-files-btn')) {
        const btn = e.target.closest('.view-files-btn');
        const chapterId = btn.dataset.chapterId;
        const chapterTitle = btn.dataset.chapterTitle;
        
        document.getElementById('files-modal-title').textContent = `Files for: ${chapterTitle}`;
        document.getElementById('files-modal').classList.remove('hidden');
        
        // Load files for this chapter
        fetch(`api/student_review.php?action=get_chapter&chapter_id=${chapterId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const chapter = data.chapter;
              const filesList = document.getElementById('files-list');
              
              // Get uploaded files for this chapter
              fetch(`api/student_review.php?action=get_files&chapter_id=${chapterId}`)
                .then(response => response.json())
                .then(fileData => {
                  if (fileData.success && fileData.files.length > 0) {
                    filesList.innerHTML = `
                      <div class="space-y-3">
                        ${fileData.files.map(file => `
                          <div class="border rounded-lg p-3 hover:bg-gray-50">
                            <div class="flex justify-between items-center">
                              <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                  <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
                                </div>
                                <div>
                                  <p class="font-medium">${file.original_filename}</p>
                                  <p class="text-xs text-gray-500">Uploaded: ${new Date(file.uploaded_at).toLocaleDateString()}</p>
                                </div>
                              </div>
                              <a href="api/download_file.php?file_id=${file.id}" class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">
                                Download
                              </a>
                            </div>
                          </div>
                        `).join('')}
                      </div>
                    `;
                    lucide.createIcons();
                  } else {
                    filesList.innerHTML = `
                      <div class="text-center py-8">
                        <i data-lucide="file-x" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                        <p class="text-gray-500">No files found for this chapter</p>
                      </div>
                    `;
                    lucide.createIcons();
                  }
                })
                .catch(error => {
                  console.error('Error loading files:', error);
                  filesList.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                      <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-3"></i>
                      <p>Error loading files</p>
                    </div>
                  `;
                  lucide.createIcons();
                });
            } else {
              showStudentError('Failed to load chapter: ' + data.error);
            }
          })
          .catch(error => {
            console.error('Error loading chapter:', error);
            showStudentError('Failed to load chapter');
          });
      }
    });
    
    // Close upload modal
    document.getElementById('close-upload-modal').addEventListener('click', function() {
      document.getElementById('upload-modal').classList.add('hidden');
    });
    
    document.getElementById('cancel-upload').addEventListener('click', function() {
      document.getElementById('upload-modal').classList.add('hidden');
    });
    
    // Close files modal
    document.getElementById('close-files-modal').addEventListener('click', function() {
      document.getElementById('files-modal').classList.add('hidden');
    });
    
    document.getElementById('close-files-btn').addEventListener('click', function() {
      document.getElementById('files-modal').classList.add('hidden');
    });
    
    // Handle file upload
    document.getElementById('upload-thesis-form').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      const fileInput = document.getElementById('thesis-document');
      
      if (!fileInput.files.length) {
        alert('Please select a file to upload');
        return;
      }
      
      // Show progress bar
      const progressBar = document.querySelector('#upload-progress div');
      const progressText = document.getElementById('upload-percentage');
      document.getElementById('upload-progress').classList.remove('hidden');
      
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'api/upload_thesis.php', true);
      
      xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
          const percentComplete = Math.round((e.loaded / e.total) * 100);
          progressBar.style.width = percentComplete + '%';
          progressText.textContent = percentComplete + '%';
        }
      });
      
      xhr.onload = function() {
        if (xhr.status === 200) {
          try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
              alert('File uploaded successfully!');
              document.getElementById('upload-modal').classList.add('hidden');
              // Reload the page to show the newly uploaded file
              window.location.reload();
            } else {
              alert('Error: ' + response.message);
            }
          } catch (e) {
            alert('Error processing server response');
          }
        } else {
          alert('Upload failed. Server returned status: ' + xhr.status);
        }
        document.getElementById('upload-progress').classList.add('hidden');
      };
      
      xhr.onerror = function() {
        alert('Upload failed. Please try again.');
        document.getElementById('upload-progress').classList.add('hidden');
      };
      
      xhr.send(formData);
    });
    
    // New Student Registration Modal
    const newStudentModal = document.createElement('div');
    newStudentModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden';
    newStudentModal.id = 'new-student-modal';
    newStudentModal.innerHTML = `
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-semibold text-lg">Register as New Student</h3>
          <button id="close-new-student-modal" class="text-gray-500 hover:text-gray-700">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <form id="new-student-form">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
            <input type="text" name="full_name" required
                   class="w-full border rounded-lg p-2 text-sm">
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input type="email" name="email" required
                   class="w-full border rounded-lg p-2 text-sm">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Student ID</label>
              <input type="text" name="student_id" required
                     class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Program</label>
              <input type="text" name="program" required
                     class="w-full border rounded-lg p-2 text-sm">
            </div>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Student ID/Document</label>
            <input type="file" name="student_document" id="student-document" required
                   class="w-full border rounded-lg p-2 text-sm"
                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, DOC, DOCX, JPG, PNG (Max 5MB)</p>
          </div>
          <div class="flex justify-end space-x-2 mt-6">
            <button type="button" id="cancel-new-student" class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              Register
            </button>
          </div>
        </form>
        <div id="new-student-progress" class="mt-4 hidden">
          <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
          </div>
          <p class="text-xs text-center mt-1">Uploading... <span id="new-student-percentage">0%</span></p>
        </div>
      </div>
    `;
    document.body.appendChild(newStudentModal);
    
    // Show new student modal when button is clicked
    document.getElementById('new-student-btn').addEventListener('click', function() {
      document.getElementById('new-student-modal').classList.remove('hidden');
    });
    
    // Close new student modal
    document.getElementById('close-new-student-modal').addEventListener('click', function() {
      document.getElementById('new-student-modal').classList.add('hidden');
    });
    
    document.getElementById('cancel-new-student').addEventListener('click', function() {
      document.getElementById('new-student-modal').classList.add('hidden');
    });
    
    // Handle new student registration
    document.getElementById('new-student-form').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      const fileInput = document.getElementById('student-document');
      
      if (!fileInput.files.length) {
        alert('Please select a file to upload');
        return;
      }
      
      // Show progress bar
      const progressBar = document.querySelector('#new-student-progress div');
      const progressText = document.getElementById('new-student-percentage');
      document.getElementById('new-student-progress').classList.remove('hidden');
      
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'api/add_student_to_adviser.php', true);
      
      xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
          const percentComplete = Math.round((e.loaded / e.total) * 100);
          progressBar.style.width = percentComplete + '%';
          progressText.textContent = percentComplete + '%';
        }
      });
      
      xhr.onload = function() {
        if (xhr.status === 200) {
          try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
              alert('Student registration successful!');
              document.getElementById('new-student-modal').classList.add('hidden');
              // Reload the page to show updated information
              window.location.reload();
            } else {
              alert('Error: ' + response.message);
            }
          } catch (e) {
            alert('Error processing server response');
          }
        } else {
          alert('Registration failed. Server returned status: ' + xhr.status);
        }
        document.getElementById('new-student-progress').classList.add('hidden');
      };
      
      xhr.onerror = function() {
        alert('Registration failed. Please try again.');
        document.getElementById('new-student-progress').classList.add('hidden');
      };
      
      xhr.send(formData);
    });
  </script>
</body>
</html> 