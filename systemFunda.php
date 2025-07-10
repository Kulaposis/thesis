<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/thesis_functions.php';

// Require adviser login
$auth = new Auth();
$auth->requireRole('adviser');

// Get current user
$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();

// Get adviser's theses and statistics
$theses = $thesisManager->getAdviserTheses($user['id']);
$stats = $thesisManager->getAdviserStats($user['id']);

// Get all students not assigned to any adviser
$db = new Database();
$pdo = $db->getConnection();
$sql = "SELECT u.* FROM users u 
        LEFT JOIN theses t ON u.id = t.student_id AND t.adviser_id = :adviser_id
        WHERE u.role = 'student' AND t.id IS NULL";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':adviser_id', $user['id']);
$stmt->execute();
$unassigned_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thesis Management Dashboard - Adviser</title>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Modern UI Framework -->
  <link rel="stylesheet" href="assets/css/modern-ui.css">
  <!-- Word Viewer Styles and Scripts -->
  <link rel="stylesheet" href="assets/css/word-viewer.css">
  <script src="assets/js/word-viewer.js"></script>
  <style>
    :root {
      --primary: #3b82f6;
      --primary-hover: #2563eb;
      --secondary: #64748b;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
    }
    
    .active-tab {
      @apply text-blue-600 font-semibold bg-blue-50 rounded-md transition-colors duration-200;
    }
    
    .student-row:hover {
      @apply bg-blue-50 cursor-pointer transition-colors duration-150;
    }
    
    .progress-bar {
      transition: width 0.6s ease;
    }
    
    .fade-in {
      animation: fadeIn 0.3s ease-in-out;
    }
    
    .card-hover {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .card-hover:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(5px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: #a1a1a1;
    }
    
    .sidebar-transition {
      transition: all 0.3s ease;
    }
    
    // Add after the existing styles:
    .paragraph-container {
      transition: background-color 0.3s ease;
    }
    
    .paragraph-container:hover {
      background-color: rgba(59, 130, 246, 0.05);
    }
    
    .paragraph-container.has-comments {
      cursor: pointer;
    }
    
    .highlight-paragraph {
      animation: highlightParagraph 2s ease;
    }
    
    .highlight-comment {
      animation: highlightComment 2s ease;
    }
    
    @keyframes highlightParagraph {
      0%, 100% { background-color: transparent; }
      50% { background-color: rgba(59, 130, 246, 0.2); }
    }
    
    @keyframes highlightComment {
      0%, 100% { box-shadow: none; }
      50% { box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5); }
    }
    
    /* Responsive Document Review Styles */
    .sidebar-mobile-open {
      overflow: hidden;
    }
    
    .sidebar-mobile-open::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 25;
    }
    
    @media (max-width: 768px) {
      #document-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 30;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }
      
      #document-sidebar.sidebar-open {
        transform: translateX(0);
      }
      
      .document-review-container {
        flex-direction: column;
      }
      
      #right-panels-container .max-w-md {
        max-width: 100%;
      }
      
      #panel-triggers {
        bottom: 4rem;
        right: 1rem;
      }
      
      /* Ensure document viewer takes full width on mobile */
      #document-review-content .flex {
        width: 100%;
      }
    }
    
    @media (max-width: 640px) {
      #document-tools .hidden {
        display: none !important;
      }
      
      #document-tools button {
        padding: 0.5rem;
      }
      
      #document-tools span {
        display: none;
      }
      
      .toolbar-wrap {
        flex-wrap: wrap;
        gap: 0.5rem;
      }
    }
  </style>
</head>

