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
  <!-- Modern UI Framework -->
  <link rel="stylesheet" href="assets/css/modern-ui.css">
  <!-- Word Viewer Styles and Scripts -->
  <link rel="stylesheet" href="assets/css/word-viewer.css">
  <script src="assets/js/word-viewer.js"></script>
  <style>
    /* Ensure sidebar active state works correctly */
    aside.sidebar nav .nav-link.sidebar-item.active {
      background: linear-gradient(135deg, #eff6ff, #dbeafe) !important;
      color: #1d4ed8 !important;
      font-weight: 600 !important;
      position: relative !important;
      border-radius: 0.75rem !important;
    }
    
    aside.sidebar nav .nav-link.sidebar-item.active::before {
      content: '' !important;
      position: absolute !important;
      left: 0 !important;
      top: 50% !important;
      transform: translateY(-50%) !important;
      width: 4px !important;
      height: 20px !important;
      background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
      border-radius: 2px !important;
    }
    
    /* Alternative styling if the above doesn't work */
    .active-sidebar-tab {
      background: linear-gradient(135deg, #eff6ff, #dbeafe) !important;
      color: #1d4ed8 !important;
      font-weight: 600 !important;
      position: relative !important;
      border-radius: 0.75rem !important;
    }
    
    .active-sidebar-tab::before {
      content: '' !important;
      position: absolute !important;
      left: 0 !important;
      top: 50% !important;
      transform: translateY(-50%) !important;
      width: 4px !important;
      height: 20px !important;
      background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
      border-radius: 2px !important;
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
<body class="bg-gray-25 font-sans text-sm antialiased">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="sidebar w-64 p-6 hidden md:block">
      <div class="flex items-center mb-6">
        <div class="bg-blue-100 p-2 rounded-lg mr-3">
          <i data-lucide="book-open" class="w-6 h-6 text-blue-600"></i>
        </div>
        <h1 class="text-blue-700 font-bold text-lg leading-tight">
          THESIS/CAPSTONE<br>STUDENT PORTAL
        </h1>
      </div>
      <nav class="space-y-2">
        <a href="#" data-tab="dashboard" class="nav-link sidebar-item active">
          <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
        </a>
        <a href="#" data-tab="thesis" class="nav-link sidebar-item">
          <i data-lucide="file-text" class="w-5 h-5"></i> My Thesis
        </a>
        <a href="#" data-tab="feedback" class="nav-link sidebar-item">
          <i data-lucide="message-circle" class="w-5 h-5"></i> Adviser Feedback
        </a>
        <a href="#" data-tab="review-feedback" class="nav-link sidebar-item">
          <i data-lucide="eye" class="w-5 h-5"></i> Document Review
        </a>
        <a href="#" data-tab="timeline" class="nav-link sidebar-item">
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
              <div class="p-4 border-b flex justify-between items-center">
                <div>
                  <h3 class="font-semibold" id="student-document-title">Select a chapter to view feedback</h3>
                  <p class="text-sm text-gray-500" id="student-document-info"></p>
                </div>
                <button id="student-fullscreen-btn" class="px-3 py-2 bg-indigo-100 text-indigo-800 rounded-lg text-sm hover:bg-indigo-200 transition-colors flex items-center hidden" title="Open in Full Screen">
                  <i data-lucide="maximize" class="w-4 h-4 mr-1"></i><span class="hidden lg:inline">Full Screen</span>
                </button>
              </div>
              <div class="h-full" style="height: calc(100vh - 200px);">
                <div id="word-document-viewer" class="h-full">
                  <div class="text-center py-12 text-gray-500 h-full flex flex-col justify-center">
                    <i data-lucide="file-text" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                    <p>Select a chapter from the left panel to view the document in Word-like interface</p>
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
    console.log('Setting up tab switching...');
    document.querySelectorAll('.sidebar-item').forEach(item => {
      console.log('Found sidebar item:', item.getAttribute('data-tab'));
      item.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Tab clicked:', this.getAttribute('data-tab'));
        
        // Remove active class from all tabs
        document.querySelectorAll('.sidebar-item').forEach(tab => {
          tab.classList.remove('active');
          tab.classList.remove('active-sidebar-tab');
        });
        console.log('Removed active from all tabs');
        
        // Add active class to clicked tab
        this.classList.add('active');
        this.classList.add('active-sidebar-tab');
        console.log('Added active to clicked tab:', this.getAttribute('data-tab'));
        console.log('Current classes after adding active:', this.className);
        
        // Force a repaint to see if that helps
        this.style.display = 'none';
        this.offsetHeight; // trigger reflow
        this.style.display = '';
        
        // Hide all content
        document.querySelectorAll('.tab-content').forEach(content => {
          content.classList.add('hidden');
        });
        
        // Show selected content
        const tabName = this.getAttribute('data-tab');
        const content = document.getElementById(tabName + '-content');
        console.log('Content element for', tabName + '-content', ':', content);
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
      
      // Clear cached content element when switching chapters
      window.studentContentElement = null;
      
      // Update document title
      document.getElementById('student-document-title').textContent = chapterTitle;
      document.getElementById('student-document-info').textContent = 'Loading chapter content...';
      
      // PRELOAD highlights immediately when chapter is selected
      console.log('[PRELOAD] Starting highlight preload for chapter:', chapterId);
      window.preloadHighlights(chapterId);
      
      // Load chapter data first to get files
      fetch(`api/student_review.php?action=get_chapter&chapter_id=${chapterId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const chapter = data.chapter;
            document.getElementById('student-document-info').textContent = 
              `Thesis: ${chapter.thesis_title}`;
            
            // Check if there are uploaded files
            const files = chapter.files || [];
            if (files.length > 0) {
              // Find Word documents (.doc, .docx)
              const wordFiles = files.filter(file => 
                file.original_filename.toLowerCase().endsWith('.doc') || 
                file.original_filename.toLowerCase().endsWith('.docx')
              );
              
              if (wordFiles.length > 0) {
                // Initialize Word viewer for the first Word document
                initializeWordViewer(wordFiles[0].id);
              } else {
                // Show non-Word files with download links
                showFilesList(files);
              }
            } else if (chapter.content) {
              // Show text content if no files but content exists
              showTextContent(chapter.content, chapterId);
            } else {
              // No content available
              showNoContent();
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

    // Global Word viewer instance
    let wordViewer = null;
    let studentCurrentFileId = null;

    // Initialize Word viewer with a document
    function initializeWordViewer(fileId) {
      // Create or recreate the word viewer
      const viewerContainer = document.getElementById('word-document-viewer');
      viewerContainer.innerHTML = '<div id="word-viewer-content" class="h-full"></div>';
      
      // Store the current file ID for fullscreen
      studentCurrentFileId = fileId;
      
      // Initialize the Word viewer
      wordViewer = new WordViewer('word-viewer-content', {
        showComments: true,
        showToolbar: true,
        allowZoom: true
      });
      
      // Load the document
      wordViewer.loadDocument(fileId).then(() => {
        // Load existing highlights and comments after document loads
        if (studentCurrentChapterId) {
          console.log('Loading highlights and comments for student chapter:', studentCurrentChapterId);
          
          // Optimized loading with immediate start and smart retries
          let loadAttempts = 0;
          const maxAttempts = 2; // Reduced from 3 to 2
          
          const loadHighlightsWithRetry = () => {
            loadAttempts++;
            console.log(`Loading highlights attempt ${loadAttempts}`);
            
            window.loadHighlights(studentCurrentChapterId);
            window.loadComments(studentCurrentChapterId);
            
            // Smart retry only if content isn't ready yet
            if (loadAttempts < maxAttempts) {
              const hasContent = document.querySelector('.word-content') || 
                               document.querySelector('.student-chapter-content') ||
                               document.querySelector('#word-viewer-content');
              
              if (!hasContent || !hasContent.textContent.trim()) {
                setTimeout(loadHighlightsWithRetry, 100); // Reduced from 200ms to 100ms
              }
            }
          };
          
          // Start loading immediately with no delay
          loadHighlightsWithRetry();
        }
      }).catch(error => {
        console.error('Error loading document:', error);
      });
      
      // Show the fullscreen button
      const fullscreenBtn = document.getElementById('student-fullscreen-btn');
      if (fullscreenBtn) {
        fullscreenBtn.classList.remove('hidden');
      }
    }

    // Show list of non-Word files
    function showFilesList(files) {
      const viewerContainer = document.getElementById('word-document-viewer');
      viewerContainer.innerHTML = `
        <div class="p-4 h-full overflow-y-auto">
          <div class="text-center py-8">
            <i data-lucide="file-text" class="w-16 h-16 mx-auto mb-4 text-blue-300"></i>
            <p class="text-lg font-medium mb-2">Documents Available</p>
            <p class="text-gray-500 mb-4">The following documents are available for this chapter:</p>
            <div class="space-y-2 max-w-md mx-auto">
              ${files.map(file => `
                <div class="border rounded-lg p-3 hover:bg-blue-50 flex justify-between items-center">
                  <div class="flex items-center">
                    <i data-lucide="file-text" class="w-5 h-5 text-blue-500 mr-2"></i>
                    <span class="text-sm">${file.original_filename}</span>
                  </div>
                  <div class="flex space-x-2">
                    ${(file.original_filename.toLowerCase().endsWith('.doc') || file.original_filename.toLowerCase().endsWith('.docx')) 
                      ? `<button onclick="initializeWordViewer(${file.id})" class="px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600">View</button>` 
                      : ''}
                    <a href="api/download_file.php?file_id=${file.id}" class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">Download</a>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      `;
      lucide.createIcons();
      
      // Hide fullscreen button for file list
      const fullscreenBtn = document.getElementById('student-fullscreen-btn');
      if (fullscreenBtn) {
        fullscreenBtn.classList.add('hidden');
      }
      studentCurrentFileId = null;
    }

    // Show text content (fallback for chapters with text content)
    function showTextContent(content, chapterId) {
      const viewerContainer = document.getElementById('word-document-viewer');
      viewerContainer.innerHTML = `
        <div class="p-4 h-full overflow-y-auto">
          <div class="student-chapter-content prose max-w-none" data-chapter-id="${chapterId}">
            ${content}
          </div>
        </div>
      `;
      
      // Load highlights and comments for text content as well - OPTIMIZED
      if (studentCurrentChapterId) {
        // Immediate loading for text content since DOM is ready
        window.loadHighlights(studentCurrentChapterId);
        window.loadComments(studentCurrentChapterId);
      }
      
      // Hide fullscreen button for text content
      const fullscreenBtn = document.getElementById('student-fullscreen-btn');
      if (fullscreenBtn) {
        fullscreenBtn.classList.add('hidden');
      }
      studentCurrentFileId = null;
    }

    // Show no content message
    function showNoContent() {
      const viewerContainer = document.getElementById('word-document-viewer');
      viewerContainer.innerHTML = `
        <div class="h-full flex flex-col justify-center text-center py-12 text-gray-500">
          <i data-lucide="file-x" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
          <p>No content available for this chapter</p>
        </div>
      `;
      lucide.createIcons();
      
      // Hide fullscreen button
      const fullscreenBtn = document.getElementById('student-fullscreen-btn');
      if (fullscreenBtn) {
        fullscreenBtn.classList.add('hidden');
      }
      studentCurrentFileId = null;
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
              
              // Check if the uploaded file is a Word document and display it automatically
              const uploadedFile = response.file_info;
              if (uploadedFile && (uploadedFile.original_filename.toLowerCase().endsWith('.doc') || 
                                  uploadedFile.original_filename.toLowerCase().endsWith('.docx'))) {
                // If we're currently viewing the same chapter that was uploaded to, refresh the Word viewer
                const uploadedChapterId = document.getElementById('upload-chapter-id').value;
                if (studentCurrentChapterId == uploadedChapterId) {
                  // Initialize Word viewer with the newly uploaded file
                  setTimeout(() => {
                    initializeWordViewer(uploadedFile.id);
                  }, 1000); // Small delay to ensure upload processing is complete
                }
              }
              
              // Alternatively, reload the page to show the newly uploaded file in the chapter list
              // window.location.reload();
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

    // Fullscreen functionality for student dashboard
    let studentFullscreenWordViewer = null;

    // Event listener for fullscreen button
    document.getElementById('student-fullscreen-btn').addEventListener('click', function() {
      if (!studentCurrentFileId) {
        alert('No document loaded. Please select a chapter with uploaded files to view in fullscreen.');
        return;
      }

      const documentTitle = document.getElementById('student-document-title').textContent;
      openStudentFullscreenView(documentTitle);
    });

    // Function to open fullscreen view for students
    function openStudentFullscreenView(title) {
      let fullscreenModal = document.getElementById('student-document-fullscreen-modal');
      
      if (!fullscreenModal) {
        fullscreenModal = document.createElement('div');
        fullscreenModal.id = 'student-document-fullscreen-modal';
        fullscreenModal.className = 'document-fullscreen-modal';
        fullscreenModal.innerHTML = `
          <div class="fullscreen-header">
            <div class="fullscreen-title" id="student-fullscreen-document-title">${title}</div>
            <div class="flex items-center space-x-4">
              <a id="student-fullscreen-download-btn" href="api/download_file.php?file_id=${studentCurrentFileId}" class="toolbar-action-btn" target="_blank">
                <i data-lucide="download" class="w-4 h-4 mr-2"></i>Download
              </a>
              <button id="student-fullscreen-reload-btn" class="toolbar-action-btn bg-green-100 text-green-800" onclick="forceReloadStudentFullscreen()">
                <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>Reload
              </button>
              <button id="close-student-fullscreen" class="fullscreen-close">
                <i data-lucide="x" class="w-4 h-4 mr-2"></i>Close
              </button>
            </div>
          </div>
          <div class="fullscreen-content">
            <div class="fullscreen-document">
              <div id="student-fullscreen-document-content"></div>
            </div>
          </div>
        `;
        document.body.appendChild(fullscreenModal);
        lucide.createIcons();

        // Add close functionality
        const closeBtn = fullscreenModal.querySelector('#close-student-fullscreen');
        closeBtn.addEventListener('click', function() {
          fullscreenModal.classList.remove('active');
          document.body.style.overflow = 'auto';
        });
      } else {
        fullscreenModal.querySelector('#student-fullscreen-document-title').textContent = title;
        fullscreenModal.querySelector('#student-fullscreen-document-content').innerHTML = '';
        fullscreenModal.querySelector('#student-fullscreen-download-btn').href = `api/download_file.php?file_id=${studentCurrentFileId}`;
        
        if (studentFullscreenWordViewer && typeof studentFullscreenWordViewer.destroy === 'function') {
          studentFullscreenWordViewer.destroy();
        }
        studentFullscreenWordViewer = null;
        lucide.createIcons();
      }

      // Show the modal
      fullscreenModal.classList.add('active');
      document.body.style.overflow = 'hidden';

      // Handle ESC key to close
      const handleEscape = function(e) {
        if (e.key === 'Escape') {
          fullscreenModal.classList.remove('active');
          document.body.style.overflow = 'auto';
          document.removeEventListener('keydown', handleEscape);
        }
      };
      document.addEventListener('keydown', handleEscape);

      // Initialize the WordViewer for fullscreen
      const fullscreenContent = fullscreenModal.querySelector('#student-fullscreen-document-content');
      
      if (studentCurrentFileId) {
        // Clear previous content
        fullscreenContent.innerHTML = '';
        
        // Initialize the WordViewer with proper error handling
        try {
          console.log('[Student Fullscreen] Creating new WordViewer instance...');
          studentFullscreenWordViewer = new WordViewer('student-fullscreen-document-content', {
            showComments: true,
            showToolbar: false,
            allowZoom: true
          });
          
          // Check if WordViewer was properly initialized
          if (!studentFullscreenWordViewer || !studentFullscreenWordViewer.container) {
            throw new Error('Failed to initialize WordViewer container');
          }
          
                     // Load the document with proper timeout handling
           const loadWithTimeout = async () => {
             const titleElement = fullscreenModal.querySelector('#student-fullscreen-document-title');
             const originalTitle = titleElement.textContent;
             
             try {
               // Update title to show loading
               titleElement.textContent = originalTitle + ' (Loading...)';
               
               console.log('[Student Fullscreen] Starting document load for file ID:', studentCurrentFileId);
               
               // Show loading indicator
               fullscreenContent.innerHTML = `
                 <div class="text-center py-12">
                   <div class="spinner"></div>
                   <h3 class="text-lg font-semibold mb-2">Loading Document...</h3>
                   <p class="text-sm text-gray-600 mb-4">Please wait while we process your document</p>
                   <div class="text-xs text-gray-500">
                     <div>• Fetching document content</div>
                     <div>• Processing Word document structure</div>
                     <div>• Preparing for display</div>
                   </div>
                 </div>
               `;
               
               // Set a reasonable timeout for the loading (reduced to 20 seconds)
               const timeoutPromise = new Promise((_, reject) => 
                 setTimeout(() => reject(new Error('Document loading timed out after 20 seconds')), 20000)
               );
               
               // Add retry mechanism
               let attempts = 0;
               const maxAttempts = 2;
               
               while (attempts < maxAttempts) {
                 try {
                   attempts++;
                   console.log(`[Student Fullscreen] Load attempt ${attempts}/${maxAttempts}`);
                   
                   await Promise.race([
                     studentFullscreenWordViewer.loadDocument(studentCurrentFileId),
                     timeoutPromise
                   ]);
                   
                   console.log('[Student Fullscreen] Document loaded successfully');
                   
                   // Load highlights and comments for fullscreen view - OPTIMIZED
                   if (studentCurrentChapterId) {
                     console.log('[Student Fullscreen] Loading highlights and comments...');
                     
                     // OPTIMIZATION 6: Use cached highlights for instant loading
                     if (window.cachedHighlights && 
                         window.cachedHighlights.chapterId == studentCurrentChapterId &&
                         (Date.now() - window.cachedHighlights.timestamp) < 30000) {
                       console.log('[FULLSCREEN INSTANT] Using cached highlights for instant loading');
                       setTimeout(() => {
                         applyHighlightsToFullscreen(window.cachedHighlights.highlights);
                         window.loadComments(studentCurrentChapterId);
                       }, 100); // Ultra-fast for cached highlights
                     } else {
                       console.log('[FULLSCREEN] Loading highlights from server');
                       setTimeout(() => {
                         loadHighlightsForFullscreen(studentCurrentChapterId);
                         window.loadComments(studentCurrentChapterId);
                       }, 200); // Significantly reduced from 1500ms to 200ms
                     }
                   }
                   
                   // Restore original title
                   titleElement.textContent = originalTitle;
                   return; // Success, exit function
                   
                 } catch (attemptError) {
                   console.log(`[Student Fullscreen] Attempt ${attempts} failed:`, attemptError.message);
                   if (attempts >= maxAttempts) {
                     throw attemptError; // Re-throw the error if all attempts failed
                   }
                   // Wait 2 seconds before retry
                   await new Promise(resolve => setTimeout(resolve, 2000));
                 }
               }
              
                         } catch (error) {
               console.error('[Student Fullscreen] Error loading document:', error);
               
               // Restore original title first
               titleElement.textContent = originalTitle;
               
               // Show error message in fullscreen with more details
               fullscreenContent.innerHTML = `
                 <div class="text-center py-12">
                   <i data-lucide="alert-triangle" class="w-16 h-16 mx-auto mb-4 text-red-600"></i>
                   <h3 class="text-lg font-semibold mb-2 text-red-800">Unable to Load Document</h3>
                   <p class="text-sm text-gray-600 mb-4">${error.message}</p>
                   
                   <div class="bg-gray-50 p-4 rounded-lg mb-4 text-left max-w-md mx-auto">
                     <h4 class="font-semibold text-sm mb-2">What you can try:</h4>
                     <ul class="text-xs text-gray-600 space-y-1">
                       <li>• Click "Try Again" to reload the document</li>
                       <li>• Check your internet connection</li>
                       <li>• Download the document to view it locally</li>
                       <li>• Close fullscreen and try again</li>
                     </ul>
                   </div>
                   
                   <div class="space-y-2">
                     <button onclick="forceReloadStudentFullscreen()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                       Try Again
                     </button>
                     <br>
                     <a href="api/download_file.php?file_id=${studentCurrentFileId}" 
                        class="inline-block px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" 
                        target="_blank">
                       Download Document
                     </a>
                     <br>
                     <button onclick="document.getElementById('close-student-fullscreen').click()" 
                             class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                       Close Fullscreen
                     </button>
                   </div>
                 </div>
               `;
               lucide.createIcons();
             }
          };
          
          // Start loading
          loadWithTimeout();
          
        } catch (error) {
          console.error('[Student Fullscreen] Error creating WordViewer:', error);
          fullscreenContent.innerHTML = `
            <div class="text-center py-12">
              <i data-lucide="alert-triangle" class="w-16 h-16 mx-auto mb-4 text-red-600"></i>
              <h3 class="text-lg font-semibold mb-2 text-red-800">Error Creating Document Viewer</h3>
              <p class="text-sm text-gray-600 mb-4">${error.message}</p>
              <button onclick="forceReloadStudentFullscreen()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Try Again
              </button>
            </div>
          `;
          lucide.createIcons();
        }
      }
    }

    // Force reload function for student fullscreen
    window.forceReloadStudentFullscreen = function() {
      console.log('[Student Force Reload] Starting force reload...');
      
      if (!studentCurrentFileId) {
        alert('No file selected. Please select a chapter first.');
        return;
      }
      
      const fullscreenContent = document.getElementById('student-fullscreen-document-content');
      if (!fullscreenContent) {
        alert('Fullscreen modal not found. Please close and reopen fullscreen mode.');
        return;
      }
      
      // Show loading indicator immediately
      fullscreenContent.innerHTML = `
        <div class="text-center py-12">
          <div class="spinner"></div>
          <h3 class="text-lg font-semibold mb-2">Reloading Document...</h3>
          <p class="text-sm text-gray-600">Please wait while we reload the document</p>
        </div>
      `;
      
      try {
        // Clean up previous instance
        if (studentFullscreenWordViewer && typeof studentFullscreenWordViewer.destroy === 'function') {
          studentFullscreenWordViewer.destroy();
        }
        studentFullscreenWordViewer = null;
        
        // Small delay to ensure cleanup
        setTimeout(() => {
          try {
            console.log('[Student Force Reload] Creating new WordViewer instance...');
            studentFullscreenWordViewer = new WordViewer('student-fullscreen-document-content', {
              showComments: true,
              showToolbar: false,
              allowZoom: true
            });
            
            console.log('[Student Force Reload] Loading document...');
            
            // Set a timeout for force reload (shorter than normal load)
            const reloadTimeout = setTimeout(() => {
              console.error('[Student Force Reload] Reload timed out');
              fullscreenContent.innerHTML = `
                <div class="text-center py-12">
                  <i data-lucide="clock" class="w-16 h-16 mx-auto mb-4 text-orange-600"></i>
                  <h3 class="text-lg font-semibold mb-2 text-orange-800">Reload Timed Out</h3>
                  <p class="text-sm text-gray-600 mb-4">The document is taking too long to reload.</p>
                  <div class="space-y-2">
                    <button onclick="forceReloadStudentFullscreen()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                      Try Again
                    </button>
                    <br>
                    <a href="api/download_file.php?file_id=${studentCurrentFileId}" 
                       class="inline-block px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" 
                       target="_blank">
                      Download Document
                    </a>
                  </div>
                </div>
              `;
              lucide.createIcons();
            }, 15000); // 15 second timeout for reload
            
            studentFullscreenWordViewer.loadDocument(studentCurrentFileId)
              .then(() => {
                clearTimeout(reloadTimeout);
                console.log('[Student Force Reload] Document loaded successfully');
                
                                 // Load highlights and comments for force reload - ULTRA OPTIMIZED
                 if (studentCurrentChapterId) {
                   console.log('[Student Force Reload] Loading highlights and comments...');
                   
                   // OPTIMIZATION 5: Immediate loading with cached highlights
                   if (window.cachedHighlights && 
                       window.cachedHighlights.chapterId == studentCurrentChapterId &&
                       (Date.now() - window.cachedHighlights.timestamp) < 30000) {
                     console.log('[FULLSCREEN INSTANT] Using cached highlights immediately');
                     // Apply highlights immediately with minimal delay
                     setTimeout(() => {
                       applyHighlightsToFullscreen(window.cachedHighlights.highlights);
                       window.loadComments(studentCurrentChapterId);
                     }, 100); // Ultra-fast for cached highlights
                   } else {
                     // Fallback to loading highlights
                     setTimeout(() => {
                       loadHighlightsForFullscreen(studentCurrentChapterId);
                       window.loadComments(studentCurrentChapterId);
                     }, 200); // Reduced from 300ms to 200ms
                   }
                 }
              })
              .catch(error => {
                clearTimeout(reloadTimeout);
                console.error('[Student Force Reload] Error loading document:', error);
                fullscreenContent.innerHTML = `
                  <div class="text-center py-12">
                    <i data-lucide="alert-triangle" class="w-16 h-16 mx-auto mb-4 text-red-600"></i>
                    <h3 class="text-lg font-semibold mb-2 text-red-800">Reload Failed</h3>
                    <p class="text-sm text-gray-600 mb-4">${error.message}</p>
                    <div class="space-y-2">
                      <button onclick="forceReloadStudentFullscreen()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Try Again
                      </button>
                      <br>
                      <a href="api/download_file.php?file_id=${studentCurrentFileId}" 
                         class="inline-block px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" 
                         target="_blank">
                        Download Document
                      </a>
                    </div>
                  </div>
                `;
                lucide.createIcons();
              });
              
          } catch (error) {
            console.error('[Student Force Reload] Error creating viewer:', error);
            fullscreenContent.innerHTML = `
              <div class="text-center py-12">
                <i data-lucide="alert-triangle" class="w-16 h-16 mx-auto mb-4 text-red-600"></i>
                <h3 class="text-lg font-semibold mb-2 text-red-800">Error Creating Viewer</h3>
                <p class="text-sm text-gray-600 mb-4">${error.message}</p>
                <button onclick="document.getElementById('close-student-fullscreen').click()" 
                        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                  Close Fullscreen
                </button>
              </div>
            `;
            lucide.createIcons();
          }
        }, 500); // 500ms delay
        
      } catch (error) {
        console.error('[Student Force Reload] Initial error:', error);
        fullscreenContent.innerHTML = `
          <div class="text-center py-12">
            <i data-lucide="alert-triangle" class="w-16 h-16 mx-auto mb-4 text-red-600"></i>
            <h3 class="text-lg font-semibold mb-2 text-red-800">Unexpected Error</h3>
            <p class="text-sm text-gray-600 mb-4">${error.message}</p>
            <button onclick="document.getElementById('close-student-fullscreen').click()" 
                    class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
              Close Fullscreen
            </button>
          </div>
        `;
        lucide.createIcons();
      }
    };

    // Load existing comments - moved to global scope
    window.loadComments = function(chapterId) {
      fetch(`api/document_review.php?action=get_comments&chapter_id=${chapterId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            displayComments(data.comments);
          }
        })
        .catch(error => console.error('Error loading comments:', error));
    };

    // PRELOAD highlights - fetch and cache before content is ready
    window.preloadHighlights = function(chapterId) {
      console.log('[PRELOAD] Fetching highlights for chapter:', chapterId);
      fetch(`api/student_review.php?action=get_highlights&chapter_id=${chapterId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            console.log('[PRELOAD] Highlights fetched and cached:', data.highlights.length);
            // Cache the highlights for immediate use
            window.cachedHighlights = {
              chapterId: chapterId,
              highlights: data.highlights,
              timestamp: Date.now()
            };
          } else {
            console.error('[PRELOAD] Failed to fetch highlights:', data.error);
          }
        })
        .catch(error => console.error('[PRELOAD] Error fetching highlights:', error));
    };

    // Load existing highlights - for students to see adviser highlights - OPTIMIZED
    window.loadHighlights = function(chapterId) {
      console.log('Loading highlights for chapter:', chapterId);
      
      // Check if we have cached highlights for this chapter (less than 30 seconds old)
      if (window.cachedHighlights && 
          window.cachedHighlights.chapterId == chapterId &&
          (Date.now() - window.cachedHighlights.timestamp) < 30000) {
        console.log('[CACHE HIT] Using cached highlights:', window.cachedHighlights.highlights.length);
        applyHighlightsForStudents(window.cachedHighlights.highlights);
        return;
      }
      
      console.log('[CACHE MISS] Fetching highlights from server');
      fetch(`api/student_review.php?action=get_highlights&chapter_id=${chapterId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            console.log('Highlights loaded:', data.highlights);
            // Update cache
            window.cachedHighlights = {
              chapterId: chapterId,
              highlights: data.highlights,
              timestamp: Date.now()
            };
            applyHighlightsForStudents(data.highlights);
          } else {
            console.error('Failed to load highlights:', data.error);
          }
        })
        .catch(error => console.error('Error loading highlights:', error));
    };

    // Load highlights specifically for fullscreen view - OPTIMIZED
    function loadHighlightsForFullscreen(chapterId) {
      console.log('[FULLSCREEN] Loading highlights for fullscreen chapter:', chapterId);
      
      // OPTIMIZATION 1: Use cached highlights if available
      if (window.cachedHighlights && 
          window.cachedHighlights.chapterId == chapterId &&
          (Date.now() - window.cachedHighlights.timestamp) < 30000) {
        console.log('[FULLSCREEN CACHE HIT] Using cached highlights:', window.cachedHighlights.highlights.length);
        applyHighlightsToFullscreen(window.cachedHighlights.highlights);
        return;
      }
      
      console.log('[FULLSCREEN CACHE MISS] Fetching highlights from server');
      fetch(`api/student_review.php?action=get_highlights&chapter_id=${chapterId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            console.log('[FULLSCREEN] Highlights loaded:', data.highlights);
            // Update cache for future use
            window.cachedHighlights = {
              chapterId: chapterId,
              highlights: data.highlights,
              timestamp: Date.now()
            };
            applyHighlightsToFullscreen(data.highlights);
          } else {
            console.error('[FULLSCREEN] Failed to load highlights:', data.error);
          }
        })
        .catch(error => console.error('[FULLSCREEN] Error loading highlights:', error));
    }

    // Apply highlights for students (read-only view) - OPTIMIZED
    function applyHighlightsForStudents(highlights) {
      if (!highlights || highlights.length === 0) {
        console.log('No highlights to apply');
        return;
      }

      console.log('Applying', highlights.length, 'highlights for student view');
      
      // Check if content is ready immediately
      const contentReady = document.querySelector('.word-content') || 
                          document.querySelector('.student-chapter-content') ||
                          document.querySelector('#word-viewer-content');
      
      if (contentReady && contentReady.textContent.trim().length > 50) {
        // Content is ready, apply highlights immediately
        console.log('Content ready, applying highlights immediately');
        highlights.forEach(highlight => {
          console.log('Processing highlight:', highlight);
          applyHighlightToContent(highlight);
        });
      } else {
        // Content not ready, use minimal delay with retry
        console.log('Content not ready, using minimal delay');
        setTimeout(() => {
          highlights.forEach(highlight => {
            console.log('Processing highlight:', highlight);
            applyHighlightToContent(highlight);
          });
        }, 100); // Reduced from 500ms to 100ms
      }
    }

    // Apply highlights specifically for fullscreen view - ULTRA OPTIMIZED
    function applyHighlightsToFullscreen(highlights) {
      if (!highlights || highlights.length === 0) {
        console.log('[FULLSCREEN] No highlights to apply to fullscreen');
        return;
      }

      console.log('[FULLSCREEN] Applying', highlights.length, 'highlights to fullscreen view');
      
      // OPTIMIZATION 2: Try immediate application first
      const fullscreenContent = document.querySelector('#student-fullscreen-document-content');
      if (fullscreenContent && fullscreenContent.textContent && fullscreenContent.textContent.trim().length > 50) {
        console.log('[FULLSCREEN IMMEDIATE] Content ready, applying highlights immediately');
        let appliedCount = 0;
        
        highlights.forEach(highlight => {
          if (applyHighlightToFullscreenContent(highlight, fullscreenContent)) {
            appliedCount++;
          }
        });
        
        console.log(`[FULLSCREEN IMMEDIATE] Applied ${appliedCount}/${highlights.length} highlights`);
        
        // If all highlights applied successfully, we're done
        if (appliedCount === highlights.length) {
          return;
        }
      }
      
      // OPTIMIZATION 3: Fast retry mechanism with reduced attempts
      let retryCount = 0;
      const maxRetries = 3; // Reduced from 5 to 3
      
      function tryApplyHighlights() {
        retryCount++;
        console.log(`[FULLSCREEN RETRY] Attempt ${retryCount}/${maxRetries}`);
        
        const fullscreenContent = document.querySelector('#student-fullscreen-document-content');
        if (!fullscreenContent) {
          console.error('[FULLSCREEN] Content container not found');
          if (retryCount < maxRetries) {
            setTimeout(tryApplyHighlights, 100); // Reduced from 200ms to 100ms
          }
          return;
        }

        // Quick content check
        const hasTextContent = fullscreenContent.textContent && fullscreenContent.textContent.trim().length > 50;
        if (!hasTextContent) {
          console.log('[FULLSCREEN] Content not ready yet, retrying...');
          if (retryCount < maxRetries) {
            setTimeout(tryApplyHighlights, 100); // Reduced from 200ms to 100ms
          }
          return;
        }

        console.log('[FULLSCREEN] Content ready, applying highlights...');
        let appliedCount = 0;
        
        highlights.forEach(highlight => {
          if (applyHighlightToFullscreenContent(highlight, fullscreenContent)) {
            appliedCount++;
          }
        });
        
        console.log(`[FULLSCREEN] Applied ${appliedCount}/${highlights.length} highlights`);
        
        // Only retry if we didn't apply many highlights and haven't reached max retries
        if (appliedCount < Math.max(1, highlights.length * 0.5) && retryCount < maxRetries) {
          console.log('[FULLSCREEN] Some highlights missing, retrying...');
          setTimeout(tryApplyHighlights, 100); // Reduced from 200ms to 100ms
        }
      }
      
      // OPTIMIZATION 4: Start with ultra-minimal delay
      setTimeout(tryApplyHighlights, 50); // Reduced from 200ms to 50ms
    }

    // Apply individual highlight to content - OPTIMIZED
    function applyHighlightToContent(highlight) {
      // Optimized selector priority - most common first for better performance
      const prioritizedSelectors = [
        '.word-content',                                    // Most common - WordViewer content
        '#word-viewer-content',                            // Second most common - Direct container
        '.student-chapter-content',                        // Text content fallback
        '.chapter-content',                                 // Legacy content
        '.word-document',                                   // Document container
        '#student-fullscreen-document-content .word-content', // Fullscreen specific
        '#student-fullscreen-document-content',            // Direct fullscreen container
        '#student-fullscreen-document-content .word-document', // Fullscreen document
        '.fullscreen-document .word-content'               // Alternative fullscreen content
      ];

      let contentElement = null;
      
      // Fast path - check if we already have content cached
      if (window.studentContentElement && window.studentContentElement.textContent.trim()) {
        contentElement = window.studentContentElement;
        console.log('Using cached content element');
      } else {
        // Find and cache content element
        for (const selector of prioritizedSelectors) {
          contentElement = document.querySelector(selector);
          if (contentElement && contentElement.textContent.trim().length > 50) {
            console.log('Found content element with selector:', selector);
            window.studentContentElement = contentElement; // Cache for future use
            break;
          }
        }
      }

      if (!contentElement) {
        console.log('No content element found for highlight application');
        console.log('Available elements:', document.querySelectorAll('*[id*="content"], *[class*="content"], *[class*="word"]'));
        return;
      }

      // Find text nodes containing the highlighted text
      const walker = document.createTreeWalker(
        contentElement,
        NodeFilter.SHOW_TEXT,
        null,
        false
      );

      let node;
      while (node = walker.nextNode()) {
        const text = node.textContent;
        const highlightText = highlight.highlighted_text;
        const index = text.indexOf(highlightText);

        if (index !== -1) {
          console.log('Found matching text for highlight:', highlightText);
          
          try {
            // Create highlight span
            const highlightSpan = document.createElement('mark');
            highlightSpan.style.backgroundColor = highlight.highlight_color || '#ffeb3b';
            highlightSpan.className = 'highlight-marker student-view-highlight';
            highlightSpan.dataset.highlightId = highlight.id;
            highlightSpan.title = `Highlighted by ${highlight.adviser_name}`;
            highlightSpan.style.cursor = 'help';
            highlightSpan.style.position = 'relative';

            // Add click handler to show highlight info
            highlightSpan.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              showHighlightInfo(highlight);
            });

            // Split the text node and wrap the highlighted portion
            const beforeText = text.substring(0, index);
            const afterText = text.substring(index + highlightText.length);

            if (beforeText) {
              const beforeNode = document.createTextNode(beforeText);
              node.parentNode.insertBefore(beforeNode, node);
            }

            highlightSpan.textContent = highlightText;
            node.parentNode.insertBefore(highlightSpan, node);

            if (afterText) {
              const afterNode = document.createTextNode(afterText);
              node.parentNode.insertBefore(afterNode, node);
            }

            // Remove the original text node
            node.parentNode.removeChild(node);
            console.log('Successfully applied highlight to text');
            break; // Found and processed this highlight
          } catch (e) {
            console.error('Error applying highlight:', e);
          }
        }
      }
    }

    // Apply individual highlight to fullscreen content
    function applyHighlightToFullscreenContent(highlight, fullscreenContainer) {
      // Look for content within the fullscreen container
      const possibleSelectors = [
        '.word-content',
        '.word-document',
        '.word-page',
        ''  // Empty string means search in the container itself
      ];

      let contentElement = null;
      for (const selector of possibleSelectors) {
        if (selector === '') {
          contentElement = fullscreenContainer;
        } else {
          contentElement = fullscreenContainer.querySelector(selector);
        }
        
        if (contentElement && contentElement.textContent.trim()) {
          console.log('Found fullscreen content element with selector:', selector || 'container');
          break;
        }
      }

      if (!contentElement) {
        console.log('No fullscreen content element found for highlight application');
        console.log('Fullscreen container content:', fullscreenContainer.innerHTML.substring(0, 200));
        return false;
      }

      // Find text nodes containing the highlighted text
      const walker = document.createTreeWalker(
        contentElement,
        NodeFilter.SHOW_TEXT,
        null,
        false
      );

      let node;
      while (node = walker.nextNode()) {
        const text = node.textContent;
        const highlightText = highlight.highlighted_text;
        const index = text.indexOf(highlightText);

        if (index !== -1) {
          console.log('Found matching text for fullscreen highlight:', highlightText);
          
          try {
            // Create highlight span
            const highlightSpan = document.createElement('mark');
            highlightSpan.style.backgroundColor = highlight.highlight_color || '#ffeb3b';
            highlightSpan.className = 'highlight-marker student-view-highlight fullscreen-student-highlight';
            highlightSpan.dataset.highlightId = highlight.id;
            highlightSpan.title = `Highlighted by ${highlight.adviser_name}`;
            highlightSpan.style.cursor = 'help';
            highlightSpan.style.position = 'relative';

            // Add click handler to show highlight info
            highlightSpan.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              showHighlightInfo(highlight);
            });

            // Split the text node and wrap the highlighted portion
            const beforeText = text.substring(0, index);
            const afterText = text.substring(index + highlightText.length);

            if (beforeText) {
              const beforeNode = document.createTextNode(beforeText);
              node.parentNode.insertBefore(beforeNode, node);
            }

            highlightSpan.textContent = highlightText;
            node.parentNode.insertBefore(highlightSpan, node);

            if (afterText) {
              const afterNode = document.createTextNode(afterText);
              node.parentNode.insertBefore(afterNode, node);
            }

            // Remove the original text node
            node.parentNode.removeChild(node);
            console.log('Successfully applied fullscreen highlight to text');
            return true; // Success
          } catch (e) {
            console.error('Error applying fullscreen highlight:', e);
            return false;
          }
        }
      }
      
      console.log('Highlight text not found in fullscreen content:', highlight.highlighted_text);
      return false; // Text not found
    }

    // Show highlight information when student clicks on a highlight - ENHANCED
    function showHighlightInfo(highlight) {
      // Check if we're in fullscreen mode
      const isFullscreen = document.querySelector('.document-fullscreen-modal.active') !== null;
      const modalId = isFullscreen ? 'fullscreen-highlight-info-modal' : 'highlight-info-modal';
      const zIndex = isFullscreen ? 'z-[9999]' : 'z-50'; // Higher z-index for fullscreen
      
      console.log('[HIGHLIGHT INFO] Showing highlight info, fullscreen:', isFullscreen);
      
      // Create a modal to show highlight details with comments
      const existingModal = document.getElementById(modalId);
      if (existingModal) {
        existingModal.remove();
      }

      // Create the modal structure first
      const modal = document.createElement('div');
      modal.id = modalId;
      modal.className = `fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center ${zIndex}`;
      
      // Show loading state first
      modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 w-full max-w-lg mx-4 max-h-[80vh] overflow-y-auto shadow-2xl">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">💡 Adviser Note</h3>
            <button class="close-modal-btn text-gray-500 hover:text-gray-700" onclick="document.getElementById('${modalId}').remove()">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          <div class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-3"></div>
            <p class="text-sm text-gray-500">Loading highlight details...</p>
          </div>
        </div>
      `;

      // Add to appropriate container
      if (isFullscreen) {
        const fullscreenModal = document.querySelector('.document-fullscreen-modal.active');
        if (fullscreenModal) {
          fullscreenModal.appendChild(modal);
        } else {
          document.body.appendChild(modal);
        }
      } else {
        document.body.appendChild(modal);
      }

      // Fetch comments for this highlight
      fetchHighlightComments(highlight.id)
        .then(comments => {
          // Update modal with full content including comments
          const modalContent = modal.querySelector('.bg-white');
          modalContent.innerHTML = `
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold flex items-center gap-2">
                💡 <span>Adviser Note</span>
              </h3>
              <button class="close-modal-btn text-gray-500 hover:text-gray-700" onclick="document.getElementById('${modalId}').remove()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            
            <!-- Adviser Info -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Highlighted by:</label>
              <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                  ${highlight.adviser_name ? highlight.adviser_name.charAt(0).toUpperCase() : 'A'}
                </div>
                <div>
                  <span class="font-medium text-blue-900">${highlight.adviser_name || 'Adviser'}</span>
                  <p class="text-xs text-blue-600">${new Date(highlight.created_at).toLocaleString()}</p>
                </div>
              </div>
            </div>
            
            <!-- Highlighted Text -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Highlighted Text:</label>
              <div class="p-4 rounded-lg border border-yellow-300 bg-gradient-to-r from-yellow-50 to-orange-50">
                <mark style="background: linear-gradient(135deg, ${highlight.highlight_color || '#ffeb3b'}, ${highlight.highlight_color || '#ffeb3b'}88); padding: 4px 8px; border-radius: 6px; font-weight: 500; border: 1px solid #d97706;">
                  "${highlight.highlighted_text}"
                </mark>
              </div>
            </div>
            
            <!-- Comments Section -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Adviser Comments ${comments.length > 0 ? `(${comments.length})` : ''}:
              </label>
              <div class="space-y-3 max-h-48 overflow-y-auto">
                ${comments.length > 0 ? comments.map(comment => `
                  <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-orange-400">
                    <div class="flex justify-between items-start mb-2">
                      <span class="text-sm font-medium text-gray-800">${comment.adviser_name || 'Adviser'}</span>
                      <span class="text-xs text-gray-500">${new Date(comment.created_at).toLocaleDateString()}</span>
                    </div>
                    <p class="text-sm text-gray-700">${comment.comment_text}</p>
                  </div>
                `).join('') : `
                  <div class="p-4 text-center text-gray-500 bg-gray-50 rounded-lg">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.001 8.001 0 01-7.003-4.003c-.598-1.505-.92-3.162-.92-4.997C5.077 7.582 8.582 4 13 4s8 3.582 8 8z"></path>
                    </svg>
                    <p class="text-sm">No additional comments for this highlight</p>
                  </div>
                `}
              </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t">
              <button onclick="document.getElementById('${modalId}').remove()" 
                      class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Close
              </button>
            </div>
          `;
        })
        .catch(error => {
          console.error('[HIGHLIGHT INFO] Error loading comments:', error);
          // Show error state
          const modalContent = modal.querySelector('.bg-white');
          modalContent.innerHTML = `
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold">💡 Adviser Note</h3>
              <button class="close-modal-btn text-gray-500 hover:text-gray-700" onclick="document.getElementById('${modalId}').remove()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            <div class="text-center py-8">
              <svg class="w-12 h-12 mx-auto mb-3 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
              <p class="text-sm text-red-600 mb-4">Failed to load comment details</p>
              <button onclick="document.getElementById('${modalId}').remove()" 
                      class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                Close
              </button>
            </div>
          `;
        });

      // Close modal when clicking outside
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          modal.remove();
        }
      });

      // Close modal with ESC key (only if not blocked by fullscreen)
      const handleEscape = function(e) {
        if (e.key === 'Escape') {
          modal.remove();
          document.removeEventListener('keydown', handleEscape);
        }
      };
      document.addEventListener('keydown', handleEscape);
    }

    // Fetch comments associated with a specific highlight
    function fetchHighlightComments(highlightId) {
      console.log('[HIGHLIGHT COMMENTS] Fetching comments for highlight:', highlightId);
      
      return fetch(`api/student_review.php?action=get_highlight_comments&highlight_id=${highlightId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            console.log('[HIGHLIGHT COMMENTS] Comments loaded:', data.comments.length);
            return data.comments || [];
          } else {
            console.error('[HIGHLIGHT COMMENTS] Failed to load comments:', data.error);
            return [];
          }
        })
        .catch(error => {
          console.error('[HIGHLIGHT COMMENTS] Error fetching comments:', error);
          return [];
        });
    }
  </script>

  <!-- Modern UI Framework -->
  <script src="assets/js/modern-ui.js"></script>
</body>
</html> 