<body class="bg-gray-25 font-sans text-sm antialiased">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="sidebar w-64 p-6 sidebar-transition hidden md:block">
      <div class="flex items-center mb-6">
        <div class="bg-blue-100 p-2 rounded-lg mr-3">
          <i data-lucide="book-open" class="w-6 h-6 text-blue-600"></i>
        </div>
        <h1 class="text-blue-700 font-bold text-lg leading-tight">
          THESIS/CAPSTONE<br>MANAGEMENT
        </h1>
      </div>
      <nav class="space-y-2">
        <a href="#" data-tab="dashboard" class="nav-link sidebar-item active">
          <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
        </a>
        <a href="#" data-tab="students" class="nav-link sidebar-item">
          <i data-lucide="users" class="w-5 h-5"></i> Students
        </a>
        <a href="#" data-tab="theses" class="nav-link sidebar-item">
          <i data-lucide="book" class="w-5 h-5"></i> Theses
        </a>
        <a href="#" data-tab="document-review" class="nav-link sidebar-item">
          <i data-lucide="file-edit" class="w-5 h-5"></i> Document Review
        </a>
        <a href="#" data-tab="feedback" class="nav-link sidebar-item">
          <i data-lucide="message-circle" class="w-5 h-5"></i> Feedback
        </a>
        <a href="#" data-tab="timeline" class="nav-link sidebar-item">
          <i data-lucide="clock" class="w-5 h-5"></i> Timeline
        </a>
        <a href="#" data-tab="activity-logs" class="nav-link sidebar-item">
          <i data-lucide="activity" class="w-5 h-5"></i> Activity Logs
        </a>
        <a href="#" data-tab="reports" class="nav-link sidebar-item">
          <i data-lucide="bar-chart" class="w-5 h-5"></i> Reports
        </a>
      </nav>
      <div class="mt-auto pt-6">
        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
          <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold truncate"><?php echo htmlspecialchars($user['full_name']); ?></p>
            <p class="text-gray-500 text-xs">Adviser</p>
          </div>
          <a href="logout.php" class="text-gray-400 hover:text-gray-600">
            <i data-lucide="log-out" class="w-4 h-4"></i>
          </a>
        </div>
      </div>
    </aside>

    <!-- Mobile sidebar toggle -->
    <button id="sidebarToggle" class="md:hidden fixed top-4 left-4 z-50 bg-white p-2 rounded-lg shadow-md">
      <i data-lucide="menu" class="w-5 h-5"></i>
    </button>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:p-8">
      <div class="flex justify-between items-center mb-8">
        <div class="fade-in">
          <h2 class="heading-lg text-gradient">Dashboard</h2>
          <p class="body-sm text-gray-500 mt-1">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?></p>
        </div>
        <div class="flex items-center gap-4">
          <!-- Notifications -->
          <button class="btn btn-ghost hover-lift relative">
            <i data-lucide="bell" class="w-5 h-5"></i>
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full flex items-center justify-center">
              <span class="w-1.5 h-1.5 bg-white rounded-full"></span>
            </span>
          </button>
          
          <!-- User Menu -->
          <div class="relative group">
            <button class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 transition-colors">
              <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
              </div>
              <div class="hidden md:block text-left">
                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></p>
                <p class="text-xs text-gray-500">Adviser</p>
              </div>
              <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
            </button>
            
            <div class="absolute right-0 mt-2 w-64 card shadow-xl py-2 z-50 hidden group-hover:block">
              <div class="px-4 py-3 border-b border-gray-100">
                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></p>
                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
              </div>
              <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <i data-lucide="user" class="w-4 h-4"></i> Profile
              </a>
              <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <i data-lucide="settings" class="w-4 h-4"></i> Settings
              </a>
              <hr class="my-2">
              <a href="logout.php" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                <i data-lucide="log-out" class="w-4 h-4"></i> Logout
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Dashboard Tab Content -->
      <div id="dashboard-content" class="tab-content">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
          <div class="card card-interactive p-6 hover-lift">
            <div class="flex items-center justify-between">
              <div>
                <div class="flex items-center gap-4 mb-3">
                  <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
                  </div>
                  <div>
                    <p class="heading-sm text-gray-800"><?php echo $stats['in_progress']; ?></p>
                    <p class="body-sm text-gray-500">In Progress</p>
                  </div>
                </div>
              </div>
              <span class="status-badge status-info">Active</span>
            </div>
            <div class="progress-container mt-4">
              <div class="progress-bar" style="width: 65%"></div>
            </div>
          </div>
          
          <div class="card card-interactive p-6 hover-lift">
            <div class="flex items-center justify-between">
              <div>
                <div class="flex items-center gap-4 mb-3">
                  <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                  </div>
                  <div>
                    <p class="heading-sm text-gray-800"><?php echo $stats['for_review']; ?></p>
                    <p class="body-sm text-gray-500">For Review</p>
                  </div>
                </div>
              </div>
              <span class="status-badge status-warning">Pending</span>
            </div>
            <div class="progress-container mt-4">
              <div class="progress-bar" style="width: 35%; background: linear-gradient(90deg, var(--warning-500), var(--warning-400));"></div>
            </div>
          </div>
          
          <div class="card card-interactive p-6 hover-lift">
            <div class="flex items-center justify-between">
              <div>
                <div class="flex items-center gap-4 mb-3">
                  <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                  </div>
                  <div>
                    <p class="heading-sm text-gray-800"><?php echo $stats['approved']; ?></p>
                    <p class="body-sm text-gray-500">Approved</p>
                  </div>
                </div>
              </div>
              <span class="status-badge status-success">Complete</span>
            </div>
            <div class="progress-container mt-4">
              <div class="progress-bar" style="width: 85%; background: linear-gradient(90deg, var(--success-500), var(--success-400));"></div>
            </div>
          </div>
        </div>

        <!-- Students & Theses -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Student List -->
          <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b">
              <h3 class="font-semibold">Students Under Supervision</h3>
            </div>
            <div class="p-4">
              <?php if (empty($theses)): ?>
                <div class="text-center py-8">
                  <i data-lucide="users" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
                  <h3 class="text-lg font-medium text-gray-900 mb-2">No Students Yet</h3>
                  <p class="text-gray-500">Students assigned to you will appear here</p>
                </div>
              <?php else: ?>
                <div class="space-y-3">
                  <?php foreach ($theses as $thesis): ?>
                  <div class="student-row border rounded-lg p-3">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                          <?php echo strtoupper(substr($thesis['student_name'], 0, 1)); ?>
                        </div>
                        <div>
                          <h4 class="font-medium"><?php echo htmlspecialchars($thesis['student_name']); ?></h4>
                          <p class="text-sm text-gray-600"><?php echo htmlspecialchars($thesis['student_id'] ?? 'N/A'); ?> • <?php echo htmlspecialchars($thesis['program'] ?? 'N/A'); ?></p>
                        </div>
                      </div>
                      <div class="text-right">
                        <div class="text-sm font-medium"><?php echo $thesis['progress_percentage']; ?>%</div>
                        <div class="text-xs text-gray-500">Progress</div>
                      </div>
                    </div>
                    <div class="mt-3">
                      <div class="flex justify-between items-center text-sm mb-1">
                        <span class="font-medium"><?php echo htmlspecialchars($thesis['title']); ?></span>
                        <span class="text-gray-500"><?php echo ucfirst($thesis['status']); ?></span>
                      </div>
                      <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: <?php echo $thesis['progress_percentage']; ?>%"></div>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b">
              <h3 class="font-semibold">Recent Activity</h3>
            </div>
            <div class="p-4">
              <div class="space-y-4">
                <?php 
                $recent_theses = array_slice($theses, -5);
                foreach ($recent_theses as $thesis): 
                ?>
                <div class="flex items-center gap-3">
                  <div class="flex-1">
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($thesis['student_name']); ?></p>
                    <p class="text-xs text-gray-600"><?php echo htmlspecialchars($thesis['title']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($thesis['updated_at'])); ?></p>
                  </div>
                  <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                    <?php echo ucfirst($thesis['status']); ?>
                  </span>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($theses)): ?>
                <div class="text-center py-4">
                  <p class="text-sm text-gray-500">No recent activity</p>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Students Tab Content -->
      <div id="students-content" class="tab-content hidden">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">My Students</h3>
          <button id="addStudentBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Add Student
          </button>
        </div>
        
        <!-- Students List -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                  <th class="px-6 py-3 text-left">Student ID</th>
                  <th class="px-6 py-3 text-left">Name</th>
                  <th class="px-6 py-3 text-left">Program</th>
                  <th class="px-6 py-3 text-left">Thesis Title</th>
                  <th class="px-6 py-3 text-left">Status</th>
                  <th class="px-6 py-3 text-left">Progress</th>
                  <th class="px-6 py-3 text-left">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <?php if (empty($theses)): ?>
                <tr>
                  <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                    <i data-lucide="users" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                    <p class="mb-1">No students assigned yet</p>
                    <p class="text-sm">Click "Add Student" to assign students to you</p>
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($theses as $thesis): ?>
                  <tr class="hover:bg-gray-50 student-row">
                    <td class="px-6 py-4"><?php echo htmlspecialchars($thesis['student_id']); ?></td>
                    <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($thesis['student_name']); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($thesis['program']); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($thesis['title']); ?></td>
                    <td class="px-6 py-4">
                      <?php 
                      $status_class = '';
                      switch ($thesis['status']) {
                        case 'draft':
                          $status_class = 'bg-gray-100 text-gray-800';
                          break;
                        case 'in_progress':
                          $status_class = 'bg-blue-100 text-blue-800';
                          break;
                        case 'for_review':
                          $status_class = 'bg-amber-100 text-amber-800';
                          break;
                        case 'approved':
                          $status_class = 'bg-green-100 text-green-800';
                          break;
                        case 'rejected':
                          $status_class = 'bg-red-100 text-red-800';
                          break;
                      }
                      ?>
                      <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $thesis['status'])); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $thesis['progress_percentage']; ?>%"></div>
                      </div>
                      <div class="text-xs text-gray-500"><?php echo $thesis['progress_percentage']; ?>%</div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-800" title="View Details">
                          <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        <button class="text-green-600 hover:text-green-800" title="Send Message">
                          <i data-lucide="message-circle" class="w-4 h-4"></i>
                        </button>
                        <button 
                          onclick="openEditStudentModal(<?php echo htmlspecialchars(json_encode([
                            'id' => $thesis['student_id'],
                            'name' => $thesis['student_name'],
                            'program' => $thesis['program'],
                            'thesis_title' => $thesis['title']
                          ])); ?>)" 
                          class="text-amber-600 hover:text-amber-800" 
                          title="Edit Student">
                          <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Add Student Modal -->
        <div id="addStudentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
          <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
            <div class="p-4 border-b flex justify-between items-center">
              <h3 class="font-semibold text-lg">Add Student</h3>
              <button id="closeAddStudentModal" class="text-gray-500 hover:text-gray-700">
                <i data-lucide="x" class="w-5 h-5"></i>
              </button>
            </div>
            <div class="p-4">
              <form id="addStudentForm" action="api/add_student_to_adviser.php" method="post">
                <input type="hidden" name="adviser_id" value="<?php echo $user['id']; ?>">
                
                <div class="mb-4">
                  <label class="block text-gray-700 text-sm font-medium mb-2">Select Existing Student</label>
                  <select name="student_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Select a student --</option>
                    <?php foreach ($unassigned_students as $student): ?>
                    <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['full_name']); ?> (<?php echo htmlspecialchars($student['student_id']); ?>) - <?php echo htmlspecialchars($student['program']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="border-t border-gray-200 my-4 pt-4">
                  <p class="text-gray-600 mb-4">Or register a new student</p>
                  
                  <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                  </div>
                  
                  <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="email">Email</label>
                    <input type="email" id="email" name="email" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                  </div>
                  
                  <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                      <label class="block text-gray-700 text-sm font-medium mb-2" for="student_id">Student ID</label>
                      <input type="text" id="student_id" name="new_student_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                      <label class="block text-gray-700 text-sm font-medium mb-2" for="program">Program</label>
                      <input type="text" id="program" name="program" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                  </div>
                  
                  <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="password">Password</label>
                    <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to generate a random password</p>
                  </div>
                </div>
                
                <div class="border-t border-gray-200 my-4 pt-4">
                  <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="thesis_title">Initial Thesis Title (Optional)</label>
                    <input type="text" id="thesis_title" name="thesis_title" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                  </div>
                  
                  <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="thesis_abstract">Abstract (Optional)</label>
                    <textarea id="thesis_abstract" name="thesis_abstract" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                  </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                  <button type="button" id="cancelAddStudent" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                  <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Add Student</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Edit Student Modal -->
        <div id="editStudentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
          <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
            <div class="p-4 border-b flex justify-between items-center">
              <h3 class="font-semibold text-lg">Edit Student</h3>
              <button onclick="closeEditStudentModal()" class="text-gray-500 hover:text-gray-700">
                <i data-lucide="x" class="w-5 h-5"></i>
              </button>
            </div>
            <div class="p-4">
              <form id="editStudentForm" onsubmit="handleEditStudent(event)">
                <input type="hidden" name="student_id" id="edit_student_id">
                
                <div class="mb-4">
                  <label class="block text-gray-700 text-sm font-medium mb-2">Student Name</label>
                  <input type="text" name="edit_student_name" id="edit_student_name" required
                         class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="mb-4">
                  <label class="block text-gray-700 text-sm font-medium mb-2">Program</label>
                  <input type="text" name="edit_program" id="edit_program" required
                         class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="mb-4">
                  <label class="block text-gray-700 text-sm font-medium mb-2">Thesis Title</label>
                  <input type="text" name="edit_thesis_title" id="edit_thesis_title" required
                         class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex justify-end space-x-2">
                  <button type="button" onclick="closeEditStudentModal()" 
                          class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                    Cancel
                  </button>
                  <button type="submit" 
                          class="px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                    Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Theses Tab Content -->
      <div id="theses-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow">
          <div class="p-4 border-b">
            <h3 class="font-semibold">All Theses</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thesis Title</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($theses as $thesis): ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        <?php echo strtoupper(substr($thesis['student_name'], 0, 1)); ?>
                      </div>
                      <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($thesis['student_name']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($thesis['student_id'] ?? 'N/A'); ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($thesis['title']); ?></div>
                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($thesis['program'] ?? 'N/A'); ?></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php 
                    $statusColors = [
                      'draft' => 'bg-gray-100 text-gray-800',
                      'in_progress' => 'bg-blue-100 text-blue-800',
                      'for_review' => 'bg-yellow-100 text-yellow-800',
                      'approved' => 'bg-green-100 text-green-800',
                      'rejected' => 'bg-red-100 text-red-800'
                    ];
                    $statusColor = $statusColors[$thesis['status']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                      <?php echo ucfirst(str_replace('_', ' ', $thesis['status'])); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-16 bg-gray-200 rounded-full h-2 mr-3">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $thesis['progress_percentage']; ?>%"></div>
                      </div>
                      <span class="text-sm text-gray-900"><?php echo $thesis['progress_percentage']; ?>%</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo date('M j, Y', strtotime($thesis['updated_at'])); ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($theses)): ?>
                <tr>
                  <td colspan="5" class="px-6 py-12 text-center">
                    <div class="text-gray-500">
                      <i data-lucide="file-text" class="w-12 h-12 mx-auto mb-3 text-gray-400"></i>
                      <p>No theses assigned yet</p>
                    </div>
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Document Review Tab Content -->
      <div id="document-review-content" class="tab-content hidden">
        <div class="flex h-[calc(100vh-200px)] relative overflow-hidden">
          <!-- Collapsible Sidebar -->
          <div id="document-sidebar" class="w-80 min-w-80 bg-white border-r border-gray-200 flex flex-col transition-all duration-300 ease-in-out shrink-0">
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
              <div class="flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">Document Navigation</h3>
                <button id="toggle-sidebar" class="p-2 hover:bg-white/50 rounded-lg transition-colors">
                  <i data-lucide="sidebar" class="w-4 h-4 text-gray-600"></i>
                </button>
              </div>
            </div>

            <!-- Students List -->
            <div class="flex-1 overflow-y-auto">
              <div class="p-4">
                <div class="flex justify-between items-center mb-3">
                  <h4 class="text-sm font-medium text-gray-700">Select Document</h4>
                  <button id="refresh-document-list" class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200 transition-colors">
                    <i data-lucide="refresh-cw" class="w-3 h-3 inline-block mr-1"></i> Refresh
                  </button>
                </div>
                
                <div id="chapter-list" class="space-y-2">
                  <!-- Loading state -->
                  <div id="loading-students" class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
                    <p class="mt-2 text-sm text-gray-500">Loading students...</p>
                  </div>
                  
                  <!-- Students will be dynamically loaded here -->
                  <div id="students-list"></div>
                  
                  <!-- Empty state -->
                  <div id="no-students" class="text-center py-12 text-gray-500 hidden">
                    <i data-lucide="users" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                    <p class="text-sm">No students assigned for document review</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Analysis & Comments Quick View -->
            <div class="border-t border-gray-200 p-4 bg-gray-50">
              <div class="space-y-3">
                <button id="toggle-analysis" class="w-full flex items-center justify-between p-2 text-left text-sm font-medium text-gray-700 hover:bg-white rounded-lg transition-colors">
                  <span class="flex items-center">
                    <i data-lucide="file-check" class="w-4 h-4 mr-2 text-blue-500"></i>
                    Quick Analysis
                  </span>
                  <i data-lucide="chevron-right" class="w-4 h-4 transform transition-transform" id="analysis-chevron"></i>
                </button>
                
                <button id="toggle-comments" class="w-full flex items-center justify-between p-2 text-left text-sm font-medium text-gray-700 hover:bg-white rounded-lg transition-colors">
                  <span class="flex items-center">
                    <i data-lucide="message-circle" class="w-4 h-4 mr-2 text-green-500"></i>
                    Comments Panel
                  </span>
                  <i data-lucide="chevron-right" class="w-4 h-4 transform transition-transform" id="comments-chevron"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Main Document Viewer -->
          <div class="flex-1 flex flex-col bg-gray-50 min-w-0 overflow-hidden">
            <!-- Enhanced Toolbar -->
            <div class="bg-white border-b border-gray-200 p-4 shrink-0">
              <div class="flex justify-between items-center flex-wrap gap-4">
                <!-- Mobile Sidebar Toggle -->
                <button id="mobile-sidebar-toggle" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                  <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-lg text-gray-800 truncate" id="document-title">Select a document to begin review</h3>
                  <p class="text-sm text-gray-500 mt-1 truncate" id="document-info">Choose a student and chapter from the sidebar</p>
                </div>
                
                <!-- Enhanced Document Tools -->
                <div class="flex items-center space-x-3 flex-wrap" id="document-tools" style="display: none;">
                  <div class="flex items-center space-x-2 bg-gray-50 p-2 rounded-lg flex-wrap gap-2">
                    <button id="highlight-btn" class="px-3 py-2 bg-yellow-100 text-yellow-800 rounded-lg text-sm hover:bg-yellow-200 transition-colors flex items-center">
                      <i data-lucide="highlighter" class="w-4 h-4 mr-1"></i><span class="hidden sm:inline">Highlight</span>
                    </button>
                    <button id="comment-btn" class="px-3 py-2 bg-blue-100 text-blue-800 rounded-lg text-sm hover:bg-blue-200 transition-colors flex items-center">
                      <i data-lucide="message-circle" class="w-4 h-4 mr-1"></i><span class="hidden sm:inline">Comment</span>
                    </button>
                    
                    <!-- Color Picker -->
                    <div class="relative">
                      <button id="color-picker-btn" class="px-3 py-2 bg-gray-100 text-gray-800 rounded-lg text-sm hover:bg-gray-200 transition-colors flex items-center">
                        <div class="w-4 h-4 mr-2 rounded border" id="current-color" style="background-color: #ffeb3b;"></div>
                        <i data-lucide="chevron-down" class="w-3 h-3"></i>
                      </button>
                      <div id="color-picker" class="absolute top-full mt-2 right-0 bg-white border rounded-lg shadow-lg p-3 hidden z-50">
                        <div class="grid grid-cols-4 gap-2">
                          <button class="w-8 h-8 rounded-lg border-2 color-option hover:scale-110 transition-transform" style="background-color: #ffeb3b;" data-color="#ffeb3b" title="Yellow"></button>
                          <button class="w-8 h-8 rounded-lg border-2 color-option hover:scale-110 transition-transform" style="background-color: #4caf50;" data-color="#4caf50" title="Green"></button>
                          <button class="w-8 h-8 rounded-lg border-2 color-option hover:scale-110 transition-transform" style="background-color: #2196f3;" data-color="#2196f3" title="Blue"></button>
                          <button class="w-8 h-8 rounded-lg border-2 color-option hover:scale-110 transition-transform" style="background-color: #ff9800;" data-color="#ff9800" title="Orange"></button>
                          <button class="w-8 h-8 rounded-lg border-2 color-option hover:scale-110 transition-transform" style="background-color: #f44336;" data-color="#f44336" title="Red"></button>
                          <button class="w-8 h-8 rounded-lg border-2 color-option hover:scale-110 transition-transform" style="background-color: #9c27b0;" data-color="#9c27b0" title="Purple"></button>
                          <button class="w-8 h-8 rounded-lg border-2 color-option hover:scale-110 transition-transform" style="background-color: #607d8b;" data-color="#607d8b" title="Blue Grey"></button>
                          <button class="w-8 h-8 rounded-lg border-2 color-option hover:scale-110 transition-transform" style="background-color: #795548;" data-color="#795548" title="Brown"></button>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="h-6 w-px bg-gray-300"></div>
                  
                  <!-- Document Actions -->
                  <div class="flex items-center space-x-2 flex-wrap gap-2">
                    <button id="fullscreen-btn" class="px-3 py-2 bg-indigo-100 text-indigo-800 rounded-lg text-sm hover:bg-indigo-200 transition-colors flex items-center" title="Open in Full Screen">
                      <i data-lucide="maximize" class="w-4 h-4 mr-1"></i><span class="hidden lg:inline">Full Screen</span>
                    </button>
                    <a id="download-document-btn" href="#" class="px-3 py-2 bg-green-100 text-green-800 rounded-lg text-sm hover:bg-green-200 transition-colors flex items-center" target="_blank">
                      <i data-lucide="download" class="w-4 h-4 mr-1"></i><span class="hidden lg:inline">Download</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Document Viewer Area -->
            <div class="flex-1 relative bg-gray-100 overflow-hidden">
              <div id="adviser-word-document-viewer" class="w-full h-full overflow-auto">
                <div class="flex items-center justify-center h-full text-gray-500">
                  <div class="text-center px-4">
                    <i data-lucide="file-text" class="w-20 h-20 mx-auto mb-4 text-gray-300"></i>
                    <h3 class="text-lg font-medium mb-2">No Document Selected</h3>
                    <p class="text-sm">Select a student and chapter from the sidebar to start reviewing</p>
                  </div>
                </div>
              </div>
              
              <!-- Fallback document preview for non-Word files -->
              <div id="document-preview" class="hidden w-full h-full overflow-y-auto p-4 md:p-6">
                <div class="max-w-4xl mx-auto">
                  <div class="bg-white rounded-lg p-4 md:p-6 mb-6 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-4">
                      <div class="min-w-0">
                        <h4 id="file-name" class="text-lg font-medium text-gray-800 truncate"></h4>
                        <p id="file-info" class="text-sm text-gray-500 truncate"></p>
                      </div>
                      <div class="shrink-0">
                        <span id="file-type-badge" class="px-3 py-1 text-sm rounded-full"></span>
                      </div>
                    </div>
                    <div class="text-center py-8">
                      <p class="mb-4 text-gray-600">This document can be downloaded for detailed review.</p>
                      <p class="text-sm text-gray-500 mb-4">Use the highlight and comment tools to provide feedback.</p>
                    </div>
                  </div>
                  
                  <!-- Text content preview for highlighting and commenting -->
                  <div id="text-content-preview" class="bg-white rounded-lg p-4 md:p-6 shadow-sm">
                    <!-- Text content will be loaded here for highlighting -->
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Panel Container - Responsive Overlay -->
          <div id="right-panels-container" class="fixed inset-0 z-20 pointer-events-none" style="display: none;">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black bg-opacity-25 pointer-events-auto" id="panel-backdrop"></div>
            
            <!-- Tabbed Panel System -->
            <div id="tabbed-panel" class="absolute right-0 top-0 h-full w-full max-w-md bg-white border-l border-gray-200 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out pointer-events-auto">
              <!-- Panel Tabs -->
              <div class="flex border-b border-gray-200 shrink-0">
                <button id="analysis-tab" class="flex-1 px-3 py-3 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 border-b-2 border-transparent transition-all">
                  <i data-lucide="file-check" class="w-4 h-4 mr-1 inline"></i>
                  <span class="hidden sm:inline">Analysis</span>
                </button>
                <button id="comments-tab" class="flex-1 px-3 py-3 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 border-b-2 border-transparent transition-all">
                  <i data-lucide="message-circle" class="w-4 h-4 mr-1 inline"></i>
                  <span class="hidden sm:inline">Comments</span>
                </button>
                <button id="close-panel" class="px-3 py-3 text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
                  <i data-lucide="x" class="w-4 h-4"></i>
                </button>
              </div>

              <!-- Panel Content -->
              <div class="flex-1 overflow-hidden flex flex-col">
                <!-- Analysis Panel Content -->
                <div id="analysis-content" class="h-full hidden flex flex-col">
                  <div class="p-4 bg-gradient-to-r from-purple-50 to-blue-50 border-b border-gray-200 shrink-0">
                    <h3 class="font-semibold text-gray-800 flex items-center">
                      <i data-lucide="file-check" class="w-5 h-5 mr-2 text-purple-500"></i>
                      Document Format Analysis
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">AI-powered formatting and structure analysis</p>
                  </div>
                  <div class="flex-1 overflow-y-auto p-4">
                    <div id="format-analysis-content">
                      <div class="text-center py-12 text-gray-500">
                        <i data-lucide="search" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                        <p class="text-sm">Select a document to analyze formatting</p>
                        <p class="text-xs text-gray-400 mt-2">Analysis will check structure, citations, and formatting compliance</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Comments Panel Content -->
                <div id="comments-content" class="h-full hidden flex flex-col">
                  <div class="p-4 bg-gradient-to-r from-green-50 to-blue-50 border-b border-gray-200 shrink-0">
                    <h3 class="font-semibold text-gray-800 flex items-center">
                      <i data-lucide="message-circle" class="w-5 h-5 mr-2 text-green-500"></i>
                      Comments & Feedback
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Review and add feedback to the document</p>
                  </div>
                  <div class="flex-1 overflow-y-auto p-4">
                    <div id="comments-list" class="space-y-3 mb-4">
                      <div class="text-center py-12 text-gray-500">
                        <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                        <p class="text-sm">No comments yet</p>
                        <p class="text-xs mt-1 text-gray-400">Click on text in the document to add comments</p>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Fixed Quick Comment Form -->
                  <div class="border-t border-gray-200 p-4 bg-gray-50 shrink-0">
                    <h4 class="font-medium text-sm mb-3 text-gray-700">Add Quick Comment</h4>
                    <div class="space-y-3">
                      <textarea id="quick-comment-text" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none" rows="3" placeholder="Type your general feedback here..."></textarea>
                      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                        <div class="text-xs text-gray-500">
                          <i data-lucide="info" class="w-3 h-3 inline mr-1"></i>
                          For specific feedback, click on document text
                        </div>
                        <button id="submit-quick-comment" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors flex items-center">
                          <i data-lucide="send" class="w-4 h-4 mr-2"></i>
                          Submit
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
          
          <!-- Quick Access Floating Buttons -->
          <div id="panel-triggers" class="fixed bottom-6 right-6 space-y-3 z-30">
            <button id="trigger-analysis" class="w-12 h-12 bg-purple-600 text-white rounded-full shadow-lg hover:bg-purple-700 transition-all duration-200 hover:scale-110 flex items-center justify-center" title="Format Analysis">
              <i data-lucide="file-check" class="w-5 h-5"></i>
            </button>
            <button id="trigger-comments" class="w-12 h-12 bg-green-600 text-white rounded-full shadow-lg hover:bg-green-700 transition-all duration-200 hover:scale-110 flex items-center justify-center" title="Comments & Feedback">
              <i data-lucide="message-circle" class="w-5 h-5"></i>
            </button>
          </div>
        </div>

        <!-- Comment Modal -->
        <div id="comment-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
          <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
              <h3 class="text-lg font-semibold mb-4">Add Comment</h3>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Selected Text:</label>
                <div id="selected-text-preview" class="p-2 bg-gray-100 rounded text-sm italic"></div>
              </div>
              <div class="mb-4">
                <label for="comment-text" class="block text-sm font-medium text-gray-700 mb-2">Comment:</label>
                <textarea 
                  id="comment-text" 
                  rows="4" 
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter your feedback or comments..."></textarea>
              </div>
              <div class="flex justify-end space-x-2">
                <button id="cancel-comment" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                  Cancel
                </button>
                <button id="save-comment" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                  Save Comment
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Paragraph Comment Modal -->
        <div id="paragraph-comment-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
          <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
              <h3 class="text-lg font-semibold mb-4">Add Paragraph Comment</h3>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Paragraph:</label>
                <div id="paragraph-text-preview" class="p-2 bg-gray-100 rounded text-sm max-h-32 overflow-y-auto"></div>
                <input type="hidden" id="paragraph-id-input">
              </div>
              <div class="mb-4">
                <label for="paragraph-comment-text" class="block text-sm font-medium text-gray-700 mb-2">Comment:</label>
                <textarea 
                  id="paragraph-comment-text" 
                  rows="4" 
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter your feedback or suggestions for this paragraph..."></textarea>
              </div>
              <div class="flex justify-end space-x-2">
                <button id="cancel-paragraph-comment" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                  Cancel
                </button>
                <button id="save-paragraph-comment" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                  Save Comment
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Feedback Tab Content -->
      <div id="feedback-content" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Student Selection Panel -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-4">
              <h3 class="font-semibold mb-4">Students</h3>
              <div id="feedback-student-list" class="space-y-2">
                <?php if (empty($theses)): ?>
                  <div class="text-center py-8 text-gray-500">
                    <i data-lucide="users" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                    <p class="text-sm">No students assigned yet</p>
                  </div>
                <?php else: ?>
                  <?php foreach ($theses as $thesis): ?>
                    <button 
                      class="w-full text-left px-3 py-2 border rounded-lg hover:bg-blue-50 feedback-student-item"
                      data-student-id="<?php echo $thesis['student_user_id']; ?>"
                      data-student-name="<?php echo htmlspecialchars($thesis['student_name']); ?>"
                      data-thesis-title="<?php echo htmlspecialchars($thesis['title']); ?>">
                      <div class="flex justify-between items-center">
                        <div>
                          <span class="font-medium text-sm"><?php echo htmlspecialchars($thesis['student_name']); ?></span>
                          <div class="text-xs text-gray-500 mt-1">
                            <?php echo htmlspecialchars($thesis['student_id'] ?? 'N/A'); ?> • <?php echo htmlspecialchars($thesis['program'] ?? 'N/A'); ?>
                          </div>
                        </div>
                      </div>
                    </button>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <!-- Chapter Selection Panel -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-4">
              <h3 class="font-semibold mb-4">Chapters</h3>
              <div id="feedback-chapter-list" class="space-y-2">
                <div class="text-center py-8 text-gray-500">
                  <i data-lucide="file-text" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                  <p class="text-sm">Select a student to view chapters</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Feedback Form Panel -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-4">
              <h3 class="font-semibold mb-4">Add Feedback</h3>
              <div id="feedback-form-container">
                <div class="text-center py-8 text-gray-500">
                  <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                  <p class="text-sm">Select a chapter to add feedback</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Feedback History -->
        <div class="mt-6">
          <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-4">Feedback History</h3>
            <div id="feedback-history" class="space-y-4">
              <div class="text-center py-8 text-gray-500">
                <i data-lucide="history" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                <p class="text-sm">Select a student to view feedback history</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div id="timeline-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Timeline Management</h3>
          <p class="text-gray-500">Timeline management features coming soon!</p>
        </div>
      </div>

      <!-- Activity Logs Tab Content -->
      <div id="activity-logs-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold">Activity Logs</h3>
            <div class="flex gap-2">
              <select id="activity-type-filter" class="text-sm border rounded-md px-3 py-2">
                <option value="">All Activities</option>
                <option value="Chapter Submission">Chapter Submissions</option>
                <option value="Feedback Given">Feedback</option>
                <option value="Document Review">Document Reviews</option>
                <option value="Comment Activity">Comments & Highlights</option>
                <option value="Timeline Update">Timeline Updates</option>
              </select>
              <select id="activity-time-filter" class="text-sm border rounded-md px-3 py-2">
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last 90 Days</option>
              </select>
            </div>
          </div>
          
          <div id="activity-logs-list" class="space-y-4">
            <div class="flex items-center justify-center py-8">
              <i data-lucide="loader" class="w-5 h-5 animate-spin mr-2"></i>
              Loading activity logs...
            </div>
          </div>
        </div>
      </div>

      <!-- Reports Tab Content -->
      <div id="reports-content" class="tab-content hidden">
        <!-- Quick Analytics Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6" id="analytics-summary" style="display: none;">
          <div class="bg-white p-4 rounded-lg shadow text-center card-hover">
            <div class="bg-blue-100 p-3 rounded-full w-12 h-12 mx-auto mb-2 flex items-center justify-center">
              <i data-lucide="file-text" class="w-6 h-6 text-blue-600"></i>
            </div>
            <p class="font-bold text-2xl" id="total-chapters">-</p>
            <p class="text-gray-500 text-sm">Total Chapters</p>
          </div>
          <div class="bg-white p-4 rounded-lg shadow text-center card-hover">
            <div class="bg-green-100 p-3 rounded-full w-12 h-12 mx-auto mb-2 flex items-center justify-center">
              <i data-lucide="upload" class="w-6 h-6 text-green-600"></i>
            </div>
            <p class="font-bold text-2xl" id="submitted-chapters">-</p>
            <p class="text-gray-500 text-sm">Submitted</p>
          </div>
          <div class="bg-white p-4 rounded-lg shadow text-center card-hover">
            <div class="bg-emerald-100 p-3 rounded-full w-12 h-12 mx-auto mb-2 flex items-center justify-center">
              <i data-lucide="check-circle" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <p class="font-bold text-2xl" id="approved-chapters">-</p>
            <p class="text-gray-500 text-sm">Approved</p>
          </div>
          <div class="bg-white p-4 rounded-lg shadow text-center card-hover">
            <div class="bg-purple-100 p-3 rounded-full w-12 h-12 mx-auto mb-2 flex items-center justify-center">
              <i data-lucide="users" class="w-6 h-6 text-purple-600"></i>
            </div>
            <p class="font-bold text-2xl" id="active-students">-</p>
            <p class="text-gray-500 text-sm">Active Students</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
          <!-- Reports Sidebar -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-4 mb-4">
              <h3 class="font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                Report Templates
              </h3>
              <div id="report-templates-list" class="space-y-2">
                <div class="flex items-center justify-center py-8 text-gray-500">
                  <i data-lucide="loader" class="w-4 h-4 animate-spin mr-2"></i>
                  Loading templates...
                </div>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
              <h3 class="font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="bookmark" class="w-4 h-4"></i>
                Saved Reports
              </h3>
              <div id="saved-reports-list" class="space-y-2">
                <div class="flex items-center justify-center py-8 text-gray-500">
                  <i data-lucide="loader" class="w-4 h-4 animate-spin mr-2"></i>
                  Loading reports...
                </div>
              </div>
            </div>
          </div>

          <!-- Reports Main Content -->
          <div class="lg:col-span-3">
            <div class="bg-white rounded-lg shadow">
              <div class="p-4 border-b">
                <h3 class="font-semibold flex items-center gap-2" id="report-title">
                  <i data-lucide="chart-area" class="w-4 h-4"></i>
                  Select a report to view
                </h3>
              </div>
              <div class="p-6">
                <div id="report-description" class="mb-4 p-3 bg-blue-50 border-l-4 border-blue-500 rounded text-sm hidden"></div>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-6" style="height: 400px;">
                  <canvas id="reportChart"></canvas>
                </div>
                
                <div id="report-data-table">
                  <div class="text-center py-12 text-gray-500">
                    <i data-lucide="chart-pie" class="w-12 h-12 mx-auto mb-3 text-gray-400"></i>
                    <h3 class="font-medium mb-2">No Report Selected</h3>
                    <p class="text-sm">Choose a report template from the sidebar to get started</p>
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
    // Initialize theses data from PHP
    const theses = <?php echo json_encode($theses); ?>;
    console.log("Theses data loaded:", theses);
    
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize Lucide icons
      lucide.createIcons();
      
      // Tab switching
      const tabLinks = document.querySelectorAll('.sidebar-item');
      const tabContents = document.querySelectorAll('.tab-content');
      
      tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          const targetTab = this.getAttribute('data-tab');
          
          e.preventDefault();
          
          // Update active tab
          tabLinks.forEach(item => item.classList.remove('active-tab'));
          this.classList.add('active-tab');
          
          // Show target content
          tabContents.forEach(content => {
            if (content.id === `${targetTab}-content`) {
              content.classList.remove('hidden');
              document.querySelector('h2').textContent = targetTab.charAt(0).toUpperCase() + targetTab.slice(1);
              
              // Initialize reports if reports tab is clicked
              if (targetTab === 'reports') {
                initializeReports();
              }
            } else {
              content.classList.add('hidden');
            }
          });
        });
      });
      
      // Add Student Modal
      const addStudentBtn = document.getElementById('addStudentBtn');
      const addStudentModal = document.getElementById('addStudentModal');
      const closeAddStudentModal = document.getElementById('closeAddStudentModal');
      const cancelAddStudent = document.getElementById('cancelAddStudent');
      
      if (addStudentBtn) {
        addStudentBtn.addEventListener('click', function() {
          addStudentModal.classList.remove('hidden');
        });
      }
      
      if (closeAddStudentModal) {
        closeAddStudentModal.addEventListener('click', function() {
          addStudentModal.classList.add('hidden');
        });
      }
      
      if (cancelAddStudent) {
        cancelAddStudent.addEventListener('click', function() {
          addStudentModal.classList.add('hidden');
        });
      }
      
      // Handle Add Student Form Submission
      const addStudentForm = document.getElementById('addStudentForm');
      if (addStudentForm) {
        addStudentForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          // Create notification element
          const notification = document.createElement('div');
          notification.className = 'fixed top-4 right-4 bg-white shadow-lg rounded-lg p-4 max-w-md z-50';
          notification.innerHTML = `
            <div class="flex items-center">
              <div class="mr-3">
                <i data-lucide="loader" class="w-6 h-6 text-blue-500 animate-spin"></i>
              </div>
              <div>
                <p class="font-medium">Processing...</p>
                <p class="text-sm text-gray-500">Please wait while we process your request.</p>
              </div>
            </div>
          `;
          document.body.appendChild(notification);
          lucide.createIcons();
          
          // Get form data
          const formData = new FormData(this);
          
          // Send AJAX request
          fetch('api/add_student_to_adviser.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            // Remove loading notification
            document.body.removeChild(notification);
            
            // Create result notification
            const resultNotification = document.createElement('div');
            resultNotification.className = `fixed top-4 right-4 bg-white shadow-lg rounded-lg p-4 max-w-md z-50 ${data.success ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500'}`;
            resultNotification.innerHTML = `
              <div class="flex items-center">
                <div class="mr-3">
                  <i data-lucide="${data.success ? 'check-circle' : 'alert-circle'}" class="w-6 h-6 ${data.success ? 'text-green-500' : 'text-red-500'}"></i>
                </div>
                <div>
                  <p class="font-medium">${data.success ? 'Success' : 'Error'}</p>
                  <p class="text-sm text-gray-700">${data.message}</p>
                </div>
              </div>
            `;
            document.body.appendChild(resultNotification);
            lucide.createIcons();
            
            // Auto-remove notification after 5 seconds
            setTimeout(() => {
              document.body.removeChild(resultNotification);
              
              // Redirect if successful
              if (data.success && data.redirect) {
                window.location.href = data.redirect;
              }
            }, 5000);
            
            // Close modal if successful
            if (data.success) {
              addStudentModal.classList.add('hidden');
              addStudentForm.reset();
            }
          })
          .catch(error => {
            console.error('Error:', error);
            document.body.removeChild(notification);
            
            // Create error notification
            const errorNotification = document.createElement('div');
            errorNotification.className = 'fixed top-4 right-4 bg-white shadow-lg rounded-lg p-4 max-w-md z-50 border-l-4 border-red-500';
            errorNotification.innerHTML = `
              <div class="flex items-center">
                <div class="mr-3">
                  <i data-lucide="alert-circle" class="w-6 h-6 text-red-500"></i>
                </div>
                <div>
                  <p class="font-medium">Error</p>
                  <p class="text-sm text-gray-700">An unexpected error occurred. Please try again.</p>
                </div>
              </div>
            `;
            document.body.appendChild(errorNotification);
            lucide.createIcons();
            
            // Auto-remove notification after 5 seconds
            setTimeout(() => {
              document.body.removeChild(errorNotification);
            }, 5000);
          });
        });
      }
      
      // Check for tab parameter in URL
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab');
      
      if (tabParam) {
        const tabLink = document.querySelector(`.sidebar-item[data-tab="${tabParam}"]`);
        if (tabLink) {
          tabLink.click();
        }
      }
      
      // Mobile sidebar toggle
      document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.querySelector('aside').classList.toggle('hidden');
      });
      
      // Document Review Functionality
      let currentChapterId = null;
      window.currentChapterId = null;
      window.selectedText = '';
      window.selectedRange = null;
      window.currentHighlightColor = '#ffeb3b';
      window.isHighlightMode = false;
      
      // Refresh document list
      document.getElementById('refresh-document-list')?.addEventListener('click', function() {
        window.location.href = 'systemFunda.php?tab=document-review';
      });

      // Chapter selection
      document.addEventListener('click', function(e) {
        if (e.target.closest('.chapter-item')) {
          const chapterItem = e.target.closest('.chapter-item');
          const chapterId = chapterItem.dataset.chapterId;
          const chapterTitle = chapterItem.dataset.chapterTitle;
          
          // Remove active class from all chapters
          document.querySelectorAll('.chapter-item').forEach(item => {
            item.classList.remove('bg-blue-100');
          });
          
          // Add active class to selected chapter
          chapterItem.classList.add('bg-blue-100');
          
          loadChapter(chapterId, chapterTitle);
        }
      });

      // Load chapter content - moved to global scope
      window.loadChapter = function(chapterId, chapterTitle) {
        console.log('=== loadChapter called ===');
        console.log('Setting currentChapterId to:', chapterId);
        
        currentChapterId = chapterId;
        window.currentChapterId = chapterId;
        
        console.log('currentChapterId is now:', currentChapterId);
        console.log('window.currentChapterId is now:', window.currentChapterId);
        
        // Update document title
        document.getElementById('document-title').textContent = chapterTitle;
        document.getElementById('document-info').textContent = 'Loading chapter content...';
        document.getElementById('document-tools').style.display = 'flex';
        
        // Load chapter data
        fetch(`api/document_review.php?action=get_chapter&chapter_id=${chapterId}`)
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
          })
          .then(data => {
            if (data.success) {
              const chapter = data.chapter;
              document.getElementById('document-info').textContent = 
                `${chapter.student_name} • ${chapter.thesis_title}`;
              
              // Check if there are file uploads for this chapter
              if (chapter.files && chapter.files.length > 0) {
                const files = chapter.files;
                
                // Check for Word documents using both file extension and MIME type
                const wordFiles = files.filter(file => {
                  const filename = file.original_filename.toLowerCase();
                  const mimeType = file.file_type;
                  
                  // Check by extension
                  const isWordByExtension = filename.endsWith('.doc') || filename.endsWith('.docx');
                  
                  // Check by MIME type
                  const isWordByMimeType = mimeType === 'application/msword' || 
                                           mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ||
                                           mimeType.includes('word') || 
                                           mimeType.includes('document');
                  
                  return isWordByExtension || isWordByMimeType;
                });
                
                if (wordFiles.length > 0) {
                  // Use Word viewer for Word documents
                  const wordFile = wordFiles[0]; // Use the first Word file
                  window.currentFileId = wordFile.id; // <-- Ensure this is set for fullscreen
                  // Hide fallback preview and show Word viewer
                  document.getElementById('document-preview').classList.add('hidden');
                  
                  // Initialize Word viewer (it will handle server limitations gracefully)
                  initializeAdviserWordViewer(wordFile.id);
                  
                  // Set download link
                  const downloadBtn = document.getElementById('download-document-btn');
                  downloadBtn.href = `api/download_file.php?file_id=${wordFile.id}`;
                  
                  // Load formatting analysis
                  loadFormatAnalysis(wordFile.id);
                  
                } else {
                  // Show fallback preview for non-Word files
                  const latestFile = files[0];
                  window.currentFileId = latestFile.id; // <-- Ensure this is set for fullscreen
                  // Hide Word viewer and show fallback preview
                  document.getElementById('adviser-word-document-viewer').innerHTML = `
                    <div class="text-center py-12 text-gray-500 h-full flex flex-col justify-center">
                      <i data-lucide="file-text" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                      <p>Word viewer not available for this file type</p>
                    </div>
                  `;
                  document.getElementById('document-preview').classList.remove('hidden');
                  
                  // Update file information
                  document.getElementById('file-name').textContent = latestFile.original_filename;
                  document.getElementById('file-info').textContent = `Uploaded on ${new Date(latestFile.uploaded_at).toLocaleString()}`;
                  
                  // Set file type badge
                  const fileType = latestFile.file_type;
                  let badgeClass = 'bg-gray-100 text-gray-800';
                  let fileTypeText = 'Unknown';
                  
                  if (fileType.includes('pdf')) {
                    badgeClass = 'bg-red-100 text-red-800';
                    fileTypeText = 'PDF';
                  } else if (fileType.includes('word') || fileType.includes('document') || 
                             fileType === 'application/msword' || 
                             fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    badgeClass = 'bg-blue-100 text-blue-800';
                    fileTypeText = 'Word';
                  }
                  
                  document.getElementById('file-type-badge').className = `px-2 py-1 text-xs rounded ${badgeClass}`;
                  document.getElementById('file-type-badge').textContent = fileTypeText;
                  
                  // Set download link
                  const downloadBtn = document.getElementById('download-document-btn');
                  downloadBtn.href = `api/download_file.php?file_id=${latestFile.id}`;
                  
                  // Load formatting analysis for non-Word files
                  loadFormatAnalysis(latestFile.id);
                }
                
                // Show quick comment form (element is in the comments panel)
                // No need to show/hide as it's always visible in the panel
                
                // Load existing comments
                loadComments(chapterId);
                
              } else {
                // No files uploaded, show no content message
                document.getElementById('document-preview').classList.add('hidden');
                document.getElementById('adviser-word-document-viewer').innerHTML = `
                  <div class="text-center py-12 text-gray-500 h-full flex flex-col justify-center">
                    <i data-lucide="file-x" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                    <p>No files uploaded for this chapter</p>
                      </div>
                    `;
                
                // Hide tools since there's no content
                document.getElementById('document-tools').style.display = 'none';
                // Quick comment form is in the panel, no need to hide
                
                // Clear format analysis
                document.getElementById('format-analysis-content').innerHTML = `
                  <div class="text-center py-8 text-gray-500">
                    <i data-lucide="search" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                    <p class="text-sm">Select a document to analyze formatting</p>
                  </div>
                `;
              }
                
                // Refresh Lucide icons
                lucide.createIcons();
            } else {
              showError('Failed to load chapter: ' + data.error);
              // Reset document viewer to show no content
              document.getElementById('adviser-word-document-viewer').innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-500">
                  <div class="text-center px-4">
                    <i data-lucide="file-text" class="w-20 h-20 mx-auto mb-4 text-gray-300"></i>
                    <h3 class="text-lg font-medium mb-2">Error Loading Document</h3>
                    <p class="text-sm">${data.error}</p>
                  </div>
                </div>
              `;
              document.getElementById('document-tools').style.display = 'none';
              return; // Exit early to prevent further processing
            }
          })
          .catch(error => {
            console.error('Error loading chapter:', error);
            showError('Failed to load chapter: ' + error.message);
            // Reset document viewer to show no content
            document.getElementById('adviser-word-document-viewer').innerHTML = `
              <div class="flex items-center justify-center h-full text-gray-500">
                <div class="text-center px-4">
                  <i data-lucide="file-text" class="w-20 h-20 mx-auto mb-4 text-gray-300"></i>
                  <h3 class="text-lg font-medium mb-2">Error Loading Document</h3>
                  <p class="text-sm">${error.message}</p>
                </div>
              </div>
            `;
            document.getElementById('document-tools').style.display = 'none';
          });
      };

      // Global Word viewer instance for adviser
      let adviserWordViewer = null;

      // Initialize Word viewer for adviser
      function initializeAdviserWordViewer(fileId) {
        // Debug: Log file ID and fetch file info
        console.log('Initializing Word viewer for file ID:', fileId);
        console.log('Current chapter ID:', window.currentChapterId);
        
        // Fetch debug info first
        fetch(`api/document_review.php?action=debug_file&file_id=${fileId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              console.log('File debug info:', data.debug_info);
            } else {
              console.error('Debug info error:', data.error);
            }
          })
          .catch(error => console.error('Debug fetch error:', error));
        
        // Create or recreate the word viewer
        const viewerContainer = document.getElementById('adviser-word-document-viewer');
        viewerContainer.innerHTML = '<div id="adviser-word-viewer-content" class="h-full"></div>';
        
        // Initialize the Word viewer
        adviserWordViewer = new WordViewer('adviser-word-viewer-content', {
          showComments: true,
          showToolbar: true,
          allowZoom: true
        });
        
        // Load the document
        adviserWordViewer.loadDocument(fileId);
        
        // Load existing comments and highlights for this chapter
        if (window.currentChapterId) {
          setTimeout(() => {
            loadComments(window.currentChapterId);
            loadHighlights(window.currentChapterId);
          }, 1000); // Wait for document to load
        }
      }

      // Load existing highlights - moved to global scope
      window.loadHighlights = function(chapterId) {
        fetch(`api/document_review.php?action=get_highlights&chapter_id=${chapterId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              applyHighlights(data.highlights);
            }
          })
          .catch(error => console.error('Error loading highlights:', error));
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

      // Apply highlights to content
      function applyHighlights(highlights) {
        const contentElement = document.querySelector('.chapter-content');
        if (!contentElement) return;
        
        highlights.forEach(highlight => {
          // Create highlight span
          const highlightSpan = document.createElement('span');
          highlightSpan.className = 'document-highlight';
          highlightSpan.style.backgroundColor = highlight.highlight_color;
          highlightSpan.style.cursor = 'pointer';
          highlightSpan.dataset.highlightId = highlight.id;
          highlightSpan.title = `Highlighted by ${highlight.adviser_name}`;
          
          // For now, we'll add a simple highlight marker
          // In a real implementation, you'd need more sophisticated text range handling
          const highlightMarker = document.createElement('mark');
          highlightMarker.style.backgroundColor = highlight.highlight_color;
          highlightMarker.textContent = highlight.highlighted_text;
          highlightMarker.className = 'highlight-marker';
          highlightMarker.dataset.highlightId = highlight.id;
          
          // Add context menu for removing highlights
          highlightMarker.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            if (confirm('Remove this highlight?')) {
              removeHighlight(highlight.id);
            }
          });
        });
      }

      // Display comments in the comments panel - moved to global scope
      window.displayComments = function(comments) {
        const commentsList = document.getElementById('comments-list');
        if (!commentsList) return;
        
        if (comments && comments.length > 0) {
          commentsList.innerHTML = comments.map(comment => `
            <div class="border rounded-lg p-3 bg-white">
              <div class="flex justify-between items-start mb-2">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                    ${comment.adviser_name ? comment.adviser_name.charAt(0).toUpperCase() : 'A'}
                  </div>
                  <span class="text-sm font-medium">${comment.adviser_name || 'Adviser'}</span>
                </div>
                <span class="text-xs text-gray-500">${new Date(comment.created_at).toLocaleString()}</span>
              </div>
              <p class="text-sm text-gray-700">${comment.comment_text}</p>
              ${comment.highlighted_text ? `
                <div class="mt-2 p-2 bg-gray-50 rounded text-xs">
                  <span class="font-medium">Highlighted:</span> "${comment.highlighted_text}"
                </div>
              ` : ''}
            </div>
          `).join('');
        } else {
          commentsList.innerHTML = `
            <div class="text-center py-8 text-gray-500">
              <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
              <p class="text-sm">No comments yet</p>
              <p class="text-xs mt-1 text-gray-400">Click on text in the document to add comments</p>
            </div>
          `;
        }
        lucide.createIcons();
      };

      // Make text selectable for highlighting
      function makeTextSelectable() {
        const contentElement = document.querySelector('.chapter-content');
        if (!contentElement) return;
        
        contentElement.addEventListener('mouseup', function(e) {
          const selection = window.getSelection();
          if (selection.toString().trim().length > 0) {
            selectedText = selection.toString().trim();
            selectedRange = selection.getRangeAt(0);
            
            if (isHighlightMode) {
              addHighlight();
            }
          }
        });
      }

      // === Reusable Highlight and Comment Functions ===
      function enableHighlightMode(container, highlightBtn) {
        let highlightMode = false;
        let selectedText = '';
        let selectedRange = null;

        function resetHighlightMode() {
          highlightMode = false;
          container.style.cursor = 'default';
          if (highlightBtn) highlightBtn.textContent = 'Highlight';
        }

        if (!container) return;

        highlightBtn.addEventListener('click', function() {
          highlightMode = !highlightMode;
          container.style.cursor = highlightMode ? 'crosshair' : 'default';
          this.textContent = highlightMode ? 'Cancel Highlight' : 'Highlight';
        });

        container.addEventListener('mouseup', function(e) {
          if (highlightMode) {
            const selection = window.getSelection();
            if (selection.toString().trim().length > 0) {
              selectedText = selection.toString().trim();
              selectedRange = selection.getRangeAt(0);
              // Use the addHighlight logic from main view
              if (typeof window.currentChapterId !== 'undefined' && window.currentChapterId) {
                addHighlightGeneric(selectedText, selectedRange, window.currentChapterId, container);
              }
              // Reset
              resetHighlightMode();
            }
          }
        });
      }

      function enableCommentMode(container, commentBtn) {
        if (!container) return;
        commentBtn.addEventListener('click', function() {
          // For now, show a notification or modal
          showNotification('Click on any paragraph to add a comment to it.', 'info');
          // You can expand this to allow paragraph-based commenting in fullscreen
        });
      }

      // Generic addHighlight function for both main and fullscreen
      function addHighlightGeneric(selectedText, selectedRange, chapterId, container) {
        if (!selectedText || !chapterId) return;
        const formData = new FormData();
        formData.append('action', 'add_highlight');
        formData.append('chapter_id', chapterId);
        formData.append('start_offset', 0); // Simplified
        formData.append('end_offset', selectedText.length);
        formData.append('highlighted_text', selectedText);
        formData.append('highlight_color', window.currentHighlightColor || '#ffeb3b');

        fetch('api/document_review.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Apply highlight visually in the given container
            if (selectedRange) {
              const highlightSpan = document.createElement('mark');
              highlightSpan.style.backgroundColor = window.currentHighlightColor || '#ffeb3b';
              highlightSpan.className = 'highlight-marker';
              highlightSpan.dataset.highlightId = data.highlight_id;
              try {
                selectedRange.surroundContents(highlightSpan);
              } catch (e) {
                highlightSpan.textContent = selectedText;
                selectedRange.deleteContents();
                selectedRange.insertNode(highlightSpan);
              }
            }
            window.getSelection().removeAllRanges();
          } else {
            showError('Failed to add highlight: ' + data.error);
          }
        })
        .catch(error => {
          showError('Failed to add highlight');
        });
      }

      // Highlight button functionality
      document.getElementById('highlight-btn')?.addEventListener('click', function() {
        window.isHighlightMode = !window.isHighlightMode;
        this.textContent = window.isHighlightMode ? 'Cancel Highlight' : 'Highlight';
        this.className = window.isHighlightMode ? 
          'px-3 py-1 bg-red-100 text-red-800 rounded text-sm hover:bg-red-200' :
          'px-3 py-1 bg-yellow-100 text-yellow-800 rounded text-sm hover:bg-yellow-200';
        
        // Change cursor style for Word viewer content
        const wordContent = document.querySelector('.word-content');
        if (wordContent) {
          wordContent.style.cursor = window.isHighlightMode ? 'crosshair' : 'default';
        }
        
        // Also check for legacy chapter content
        const chapterContent = document.querySelector('.chapter-content');
        if (chapterContent) {
          chapterContent.style.cursor = window.isHighlightMode ? 'crosshair' : 'default';
        }
        
        // Show instruction message
        if (window.isHighlightMode) {
          showNotification('Highlight mode active. Select text to highlight it.', 'info');
        }
      });

      // Comment button functionality
      document.getElementById('comment-btn')?.addEventListener('click', function() {
        // For Word viewer, we'll use paragraph-based commenting
        showNotification('Click on any paragraph to add a comment to it.', 'info');
      });
      
      // Quick comment submission
      document.getElementById('submit-quick-comment')?.addEventListener('click', function() {
        console.log('=== Quick comment submit clicked ===');
        
        const commentText = document.getElementById('quick-comment-text').value.trim();
        console.log('Comment text:', commentText);
        console.log('Current chapter ID:', currentChapterId);
        console.log('Window chapter ID:', window.currentChapterId);
        
        if (!commentText) {
          showNotification('Please enter a comment', 'error');
          return;
        }
        
        if (!currentChapterId && !window.currentChapterId) {
          showNotification('Please select a chapter first', 'error');
          return;
        }
        
        addComment(commentText);
      });

      // Color picker functionality
      document.getElementById('color-picker-btn')?.addEventListener('click', function() {
        document.getElementById('color-picker').classList.toggle('hidden');
      });

      document.querySelectorAll('.color-option').forEach(button => {
        button.addEventListener('click', function() {
          window.currentHighlightColor = this.dataset.color;
          document.getElementById('current-color').style.backgroundColor = window.currentHighlightColor;
          document.getElementById('color-picker').classList.add('hidden');
        });
      });

      // Add comment function
      function addComment(commentText, highlightId = null) {
        console.log('=== addComment called ===');
        console.log('currentChapterId:', currentChapterId);
        console.log('window.currentChapterId:', window.currentChapterId);
        console.log('commentText:', commentText);
        
        if (!currentChapterId && !window.currentChapterId) {
          console.error('No chapter ID available');
          showNotification('Please select a chapter first', 'error');
          return;
        }
        
        const chapterId = currentChapterId || window.currentChapterId;
        console.log('Using chapter ID:', chapterId);
        
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('chapter_id', chapterId);
        formData.append('comment_text', commentText);
        
        // If there's a highlight ID, include it
        if (highlightId) {
          formData.append('highlight_id', highlightId);
        }
        
        // If there's selected text but no highlight, include text position info
        if (selectedText && !highlightId) {
          formData.append('start_offset', 0); // Simplified
          formData.append('end_offset', selectedText.length);
        }
        
        fetch('api/document_review.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          console.log('Response status:', response.status);
          return response.json();
        })
        .then(data => {
          console.log('Response data:', data);
          if (data.success) {
            // Clear comment field
            document.getElementById('quick-comment-text').value = '';
            
            // Show success notification
            showNotification('Comment added successfully!', 'success');
            
            // Reload comments
            loadComments(chapterId);
          } else {
            console.error('Server error:', data.error);
            showNotification('Failed to add comment: ' + data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Error adding comment:', error);
          showNotification('Failed to add comment: ' + error.message, 'error');
        });
      }
      
      // Add highlight function
      function addHighlight() {
        if (!window.selectedText.trim() || !window.currentChapterId) return;
        
        const formData = new FormData();
        formData.append('action', 'add_highlight');
        formData.append('chapter_id', window.currentChapterId);
        formData.append('start_offset', 0); // Simplified - in real implementation, calculate actual offsets
        formData.append('end_offset', window.selectedText.length);
        formData.append('highlighted_text', window.selectedText);
        formData.append('highlight_color', window.currentHighlightColor);
        
        fetch('api/document_review.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Apply highlight visually
            if (window.selectedRange) {
              const highlightSpan = document.createElement('mark');
              highlightSpan.style.backgroundColor = window.currentHighlightColor;
              highlightSpan.className = 'highlight-marker';
              highlightSpan.dataset.highlightId = data.highlight_id;
              
              try {
                window.selectedRange.surroundContents(highlightSpan);
              } catch (e) {
                // Fallback for complex selections
                highlightSpan.textContent = window.selectedText;
                window.selectedRange.deleteContents();
                window.selectedRange.insertNode(highlightSpan);
              }
            }
            
            // Clear selection
            window.getSelection().removeAllRanges();
            window.selectedText = '';
            window.selectedRange = null;
            
            // Exit highlight mode
            window.isHighlightMode = false;
            const highlightBtn = document.getElementById('highlight-btn');
            if (highlightBtn) {
              highlightBtn.textContent = 'Highlight';
              highlightBtn.className = 'px-3 py-1 bg-yellow-100 text-yellow-800 rounded text-sm hover:bg-yellow-200';
            }
          } else {
            showError('Failed to add highlight: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Error adding highlight:', error);
          showError('Failed to add highlight');
        });
      }

      // Comment modal functionality
      document.getElementById('cancel-comment')?.addEventListener('click', function() {
        document.getElementById('comment-modal').classList.add('hidden');
        document.getElementById('comment-text').value = '';
      });

      // Format Analysis Functions - moved to global scope
      window.loadFormatAnalysis = function(fileId) {
        const analysisContent = document.getElementById('format-analysis-content');
        
        // Show loading state
        analysisContent.innerHTML = `
          <div class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-3"></div>
            <p class="text-sm text-gray-500">Analyzing document format...</p>
          </div>
        `;
        
        // Fetch format analysis
        fetch(`api/document_format_analyzer.php?file_id=${fileId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              displayFormatAnalysis(data.analysis, data.file_info);
            } else {
              analysisContent.innerHTML = `
                <div class="text-center py-8 text-red-500">
                  <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-3"></i>
                  <p class="text-sm">Analysis failed: ${data.error}</p>
                </div>
              `;
              lucide.createIcons();
            }
          })
          .catch(error => {
            console.error('Error loading format analysis:', error);
            analysisContent.innerHTML = `
              <div class="text-center py-8 text-red-500">
                <i data-lucide="wifi-off" class="w-12 h-12 mx-auto mb-3"></i>
                <p class="text-sm">Failed to load analysis</p>
              </div>
            `;
            lucide.createIcons();
          });
      };

      window.displayFormatAnalysis = function(analysis, fileInfo) {
        const analysisContent = document.getElementById('format-analysis-content');
        
        // Determine overall status color
        let statusColor = 'text-gray-500';
        let statusBg = 'bg-gray-100';
        let statusIcon = 'file-question';
        
        switch(analysis.compliance_level) {
          case 'excellent':
            statusColor = 'text-green-700';
            statusBg = 'bg-green-100';
            statusIcon = 'check-circle';
            break;
          case 'good':
            statusColor = 'text-blue-700';
            statusBg = 'bg-blue-100';
            statusIcon = 'info';
            break;
          case 'fair':
            statusColor = 'text-yellow-700';
            statusBg = 'bg-yellow-100';
            statusIcon = 'alert-triangle';
            break;
          case 'poor':
            statusColor = 'text-red-700';
            statusBg = 'bg-red-100';
            statusIcon = 'x-circle';
            break;
        }
        
        analysisContent.innerHTML = `
          <!-- Overall Score -->
          <div class="border rounded-lg p-4 mb-4 ${statusBg}">
            <div class="flex items-center mb-2">
              <i data-lucide="${statusIcon}" class="w-5 h-5 ${statusColor} mr-2"></i>
              <h4 class="font-semibold ${statusColor}">Overall Score: ${analysis.overall_score}%</h4>
            </div>
            <p class="text-sm ${statusColor} capitalize">${analysis.compliance_level} compliance level</p>
            ${analysis.total_issues > 0 ? `
              <div class="mt-2 text-xs ${statusColor}">
                ${analysis.critical_issues} critical issues • ${analysis.warnings} warnings
              </div>
            ` : ''}
          </div>

          <!-- File Information -->
          <div class="border rounded-lg p-3 mb-4 bg-gray-50">
            <h4 class="font-medium text-sm mb-2">File Information</h4>
            <div class="space-y-1 text-xs text-gray-600">
              <div><strong>Name:</strong> ${fileInfo.name}</div>
              <div><strong>Size:</strong> ${(fileInfo.size / (1024 * 1024)).toFixed(2)} MB</div>
              <div><strong>Type:</strong> ${fileInfo.type}</div>
            </div>
          </div>

          <!-- Categories Analysis -->
          <div class="space-y-3">
            ${Object.values(analysis.categories).map(category => {
              let categoryColor = 'text-gray-600';
              let categoryBg = 'bg-gray-100';
              let categoryIcon = 'minus';
              
              switch(category.status) {
                case 'good':
                  categoryColor = 'text-green-600';
                  categoryBg = 'bg-green-50';
                  categoryIcon = 'check';
                  break;
                case 'warning':
                  categoryColor = 'text-yellow-600';
                  categoryBg = 'bg-yellow-50';
                  categoryIcon = 'alert-triangle';
                  break;
                case 'error':
                  categoryColor = 'text-red-600';
                  categoryBg = 'bg-red-50';
                  categoryIcon = 'x';
                  break;
              }
              
              return `
                <div class="border rounded-lg p-3 ${categoryBg}">
                  <div class="flex items-center justify-between mb-2">
                    <h5 class="font-medium text-sm ${categoryColor}">${category.category}</h5>
                    <div class="flex items-center">
                      <i data-lucide="${categoryIcon}" class="w-4 h-4 ${categoryColor} mr-1"></i>
                      <span class="text-xs ${categoryColor}">${category.score}%</span>
                    </div>
                  </div>
                  <p class="text-xs ${categoryColor} mb-2">${category.message}</p>
                  
                  ${category.issues && category.issues.length > 0 ? `
                    <div class="mb-2">
                      <h6 class="text-xs font-medium ${categoryColor} mb-1">Issues:</h6>
                      <ul class="text-xs ${categoryColor} space-y-1">
                        ${category.issues.map(issue => `<li>• ${issue}</li>`).join('')}
                      </ul>
                    </div>
                  ` : ''}
                  
                  ${category.recommendations && category.recommendations.length > 0 ? `
                    <div>
                      <h6 class="text-xs font-medium ${categoryColor} mb-1">Recommendations:</h6>
                      <ul class="text-xs ${categoryColor} space-y-1">
                        ${category.recommendations.map(rec => `<li>• ${rec}</li>`).join('')}
                      </ul>
                    </div>
                  ` : ''}
                  
                  ${category.details ? `
                    <details class="mt-2">
                      <summary class="text-xs ${categoryColor} cursor-pointer">View Details</summary>
                      <div class="mt-1 text-xs ${categoryColor} bg-white p-2 rounded border">
                        <pre class="text-xs overflow-x-auto">${JSON.stringify(category.details, null, 2)}</pre>
                      </div>
                    </details>
                  ` : ''}
                </div>
              `;
            }).join('')}
          </div>

          <!-- Thesis Formatting Guidelines -->
          <div class="border rounded-lg p-3 mt-4 bg-blue-50">
            <h4 class="font-medium text-sm text-blue-700 mb-2 flex items-center">
              <i data-lucide="book-open" class="w-4 h-4 mr-2"></i>
              Thesis Formatting Standards
            </h4>
            <div class="text-xs text-blue-600 space-y-1">
              <div>• <strong>Margins:</strong> 1 inch minimum on all sides</div>
              <div>• <strong>Font:</strong> Times New Roman 12pt (body text)</div>
              <div>• <strong>Spacing:</strong> Double spacing for body text</div>
              <div>• <strong>Page Numbers:</strong> Required in header or footer</div>
              <div>• <strong>Headings:</strong> Use consistent heading styles</div>
              <div>• <strong>Paragraphs:</strong> 0.5-inch first-line indentation</div>
            </div>
          </div>
        `;
        
        // Refresh Lucide icons
        lucide.createIcons();
      };

      document.getElementById('save-comment')?.addEventListener('click', function() {
        const commentText = document.getElementById('comment-text').value.trim();
        if (!commentText || !currentChapterId) return;
        
        // Use the addComment function
        addComment(commentText);
        
        // Close modal
        document.getElementById('comment-modal').classList.add('hidden');
        document.getElementById('comment-text').value = '';
        
        // Clear selection
        window.getSelection().removeAllRanges();
        selectedText = '';
      });

      // Remove highlight function - moved to global scope
      window.removeHighlight = function(highlightId) {
        const formData = new FormData();
        formData.append('action', 'remove_highlight');
        formData.append('highlight_id', highlightId);
        
        fetch('api/document_review.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Remove highlight from DOM
            const highlightElement = document.querySelector(`[data-highlight-id="${highlightId}"]`);
            if (highlightElement) {
              const parent = highlightElement.parentNode;
              parent.insertBefore(document.createTextNode(highlightElement.textContent), highlightElement);
              parent.removeChild(highlightElement);
            }
          } else {
            showError('Failed to remove highlight: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Error removing highlight:', error);
          showError('Failed to remove highlight');
        });
      };

      // Resolve comment function - moved to global scope
      window.resolveComment = function(commentId) {
        const formData = new FormData();
        formData.append('action', 'resolve_comment');
        formData.append('comment_id', commentId);
        
        fetch('api/document_review.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            loadComments(currentChapterId);
          } else {
            showError('Failed to resolve comment: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Error resolving comment:', error);
          showError('Failed to resolve comment');
        });
      };

      // Error display function - moved to global scope
      window.showError = function(message) {
        console.error(message);
        // Use the notification system instead of alert
        showNotification(message, 'error');
      };

      // Close modal when clicking outside
      document.getElementById('comment-modal')?.addEventListener('click', function(e) {
        if (e.target === this) {
          this.classList.add('hidden');
          document.getElementById('comment-text').value = '';
        }
      });

      // Close color picker when clicking outside
      document.addEventListener('click', function(e) {
        if (!e.target.closest('#color-picker-btn') && !e.target.closest('#color-picker')) {
          document.getElementById('color-picker')?.classList.add('hidden');
        }
      });
      
      // Feedback Management Functionality
      let currentStudentId = null;
      let currentChapterIdForFeedback = null;
      
      // Student selection for feedback
      document.addEventListener('click', function(e) {
        if (e.target.closest('.feedback-student-item')) {
          const studentItem = e.target.closest('.feedback-student-item');
          const studentId = studentItem.dataset.studentId;
          const studentName = studentItem.dataset.studentName;
          const thesisTitle = studentItem.dataset.thesisTitle;
          
          // Remove active class from all students
          document.querySelectorAll('.feedback-student-item').forEach(item => {
            item.classList.remove('bg-blue-100');
          });
          
          // Add active class to selected student
          studentItem.classList.add('bg-blue-100');
          
          // Update current student
          currentStudentId = studentId;
          
          // Load chapters for this student
          loadStudentChapters(studentId, studentName, thesisTitle);
          
          // Load feedback history for this student
          loadFeedbackHistory(studentId, studentName);
        }
        
        if (e.target.closest('.feedback-chapter-item')) {
          const chapterItem = e.target.closest('.feedback-chapter-item');
          const chapterId = chapterItem.dataset.chapterId;
          const chapterTitle = chapterItem.dataset.chapterTitle;
          const studentName = chapterItem.dataset.studentName;
          
          console.log("Chapter clicked:", {
            chapterId,
            chapterTitle,
            studentName
          });
          
          // Remove active class from all chapters
          document.querySelectorAll('.feedback-chapter-item').forEach(item => {
            item.classList.remove('bg-blue-100');
          });
          
          // Add active class to selected chapter
          chapterItem.classList.add('bg-blue-100');
          
          // Update current chapter
          currentChapterIdForFeedback = chapterId;
          console.log("Set currentChapterIdForFeedback to:", currentChapterIdForFeedback);
          
          // Show feedback form
          showFeedbackForm(chapterId, chapterTitle, studentName);
        }
      });
      
      // Load chapters for a student
      function loadStudentChapters(studentId, studentName, thesisTitle) {
        console.log("Loading chapters for student ID:", studentId);
        const chapterList = document.getElementById('feedback-chapter-list');
        chapterList.innerHTML = `
          <div class="text-center py-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500">Loading chapters...</p>
          </div>
        `;
        
        // Get thesis for this student
        console.log("Available theses:", theses);
        const thesis = theses.find(t => t.student_user_id == studentId);
        console.log("Found thesis:", thesis);
        if (!thesis) {
          chapterList.innerHTML = `
            <div class="text-center py-8 text-gray-500">
              <i data-lucide="file-text" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
              <p class="text-sm">No thesis found for this student</p>
            </div>
          `;
          lucide.createIcons();
          return;
        }
        
        // Fetch chapters for this thesis
        const url = `api/document_review.php?action=get_chapters&thesis_id=${thesis.id}`;
        console.log("Fetching chapters from:", url);
        
        fetch(url, {
            credentials: 'same-origin' // Include cookies in the request
          })
          .then(response => {
            console.log("Response status:", response.status);
            if (!response.ok) {
              if (response.status === 401) {
                throw new Error('Session expired. Please refresh the page and login again.');
              } else {
                throw new Error('Failed to load chapters');
              }
            }
            return response.json();
          })
          .then(data => {
            console.log("Chapters data:", data);
            if (data.success && data.chapters && data.chapters.length > 0) {
              chapterList.innerHTML = data.chapters.map(chapter => {
                console.log("Processing chapter:", chapter);
                return `
                <button 
                  class="w-full text-left px-3 py-2 border rounded-lg hover:bg-blue-50 feedback-chapter-item"
                  data-chapter-id="${chapter.id}"
                  data-chapter-title="${chapter.title}"
                  data-student-name="${studentName}">
                  <div class="flex justify-between items-center">
                    <div>
                      <span class="font-medium text-sm">Ch. ${chapter.chapter_number}: ${chapter.title}</span>
                      <div class="text-xs text-gray-500 mt-1">
                        ${chapter.status === 'submitted' ? 'Submitted for review' : 
                          (chapter.status === 'approved' ? 'Approved' : 'Draft')}
                      </div>
                    </div>
                    <span class="text-xs px-1 py-1 rounded
                      ${chapter.status === 'submitted' ? 'bg-yellow-100 text-yellow-800' : 
                        (chapter.status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')}">
                      ${chapter.status.charAt(0).toUpperCase() + chapter.status.slice(1)}
                    </span>
                  </div>
                </button>
              `}).join('');
            } else {
              chapterList.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                  <i data-lucide="file-text" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                  <p class="text-sm">No chapters available for this student</p>
                </div>
              `;
              lucide.createIcons();
            }
          })
          .catch(error => {
            console.error('Error loading chapters:', error);
            chapterList.innerHTML = `
              <div class="text-center py-8 text-gray-500">
                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                <p class="text-sm">Failed to load chapters</p>
                <p class="text-xs text-red-500 mt-2">${error.message}</p>
              </div>
            `;
            lucide.createIcons();
            
            // If session expired, show a refresh button
            if (error.message.includes('Session expired')) {
              const refreshBtn = document.createElement('button');
              refreshBtn.className = 'mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700';
              refreshBtn.textContent = 'Refresh Page';
              refreshBtn.onclick = () => window.location.reload();
              chapterList.querySelector('div').appendChild(refreshBtn);
            }
          });
      }
      
      // Load feedback history for a student
      function loadFeedbackHistory(studentId, studentName) {
        console.log("=== Loading feedback history ===");
        console.log("Student ID:", studentId);
        console.log("Student Name:", studentName);
        
        const feedbackHistory = document.getElementById('feedback-history');
        feedbackHistory.innerHTML = `
          <div class="text-center py-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500">Loading feedback history...</p>
          </div>
        `;
        
        const url = `api/feedback_management.php?action=get_adviser_feedback&student_id=${studentId}`;
        console.log("Fetching from URL:", url);
        
        // Fetch feedback history for this student
        fetch(url)
          .then(response => {
            console.log("Feedback history response status:", response.status);
            console.log("Feedback history response OK:", response.ok);
            if (!response.ok) {
              throw new Error('Failed to load feedback history');
            }
            return response.json();
          })
          .then(data => {
            console.log("=== Feedback history response data ===");
            console.log("Full response:", data);
            console.log("Success:", data.success);
            console.log("Feedback array:", data.feedback);
            console.log("Feedback length:", data.feedback ? data.feedback.length : 'undefined');
            
            if (data.success && data.feedback && data.feedback.length > 0) {
              console.log("Displaying feedback items:");
              data.feedback.forEach((feedback, index) => {
                console.log(`Feedback ${index}:`, feedback);
              });
              
              feedbackHistory.innerHTML = data.feedback.map(feedback => `
                <div class="border rounded-lg p-4">
                  <div class="flex justify-between items-start mb-2">
                    <div>
                      <span class="font-medium">Chapter ${feedback.chapter_number}: ${feedback.chapter_title}</span>
                      <div class="text-sm text-gray-500">
                        ${new Date(feedback.created_at).toLocaleString()}
                      </div>
                    </div>
                    <span class="text-xs px-2 py-1 rounded
                      ${feedback.feedback_type === 'comment' ? 'bg-blue-100 text-blue-800' : 
                        (feedback.feedback_type === 'approval' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800')}">
                      ${feedback.feedback_type.charAt(0).toUpperCase() + feedback.feedback_type.slice(1)}
                    </span>
                  </div>
                  <p class="text-gray-700">${feedback.feedback_text}</p>
                  <div class="mt-2 flex justify-end">
                    <button class="text-red-600 text-xs hover:text-red-800 delete-feedback-btn" data-feedback-id="${feedback.id}">
                      Delete
                    </button>
                  </div>
                </div>
              `).join('');
              
              // Add event listeners for delete buttons
              document.querySelectorAll('.delete-feedback-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                  const feedbackId = this.dataset.feedbackId;
                  if (confirm('Are you sure you want to delete this feedback?')) {
                    deleteFeedback(feedbackId, studentId);
                  }
                });
              });
            } else {
              console.log("No feedback to display - showing empty state");
              feedbackHistory.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                  <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                  <p class="text-sm">No feedback history available for this student</p>
                </div>
              `;
              lucide.createIcons();
            }
          })
          .catch(error => {
            console.error('Error loading feedback history:', error);
            feedbackHistory.innerHTML = `
              <div class="text-center py-8 text-gray-500">
                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                <p class="text-sm">Failed to load feedback history</p>
              </div>
            `;
            lucide.createIcons();
          });
      }
      
      // Show feedback form for a chapter
      function showFeedbackForm(chapterId, chapterTitle, studentName) {
        const formContainer = document.getElementById('feedback-form-container');
        formContainer.innerHTML = `
          <form id="add-feedback-form">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Student:</label>
              <div class="text-sm">${studentName}</div>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Chapter:</label>
              <div class="text-sm">${chapterTitle}</div>
            </div>
            <div class="mb-4">
              <label for="feedback-type" class="block text-sm font-medium text-gray-700 mb-2">Feedback Type:</label>
              <select id="feedback-type" name="feedback_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="comment">Comment</option>
                <option value="revision">Revision Required</option>
                <option value="approval">Approval</option>
              </select>
            </div>
            <div class="mb-4">
              <label for="feedback-text" class="block text-sm font-medium text-gray-700 mb-2">Feedback:</label>
              <textarea id="feedback-text" name="feedback_text" rows="5" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your feedback here..."></textarea>
            </div>
            <div class="flex justify-end">
              <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Submit Feedback
              </button>
            </div>
          </form>
        `;
        
        // Add event listener for form submission
        document.getElementById('add-feedback-form').addEventListener('submit', function(e) {
          e.preventDefault();
          
          console.log("=== FORM SUBMISSION DEBUG ===");
          console.log("Form submitted!");
          console.log("chapterId from closure:", chapterId);
          console.log("typeof chapterId:", typeof chapterId);
          
          const feedbackType = document.getElementById('feedback-type').value;
          const feedbackText = document.getElementById('feedback-text').value.trim();
          
          console.log("feedbackType:", feedbackType);
          console.log("feedbackText:", feedbackText);
          console.log("feedbackText length:", feedbackText.length);
          
          if (!feedbackText) {
            console.log("ERROR: No feedback text entered");
            showNotification('Please enter feedback text', 'error');
            return;
          }
          
          console.log("About to call addFeedback...");
          addFeedback(chapterId, feedbackText, feedbackType);
        });
      }
      
      // Add feedback
      function addFeedback(chapterId, feedbackText, feedbackType) {
        console.log("=== ADDGEEDBACK FUNCTION CALLED ===");
        console.log("Adding feedback:", {
          chapterId,
          feedbackText,
          feedbackType
        });
        
        // Validate inputs before making the request
        if (!chapterId) {
          console.error("Chapter ID is missing or invalid:", chapterId);
          showNotification('Error: Chapter ID is missing. Please select a chapter first.', 'error');
          return;
        }
        
        if (!feedbackText || feedbackText.trim() === '') {
          console.error("Feedback text is empty");
          showNotification('Error: Please enter feedback text.', 'error');
          return;
        }
        
        // Try JSON instead of URLSearchParams
        const data = {
          action: 'add_feedback',
          chapter_id: chapterId,
          feedback_text: feedbackText,
          feedback_type: feedbackType
        };
        
        // Debug: Log data being sent
        console.log("JSON data being sent:");
        console.log(JSON.stringify(data, null, 2));
        
        console.log("=== ABOUT TO SEND FETCH REQUEST ===");
        console.log("URL: api/feedback_management.php");
        console.log("Method: POST");
        
        fetch('api/feedback_management.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(data)
        })
        .then(response => {
          console.log("Feedback response status:", response.status);
          console.log("Feedback response headers:", response.headers);
          console.log("Response OK:", response.ok);
          console.log("Response URL:", response.url);
          
          // Get response text first to check if it's valid JSON
          return response.text().then(text => {
            console.log("Raw response text:", text);
            console.log("Response text length:", text.length);
            
            if (!response.ok) {
              console.error(`HTTP Error ${response.status}: ${text}`);
              throw new Error(`HTTP ${response.status}: ${text}`);
            }
            
            if (text.trim() === '') {
              console.error("Empty response received");
              throw new Error("Empty response from server");
            }
            
            try {
              const parsed = JSON.parse(text);
              console.log("Successfully parsed JSON:", parsed);
              return parsed;
            } catch (e) {
              console.error("Failed to parse JSON response:", e);
              console.error("Response text that failed to parse:", JSON.stringify(text));
              throw new Error(`Invalid JSON response: ${text.substring(0, 200)}${text.length > 200 ? '...' : ''}`);
            }
          });
        })
        .then(data => {
          console.log("Feedback response data:", data);
          if (data.success) {
            // Reset form
            document.getElementById('feedback-text').value = '';
            
            // Show success message using notification system
            showNotification('Feedback added successfully!', 'success');
            
            // Reload feedback history
            if (currentStudentId) {
              loadFeedbackHistory(currentStudentId, '');
            } else {
              console.warn("currentStudentId is not set, skipping feedback history reload");
            }
          } else {
            // Show more detailed error information
            const errorMessage = data.error || data.message || 'Failed to add feedback';
            console.error('Server returned error:', data);
            console.error('Full server response:', JSON.stringify(data));
            throw new Error(errorMessage);
          }
        })
        .catch(error => {
          console.error('Error adding feedback:', error);
          console.error('Error stack:', error.stack);
          showNotification('Failed to add feedback: ' + error.message, 'error');
        });
      }
      
      // Delete feedback
      function deleteFeedback(feedbackId, studentId) {
        const formData = new FormData();
        formData.append('action', 'delete_feedback');
        formData.append('feedback_id', feedbackId);
        
        fetch('api/feedback_management.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Failed to delete feedback');
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            // Show success message
            alert('Feedback deleted successfully');
            
            // Reload feedback history
            loadFeedbackHistory(studentId, '');
          } else {
            throw new Error(data.error || 'Failed to delete feedback');
          }
        })
        .catch(error => {
          console.error('Error deleting feedback:', error);
          alert('Failed to delete feedback: ' + error.message);
        });
      }

      // Paragraph comment modal functionality
      function openParagraphCommentModal(paragraphId, paragraphContent) {
        const paragraphIdInput = document.getElementById('paragraph-id-input');
        const paragraphTextPreview = document.getElementById('paragraph-text-preview');
        const paragraphCommentModal = document.getElementById('paragraph-comment-modal');
        
        if (paragraphIdInput && paragraphTextPreview && paragraphCommentModal) {
          paragraphIdInput.value = paragraphId;
          paragraphTextPreview.textContent = paragraphContent.substring(0, 300) + 
            (paragraphContent.length > 300 ? '...' : '');
          paragraphCommentModal.classList.remove('hidden');
        } else {
          console.error('One or more paragraph comment modal elements not found');
        }
      }
      
      document.getElementById('cancel-paragraph-comment')?.addEventListener('click', function() {
        const modal = document.getElementById('paragraph-comment-modal');
        const commentText = document.getElementById('paragraph-comment-text');
        
        if (modal) modal.classList.add('hidden');
        if (commentText) commentText.value = '';
      });
      
      document.getElementById('save-paragraph-comment')?.addEventListener('click', function() {
        const commentTextElement = document.getElementById('paragraph-comment-text');
        const paragraphIdElement = document.getElementById('paragraph-id-input');
        const modal = document.getElementById('paragraph-comment-modal');
        
        if (!commentTextElement || !paragraphIdElement) {
          console.error('Comment text or paragraph ID element not found');
          return;
        }
        
        const commentText = commentTextElement.value.trim();
        const paragraphId = paragraphIdElement.value;
        
        if (!commentText || !currentChapterId || !paragraphId) return;
        
        // Add paragraph comment
        addParagraphComment(paragraphId, commentText);
        
        // Close modal
        if (modal) modal.classList.add('hidden');
        commentTextElement.value = '';
      });
      
      // Add paragraph comment function
      function addParagraphComment(paragraphId, commentText) {
        if (!currentChapterId) return;
        
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('chapter_id', currentChapterId);
        formData.append('comment_text', commentText);
        formData.append('paragraph_id', paragraphId);
        
        fetch('api/document_review.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Add visual indicator to the paragraph
            const paragraph = document.getElementById(paragraphId);
            if (paragraph) {
              // Add comment indicator if not already present
              if (!paragraph.querySelector('.comment-indicator')) {
                const indicator = document.createElement('div');
                indicator.className = 'comment-indicator absolute left-0 top-0 bg-blue-500 w-1 h-full';
                paragraph.classList.add('pl-3');
                paragraph.style.borderLeft = '3px solid #3b82f6';
                paragraph.appendChild(indicator);
              }
            }
            
            // Reload comments
            loadComments(currentChapterId);
            
            // Show success notification
            showNotification('Comment added successfully', 'success');
          } else {
            showError('Failed to add comment: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Error adding paragraph comment:', error);
          showError('Failed to add comment');
        });
      }
      
      // Show notification function - moved to global scope
      window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
          type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 
          type === 'error' ? 'bg-red-100 border-l-4 border-red-500 text-red-700' : 
          'bg-blue-100 border-l-4 border-blue-500 text-blue-700'
        }`;
        
        notification.innerHTML = `
          <div class="flex items-center">
            <div class="mr-3">
              <i data-lucide="${
                type === 'success' ? 'check-circle' : 
                type === 'error' ? 'alert-circle' : 
                'info'
              }" class="w-5 h-5"></i>
            </div>
            <div>${message}</div>
          </div>
        `;
        
        document.body.appendChild(notification);
        lucide.createIcons();
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
          document.body.removeChild(notification);
        }, 3000);
      };

      // Reports functionality
      let currentChart;
      
      // Initialize reports when reports tab is clicked
      function initializeReports() {
        if (document.getElementById('report-templates-list').innerHTML.includes('Loading templates...')) {
          fetchReportTemplates();
          fetchSavedReports();
          loadAnalyticsSummary();
        }
      }
      
      // Load analytics summary
      function loadAnalyticsSummary() {
        fetch('api/reports_analytics.php?action=chapter_submission_stats')
        .then(response => response.json())
        .then(data => {
          if (data.chapter_stats) {
            updateAnalyticsSummary(data.chapter_stats);
          }
        })
        .catch(error => {
          console.error('Error loading analytics summary:', error);
        });
      }

      function updateAnalyticsSummary(chapterStats) {
        let totalChapters = 0;
        let submittedChapters = 0;
        let approvedChapters = 0;
        let totalStudents = 0;

        chapterStats.forEach(stat => {
          totalChapters += parseInt(stat.total_chapters) || 0;
          submittedChapters += parseInt(stat.submitted_count) || 0;
          approvedChapters += parseInt(stat.approved_count) || 0;
          totalStudents = Math.max(totalStudents, parseInt(stat.total_students) || 0);
        });

        // Update the summary cards
        document.getElementById('total-chapters').textContent = totalChapters;
        document.getElementById('submitted-chapters').textContent = submittedChapters;
        document.getElementById('approved-chapters').textContent = approvedChapters;
        document.getElementById('active-students').textContent = totalStudents;

        // Show the analytics summary
        document.getElementById('analytics-summary').style.display = 'grid';
        
        // Recreate icons for new elements
        lucide.createIcons();
      }
      
      // Fetch report templates
      function fetchReportTemplates() {
        fetch('api/reports_analytics.php?action=templates')
        .then(response => response.json())
        .then(data => {
          if (data.templates) {
            renderReportTemplates(data.templates);
          } else {
            document.getElementById('report-templates-list').innerHTML = 
              '<div class="text-center py-4 text-gray-500"><i data-lucide="alert-triangle" class="w-4 h-4 mx-auto mb-2"></i><p class="text-xs">No templates available</p></div>';
          }
        })
        .catch(error => {
          console.error('Error fetching report templates:', error);
          document.getElementById('report-templates-list').innerHTML = 
            '<div class="text-center py-4 text-red-500"><i data-lucide="alert-circle" class="w-4 h-4 mx-auto mb-2"></i><p class="text-xs">Error loading templates</p></div>';
        });
      }

      // Fetch saved reports
      function fetchSavedReports() {
        fetch('api/reports_analytics.php?action=saved_reports')
        .then(response => response.json())
        .then(data => {
          if (data.saved_reports && data.saved_reports.length > 0) {
            renderSavedReports(data.saved_reports);
          } else {
            document.getElementById('saved-reports-list').innerHTML = 
              '<div class="text-center py-4 text-gray-500"><p class="text-xs">No saved reports</p></div>';
          }
        })
        .catch(error => {
          console.error('Error fetching saved reports:', error);
          document.getElementById('saved-reports-list').innerHTML = 
            '<div class="text-center py-4 text-red-500"><i data-lucide="alert-circle" class="w-4 h-4 mx-auto mb-2"></i><p class="text-xs">Error loading reports</p></div>';
        });
      }

      // Render report templates
      function renderReportTemplates(templates) {
        const container = document.getElementById('report-templates-list');
        container.innerHTML = '';
        
        templates.forEach(template => {
          const button = document.createElement('button');
          button.className = 'w-full text-left px-3 py-2 text-sm border rounded-lg hover:bg-blue-50 transition-colors duration-150 flex items-center gap-2';
          button.innerHTML = `<i data-lucide="file-chart" class="w-4 h-4"></i> ${template.name}`;
          button.dataset.templateId = template.id; // Set the template ID as a data attribute
          button.onclick = () => {
            // Remove active state from all buttons
            container.querySelectorAll('button').forEach(btn => btn.classList.remove('bg-blue-100', 'border-blue-300'));
            document.getElementById('saved-reports-list').querySelectorAll('button').forEach(btn => btn.classList.remove('bg-blue-100', 'border-blue-300'));
            
            // Add active state
            button.classList.add('bg-blue-100', 'border-blue-300');
            
            generateReport(template.id);
          };
          container.appendChild(button);
        });
        
        lucide.createIcons();
      }

      // Render saved reports
      function renderSavedReports(reports) {
        const container = document.getElementById('saved-reports-list');
        container.innerHTML = '';
        
        reports.forEach(report => {
          const button = document.createElement('button');
          button.className = 'w-full text-left px-3 py-2 text-sm border rounded-lg hover:bg-blue-50 transition-colors duration-150 flex items-center gap-2';
          button.innerHTML = `<i data-lucide="bookmark" class="w-4 h-4"></i> ${report.name}`;
          button.dataset.reportId = report.id; // Add report ID to dataset
          button.onclick = () => {
            // Remove active state from all buttons
            container.querySelectorAll('button').forEach(btn => btn.classList.remove('bg-blue-100', 'border-blue-300'));
            document.getElementById('report-templates-list').querySelectorAll('button').forEach(btn => btn.classList.remove('bg-blue-100', 'border-blue-300'));
            
            // Add active state
            button.classList.add('bg-blue-100', 'border-blue-300');
            
            displaySavedReport(report);
          };
          container.appendChild(button);
        });
        
        lucide.createIcons();
      }

      // Generate report
      function generateReport(templateId) {
        // Show loading state
        document.getElementById('report-data-table').innerHTML = 
          '<div class="flex items-center justify-center py-12 text-gray-500"><i data-lucide="loader" class="w-4 h-4 animate-spin mr-2"></i>Generating report...</div>';
        
        fetch(`api/reports_analytics.php?action=generate_report&template_id=${templateId}`)
        .then(response => response.json())
        .then(result => {
          if (result.report && result.report.data) {
            displayReport(result.report);
          } else {
            document.getElementById('report-data-table').innerHTML = 
              `<div class="text-center py-12 text-red-500"><i data-lucide="alert-triangle" class="w-8 h-8 mx-auto mb-3"></i><h3 class="font-medium mb-2">Error</h3><p class="text-sm">Error generating report: ${result.error || 'Unknown error'}</p></div>`;
          }
        })
        .catch(error => {
          console.error('Error generating report:', error);
          document.getElementById('report-data-table').innerHTML = 
            '<div class="text-center py-12 text-red-500"><i data-lucide="alert-triangle" class="w-8 h-8 mx-auto mb-3"></i><h3 class="font-medium mb-2">Error</h3><p class="text-sm">Failed to generate report. Please try again.</p></div>';
        });
      }

      // Display report
      function displayReport(report) {
        document.getElementById('report-title').innerHTML = `<i data-lucide="chart-area" class="w-4 h-4"></i> ${report.template.name}`;
        
        const descriptionDiv = document.getElementById('report-description');
        if (report.template.description) {
          descriptionDiv.textContent = report.template.description;
          descriptionDiv.classList.remove('hidden');
        } else {
          descriptionDiv.classList.add('hidden');
        }
        
        renderChart(report.template.chart_type, report.data, report.template.name);
        renderTable(report.data);
      }

      // Display saved report
      function displaySavedReport(report) {
        document.getElementById('report-title').innerHTML = `<i data-lucide="bookmark" class="w-4 h-4"></i> ${report.name}`;
        
        const descriptionDiv = document.getElementById('report-description');
        if (report.description) {
          descriptionDiv.textContent = report.description;
          descriptionDiv.classList.remove('hidden');
        } else {
          descriptionDiv.classList.add('hidden');
        }
        
        const reportData = JSON.parse(report.report_data);
        renderChart(reportData.template.chart_type, reportData.data, reportData.template.name);
        renderTable(reportData.data);
      }

      // Render chart
      function renderChart(type, data, label) {
        if (currentChart) {
          currentChart.destroy();
        }

        if (!data || data.length === 0) {
          document.getElementById('report-data-table').innerHTML = 
            '<div class="text-center py-12 text-gray-500"><i data-lucide="chart-pie" class="w-8 h-8 mx-auto mb-3"></i><h3 class="font-medium mb-2">No Data</h3><p class="text-sm">No data available for this report.</p></div>';
          return;
        }
        
        const ctx = document.getElementById('reportChart').getContext('2d');
        const labels = data.map(item => Object.values(item)[0]);
        const values = data.map(item => Object.values(item)[1]);
        
        const chartConfig = {
          type: type || 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: label,
              data: values,
              backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)'
              ],
              borderColor: [
                'rgba(59, 130, 246, 1)',
                'rgba(16, 185, 129, 1)',
                'rgba(245, 158, 11, 1)',
                'rgba(239, 68, 68, 1)',
                'rgba(139, 92, 246, 1)',
                'rgba(236, 72, 153, 1)'
              ],
              borderWidth: 2,
              borderRadius: 4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'top',
                labels: {
                  padding: 20,
                  usePointStyle: true
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: 'rgba(0, 0, 0, 0.1)'
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            }
          }
        };
        
        currentChart = new Chart(ctx, chartConfig);
      }

      // Render table
      function renderTable(data) {
        if (!data || data.length === 0) {
          document.getElementById('report-data-table').innerHTML = 
            '<div class="text-center py-12 text-gray-500"><i data-lucide="table" class="w-8 h-8 mx-auto mb-3"></i><h3 class="font-medium mb-2">No Data</h3><p class="text-sm">No data available for this report.</p></div>';
          return;
        }
        
        // Create table
        const table = document.createElement('table');
        table.className = 'w-full text-sm border-collapse border border-gray-200';
        
        // Create header
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        headerRow.className = 'bg-gray-50';
        
        const columns = Object.keys(data[0]);
        columns.forEach(column => {
          const th = document.createElement('th');
          th.className = 'border border-gray-200 px-4 py-2 text-left font-medium text-gray-700';
          
          // Improved column header formatting for analytics
          let headerText;
          switch(column.toLowerCase()) {
            case 'chapter_name':
              headerText = 'Chapter';
              break;
            case 'student_name':
              headerText = 'Student';
              break;
            case 'adviser_name':
              headerText = 'Adviser';
              break;
            case 'submitted_count':
              headerText = 'Submitted';
              break;
            case 'approved_count':
              headerText = 'Approved';
              break;
            case 'rejected_count':
              headerText = 'Rejected';
              break;
            case 'total_students':
              headerText = 'Total Students';
              break;
            case 'submission_percentage':
              headerText = 'Submission Rate (%)';
              break;
            case 'approval_rate':
              headerText = 'Approval Rate (%)';
              break;
            case 'progress_percentage':
              headerText = 'Progress (%)';
              break;
            case 'chapter_1':
              headerText = 'Ch. 1';
              break;
            case 'chapter_2':
              headerText = 'Ch. 2';
              break;
            case 'chapter_3':
              headerText = 'Ch. 3';
              break;
            case 'chapter_4':
              headerText = 'Ch. 4';
              break;
            case 'chapter_5':
              headerText = 'Ch. 5';
              break;
            case 'total_submitted':
              headerText = 'Total Submitted';
              break;
            case 'chapters_submitted':
              headerText = 'Chapters Submitted';
              break;
            case 'chapters_approved':
              headerText = 'Chapters Approved';
              break;
            case 'total_chapters_reviewed':
              headerText = 'Chapters Reviewed';
              break;
            case 'department_program':
              headerText = 'Department/Program';
              break;
            case 'completion_rate':
              headerText = 'Completion Rate (%)';
              break;
            case 'avg_progress':
              headerText = 'Avg Progress (%)';
              break;
            case 'students_submitted':
              headerText = 'Students Submitted';
              break;
            case 'total_students_with_chapter':
              headerText = 'Total Students';
              break;
            case 'number_of_students':
              headerText = 'Number of Students';
              break;
            case 'chapter':
              headerText = 'Chapter';
              break;
            case 'chapter_num':
              headerText = '#';
              break;
            default:
              headerText = column.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
          }
          
          th.textContent = headerText;
          headerRow.appendChild(th);
        });
        
        thead.appendChild(headerRow);
        table.appendChild(thead);
        
        // Create body
        const tbody = document.createElement('tbody');
        data.forEach((item, index) => {
          const row = document.createElement('tr');
          row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
          
          columns.forEach(column => {
            const cell = document.createElement('td');
            cell.className = 'border border-gray-200 px-4 py-2 text-gray-700';
            
            const cellValue = item[column];
            
            // Enhanced cell formatting for analytics data
            switch(column.toLowerCase()) {
              case 'progress_percentage':
              case 'submission_percentage':
              case 'approval_rate':
              case 'completion_rate':
              case 'avg_progress':
                // Add percentage formatting with color coding
                const percentage = parseFloat(cellValue) || 0;
                let colorClass = '';
                if (percentage >= 80) colorClass = 'text-green-600 font-semibold';
                else if (percentage >= 60) colorClass = 'text-blue-600 font-medium';
                else if (percentage >= 40) colorClass = 'text-yellow-600 font-medium';
                else colorClass = 'text-red-600 font-medium';
                
                cell.innerHTML = `<span class="${colorClass}">${percentage}%</span>`;
                break;
                
              case 'chapter_1':
              case 'chapter_2':
              case 'chapter_3':
              case 'chapter_4':
              case 'chapter_5':
                // Chapter status indicators
                const chapterCount = parseInt(cellValue) || 0;
                const statusClass = chapterCount > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600';
                const statusIcon = chapterCount > 0 ? '✓' : '○';
                cell.innerHTML = `<span class="${statusClass} px-2 py-1 rounded-full text-xs font-medium">${statusIcon}</span>`;
                break;
                
              case 'submitted_count':
              case 'approved_count':
              case 'rejected_count':
              case 'total_students':
              case 'chapters_submitted':
              case 'chapters_approved':
              case 'total_submitted':
              case 'total_chapters_reviewed':
                // Add badge styling for counts
                cell.innerHTML = `<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">${cellValue}</span>`;
                break;
                
              case 'student_name':
              case 'adviser_name':
                // Make names bold
                cell.innerHTML = `<span class="font-medium">${cellValue}</span>`;
                break;
                
              case 'chapter_name':
                // Style chapter names
                cell.innerHTML = `<span class="font-medium text-blue-600">${cellValue}</span>`;
                break;
                
              case 'total_chapters_reviewed':
                // Add badge styling for counts
                cell.innerHTML = `<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">${cellValue}</span>`;
                break;
                
              case 'students_submitted':
              case 'total_students_with_chapter':
              case 'number_of_students':
                // Add badge styling for student counts with different color
                cell.innerHTML = `<span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">${cellValue}</span>`;
                break;
                
              default:
                cell.textContent = cellValue;
            }
            
            row.appendChild(cell);
          });
          
          tbody.appendChild(row);
        });
        
        table.appendChild(tbody);
        
        // Create button container
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'mt-4 flex gap-2';

        // Add save button
        const saveButton = document.createElement('button');
        saveButton.className = 'bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 flex items-center gap-2';
        saveButton.innerHTML = '<i data-lucide="save" class="w-4 h-4"></i> Save Report';
        saveButton.onclick = () => saveCurrentReport(data);
        buttonContainer.appendChild(saveButton);

        // Add download button for saved reports
        const currentReportId = document.querySelector('#saved-reports-list button.bg-blue-100')?.dataset.reportId;
        if (currentReportId) {
            const downloadButton = document.createElement('a');
            downloadButton.href = `api/download_report.php?report_id=${currentReportId}`;
            downloadButton.className = 'bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700 flex items-center gap-2';
            downloadButton.innerHTML = '<i data-lucide="download" class="w-4 h-4"></i> Download CSV';
            buttonContainer.appendChild(downloadButton);
        }
        
        // Update display
        const container = document.getElementById('report-data-table');
        container.innerHTML = '';
        container.appendChild(table);
        container.appendChild(buttonContainer);
        
        lucide.createIcons();
      }

      // Save current report
      function saveCurrentReport(data) {
        console.log('Raw data received:', data);
        
        const reportName = prompt('Enter a name for this report:');
        if (!reportName) return;
        
        const reportDescription = prompt('Enter a description (optional):') || '';
        
        // Get active template ID
        const activeTemplate = document.querySelector('#report-templates-list button.bg-blue-100');
        if (!activeTemplate || !activeTemplate.dataset.templateId) {
            showNotification('Error: No active template selected', 'error');
            return;
        }
        const templateId = parseInt(activeTemplate.dataset.templateId);
        
        // Ensure data is properly structured
        let processedData;
        try {
            // If data is already an array, use it directly
            if (Array.isArray(data)) {
                processedData = data;
            }
            // If data is a table element, convert it to array
            else if (data instanceof HTMLElement && data.tagName === 'TABLE') {
                processedData = Array.from(data.querySelectorAll('tr')).slice(1).map(row => {
                    return Array.from(row.querySelectorAll('td')).map(cell => cell.textContent.trim());
                });
            }
            // If data is an object with data property
            else if (data && typeof data === 'object' && data.data) {
                processedData = Array.isArray(data.data) ? data.data : [data.data];
            }
            // Default to empty array if none of the above
            else {
                processedData = [];
            }
            
            console.log('Processed data:', processedData);
        } catch (error) {
            console.error('Error processing data:', error);
            processedData = [];
        }
        
        // Prepare report data
        const reportData = {
            template: {
                name: document.getElementById('report-title')?.textContent?.replace(/.*? /, '') || 'Report',
                description: reportDescription,
                chart_type: currentChart ? currentChart.config.type : 'bar'
            },
            data: processedData
        };

        // Log request data for debugging
        const requestData = {
            action: 'save_report',
            template_id: templateId,
            name: reportName,
            description: reportDescription,
            report_data: reportData,
            parameters_used: {}
        };
        
        console.log('Sending request with data:', JSON.stringify(requestData, null, 2));

        fetch('api/reports_analytics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(async response => {
            const text = await response.text();
            console.log('Raw server response:', text);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}, response: ${text}`);
            }
            
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error parsing JSON response:', e);
                throw new Error(`Invalid JSON response: ${text}`);
            }
        })
        .then(result => {
            if (result.success) {
                showNotification(result.message || 'Report saved successfully!', 'success');
                fetchSavedReports(); // Refresh the saved reports list
            } else {
                showNotification('Error saving report: ' + (result.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error saving report:', error);
            let errorMessage = 'Error saving report. ';
            if (error.message) {
                errorMessage += error.message;
            } else if (error.error) {
                errorMessage += error.error;
            } else {
                errorMessage += 'Please try again.';
            }
            showNotification(errorMessage, 'error');
        });
      }

      // Activity Logs Functionality
      function loadActivityLogs() {
        const typeFilter = document.getElementById('activity-type-filter').value;
        const daysFilter = document.getElementById('activity-time-filter').value;
        
        // Show loading state
        document.getElementById('activity-logs-list').innerHTML = `
          <div class="flex items-center justify-center py-8">
            <i data-lucide="loader" class="w-5 h-5 animate-spin mr-2"></i>
            Loading activity logs...
          </div>
        `;
        
        // If user selects "Comment Activity", load detailed comment logs
        if (typeFilter === 'Comment Activity') {
          fetch(`api/reports_analytics.php?action=comment_activity_logs&days=${daysFilter}`)
            .then(response => response.json())
            .then(data => {
              const commentLogs = data.comment_activity_logs || [];
              
              const logsHtml = commentLogs.length ? commentLogs.map(log => `
                <div class="flex items-start gap-3 p-4 border rounded-lg hover:bg-gray-50">
                  <div class="mt-1">
                    ${getActivityIcon('Comment Activity')}
                  </div>
                  <div class="flex-1">
                    <div class="flex justify-between items-start">
                      <div>
                        <p class="font-medium">${log.activity_type}</p>
                        <p class="text-sm text-gray-600">${log.description}</p>
                        <div class="mt-1 text-xs text-gray-500">
                          <span class="font-medium">${log.chapter_title}</span> • <span>${log.student_name}</span>
                        </div>
                      </div>
                      <span class="text-xs text-gray-500">${formatDate(log.activity_date)}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1">
                      <span class="text-xs px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full">
                        ${log.details.action.replace('_', ' ')}
                      </span>
                      ${log.details.comment_length ? `
                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded-full">
                          ${log.details.comment_length} chars
                        </span>
                      ` : ''}
                      ${log.details.highlight_color ? `
                        <span class="text-xs px-2 py-1 rounded-full" style="background-color: ${log.details.highlight_color}20; color: ${log.details.highlight_color};">
                          ${log.details.highlight_color}
                        </span>
                      ` : ''}
                    </div>
                  </div>
                </div>
              `).join('') : `
                <div class="text-center py-8 text-gray-500">
                  <i data-lucide="message-square" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                  <p>No comment activity found</p>
                </div>
              `;
              
              document.getElementById('activity-logs-list').innerHTML = logsHtml;
              lucide.createIcons();
            })
            .catch(error => {
              console.error('Error loading comment activity logs:', error);
              document.getElementById('activity-logs-list').innerHTML = `
                <div class="text-center py-8 text-red-500">
                  <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-3"></i>
                  <p>Failed to load comment activity logs</p>
                </div>
              `;
              lucide.createIcons();
            });
        } else {
          // Load general activity logs
          fetch(`api/reports_analytics.php?action=recent_activity&days=${daysFilter}`)
            .then(response => response.json())
            .then(data => {
              let activities = data.recent_activity || [];
              
              // Filter by type if selected
              if (typeFilter) {
                activities = activities.filter(activity => activity.activity_type === typeFilter);
              }
              
              // Display activities
              const logsHtml = activities.length ? activities.map(activity => `
                <div class="flex items-start gap-3 p-4 border rounded-lg hover:bg-gray-50">
                  <div class="mt-1">
                    ${getActivityIcon(activity.activity_type)}
                  </div>
                  <div class="flex-1">
                    <div class="flex justify-between items-start">
                      <div>
                        <p class="font-medium">${activity.activity_type}</p>
                        <p class="text-sm text-gray-600">${activity.details}</p>
                      </div>
                      <span class="text-xs text-gray-500">${formatDate(activity.activity_date)}</span>
                    </div>
                    <div class="mt-1">
                      <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                        ${activity.activity_count} ${activity.activity_count === 1 ? 'activity' : 'activities'}
                      </span>
                    </div>
                  </div>
                </div>
              `).join('') : `
                <div class="text-center py-8 text-gray-500">
                  <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                  <p>No activity logs found</p>
                </div>
              `;
              
              document.getElementById('activity-logs-list').innerHTML = logsHtml;
              lucide.createIcons();
            })
            .catch(error => {
              console.error('Error loading activity logs:', error);
              document.getElementById('activity-logs-list').innerHTML = `
                <div class="text-center py-8 text-red-500">
                  <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-3"></i>
                  <p>Failed to load activity logs</p>
                </div>
              `;
              lucide.createIcons();
            });
        }
      }
      
      function getActivityIcon(type) {
        const iconMap = {
          'Chapter Submission': '<i data-lucide="file-plus" class="w-5 h-5 text-green-600"></i>',
          'Feedback Given': '<i data-lucide="message-circle" class="w-5 h-5 text-blue-600"></i>',
          'Document Review': '<i data-lucide="file-check" class="w-5 h-5 text-purple-600"></i>',
          'Comment Activity': '<i data-lucide="message-square" class="w-5 h-5 text-indigo-600"></i>',
          'Timeline Update': '<i data-lucide="clock" class="w-5 h-5 text-amber-600"></i>'
        };
        return iconMap[type] || '<i data-lucide="activity" class="w-5 h-5 text-gray-600"></i>';
      }
      
      function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { 
          month: 'short', 
          day: 'numeric',
          year: 'numeric'
        });
      }
      
      // Initialize activity logs when tab is clicked
      document.querySelector('[data-tab="activity-logs"]').addEventListener('click', loadActivityLogs);
      
      // Initialize document review when tab is clicked
      document.querySelector('[data-tab="document-review"]').addEventListener('click', loadAllStudentsForReview);
      
      // Add filter change handlers
      document.getElementById('activity-type-filter').addEventListener('change', loadActivityLogs);
      document.getElementById('activity-time-filter').addEventListener('change', loadActivityLogs);
      
      // Add refresh button functionality
      document.getElementById('refresh-document-list').addEventListener('click', loadAllStudentsForReview);
      
      // Load students initially if document review tab is active
      const currentTab = new URLSearchParams(window.location.search).get('tab');
      if (currentTab === 'document-review') {
        // Small delay to ensure DOM is ready
        setTimeout(loadAllStudentsForReview, 100);
      }
    });

    // Load all students for document review
    function loadAllStudentsForReview() {
      console.log("Loading all students for document review...");
      
      const loadingDiv = document.getElementById('loading-students');
      const studentsList = document.getElementById('students-list');
      const noStudentsDiv = document.getElementById('no-students');
      
      // Show loading state
      loadingDiv.classList.remove('hidden');
      studentsList.innerHTML = '';
      noStudentsDiv.classList.add('hidden');
      
      fetch('api/document_review.php?action=get_all_students')
        .then(response => {
          if (!response.ok) {
            throw new Error('Failed to load students');
          }
          return response.json();
        })
        .then(data => {
          console.log("Students data:", data);
          
          // Hide loading state
          loadingDiv.classList.add('hidden');
          
          if (data.success && data.students && data.students.length > 0) {
            displayStudentsForReview(data.students);
          } else {
            // Show no students message
            noStudentsDiv.classList.remove('hidden');
          }
        })
        .catch(error => {
          console.error('Error loading students:', error);
          loadingDiv.classList.add('hidden');
          
          studentsList.innerHTML = `
            <div class="text-center py-8 text-gray-500">
              <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
              <p class="text-sm">Failed to load students</p>
              <p class="text-xs text-red-500 mt-2">${error.message}</p>
            </div>
          `;
          lucide.createIcons();
        });
    }

    // Display students and their chapters
    function displayStudentsForReview(students) {
      const studentsList = document.getElementById('students-list');
      
      studentsList.innerHTML = students.map(student => {
        let chaptersHtml = '';
        let statusBadge = '';
        
        // Create status badge based on student progress
        if (student.is_placeholder) {
          statusBadge = '<span class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded-full">Not started</span>';
        } else if (student.chapters && student.chapters.length > 0) {
          const submittedCount = student.chapters.filter(ch => ch.status === 'submitted' || ch.status === 'approved').length;
          statusBadge = `<span class="text-xs px-2 py-1 bg-blue-100 text-blue-600 rounded-full">${submittedCount}/${student.chapters.length} chapters submitted</span>`;
        } else {
          statusBadge = '<span class="text-xs px-2 py-1 bg-orange-100 text-orange-600 rounded-full">Thesis created, no chapters</span>';
        }
        
        if (student.chapters && student.chapters.length > 0 && !student.is_placeholder) {
          chaptersHtml = student.chapters.map(chapter => {
            const hasFiles = chapter.has_files;
            const fileStatus = hasFiles ? 'has-files' : 'no-files';
            const submittedClass = chapter.status === 'submitted' ? 'border-l-4 border-yellow-400' : '';
            
            return `
              <button 
                class="w-full text-left px-2 py-1 text-sm rounded hover:bg-blue-50 chapter-item ${fileStatus} ${submittedClass}"
                data-chapter-id="${chapter.id}"
                data-chapter-title="${chapter.title}"
                data-has-files="${hasFiles ? 'true' : 'false'}">
                <div class="flex justify-between items-center">
                  <span>
                    Ch. ${chapter.chapter_number}: ${chapter.title}
                    ${hasFiles ? '<i data-lucide="file-text" class="inline-block w-4 h-4 ml-1 text-blue-500"></i>' : ''}
                  </span>
                  <span class="text-xs px-1 py-0.5 rounded
                    ${chapter.status === 'submitted' ? 'bg-yellow-100 text-yellow-800' : 
                      (chapter.status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')}">
                    ${chapter.status.charAt(0).toUpperCase() + chapter.status.slice(1)}
                  </span>
                </div>
                ${hasFiles ? `
                  <div class="text-xs text-gray-500 mt-1">
                    ${chapter.files.length} file(s) uploaded
                    <span class="text-xs text-gray-400">· ${new Date(chapter.files[0].uploaded_at).toLocaleDateString()}</span>
                  </div>
                ` : ''}
              </button>
            `;
          }).join('');
        } else {
          const emptyMessage = student.is_placeholder 
            ? 'Student has not started thesis work yet. They need to contact you to begin their thesis project.'
            : 'No chapters created yet';
          
          chaptersHtml = `
            <div class="text-center py-4 text-gray-500 bg-gray-50 rounded-md">
              <i data-lucide="${student.is_placeholder ? 'user-x' : 'file-x'}" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
              <p class="text-xs">${emptyMessage}</p>
              ${student.is_placeholder ? '<p class="text-xs text-gray-400 mt-1">Encourage them to set up their thesis topic and begin writing.</p>' : ''}
            </div>
          `;
        }
        
        return `
          <div class="border rounded-lg p-3 ${student.is_placeholder ? 'bg-gray-50' : 'bg-white'}">
            <div class="flex justify-between items-start mb-2">
              <h4 class="font-medium text-sm">${student.full_name}</h4>
              ${statusBadge}
            </div>
            <p class="text-xs text-gray-600 mb-2 font-medium">${student.thesis_title}</p>
            <div class="text-xs text-gray-400 mb-3">
              Student ID: ${student.student_id} | ${student.program}
            </div>
            <div class="space-y-1">
              ${chaptersHtml}
            </div>
          </div>
        `;
      }).join('');
      
      // Refresh icons
      lucide.createIcons();
      
      // Re-attach event listeners to chapter items
      document.querySelectorAll('.chapter-item').forEach(item => {
        item.addEventListener('click', function() {
          // Remove active class from all chapters
          document.querySelectorAll('.chapter-item').forEach(ch => ch.classList.remove('bg-blue-100'));
          
          // Add active class to clicked chapter
          this.classList.add('bg-blue-100');
          
          // Load the chapter
          const chapterId = this.dataset.chapterId;
          const chapterTitle = this.dataset.chapterTitle;
          loadChapter(chapterId, chapterTitle);
        });
      });
    }

    // Edit Student Modal Functions
    function openEditStudentModal(student) {
      console.log('Student data received:', student);
      document.getElementById('edit_student_id').value = student.id;
      document.getElementById('edit_student_name').value = student.name;
      document.getElementById('edit_program').value = student.program;
      document.getElementById('edit_thesis_title').value = student.thesis_title;
      
      document.getElementById('editStudentModal').classList.remove('hidden');
      lucide.createIcons();
    }

    function closeEditStudentModal() {
      document.getElementById('editStudentModal').classList.add('hidden');
    }

          function handleEditStudent(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            // Log form data for debugging
            console.log('Form data being sent:');
            for (const pair of formData.entries()) {
              console.log(pair[0] + ': ' + pair[1]);
            }
            
            fetch('api/edit_student.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              console.log('Server response:', data);
              if (data.success) {
                alert(data.message || 'Student updated successfully');
                closeEditStudentModal();
                // Reload the page to show updated information
                window.location.reload();
              } else {
                alert(data.message || 'Error updating student');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              alert('Error updating student');
            });
    }

    // Enhanced Document Review UI Functionality
    document.addEventListener('DOMContentLoaded', function() {
      // Sidebar toggle functionality
      const sidebarToggle = document.getElementById('toggle-sidebar');
      const sidebar = document.getElementById('document-sidebar');
      let sidebarCollapsed = false;

      if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
          sidebarCollapsed = !sidebarCollapsed;
          if (sidebarCollapsed) {
            // On mobile, hide sidebar completely; on desktop, collapse to icon only
            if (window.innerWidth <= 768) {
              sidebar.style.display = 'none';
            } else {
              sidebar.style.width = '60px';
              sidebar.style.minWidth = '60px';
              const content = sidebar.querySelector('.flex-1');
              const quickView = sidebar.querySelector('.border-t');
              if (content) content.style.display = 'none';
              if (quickView) quickView.style.display = 'none';
            }
          } else {
            sidebar.style.display = 'flex';
            sidebar.style.width = '320px';
            sidebar.style.minWidth = '320px';
            const content = sidebar.querySelector('.flex-1');
            const quickView = sidebar.querySelector('.border-t');
            if (content) content.style.display = 'block';
            if (quickView) quickView.style.display = 'block';
          }
        });
      }

      // Tabbed Panel System
      const tabbedPanel = document.getElementById('tabbed-panel');
      const analysisTab = document.getElementById('analysis-tab');
      const commentsTab = document.getElementById('comments-tab');
      const analysisContent = document.getElementById('analysis-content');
      const commentsContent = document.getElementById('comments-content');
      const closePanel = document.getElementById('close-panel');
      const triggerAnalysis = document.getElementById('trigger-analysis');
      const triggerComments = document.getElementById('trigger-comments');
      const toggleAnalysis = document.getElementById('toggle-analysis');
      const toggleComments = document.getElementById('toggle-comments');
      const analysisChevron = document.getElementById('analysis-chevron');
      const commentsChevron = document.getElementById('comments-chevron');

      let currentActiveTab = null;

      function openPanel(tabType) {
        const panelContainer = document.getElementById('right-panels-container');
        
        // Show the panel container and backdrop
        panelContainer.style.display = 'block';
        setTimeout(() => {
          tabbedPanel.classList.remove('translate-x-full');
        }, 10);
        
        // Reset all tabs
        analysisTab.classList.remove('border-blue-500', 'text-blue-600', 'bg-blue-50');
        commentsTab.classList.remove('border-green-500', 'text-green-600', 'bg-green-50');
        analysisContent.classList.add('hidden');
        commentsContent.classList.add('hidden');
        
        // Activate the selected tab
        if (tabType === 'analysis') {
          analysisTab.classList.add('border-blue-500', 'text-blue-600', 'bg-blue-50');
          analysisContent.classList.remove('hidden');
          currentActiveTab = 'analysis';
          if (analysisChevron) analysisChevron.classList.add('rotate-90');
        } else if (tabType === 'comments') {
          commentsTab.classList.add('border-green-500', 'text-green-600', 'bg-green-50');
          commentsContent.classList.remove('hidden');
          currentActiveTab = 'comments';
          if (commentsChevron) commentsChevron.classList.add('rotate-90');
        }
        
        // Prevent body scroll on mobile
        document.body.style.overflow = 'hidden';
      }

      function closeTabPanel() {
        const panelContainer = document.getElementById('right-panels-container');
        
        tabbedPanel.classList.add('translate-x-full');
        
        // Hide the panel container after animation
        setTimeout(() => {
          panelContainer.style.display = 'none';
        }, 300);
        
        currentActiveTab = null;
        if (analysisChevron) analysisChevron.classList.remove('rotate-90');
        if (commentsChevron) commentsChevron.classList.remove('rotate-90');
        
        // Restore body scroll
        document.body.style.overflow = '';
      }

      // Floating button triggers
      if (triggerAnalysis) {
        triggerAnalysis.addEventListener('click', () => openPanel('analysis'));
      }

      if (triggerComments) {
        triggerComments.addEventListener('click', () => openPanel('comments'));
      }

      // Sidebar button triggers (maintain compatibility)
      if (toggleAnalysis) {
        toggleAnalysis.addEventListener('click', () => {
          if (currentActiveTab === 'analysis') {
            closeTabPanel();
          } else {
            openPanel('analysis');
          }
        });
      }

      if (toggleComments) {
        toggleComments.addEventListener('click', () => {
          if (currentActiveTab === 'comments') {
            closeTabPanel();
          } else {
            openPanel('comments');
          }
        });
      }

      // Tab clicks
      if (analysisTab) {
        analysisTab.addEventListener('click', () => openPanel('analysis'));
      }

      if (commentsTab) {
        commentsTab.addEventListener('click', () => openPanel('comments'));
      }

      if (closePanel) {
        closePanel.addEventListener('click', closeTabPanel);
      }

      // Close panel when clicking backdrop
      const panelBackdrop = document.getElementById('panel-backdrop');
      if (panelBackdrop) {
        panelBackdrop.addEventListener('click', closeTabPanel);
      }

      // Mobile sidebar toggle
      const mobileSidebarToggle = document.getElementById('mobile-sidebar-toggle');
      if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', function() {
          sidebar.classList.toggle('sidebar-open');
          document.body.classList.toggle('sidebar-mobile-open');
        });
      }

      // Close mobile sidebar when clicking outside
      document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
          if (!sidebar.contains(e.target) && !mobileSidebarToggle.contains(e.target)) {
            sidebar.classList.remove('sidebar-open');
            document.body.classList.remove('sidebar-mobile-open');
          }
        }
      });

      // Fullscreen functionality
      const fullscreenBtn = document.getElementById('fullscreen-btn');
      const closeFullscreen = document.getElementById('close-fullscreen');

      if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function() {
          const documentViewer = document.getElementById('adviser-word-document-viewer');
          const documentTitle = document.getElementById('document-title').textContent;
          
          if (documentViewer && documentViewer.innerHTML.trim() !== '') {
            // Create fullscreen view
            openFullscreenView(documentViewer.innerHTML, documentTitle);
          }
        });
      }

      // Color picker functionality
      const colorPickerBtn = document.getElementById('color-picker-btn');
      const colorPicker = document.getElementById('color-picker');
      const currentColor = document.getElementById('current-color');

      if (colorPickerBtn) {
        colorPickerBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          colorPicker.classList.toggle('hidden');
        });
      }

      // Close color picker when clicking outside
      document.addEventListener('click', function(e) {
        if (colorPicker && !colorPicker.contains(e.target) && !colorPickerBtn.contains(e.target)) {
          colorPicker.classList.add('hidden');
        }
      });

      // Color selection
      const colorOptions = document.querySelectorAll('.color-option');
      colorOptions.forEach(option => {
        option.addEventListener('click', function() {
          const color = this.dataset.color;
          currentColor.style.backgroundColor = color;
          colorPicker.classList.add('hidden');
          
          // Update highlight color globally
          window.currentHighlightColor = color;
        });
      });

      // Initialize highlight color
      window.currentHighlightColor = '#ffeb3b';
    });

    // Fullscreen view function
    // Add a global variable to store the fullscreen WordViewer instance
    let fullscreenWordViewer = null;
    let fullscreenLoadedFileId = null;

    function openFullscreenView(_, title) {
      let fullscreenModal = document.getElementById('document-fullscreen-modal');
      if (!fullscreenModal) {
        fullscreenModal = document.createElement('div');
        fullscreenModal.id = 'document-fullscreen-modal';
        fullscreenModal.className = 'document-fullscreen-modal';
        fullscreenModal.innerHTML = `
          <div class="fullscreen-header">
            <div class="fullscreen-title" id="fullscreen-document-title">${title}</div>
            <div class="flex items-center space-x-4">
              <div class="flex items-center space-x-2">
                <button id="fullscreen-highlight-btn" class="toolbar-action-btn">
                  <i data-lucide="highlighter" class="w-4 h-4 mr-2"></i>Highlight
                </button>
                <button id="fullscreen-comment-btn" class="toolbar-action-btn">
                  <i data-lucide="message-circle" class="w-4 h-4 mr-2"></i>Comment
                </button>
                <a id="fullscreen-download-btn" href="#" class="toolbar-action-btn" target="_blank">
                  <i data-lucide="download" class="w-4 h-4 mr-2"></i>Download
                </a>
              </div>
              <button id="close-fullscreen" class="fullscreen-close">
                <i data-lucide="x" class="w-4 h-4 mr-2"></i>Close
              </button>
            </div>
          </div>
          <div class="fullscreen-content">
            <div class="fullscreen-document" id="fullscreen-document-content"></div>
          </div>
        `;
        document.body.appendChild(fullscreenModal);
        lucide.createIcons();

        // Add close functionality
        const closeBtn = fullscreenModal.querySelector('#close-fullscreen');
        closeBtn.addEventListener('click', function() {
          fullscreenModal.classList.remove('active');
          document.body.style.overflow = 'auto';
        });

        // Copy download link
        const downloadBtn = document.getElementById('download-document-btn');
        const fullscreenDownloadBtn = fullscreenModal.querySelector('#fullscreen-download-btn');
        if (downloadBtn && fullscreenDownloadBtn) {
          fullscreenDownloadBtn.href = downloadBtn.href;
        }
      } else {
        fullscreenModal.querySelector('#fullscreen-document-title').textContent = title;
        fullscreenModal.querySelector('#fullscreen-document-content').innerHTML = '';
        if (fullscreenWordViewer && typeof fullscreenWordViewer.destroy === 'function') {
          fullscreenWordViewer.destroy();
        }
        fullscreenWordViewer = null;
        fullscreenLoadedFileId = null;
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

      // Always create a new WordViewer for fullscreen
      const fullscreenContent = fullscreenModal.querySelector('#fullscreen-document-content');
      if (window.currentFileId) {
        fullscreenContent.innerHTML = '';
        fullscreenWordViewer = new WordViewer('fullscreen-document-content', {
          showComments: true,
          showToolbar: false,
          allowZoom: true
        });
        fullscreenWordViewer.loadDocument(window.currentFileId);
        fullscreenLoadedFileId = window.currentFileId;
      } else {
        fullscreenContent.innerHTML = '<div class="text-center py-8 text-gray-500">No document loaded.</div>';
        fullscreenLoadedFileId = null;
      }
    }
  </script>

  <!-- Modern UI Framework -->
  <script src="assets/js/modern-ui.js"></script>

  <!-- Document Fullscreen Modal (will be created dynamically) -->

</body>
</html> 