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
    
    /* Enhanced Custom Scrollbar for Analysis and Comments Panels */
    .custom-scrollbar {
      scrollbar-width: thin;
      scrollbar-color: #9333ea #f3f4f6;
    }
    
    .custom-scrollbar::-webkit-scrollbar {
      width: 12px;
      height: 12px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
      background: #f8fafc;
      border-radius: 6px;
      margin: 4px;
      border: 1px solid #e2e8f0;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #9333ea 0%, #7c3aed 50%, #6d28d9 100%);
      border-radius: 6px;
      border: 2px solid #f8fafc;
      transition: all 0.3s ease;
      min-height: 30px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #5b21b6 100%);
      box-shadow: 0 4px 12px rgba(147, 51, 234, 0.3);
      transform: scale(1.1);
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:active {
      background: linear-gradient(135deg, #6d28d9 0%, #5b21b6 50%, #4c1d95 100%);
      transform: scale(0.95);
    }
    
    .custom-scrollbar::-webkit-scrollbar-corner {
      background: #f8fafc;
    }
    
    /* Force scroll container height */
    .scroll-container {
      max-height: 400px;
      min-height: 200px;
    }
    
    /* Always show scrollbars for better UX */
    .custom-scrollbar::-webkit-scrollbar {
      -webkit-appearance: none;
    }
    
    /* Firefox scrollbar enhancement */
    .custom-scrollbar {
      scrollbar-width: auto;
      scrollbar-color: #9333ea #f3f4f6;
    }
    
    /* Force scrollbars to always be visible */
    .custom-scrollbar {
      overflow-y: scroll !important;
    }
    
    /* Make sure containers have proper scroll behavior */
    .scroll-container {
      scroll-behavior: smooth;
    }
    
    /* Tab-specific scrollbar styling */
    .settings-scroll-container,
    .students-scroll-container,
    .theses-scroll-container,
    .reports-scroll-container,
    .profile-scroll-container,
    .feedback-scroll-container,
    .activity-logs-scroll-container {
      scroll-behavior: smooth;
    }
    
    /* Ensure scrollbar doesn't interfere with sidebar */
    .settings-scroll-container::-webkit-scrollbar,
    .students-scroll-container::-webkit-scrollbar,
    .theses-scroll-container::-webkit-scrollbar,
    .reports-scroll-container::-webkit-scrollbar,
    .profile-scroll-container::-webkit-scrollbar,
    .feedback-scroll-container::-webkit-scrollbar,
    .activity-logs-scroll-container::-webkit-scrollbar {
      width: 12px;
      background: transparent;
    }
    
    .settings-scroll-container::-webkit-scrollbar-track,
    .students-scroll-container::-webkit-scrollbar-track,
    .theses-scroll-container::-webkit-scrollbar-track,
    .reports-scroll-container::-webkit-scrollbar-track,
    .profile-scroll-container::-webkit-scrollbar-track,
    .feedback-scroll-container::-webkit-scrollbar-track,
    .activity-logs-scroll-container::-webkit-scrollbar-track {
      background: #f8fafc;
      border-radius: 6px;
      margin: 4px 0;
      border: 1px solid #e2e8f0;
    }
    
    .settings-scroll-container::-webkit-scrollbar-thumb,
    .students-scroll-container::-webkit-scrollbar-thumb,
    .theses-scroll-container::-webkit-scrollbar-thumb,
    .reports-scroll-container::-webkit-scrollbar-thumb,
    .profile-scroll-container::-webkit-scrollbar-thumb,
    .feedback-scroll-container::-webkit-scrollbar-thumb,
    .activity-logs-scroll-container::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #9333ea 0%, #7c3aed 50%, #6d28d9 100%);
      border-radius: 6px;
      border: 2px solid #f8fafc;
      transition: all 0.3s ease;
      min-height: 30px;
    }
    
    .settings-scroll-container::-webkit-scrollbar-thumb:hover,
    .students-scroll-container::-webkit-scrollbar-thumb:hover,
    .theses-scroll-container::-webkit-scrollbar-thumb:hover,
    .reports-scroll-container::-webkit-scrollbar-thumb:hover,
    .profile-scroll-container::-webkit-scrollbar-thumb:hover,
    .feedback-scroll-container::-webkit-scrollbar-thumb:hover,
    .activity-logs-scroll-container::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 50%, #5b21b6 100%);
      box-shadow: 0 4px 12px rgba(147, 51, 234, 0.3);
      transform: scale(1.05);
    }
    
    .settings-scroll-container::-webkit-scrollbar-thumb:active,
    .students-scroll-container::-webkit-scrollbar-thumb:active,
    .theses-scroll-container::-webkit-scrollbar-thumb:active,
    .reports-scroll-container::-webkit-scrollbar-thumb:active,
    .profile-scroll-container::-webkit-scrollbar-thumb:active,
    .feedback-scroll-container::-webkit-scrollbar-thumb:active,
    .activity-logs-scroll-container::-webkit-scrollbar-thumb:active {
      background: linear-gradient(135deg, #6d28d9 0%, #5b21b6 50%, #4c1d95 100%);
      transform: scale(0.95);
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
     
     /* Enhanced Empty State Animations */
     @keyframes float {
       0%, 100% { transform: translateY(0px); }
       50% { transform: translateY(-10px); }
     }
     
     @keyframes pulse-ring {
       0% { transform: scale(0.33); opacity: 1; }
       80%, 100% { transform: scale(1.33); opacity: 0; }
     }
     
     @keyframes pulse-dot {
       0% { transform: scale(0.8); }
       50% { transform: scale(1.0); }
       100% { transform: scale(0.8); }
     }
     
     .empty-state-icon {
       animation: float 3s ease-in-out infinite;
     }
     
     .feature-card-hover {
       transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
     }
     
     .feature-card-hover:hover {
       transform: translateY(-8px) scale(1.02);
       box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
     }
     
     .quick-action-btn {
       transition: all 0.2s ease;
       position: relative;
       overflow: hidden;
     }
     
     .quick-action-btn:before {
       content: '';
       position: absolute;
       top: 0;
       left: -100%;
       width: 100%;
       height: 100%;
       background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
       transition: left 0.5s;
     }
     
     .quick-action-btn:hover:before {
       left: 100%;
     }
     
     .status-indicator-pulse::before {
       content: '';
       position: absolute;
       top: 50%;
       left: 50%;
       width: 100%;
       height: 100%;
       border-radius: 50%;
       background-color: rgba(34, 197, 94, 0.3);
       transform: translate(-50%, -50%);
       animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
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
    
    /* Activity Logs Mode - Remove top padding */
    main.activity-logs-mode {
      padding-top: 0.5rem !important;
    }
    
    @media (min-width: 768px) {
      main.activity-logs-mode {
        padding-top: 1rem !important;
      }
    }
    
    .fullscreen-highlight {
      background-color: #ffeb3b !important;
      padding: 2px 4px !important;
      border-radius: 3px !important;
      position: relative !important;
      cursor: pointer !important;
      display: inline !important;
      z-index: 1 !important;
      transition: all 0.2s ease !important;
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
        <a href="#" data-tab="profile" class="nav-link sidebar-item">
          <i data-lucide="user" class="w-5 h-5"></i> Profile
        </a>
        <a href="#" data-tab="settings" class="nav-link sidebar-item">
          <i data-lucide="settings" class="w-5 h-5"></i> Settings
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
      <div class="flex justify-between items-center mb-8" id="dashboard-header">
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
              <a href="#" data-tab="profile" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 nav-link">
                <i data-lucide="user" class="w-4 h-4"></i> Profile
              </a>
              <a href="#" data-tab="settings" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 nav-link">
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
        <div class="students-scroll-container max-h-[80vh] overflow-y-auto custom-scrollbar pr-4">
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
        </div> <!-- End of students-scroll-container -->
      </div>

      <!-- Theses Tab Content -->
      <div id="theses-content" class="tab-content hidden">
        <div class="theses-scroll-container max-h-[80vh] overflow-y-auto custom-scrollbar pr-4">
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
        </div> <!-- End of theses-scroll-container -->
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
                    <button id="remove-highlights-btn" class="px-3 py-2 bg-red-100 text-red-800 rounded-lg text-sm hover:bg-red-200 transition-colors flex items-center" title="Remove All Highlights">
                      <i data-lucide="eraser" class="w-4 h-4 mr-1"></i><span class="hidden sm:inline">Clear All</span>
                    </button>
                    <button id="reload-highlights-btn" class="px-3 py-2 bg-green-100 text-green-800 rounded-lg text-sm hover:bg-green-200 transition-colors flex items-center" title="Reload Highlights">
                      <i data-lucide="refresh-cw" class="w-4 h-4 mr-1"></i><span class="hidden sm:inline">Reload</span>
                    </button>
                    <button id="quick-fix-highlights-btn" class="px-3 py-2 bg-blue-100 text-blue-800 rounded-lg text-sm hover:bg-blue-200 transition-colors flex items-center" title="Quick Fix Highlights">
                      <i data-lucide="zap" class="w-4 h-4 mr-1"></i><span class="hidden sm:inline">Quick Fix</span>
                    </button>
                    <button id="debug-text-matching-btn" class="px-3 py-2 bg-purple-100 text-purple-800 rounded-lg text-sm hover:bg-purple-200 transition-colors flex items-center" title="Debug Text Matching">
                      <i data-lucide="search" class="w-4 h-4 mr-1"></i><span class="hidden sm:inline">Debug</span>
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
                <!-- Enhanced Empty State UI -->
                <div class="flex items-center justify-center h-full bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
                  <div class="text-center px-6 py-6 max-w-4xl mx-auto">
                    <!-- Animated Icon -->
                    <div class="relative mb-6">
                      <div class="empty-state-icon w-24 h-24 mx-auto bg-gradient-to-br from-blue-100 to-indigo-200 rounded-full flex items-center justify-center shadow-lg">
                        <i data-lucide="file-text" class="w-12 h-12 text-blue-600"></i>
                      </div>
                      <div class="absolute -top-1 -right-1 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center shadow-md animate-bounce">
                        <i data-lucide="plus" class="w-3 h-3 text-white"></i>
                      </div>
                    </div>

                    <!-- Main Content -->
                    <h2 class="text-xl font-bold text-gray-800 mb-3">Document Review Center</h2>
                    <p class="text-gray-600 mb-6 text-base leading-relaxed">
                      Welcome to your thesis review workspace! Select a student and chapter to begin providing feedback with our advanced review tools.
                    </p>

                    <!-- Feature Cards -->
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                      <!-- Review Tools Card -->
                      <div class="feature-card-hover bg-white rounded-xl p-4 shadow-md">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mb-3 mx-auto">
                          <i data-lucide="highlighter" class="w-5 h-5 text-yellow-600"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2 text-sm">Advanced Review Tools</h3>
                        <p class="text-xs text-gray-600">Highlight text, add comments, and provide detailed feedback with our intuitive tools.</p>
                      </div>

                      <!-- Real-time Collaboration Card -->
                      <div class="feature-card-hover bg-white rounded-xl p-4 shadow-md">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mb-3 mx-auto">
                          <i data-lucide="users" class="w-5 h-5 text-green-600"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2 text-sm">Student Interaction</h3>
                        <p class="text-xs text-gray-600">Communicate directly with students through comments and feedback on their work.</p>
                      </div>

                      <!-- Progress Tracking Card -->
                      <div class="feature-card-hover bg-white rounded-xl p-4 shadow-md">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-3 mx-auto">
                          <i data-lucide="trending-up" class="w-5 h-5 text-purple-600"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2 text-sm">Progress Tracking</h3>
                        <p class="text-xs text-gray-600">Monitor student progress and track revision history with comprehensive analytics.</p>
                      </div>

                      <!-- Format Analysis Card -->
                      <div class="feature-card-hover bg-white rounded-xl p-4 shadow-md">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-3 mx-auto">
                          <i data-lucide="check-circle" class="w-5 h-5 text-blue-600"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-2 text-sm">Format Analysis</h3>
                        <p class="text-xs text-gray-600">Automatically check document formatting against thesis standards and guidelines.</p>
                      </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl p-4 shadow-md mb-4">
                      <h3 class="font-semibold text-gray-800 mb-3 flex items-center justify-center text-sm">
                        <i data-lucide="zap" class="w-4 h-4 mr-2 text-yellow-500"></i>
                        Quick Actions
                      </h3>
                      <div class="flex flex-wrap justify-center gap-3">
                        <button onclick="switchToTab('students')" class="quick-action-btn px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                          <i data-lucide="users" class="w-4 h-4 mr-1"></i>
                          View Students
                        </button>
                        <button onclick="switchToTab('feedback')" class="quick-action-btn px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                          <i data-lucide="message-circle" class="w-4 h-4 mr-1"></i>
                          Manage Feedback
                        </button>
                        <button onclick="switchToTab('reports')" class="quick-action-btn px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium">
                          <i data-lucide="bar-chart" class="w-4 h-4 mr-1"></i>
                          View Reports
                        </button>
                      </div>
                    </div>

                    <!-- Help Tips -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
                      <h3 class="font-semibold text-blue-800 mb-2 flex items-center justify-center text-sm">
                        <i data-lucide="lightbulb" class="w-4 h-4 mr-2"></i>
                        Getting Started Tips
                      </h3>
                      <div class="text-left space-y-1 text-xs text-blue-700">
                        <div class="flex items-start">
                          <span class="font-medium mr-2">1.</span>
                          <span>Navigate to the sidebar and select a student from your assigned list</span>
                        </div>
                        <div class="flex items-start">
                          <span class="font-medium mr-2">2.</span>
                          <span>Choose a chapter that has been submitted for review</span>
                        </div>
                        <div class="flex items-start">
                          <span class="font-medium mr-2">3.</span>
                          <span>Use the highlight tool to mark important sections and add comments</span>
                        </div>
                        <div class="flex items-start">
                          <span class="font-medium mr-2">4.</span>
                          <span>Access format analysis and other tools from the right panel</span>
                        </div>
                      </div>
                    </div>

                    <!-- Status Indicator -->
                    <div class="mt-4 text-center">
                      <div class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium relative">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse status-indicator-pulse relative"></div>
                        System Ready
                      </div>
                    </div>
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
                  <div class="flex-1 overflow-y-auto p-4 custom-scrollbar scroll-container">
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
                  <div class="flex-1 overflow-y-auto p-4 custom-scrollbar scroll-container">
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
        <div class="feedback-scroll-container max-h-[80vh] overflow-y-auto custom-scrollbar pr-4">
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
        </div> <!-- End of feedback-scroll-container -->
      </div>

      <div id="timeline-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Timeline Management</h3>
          <p class="text-gray-500">Timeline management features coming soon!</p>
        </div>
      </div>

      <!-- Activity Logs Tab Content -->
      <div id="activity-logs-content" class="tab-content hidden">
        <div class="activity-logs-scroll-container max-h-[80vh] overflow-y-auto custom-scrollbar pr-4">
          <!-- Activity Logs Main Section -->
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
                <option value="all">All Time</option>
              </select>
              <select id="activity-sort-filter" class="text-sm border rounded-md px-3 py-2">
                <option value="created_at:DESC">Newest First</option>
                <option value="created_at:ASC">Oldest First</option>
                <option value="event_type:ASC">Type (A-Z)</option>
                <option value="event_type:DESC">Type (Z-A)</option>
              </select>
              <button id="clear-logs-btn" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 transition-colors">
                <i data-lucide="trash-2" class="w-4 h-4 inline mr-1"></i>
                Clear Logs
              </button>
              <button id="view-archive-btn" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 transition-colors">
                <i data-lucide="archive" class="w-4 h-4 inline mr-1"></i>
                View Archive
              </button>
            </div>
          </div>
          
          <div id="activity-logs-list" class="space-y-4">
            <div class="flex items-center justify-center py-8">
              <i data-lucide="loader" class="w-5 h-5 animate-spin mr-2"></i>
              Loading activity logs...
            </div>
          </div>

          <!-- Activity Logs Pagination -->
          <div id="activity-logs-pagination" class="flex justify-center items-center mt-6 space-x-2 hidden">
            <button id="activity-prev-page" class="px-3 py-1 bg-gray-300 text-gray-700 rounded disabled:opacity-50 hover:bg-gray-400 transition-colors">
              <i data-lucide="chevron-left" class="w-4 h-4 inline mr-1"></i>
              Previous
            </button>
            <span id="activity-page-info" class="text-sm text-gray-600 mx-4">Page 1 of 1</span>
            <button id="activity-next-page" class="px-3 py-1 bg-gray-300 text-gray-700 rounded disabled:opacity-50 hover:bg-gray-400 transition-colors">
              Next
              <i data-lucide="chevron-right" class="w-4 h-4 inline ml-1"></i>
            </button>
          </div>

          <!-- Pagination Settings -->
          <div class="flex justify-between items-center mt-4 text-sm text-gray-600">
            <div class="flex items-center gap-2">
              <span>Show:</span>
              <select id="activity-logs-per-page" class="border rounded px-2 py-1 text-sm">
                <option value="5">5 per page</option>
                <option value="10" selected>10 per page</option>
                <option value="20">20 per page</option>
                <option value="50">50 per page</option>
              </select>
            </div>
            <div id="activity-logs-total-info" class="text-xs text-gray-500">
              <!-- Total logs info will be displayed here -->
            </div>
          </div>
        </div>

        <!-- Archive Section (Hidden by default) -->
        <div id="archive-section" class="bg-white rounded-lg shadow p-6 hidden">
          <div class="flex justify-between items-center mb-6">
            <div>
              <h3 class="text-lg font-semibold">Archived Activity Logs</h3>
              <p class="text-sm text-gray-600">Manage your archived activity logs</p>
            </div>
            <div class="flex gap-2">
              <input type="date" id="archive-date-from" class="text-sm border rounded-md px-3 py-2" placeholder="From date">
              <input type="date" id="archive-date-to" class="text-sm border rounded-md px-3 py-2" placeholder="To date">
              <select id="archive-type-filter" class="text-sm border rounded-md px-3 py-2">
                <option value="">All Types</option>
                <option value="comment_activity">Comments</option>
                <option value="highlight_activity">Highlights</option>
                <option value="submission_activity">Submissions</option>
              </select>
              <select id="archive-sort-filter" class="text-sm border rounded-md px-3 py-2">
                <option value="archived_at:DESC">Recently Archived</option>
                <option value="archived_at:ASC">Oldest Archived</option>
                <option value="original_created_at:DESC">Original Date (New)</option>
                <option value="original_created_at:ASC">Original Date (Old)</option>
              </select>
              <button id="export-archive-btn" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 transition-colors">
                <i data-lucide="download" class="w-4 h-4 inline mr-1"></i>
                Export
              </button>
              <button id="back-to-logs-btn" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4 inline mr-1"></i>
                Back to Logs
              </button>
            </div>
          </div>

          <!-- Archive Statistics -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg text-center">
              <div class="text-2xl font-bold text-blue-600" id="archive-total-count">0</div>
              <div class="text-sm text-gray-600">Total Archived</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg text-center">
              <div class="text-2xl font-bold text-green-600" id="archive-this-month">0</div>
              <div class="text-sm text-gray-600">This Month</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg text-center">
              <div class="text-2xl font-bold text-purple-600" id="archive-most-type">-</div>
              <div class="text-sm text-gray-600">Most Common Type</div>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg text-center">
              <div class="text-2xl font-bold text-orange-600" id="archive-oldest-date">-</div>
              <div class="text-sm text-gray-600">Oldest Archive</div>
            </div>
          </div>
          
          <div id="archived-logs-list" class="space-y-4">
            <div class="flex items-center justify-center py-8">
              <i data-lucide="loader" class="w-5 h-5 animate-spin mr-2"></i>
              Loading archived logs...
            </div>
          </div>

          <!-- Pagination -->
          <div id="archive-pagination" class="flex justify-center items-center mt-6 space-x-2 hidden">
            <button id="archive-prev-page" class="px-3 py-1 bg-gray-300 text-gray-700 rounded disabled:opacity-50">Previous</button>
            <span id="archive-page-info" class="text-sm text-gray-600">Page 1 of 1</span>
            <button id="archive-next-page" class="px-3 py-1 bg-gray-300 text-gray-700 rounded disabled:opacity-50">Next</button>
          </div>
        </div>
      </div>

      <!-- Clear Logs Modal -->
      <div id="clear-logs-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
          <h3 class="text-lg font-semibold mb-4">Clear Activity Logs</h3>
          <p class="text-gray-600 mb-4">Choose what logs to clear. Cleared logs will be moved to the archive.</p>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-2">Clear logs older than:</label>
              <select id="clear-days-select" class="w-full border rounded-md px-3 py-2">
                <option value="">Select time period</option>
                <option value="7">7 days</option>
                <option value="30">30 days</option>
                <option value="90">90 days</option>
                <option value="365">1 year</option>
                <option value="all">All logs</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-2">Activity types to clear:</label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input type="checkbox" value="comment_activity" class="clear-activity-type mr-2">
                  <span class="text-sm">Comment Activities</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" value="highlight_activity" class="clear-activity-type mr-2">
                  <span class="text-sm">Highlight Activities</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" value="submission_activity" class="clear-activity-type mr-2">
                  <span class="text-sm">Submission Activities</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" id="clear-all-types" class="mr-2">
                  <span class="text-sm font-medium">Select All</span>
                </label>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-2">Reason for clearing:</label>
              <input type="text" id="clear-reason" class="w-full border rounded-md px-3 py-2" placeholder="e.g., Monthly cleanup, Storage optimization">
            </div>
          </div>
          
          <div class="flex justify-end gap-2 mt-6">
            <button id="cancel-clear-logs" class="px-4 py-2 text-gray-600 border rounded-md hover:bg-gray-50">Cancel</button>
            <button id="confirm-clear-logs" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Clear Logs</button>
          </div>
        </div>
        </div> <!-- End of activity-logs-scroll-container -->
      </div>

      <!-- Export Archive Modal -->
      <div id="export-archive-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
          <h3 class="text-lg font-semibold mb-4">Export Archived Logs</h3>
          <p class="text-gray-600 mb-4">Choose export options for your archived logs.</p>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-2">Export format:</label>
              <select id="export-format-select" class="w-full border rounded-md px-3 py-2">
                <option value="json">JSON</option>
                <option value="csv">CSV</option>
              </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-2">From date:</label>
                <input type="date" id="export-date-from" class="w-full border rounded-md px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">To date:</label>
                <input type="date" id="export-date-to" class="w-full border rounded-md px-3 py-2">
              </div>
            </div>
          </div>
          
          <div class="flex justify-end gap-2 mt-6">
            <button id="cancel-export" class="px-4 py-2 text-gray-600 border rounded-md hover:bg-gray-50">Cancel</button>
            <button id="confirm-export" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Export</button>
          </div>
        </div>
      </div>

      <!-- Reports Tab Content -->
      <div id="reports-content" class="tab-content hidden">
        <div class="reports-scroll-container max-h-[80vh] overflow-y-auto custom-scrollbar pr-4">
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
        </div> <!-- End of reports-scroll-container -->
      </div>

      <!-- Profile Tab Content -->
      <div id="profile-content" class="tab-content hidden">
        <div class="profile-scroll-container max-h-[80vh] overflow-y-auto custom-scrollbar pr-4">
          <div class="max-w-4xl mx-auto">
            <div class="mb-8">
              <h2 class="text-3xl font-bold text-gray-900 mb-2">Profile Settings</h2>
              <p class="text-gray-600">Manage your personal information and account details.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Picture Section -->
            <div class="lg:col-span-1">
              <div class="bg-white rounded-lg shadow p-6">
                <div class="text-center">
                  <div class="w-32 h-32 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center text-white font-bold text-4xl mx-auto mb-4 shadow-lg">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                  </div>
                  <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                  <p class="text-gray-500 mb-4"><?php echo htmlspecialchars($user['role']); ?></p>
                  <button class="btn btn-primary btn-sm">
                    <i data-lucide="camera" class="w-4 h-4 mr-2"></i>
                    Change Photo
                  </button>
                </div>
              </div>

              <!-- Account Info -->
              <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h4 class="font-semibold text-gray-900 mb-4">Account Information</h4>
                <div class="space-y-3">
                  <div class="flex items-center gap-3 text-sm">
                    <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                    <span class="text-gray-600">User ID:</span>
                    <span class="font-medium"><?php echo $user['id']; ?></span>
                  </div>
                  <div class="flex items-center gap-3 text-sm">
                    <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                    <span class="text-gray-600">Member since:</span>
                    <span class="font-medium"><?php echo date('M Y', strtotime($user['created_at'] ?? '2024-01-01')); ?></span>
                  </div>
                  <div class="flex items-center gap-3 text-sm">
                    <i data-lucide="shield-check" class="w-4 h-4 text-gray-400"></i>
                    <span class="text-gray-600">Role:</span>
                    <span class="font-medium capitalize"><?php echo $user['role']; ?></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Profile Form Section -->
            <div class="lg:col-span-2">
              <div class="bg-white rounded-lg shadow p-6">
                <h4 class="font-semibold text-gray-900 mb-6">Personal Information</h4>
                
                <form id="profileForm" class="space-y-6">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label for="profileFullName" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                      <input type="text" id="profileFullName" name="full_name" 
                             value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                             class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>

                    <div>
                      <label for="profileEmail" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                      <input type="email" id="profileEmail" name="email" 
                             value="<?php echo htmlspecialchars($user['email']); ?>" 
                             class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>

                    <div>
                      <label for="profileFacultyId" class="block text-sm font-medium text-gray-700 mb-2">Faculty ID</label>
                      <input type="text" id="profileFacultyId" name="faculty_id" 
                             value="<?php echo htmlspecialchars($user['faculty_id'] ?? ''); ?>" 
                             class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                      <label for="profileDepartment" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                      <select id="profileDepartment" name="department" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Department</option>
                        <option value="Computer Science" <?php echo ($user['department'] ?? '') === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                        <option value="Information Technology" <?php echo ($user['department'] ?? '') === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                        <option value="Engineering" <?php echo ($user['department'] ?? '') === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                        <option value="Business" <?php echo ($user['department'] ?? '') === 'Business' ? 'selected' : ''; ?>>Business</option>
                        <option value="Education" <?php echo ($user['department'] ?? '') === 'Education' ? 'selected' : ''; ?>>Education</option>
                      </select>
                    </div>

                    <div class="md:col-span-2">
                      <label for="profileSpecialization" class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                      <input type="text" id="profileSpecialization" name="specialization" 
                             value="<?php echo htmlspecialchars($user['specialization'] ?? ''); ?>" 
                             placeholder="e.g., Machine Learning, Software Engineering"
                             class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="md:col-span-2">
                      <label for="profileBio" class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                      <textarea id="profileBio" name="bio" rows="4" 
                                placeholder="Tell us about yourself, your research interests, etc."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                  </div>

                  <div class="flex justify-end space-x-3">
                    <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                      Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                      <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i>
                      Save Changes
                    </button>
                  </div>
                </form>
              </div>

              <!-- Change Password Section -->
              <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h4 class="font-semibold text-gray-900 mb-6">Change Password</h4>
                
                <form id="passwordForm" class="space-y-6">
                  <div>
                    <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                    <input type="password" id="currentPassword" name="current_password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                      <input type="password" id="newPassword" name="new_password" 
                             class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>

                    <div>
                      <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                      <input type="password" id="confirmPassword" name="confirm_password" 
                             class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                  </div>

                  <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                      <i data-lucide="key" class="w-4 h-4 mr-2 inline"></i>
                      Update Password
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        </div> <!-- End of profile-scroll-container -->
      </div>

      <!-- Settings Tab Content -->
      <div id="settings-content" class="tab-content hidden">
        <div class="max-w-4xl mx-auto">
          <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Settings</h2>
            <p class="text-gray-600">Configure your preferences and application settings.</p>
          </div>

          <div class="settings-scroll-container max-h-[80vh] overflow-y-auto custom-scrollbar pr-4">
            <div class="space-y-6">
            <!-- Notification Settings -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Notification Preferences</h3>
              
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-700">Email Notifications</label>
                    <p class="text-sm text-gray-500">Receive email notifications for important updates</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" value="" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                  </label>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-700">Student Submission Alerts</label>
                    <p class="text-sm text-gray-500">Get notified when students submit new chapters</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" value="" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                  </label>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-700">Weekly Summary Reports</label>
                    <p class="text-sm text-gray-500">Receive weekly summaries of student progress</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" value="" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                  </label>
                </div>
              </div>
            </div>

            <!-- Display Settings -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Display Settings</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="theme" class="block text-sm font-medium text-gray-700 mb-2">Theme</label>
                  <select id="theme" name="theme" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="light">Light Mode</option>
                    <option value="dark">Dark Mode</option>
                    <option value="auto">Auto (System)</option>
                  </select>
                </div>

                <div>
                  <label for="language" class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                  <select id="language" name="language" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="en">English</option>
                    <option value="fil">Filipino</option>
                  </select>
                </div>

                <div>
                  <label for="itemsPerPage" class="block text-sm font-medium text-gray-700 mb-2">Items per Page</label>
                  <select id="itemsPerPage" name="items_per_page" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                </div>

                <div>
                  <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                  <select id="timezone" name="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="Asia/Manila" selected>Asia/Manila (PHT)</option>
                    <option value="UTC">UTC</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Privacy Settings -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Privacy Settings</h3>
              
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-700">Show Profile to Students</label>
                    <p class="text-sm text-gray-500">Allow students to view your profile information</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" value="" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                  </label>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <label class="text-sm font-medium text-gray-700">Activity Status</label>
                    <p class="text-sm text-gray-500">Show when you're online to students</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" value="" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                  </label>
                </div>
              </div>
            </div>

            <!-- Academic Settings -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Academic Settings</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="defaultReviewTime" class="block text-sm font-medium text-gray-700 mb-2">Default Review Time (days)</label>
                  <input type="number" id="defaultReviewTime" name="default_review_time" value="7" min="1" max="30"
                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                  <label for="autoReminders" class="block text-sm font-medium text-gray-700 mb-2">Auto Reminder Schedule</label>
                  <select id="autoReminders" name="auto_reminders" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="none">No Reminders</option>
                    <option value="1day">1 Day Before</option>
                    <option value="3days" selected>3 Days Before</option>
                    <option value="1week">1 Week Before</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Format Requirements Settings -->
            <div class="bg-white rounded-lg shadow p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Document Format Requirements</h3>
                <button type="button" id="resetFormatRequirementsBtn" class="text-sm text-gray-500 hover:text-gray-700 underline">
                  Reset to Defaults
                </button>
              </div>
              <p class="text-sm text-gray-600 mb-6">Set your formatting standards for student documents. Enabled requirements will be checked during analysis.</p>
              
              <!-- Format Requirements Content -->
              <div id="format-requirements-content" class="space-y-6">
                <!-- Loading state -->
                <div id="format-requirements-loading" class="text-center py-8">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
                  <p class="mt-2 text-sm text-gray-500">Loading format requirements...</p>
                </div>
                
                <!-- Requirements will be loaded here -->
                <div id="format-requirements-form" class="hidden">
                  
                  <!-- Margins Section -->
                  <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                      <i data-lucide="layout" class="w-4 h-4 mr-2 text-blue-500"></i>
                      Page Margins
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_margin_top" class="mr-2">
                          <span class="text-sm font-medium">Top Margin</span>
                        </label>
                        <div class="flex items-center space-x-2">
                          <input type="number" id="margin_top" step="0.1" min="0.25" max="5" class="w-20 px-2 py-1 border rounded text-sm">
                          <span class="text-sm text-gray-500">inches</span>
                        </div>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_margin_bottom" class="mr-2">
                          <span class="text-sm font-medium">Bottom Margin</span>
                        </label>
                        <div class="flex items-center space-x-2">
                          <input type="number" id="margin_bottom" step="0.1" min="0.25" max="5" class="w-20 px-2 py-1 border rounded text-sm">
                          <span class="text-sm text-gray-500">inches</span>
                        </div>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_margin_left" class="mr-2">
                          <span class="text-sm font-medium">Left Margin</span>
                        </label>
                        <div class="flex items-center space-x-2">
                          <input type="number" id="margin_left" step="0.1" min="0.25" max="5" class="w-20 px-2 py-1 border rounded text-sm">
                          <span class="text-sm text-gray-500">inches</span>
                        </div>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_margin_right" class="mr-2">
                          <span class="text-sm font-medium">Right Margin</span>
                        </label>
                        <div class="flex items-center space-x-2">
                          <input type="number" id="margin_right" step="0.1" min="0.25" max="5" class="w-20 px-2 py-1 border rounded text-sm">
                          <span class="text-sm text-gray-500">inches</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Typography Section -->
                  <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                      <i data-lucide="type" class="w-4 h-4 mr-2 text-green-500"></i>
                      Typography
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_font_family" class="mr-2">
                          <span class="text-sm font-medium">Font Family</span>
                        </label>
                        <select id="font_family" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="Times New Roman">Times New Roman</option>
                          <option value="Arial">Arial</option>
                          <option value="Calibri">Calibri</option>
                          <option value="Georgia">Georgia</option>
                          <option value="Garamond">Garamond</option>
                        </select>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_font_size" class="mr-2">
                          <span class="text-sm font-medium">Font Size</span>
                        </label>
                        <div class="flex items-center space-x-2">
                          <input type="number" id="font_size" min="8" max="72" class="w-20 px-2 py-1 border rounded text-sm">
                          <span class="text-sm text-gray-500">pt</span>
                        </div>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_font_style" class="mr-2">
                          <span class="text-sm font-medium">Font Style</span>
                        </label>
                        <select id="font_style" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="normal">Normal</option>
                          <option value="bold">Bold</option>
                          <option value="italic">Italic</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Spacing Section -->
                  <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                      <i data-lucide="align-justify" class="w-4 h-4 mr-2 text-purple-500"></i>
                      Spacing & Alignment
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_line_spacing" class="mr-2">
                          <span class="text-sm font-medium">Line Spacing</span>
                        </label>
                        <select id="line_spacing" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="1.0">Single (1.0)</option>
                          <option value="1.5">1.5 lines</option>
                          <option value="2.0">Double (2.0)</option>
                          <option value="2.5">2.5 lines</option>
                        </select>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_paragraph_spacing" class="mr-2">
                          <span class="text-sm font-medium">Paragraph Spacing</span>
                        </label>
                        <div class="flex items-center space-x-2">
                          <input type="number" id="paragraph_spacing" min="0" max="50" class="w-20 px-2 py-1 border rounded text-sm">
                          <span class="text-sm text-gray-500">pt</span>
                        </div>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_indentation" class="mr-2">
                          <span class="text-sm font-medium">First Line Indent</span>
                        </label>
                        <div class="flex items-center space-x-2">
                          <input type="number" id="indentation" step="0.1" min="0" max="2" class="w-20 px-2 py-1 border rounded text-sm">
                          <span class="text-sm text-gray-500">inches</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Page Setup Section -->
                  <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                      <i data-lucide="file-text" class="w-4 h-4 mr-2 text-orange-500"></i>
                      Page Setup
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_page_numbers" class="mr-2">
                          <span class="text-sm font-medium">Page Numbers</span>
                        </label>
                        <select id="page_numbers" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="required">Required</option>
                          <option value="optional">Optional</option>
                          <option value="forbidden">Not Allowed</option>
                        </select>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_header_footer" class="mr-2">
                          <span class="text-sm font-medium">Header/Footer</span>
                        </label>
                        <select id="header_footer" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="required">Required</option>
                          <option value="optional">Optional</option>
                          <option value="forbidden">Not Allowed</option>
                        </select>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_page_size" class="mr-2">
                          <span class="text-sm font-medium">Page Size</span>
                        </label>
                        <select id="page_size" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="A4">A4</option>
                          <option value="Letter">Letter (8.5" x 11")</option>
                          <option value="Legal">Legal</option>
                        </select>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_orientation" class="mr-2">
                          <span class="text-sm font-medium">Orientation</span>
                        </label>
                        <select id="orientation" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="portrait">Portrait</option>
                          <option value="landscape">Landscape</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Structure Section -->
                  <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                      <i data-lucide="list" class="w-4 h-4 mr-2 text-red-500"></i>
                      Document Structure
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_title_page" class="mr-2">
                          <span class="text-sm font-medium">Title Page</span>
                        </label>
                        <select id="title_page" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="required">Required</option>
                          <option value="optional">Optional</option>
                        </select>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_table_of_contents" class="mr-2">
                          <span class="text-sm font-medium">Table of Contents</span>
                        </label>
                        <select id="table_of_contents" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="required">Required</option>
                          <option value="optional">Optional</option>
                        </select>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_abstract" class="mr-2">
                          <span class="text-sm font-medium">Abstract</span>
                        </label>
                        <select id="abstract" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="required">Required</option>
                          <option value="optional">Optional</option>
                        </select>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="flex items-center">
                          <input type="checkbox" id="enable_bibliography" class="mr-2">
                          <span class="text-sm font-medium">Bibliography/References</span>
                        </label>
                        <select id="bibliography" class="w-full px-2 py-1 border rounded text-sm">
                          <option value="required">Required</option>
                          <option value="optional">Optional</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                </div>
              </div>
            </div>

            <!-- Save Settings -->
            <div class="flex justify-between">
              <button type="button" id="saveFormatRequirementsBtn" class="px-6 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                <i data-lucide="check" class="w-4 h-4 mr-2 inline"></i>
                Save Format Requirements
              </button>
              
              <button type="button" id="saveSettingsBtn" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i>
                Save Settings
              </button>
            </div>
            </div> <!-- End of settings-scroll-container -->
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
          
          // Restore header and normal padding for all tabs except activity-logs
          if (targetTab !== 'activity-logs') {
            document.getElementById('dashboard-header').style.display = 'flex';
            document.querySelector('main').classList.remove('activity-logs-mode');
          }
          
          // Show target content
          tabContents.forEach(content => {
            if (content.id === `${targetTab}-content`) {
              content.classList.remove('hidden');
              document.querySelector('h2').textContent = targetTab.charAt(0).toUpperCase() + targetTab.slice(1);
              
              // Initialize reports if reports tab is clicked
              if (targetTab === 'reports') {
                initializeReports();
              }
              
              // Load students if document review tab is clicked
              if (targetTab === 'document-review') {
                loadAllStudentsForReview();
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

      // Profile Form Handler
      const profileForm = document.getElementById('profileForm');
      if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          // Show loading state
          const submitBtn = this.querySelector('button[type="submit"]');
          const originalText = submitBtn.innerHTML;
          submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 inline animate-spin"></i>Saving...';
          submitBtn.disabled = true;
          
          // Get form data
          const formData = new FormData(this);
          formData.append('action', 'update_profile');
          
          // Send AJAX request
          fetch('api/update_profile.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showNotification('Profile updated successfully!', 'success');
              // Update profile display areas if needed
              if (formData.get('full_name')) {
                document.querySelectorAll('[data-user-name]').forEach(el => {
                  el.textContent = formData.get('full_name');
                });
              }
            } else {
              showNotification(data.error || 'Failed to update profile', 'error');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('An unexpected error occurred', 'error');
          })
          .finally(() => {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            lucide.createIcons();
          });
        });
      }

      // Password Form Handler
      const passwordForm = document.getElementById('passwordForm');
      if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          // Validate passwords match
          const newPassword = this.querySelector('#newPassword').value;
          const confirmPassword = this.querySelector('#confirmPassword').value;
          
          if (newPassword !== confirmPassword) {
            showNotification('New passwords do not match', 'error');
            return;
          }
          
          // Show loading state
          const submitBtn = this.querySelector('button[type="submit"]');
          const originalText = submitBtn.innerHTML;
          submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 inline animate-spin"></i>Updating...';
          submitBtn.disabled = true;
          
          // Get form data
          const formData = new FormData(this);
          formData.append('action', 'change_password');
          
          // Send AJAX request
          fetch('api/update_profile.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showNotification('Password updated successfully!', 'success');
              this.reset(); // Clear form
            } else {
              showNotification(data.error || 'Failed to update password', 'error');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('An unexpected error occurred', 'error');
          })
          .finally(() => {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            lucide.createIcons();
          });
        });
      }

      // Load Format Requirements on Page Load
      function loadFormatRequirements() {
        fetch('api/format_requirements.php?action=get_requirements')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              populateFormatRequirements(data.requirements);
              document.getElementById('format-requirements-loading').classList.add('hidden');
              document.getElementById('format-requirements-form').classList.remove('hidden');
            } else {
              console.error('Failed to load format requirements:', data.error);
              document.getElementById('format-requirements-loading').innerHTML = `
                <div class="text-center py-8 text-red-500">
                  <i data-lucide="alert-circle" class="w-8 h-8 mx-auto mb-2"></i>
                  <p class="text-sm">Failed to load format requirements</p>
                </div>
              `;
              lucide.createIcons();
            }
          })
          .catch(error => {
            console.error('Error loading format requirements:', error);
          });
      }

      // Populate format requirements form
      function populateFormatRequirements(requirements) {
        // Margins
        if (requirements.margins) {
          Object.keys(requirements.margins).forEach(key => {
            const enableCheckbox = document.getElementById(`enable_margin_${key}`);
            const valueInput = document.getElementById(`margin_${key}`);
            if (enableCheckbox && valueInput) {
              enableCheckbox.checked = requirements.margins[key].enabled;
              valueInput.value = requirements.margins[key].value;
              valueInput.disabled = !requirements.margins[key].enabled;
            }
          });
        }

        // Typography
        if (requirements.typography) {
          Object.keys(requirements.typography).forEach(key => {
            const enableCheckbox = document.getElementById(`enable_${key}`);
            const valueInput = document.getElementById(key);
            if (enableCheckbox && valueInput) {
              enableCheckbox.checked = requirements.typography[key].enabled;
              valueInput.value = requirements.typography[key].value;
              valueInput.disabled = !requirements.typography[key].enabled;
            }
          });
        }

        // Spacing
        if (requirements.spacing) {
          Object.keys(requirements.spacing).forEach(key => {
            const enableCheckbox = document.getElementById(`enable_${key}`);
            const valueInput = document.getElementById(key);
            if (enableCheckbox && valueInput) {
              enableCheckbox.checked = requirements.spacing[key].enabled;
              valueInput.value = requirements.spacing[key].value;
              valueInput.disabled = !requirements.spacing[key].enabled;
            }
          });
        }

        // Page Setup
        if (requirements.page_setup) {
          Object.keys(requirements.page_setup).forEach(key => {
            const enableCheckbox = document.getElementById(`enable_${key}`);
            const valueInput = document.getElementById(key);
            if (enableCheckbox && valueInput) {
              enableCheckbox.checked = requirements.page_setup[key].enabled;
              valueInput.value = requirements.page_setup[key].value;
              valueInput.disabled = !requirements.page_setup[key].enabled;
            }
          });
        }

        // Structure
        if (requirements.structure) {
          Object.keys(requirements.structure).forEach(key => {
            const enableCheckbox = document.getElementById(`enable_${key}`);
            const valueInput = document.getElementById(key);
            if (enableCheckbox && valueInput) {
              enableCheckbox.checked = requirements.structure[key].enabled;
              valueInput.value = requirements.structure[key].value;
              valueInput.disabled = !requirements.structure[key].enabled;
            }
          });
        }

        // Setup event listeners for checkboxes
        setupFormatRequirementsEventListeners();
      }

      // Setup event listeners for format requirements
      function setupFormatRequirementsEventListeners() {
        // Add event listeners to all enable checkboxes
        document.querySelectorAll('#format-requirements-form input[type="checkbox"]').forEach(checkbox => {
          checkbox.addEventListener('change', function() {
            const fieldId = this.id.replace('enable_', '');
            const fieldInput = document.getElementById(fieldId);
            if (fieldInput) {
              fieldInput.disabled = !this.checked;
              if (this.checked) {
                fieldInput.focus();
              }
            }
          });
        });
      }

      // Save Format Requirements Handler
      function saveFormatRequirements() {
        const saveBtn = document.getElementById('saveFormatRequirementsBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 inline animate-spin"></i>Saving...';
        saveBtn.disabled = true;

        // Collect all form data
        const formData = new FormData();
        formData.append('action', 'save_requirements');

        // Collect all checkbox and input values
        document.querySelectorAll('#format-requirements-form input, #format-requirements-form select').forEach(input => {
          if (input.type === 'checkbox') {
            if (input.checked) {
              formData.append(input.id, '1');
            }
          } else {
            formData.append(input.id, input.value);
          }
        });

        fetch('api/format_requirements.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification('Format requirements saved successfully!', 'success');
          } else {
            showNotification('Failed to save format requirements: ' + data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Error saving format requirements:', error);
          showNotification('Failed to save format requirements', 'error');
        })
        .finally(() => {
          saveBtn.innerHTML = originalText;
          saveBtn.disabled = false;
        });
      }

      // Reset Format Requirements to Defaults
      function resetFormatRequirements() {
        if (!confirm('Are you sure you want to reset all format requirements to defaults? This will overwrite your current settings.')) {
          return;
        }

        const formData = new FormData();
        formData.append('action', 'reset_to_defaults');

        fetch('api/format_requirements.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification('Format requirements reset to defaults', 'success');
            loadFormatRequirements(); // Reload the form
          } else {
            showNotification('Failed to reset format requirements: ' + data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Error resetting format requirements:', error);
          showNotification('Failed to reset format requirements', 'error');
        });
      }

      // Load User Settings on Page Load
      function loadUserSettings() {
        fetch('api/get_user_settings.php')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const settings = data.settings;
              
              // Populate notification checkboxes
              const notificationCheckboxes = document.querySelectorAll('#settings-content input[type="checkbox"]');
              if (notificationCheckboxes.length >= 5) {
                notificationCheckboxes[0].checked = settings.email_notifications === '1';
                notificationCheckboxes[1].checked = settings.submission_alerts === '1';
                notificationCheckboxes[2].checked = settings.weekly_reports === '1';
                notificationCheckboxes[3].checked = settings.show_profile === '1';
                notificationCheckboxes[4].checked = settings.activity_status === '1';
              }
              
              // Populate display settings
              const themeSelect = document.getElementById('theme');
              if (themeSelect) themeSelect.value = settings.theme || 'light';
              
              const languageSelect = document.getElementById('language');
              if (languageSelect) languageSelect.value = settings.language || 'en';
              
              const itemsPerPageSelect = document.getElementById('itemsPerPage');
              if (itemsPerPageSelect) itemsPerPageSelect.value = settings.items_per_page || '25';
              
              const timezoneSelect = document.getElementById('timezone');
              if (timezoneSelect) timezoneSelect.value = settings.timezone || 'Asia/Manila';
              
              // Populate academic settings
              const defaultReviewTimeInput = document.getElementById('defaultReviewTime');
              if (defaultReviewTimeInput) defaultReviewTimeInput.value = settings.default_review_time || '7';
              
              const autoRemindersSelect = document.getElementById('autoReminders');
              if (autoRemindersSelect) autoRemindersSelect.value = settings.auto_reminders || '3days';
              
            } else {
              console.error('Failed to load user settings:', data.error);
            }
          })
          .catch(error => {
            console.error('Error loading user settings:', error);
          });
      }

      // Load settings when the settings tab is first opened
      const settingsNavLink = document.querySelector('[data-tab="settings"]');
      if (settingsNavLink) {
        settingsNavLink.addEventListener('click', function() {
          // Small delay to ensure the tab content is visible
          setTimeout(() => {
            loadUserSettings();
            loadFormatRequirements();
          }, 100);
        });
      }
      


      


      // Also load settings if the settings tab is already active on page load
      if (document.getElementById('settings-content') && !document.getElementById('settings-content').classList.contains('hidden')) {
        loadUserSettings();
        loadFormatRequirements();
      }

      // Settings Save Handler
      const saveSettingsBtn = document.getElementById('saveSettingsBtn');
      if (saveSettingsBtn) {
        saveSettingsBtn.addEventListener('click', function() {
          // Show loading state
          const originalText = this.innerHTML;
          this.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 inline animate-spin"></i>Saving...';
          this.disabled = true;
          
          // Collect all settings data
          const settingsData = new FormData();
          settingsData.append('action', 'update_settings');
          
          // Notification preferences - collect all checkboxes properly
          const notificationCheckboxes = document.querySelectorAll('#settings-content input[type="checkbox"]');
          let emailNotifications = 0;
          let submissionAlerts = 0;
          let weeklyReports = 0;
          let showProfile = 0;
          let activityStatus = 0;
          
          notificationCheckboxes.forEach((checkbox, index) => {
            if (checkbox.checked) {
              switch(index) {
                case 0: emailNotifications = 1; break;
                case 1: submissionAlerts = 1; break;
                case 2: weeklyReports = 1; break;
                case 3: showProfile = 1; break;
                case 4: activityStatus = 1; break;
              }
            }
          });
          
          settingsData.append('email_notifications', emailNotifications);
          settingsData.append('submission_alerts', submissionAlerts);
          settingsData.append('weekly_reports', weeklyReports);
          settingsData.append('show_profile', showProfile);
          settingsData.append('activity_status', activityStatus);
          
          // Display settings
          settingsData.append('theme', document.getElementById('theme')?.value || 'light');
          settingsData.append('language', document.getElementById('language')?.value || 'en');
          settingsData.append('items_per_page', document.getElementById('itemsPerPage')?.value || '25');
          settingsData.append('timezone', document.getElementById('timezone')?.value || 'Asia/Manila');
          
          // Academic settings
          settingsData.append('default_review_time', document.getElementById('defaultReviewTime')?.value || '7');
          settingsData.append('auto_reminders', document.getElementById('autoReminders')?.value || '3days');
          
          // Send AJAX request
          fetch('api/update_settings.php', {
            method: 'POST',
            body: settingsData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showNotification('Settings saved successfully!', 'success');
            } else {
              showNotification(data.error || 'Failed to save settings', 'error');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('An unexpected error occurred', 'error');
          })
          .finally(() => {
            // Restore button state
            this.innerHTML = originalText;
            this.disabled = false;
            lucide.createIcons();
          });
        });
      }

      // Format Requirements Save Handler
      const saveFormatRequirementsBtn = document.getElementById('saveFormatRequirementsBtn');
      if (saveFormatRequirementsBtn) {
        saveFormatRequirementsBtn.addEventListener('click', saveFormatRequirements);
      }

      // Format Requirements Reset Handler
      const resetFormatRequirementsBtn = document.getElementById('resetFormatRequirementsBtn');
      if (resetFormatRequirementsBtn) {
        resetFormatRequirementsBtn.addEventListener('click', resetFormatRequirements);
      }
      
      // Notification utility function
      function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
        const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'alert-circle' : 'info';
        
        notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-md`;
        notification.innerHTML = `
          <div class="flex items-center">
            <i data-lucide="${icon}" class="w-5 h-5 mr-3"></i>
            <p class="text-sm font-medium">${message}</p>
          </div>
        `;
        
        document.body.appendChild(notification);
        lucide.createIcons();
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
          notification.style.opacity = '0';
          notification.style.transform = 'translateX(100%)';
          setTimeout(() => {
            if (notification.parentNode) {
              document.body.removeChild(notification);
            }
          }, 300);
        }, 5000);
      }

      // Document Review Functionality
      let currentChapterId = null;
      window.currentChapterId = null;
      window.selectedText = '';
      window.selectedRange = null;
      window.currentHighlightColor = '#ffeb3b';
      window.isHighlightMode = false;
      
      // Refresh document list - handled by the main event handler at the bottom

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
                
                // Load existing comments for all file types (highlights will be loaded by Word viewer)
                loadComments(chapterId);
                // Note: loadHighlights is now called by the Word viewer initialization with proper timing
                
              } else {
                // No files uploaded, show no content message
                window.currentFileId = null; // Clear file ID for fullscreen
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
                
                // Still load comments and highlights for text-only chapters
                loadComments(chapterId);
                loadHighlights(chapterId);
                window.updateHighlightCommentIndicators(chapterId);
                
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
              window.currentFileId = null; // Clear file ID to prevent fullscreen issues
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
            window.currentFileId = null; // Clear file ID to prevent fullscreen issues
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
        
        // Add direct completion monitoring
        window.monitorWordViewerCompletion = function() {
          console.log('🔍 Starting Word viewer completion monitoring...');
          
          let monitorAttempts = 0;
          const maxMonitorAttempts = 30; // 15 seconds total
          
          const checkCompletion = setInterval(() => {
            monitorAttempts++;
            
            // Check if Word viewer has rendered content
            const viewerContent = document.getElementById('adviser-word-viewer-content');
            if (viewerContent) {
              const hasWordContent = viewerContent.querySelector('.word-content');
              const hasParagraphs = viewerContent.querySelectorAll('.word-paragraph, div').length > 5;
              const hasSubstantialText = viewerContent.textContent.length > 300;
              const notLoading = !viewerContent.textContent.includes('Loading document');
              
              console.log(`Monitor attempt ${monitorAttempts}: content=${!!hasWordContent}, paragraphs=${hasParagraphs}, text=${hasSubstantialText}, notLoading=${notLoading}`);
              
              if ((hasWordContent || hasParagraphs) && hasSubstantialText && notLoading) {
                console.log('✅ Word viewer completion detected! Loading highlights...');
                clearInterval(checkCompletion);
                
                // Immediate highlight loading
                setTimeout(() => {
                  if (window.currentChapterId) {
                    console.log('🚀 Direct highlight loading triggered');
                    
                    // Use the most direct approach - find content and apply highlights
                    const bestElement = viewerContent.querySelector('.word-content') || 
                                       viewerContent.querySelector('.word-paragraph') || 
                                       viewerContent;
                    
                    if (bestElement && bestElement.textContent.length > 100) {
                      fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
                        .then(response => response.json())
                        .then(data => {
                          if (data.success && data.highlights.length > 0) {
                            console.log(`Direct loading ${data.highlights.length} highlights`);
                            
                            let applied = 0;
                            data.highlights.forEach(highlight => {
                              if (window.ultraAggressiveHighlightApply(highlight, bestElement)) {
                                applied++;
                              }
                            });
                            
                                                         if (applied > 0) {
                               console.log(`✅ Successfully auto-loaded ${applied} highlights`);
                             }
                          }
                        })
                        .catch(error => console.error('Direct loading error:', error));
                    }
                  }
                }, 100);
                return;
              }
            }
            
            if (monitorAttempts >= maxMonitorAttempts) {
              console.log('⚠️ Word viewer monitoring timeout, stopping');
              clearInterval(checkCompletion);
            }
          }, 500);
        };
        
        // Start monitoring immediately
        window.monitorWordViewerCompletion();
        
        // Enhanced automatic highlight loading system
        const setupAutomaticHighlightLoading = () => {
          if (!window.currentChapterId) return;
          
          console.log('🚀 Setting up ENHANCED automatic highlight loading for chapter:', window.currentChapterId);
          
          let highlightsLoaded = false; // Prevent duplicate loading
          
                     // Function to load highlights when content is ready
           const loadHighlightsWhenReady = (source) => {
             if (highlightsLoaded) {
               console.log(`[${source}] Highlights already loaded, skipping`);
               return;
             }
             
             console.log(`[${source}] Loading highlights and comments`);
             highlightsLoaded = true;
             
             // More aggressive automatic loading with multiple attempts
             const attemptAutoLoad = (attempt = 1, maxAttempts = 5) => {
               console.log(`[${source}] Auto-load attempt ${attempt}/${maxAttempts}`);
               
               // Find ANY element with substantial text content
               const allElements = [...document.querySelectorAll('*')];
               const contentCandidates = allElements.filter(el => {
                 if (!el.textContent) return false;
                 const text = el.textContent.trim();
                 return text.length > 200 && 
                        !text.includes('Loading') && 
                        !text.includes('No content') &&
                        !el.classList.contains('highlight-marker');
               }).sort((a, b) => {
                 let scoreA = a.textContent.length;
                 let scoreB = b.textContent.length;
                 if (a.className.includes('word-content')) scoreA += 10000;
                 if (b.className.includes('word-content')) scoreB += 10000;
                 if (a.className.includes('word-')) scoreA += 5000;
                 if (b.className.includes('word-')) scoreB += 5000;
                 return scoreB - scoreA;
               });
               
               if (contentCandidates.length > 0 && window.currentChapterId) {
                 const targetElement = contentCandidates[0];
                 console.log(`[${source}] Found content, applying highlights directly`);
                 
                 // Apply highlights directly without waiting
                 fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
                   .then(response => response.json())
                   .then(data => {
                     if (data.success && data.highlights.length > 0) {
                       console.log(`[${source}] Applying ${data.highlights.length} highlights`);
                       
                       // Clear existing highlights
                       targetElement.querySelectorAll('.highlight-marker').forEach(h => {
                         const parent = h.parentNode;
                         if (parent) {
                           parent.insertBefore(document.createTextNode(h.textContent), h);
                           parent.removeChild(h);
                         }
                       });
                       
                       // Apply highlights using our ultra-aggressive method
                       let successCount = 0;
                       data.highlights.forEach(highlight => {
                         if (highlight.highlighted_text && window.ultraAggressiveHighlightApply(highlight, targetElement)) {
                           successCount++;
                         }
                       });
                       
                                               if (successCount > 0) {
                          console.log(`[${source}] ✅ Auto-loaded ${successCount} highlights successfully!`);
                        } else {
                         console.log(`[${source}] ⚠️ Found highlights but could not apply them`);
                         if (attempt < maxAttempts) {
                           setTimeout(() => attemptAutoLoad(attempt + 1, maxAttempts), 1000);
                         }
                       }
                     } else {
                       console.log(`[${source}] No highlights found in database`);
                     }
                     
                     // Load comments regardless
                     loadComments(window.currentChapterId);
                     window.updateHighlightCommentIndicators(window.currentChapterId);
                   })
                   .catch(error => {
                     console.error(`[${source}] Error loading highlights:`, error);
                     if (attempt < maxAttempts) {
                       setTimeout(() => attemptAutoLoad(attempt + 1, maxAttempts), 1000);
                     }
                   });
               } else {
                 console.log(`[${source}] No content found, retrying...`);
                 if (attempt < maxAttempts) {
                   setTimeout(() => attemptAutoLoad(attempt + 1, maxAttempts), 1000);
                 } else {
                   console.log(`[${source}] Max attempts reached, giving up`);
                 }
               }
             };
             
             // Start auto-loading with a small delay
             setTimeout(() => attemptAutoLoad(), 200);
           };
          
          // Method 1: Promise-based loading
          adviserWordViewer.loadDocument(fileId).then(() => {
            console.log('[Promise] Document loaded successfully');
            setTimeout(() => loadHighlightsWhenReady('Promise'), 300);
          }).catch(error => {
            console.log('[Promise] Document load failed:', error);
          });
          
          // Method 2: Enhanced MutationObserver with multiple triggers
          const observer = new MutationObserver((mutations) => {
            for (let mutation of mutations) {
              if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                // Check for multiple content indicators
                const indicators = [
                  document.querySelector('.word-content'),
                  document.querySelector('.word-paragraph'),
                  document.querySelector('[class*="word-"]')
                ].filter(el => el && el.textContent && el.textContent.trim().length > 50);
                
                if (indicators.length > 0) {
                  console.log('[Observer] Content detected, triggering highlight load');
                  observer.disconnect();
                  setTimeout(() => loadHighlightsWhenReady('Observer'), 200);
                  return;
                }
              }
            }
          });
          
          const observerTarget = document.getElementById('adviser-word-viewer-content');
          if (observerTarget) {
            observer.observe(observerTarget, {
              childList: true,
              subtree: true,
              attributes: true,
              attributeFilter: ['class']
            });
          }
          
          // Method 3: Smart polling with content detection
          let pollCount = 0;
          const maxPolls = 20;
          const smartPoll = setInterval(() => {
            pollCount++;
            
            // Multiple content detection strategies
            const contentElements = [
              document.querySelector('.word-content'),
              document.querySelector('.word-paragraph'),
              document.querySelector('[class*="word-"] div'),
              ...document.querySelectorAll('#adviser-word-viewer-content div')
            ].filter(el => el && el.textContent && el.textContent.trim().length > 100);
            
            if (contentElements.length > 0) {
              console.log(`[SmartPoll] Content found after ${pollCount} attempts`);
              clearInterval(smartPoll);
              observer.disconnect();
              setTimeout(() => loadHighlightsWhenReady('SmartPoll'), 100);
            } else if (pollCount >= maxPolls) {
              console.log('[SmartPoll] Max attempts reached, using fallback');
              clearInterval(smartPoll);
              observer.disconnect();
              setTimeout(() => loadHighlightsWhenReady('SmartPollFallback'), 100);
            }
          }, 300);
          
          // Method 4: Text-based content detection
          const textDetectionInterval = setInterval(() => {
            const viewerContent = document.getElementById('adviser-word-viewer-content');
            if (viewerContent && viewerContent.textContent.trim().length > 200) {
              console.log('[TextDetection] Substantial text content detected');
              clearInterval(textDetectionInterval);
              setTimeout(() => loadHighlightsWhenReady('TextDetection'), 100);
            }
          }, 500);
          
          // Method 5: Final fallback
          setTimeout(() => {
            if (!highlightsLoaded) {
              console.log('[FinalFallback] Force loading highlights after 8 seconds');
              loadHighlightsWhenReady('FinalFallback');
            }
            clearInterval(textDetectionInterval);
          }, 8000);
        };
        
        // Start document loading and setup automatic highlight loading
        adviserWordViewer.loadDocument(fileId);
        setupAutomaticHighlightLoading();
      }

      // Load existing highlights - moved to global scope
      window.loadHighlights = function(chapterId) {
        console.log('loadHighlights called for chapter:', chapterId);
        fetch(`api/document_review.php?action=get_highlights&chapter_id=${chapterId}`)
          .then(response => response.json())
          .then(data => {
            console.log('Highlights API response:', data);
            if (data.success) {
              console.log('Applying', data.highlights.length, 'highlights');
              
              // Validate highlights before applying
              const validHighlights = data.highlights.filter((highlight, index) => {
                if (!highlight || typeof highlight !== 'object') {
                  console.error(`❌ Highlight ${index} is not a valid object:`, highlight);
                  return false;
                }
                if (!highlight.id) {
                  console.error(`❌ Highlight ${index} missing ID:`, highlight);
                  return false;
                }
                if (!highlight.highlighted_text) {
                  console.error(`❌ Highlight ${index} missing highlighted_text:`, highlight);
                  return false;
                }
                console.log(`✅ Highlight ${index} is valid:`, {
                  id: highlight.id,
                  text: highlight.highlighted_text.substring(0, 50) + '...',
                  color: highlight.highlight_color
                });
                return true;
              });
              
              console.log(`Filtered ${validHighlights.length}/${data.highlights.length} valid highlights`);
              
              if (validHighlights.length > 0) {
                applyHighlights(validHighlights);
              } else {
                console.error('❌ No valid highlights found to apply');
                showNotification('No valid highlights found', 'warning');
              }
            } else {
              console.error('Failed to load highlights:', data.error);
              showNotification('Failed to load highlights: ' + (data.error || 'Unknown error'), 'error');
            }
          })
          .catch(error => {
            console.error('Error loading highlights:', error);
            showNotification('Error loading highlights: ' + error.message, 'error');
          });
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
        console.log('=== APPLY HIGHLIGHTS START ===');
        console.log('Applying', highlights.length, 'highlights for adviser');
        console.log('Current chapter ID:', window.currentChapterId);
        
        // Try multiple selectors to find the document content (improved priority order)
        const possibleSelectors = [
          '.word-content',                           // WordViewer content (most common)
          '#adviser-word-viewer-content .word-content', // Nested word content
          '#adviser-word-viewer-content',            // Direct container
          '.word-document .word-content',            // Word content in document
          '.word-document',                          // Document container
          '.chapter-content',                        // Legacy content
          '.prose'                                   // Text content
        ];

        let contentElement = null;
        console.log('🔍 Searching for content elements...');
        
        for (const selector of possibleSelectors) {
          const element = document.querySelector(selector);
          if (element) {
            const textLength = element.textContent?.length || 0;
            console.log(`Found element with selector: ${selector}`);
            console.log(`  - Text length: ${textLength}`);
            console.log(`  - Has children: ${element.children.length}`);
            console.log(`  - Preview: "${element.textContent?.substring(0, 100)}..."`);
            
            // More flexible content detection
            if (textLength > 50 || element.children.length > 5) {
              contentElement = element;
              console.log('✅ Using content element with selector:', selector);
              break;
            } else {
              console.log('❌ Element too small, skipping');
            }
          } else {
            console.log(`❌ Selector not found: ${selector}`);
          }
        }

        if (!contentElement) {
          console.error('❌ No suitable content element found for highlight application');
          console.log('Available elements:', possibleSelectors.map(sel => ({
            selector: sel, 
            found: !!document.querySelector(sel),
            textLength: document.querySelector(sel)?.textContent?.length || 0
          })));
          
          // If no content found, try waiting for Word viewer to load
          console.log('🔄 Content not ready, waiting for Word viewer...');
          setTimeout(() => {
            console.log('🔄 Retrying highlight application after delay...');
            applyHighlights(highlights); // Retry once after delay
          }, 2000);
          return;
        }
        
        console.log('✅ Content element found:', contentElement);
        console.log('Content preview:', contentElement.textContent.substring(0, 100));
        
        // Clear existing highlights first to prevent duplicates
        const existingHighlights = contentElement.querySelectorAll('.highlight-marker');
        console.log('Removing', existingHighlights.length, 'existing highlights');
        existingHighlights.forEach(highlight => {
          const parent = highlight.parentNode;
          if (parent) {
            parent.insertBefore(document.createTextNode(highlight.textContent), highlight);
            parent.removeChild(highlight);
          }
        });
        
        let appliedCount = 0;
        highlights.forEach((highlight, index) => {
          console.log(`Applying highlight ${index + 1}/${highlights.length}:`, highlight.highlighted_text);
          const success = applyHighlightToAdviserContent(highlight, contentElement);
          if (success) appliedCount++;
        });
        
        console.log(`✅ Applied ${appliedCount}/${highlights.length} highlights successfully`);
        console.log('=== APPLY HIGHLIGHTS END ===');
        
        // Show user feedback about highlight loading
        if (highlights.length > 0) {
          if (appliedCount === highlights.length) {
            console.log(`🎉 All ${appliedCount} highlights loaded successfully!`);
            // Only show notification if we actually applied highlights
            if (appliedCount > 0) {
              setTimeout(() => {
                showNotification(`✅ Loaded ${appliedCount} highlight${appliedCount > 1 ? 's' : ''} successfully!`, 'success');
              }, 500);
            }
          } else if (appliedCount > 0) {
            console.log(`⚠️ Partially loaded: ${appliedCount}/${highlights.length} highlights`);
            setTimeout(() => {
              showNotification(`⚠️ Loaded ${appliedCount}/${highlights.length} highlights. Some may be missing.`, 'warning');
            }, 500);
          } else {
            console.log(`❌ Failed to load any highlights`);
            setTimeout(() => {
              showNotification(`❌ Failed to load highlights. Try: reloadHighlights()`, 'error');
            }, 500);
          }
        }
      }

      // Apply individual highlight to adviser content
      function applyHighlightToAdviserContent(highlight, contentElement) {
        // Validate highlight object
        if (!highlight || !highlight.id) {
          console.error('❌ Invalid highlight object:', highlight);
          return false;
        }
        
        if (!highlight.highlighted_text) {
          console.error('❌ Highlight missing text content:', highlight);
          return false;
        }
        
        // Check if this highlight already exists to prevent duplicates
        const existingHighlight = document.querySelector(`[data-highlight-id="${highlight.id}"]`);
        if (existingHighlight) {
          console.log('Highlight already exists, skipping:', highlight.id);
          return true; // Consider this a success since it exists
        }
        
        // Find text nodes containing the highlighted text
        const walker = document.createTreeWalker(
          contentElement,
          NodeFilter.SHOW_TEXT,
          null,
          false
        );
        
        let node;
        let foundMatch = false;
        while ((node = walker.nextNode()) && !foundMatch) {
          const text = node.textContent || '';
          const highlightText = highlight.highlighted_text || '';
          
          if (!text || !highlightText) {
            continue; // Skip if either is empty
          }
          
          const index = text.indexOf(highlightText);
          
          if (index !== -1) {
            try {
              console.log(`✅ Found text match for "${highlightText}" in node:`, text.substring(Math.max(0, index - 20), index + highlightText.length + 20));
              
              // Create highlight span
              const highlightSpan = document.createElement('mark');
              highlightSpan.style.cssText = `
                background-color: ${highlight.highlight_color || '#ffeb3b'} !important;
                padding: 2px 4px !important;
                border-radius: 3px !important;
                position: relative !important;
                cursor: pointer !important;
                display: inline !important;
                z-index: 1 !important;
                transition: all 0.2s ease !important;
              `;
              highlightSpan.className = 'highlight-marker fullscreen-highlight';
              highlightSpan.dataset.highlightId = highlight.id;
              highlightSpan.title = `Highlighted by ${highlight.adviser_name} - Click to comment, Right-click to remove`;
              
              // Add click handler for commenting on highlights
              highlightSpan.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.openHighlightCommentModal(highlight.id, highlight.highlighted_text, window.currentChapterId);
              });
              
              // Add context menu for removing highlights
              highlightSpan.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                if (confirm('Remove this highlight?')) {
                  window.removeHighlight(highlight.id);
                  // Reload highlights to refresh the view
                  setTimeout(() => {
                    if (window.currentChapterId) {
                      window.loadHighlights(window.currentChapterId);
                    }
                  }, 500);
                }
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
              console.log('✅ Successfully applied adviser highlight to text');
              foundMatch = true; // Only apply to first occurrence
              return true; // Success
            } catch (e) {
              console.error('❌ Error applying adviser highlight:', e);
              return false; // Failure
            }
          }
        }
        
        if (!foundMatch) {
          console.warn(`⚠️ Could not find text "${highlight.highlighted_text}" in content for highlight ID ${highlight.id}`);
          return false; // Text not found
        }
        
        return false; // Should not reach here
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
      window.enableHighlightMode = function enableHighlightMode(container, highlightBtn) {
        let highlightMode = false;
        let selectedText = '';
        let selectedRange = null;

        function resetHighlightMode() {
          highlightMode = false;
          container.style.cursor = 'default';
          if (highlightBtn) {
            // Handle different button text formats
            const currentText = highlightBtn.textContent || highlightBtn.innerHTML;
            if (currentText.includes('Cancel')) {
              highlightBtn.innerHTML = highlightBtn.innerHTML.replace('Cancel Highlight', 'Highlight');
            }
            // Reset button styling
            highlightBtn.classList.remove('bg-red-100', 'text-red-800');
            if (!highlightBtn.classList.contains('toolbar-action-btn')) {
              highlightBtn.classList.add('toolbar-action-btn');
            }
          }
        }

        if (!container) return;

        highlightBtn.addEventListener('click', function() {
          highlightMode = !highlightMode;
          container.style.cursor = highlightMode ? 'crosshair' : 'default';
          
          if (highlightMode) {
            // Update button appearance for active highlight mode
            const currentHTML = this.innerHTML;
            this.innerHTML = currentHTML.replace('Highlight', 'Cancel Highlight');
            this.classList.add('bg-red-100', 'text-red-800');
            this.classList.remove('toolbar-action-btn');
            showNotification('Highlight mode active. Select text to highlight it.', 'info');
          } else {
            resetHighlightMode();
          }
        });

        container.addEventListener('mouseup', function(e) {
          if (highlightMode) {
            const selection = window.getSelection();
            if (selection.toString().trim().length > 0) {
              selectedText = selection.toString().trim();
              selectedRange = selection.getRangeAt(0);
              
              // Ensure we have a chapter ID
              if (window.currentChapterId) {
                window.addHighlightGeneric(selectedText, selectedRange, window.currentChapterId, container);
                resetHighlightMode();
              } else {
                showNotification('No chapter selected. Please select a chapter first.', 'warning');
              }
            }
          }
        });

        // Handle text selection events for better UX
        container.addEventListener('mousedown', function(e) {
          if (highlightMode) {
            // Clear any existing selection
            window.getSelection().removeAllRanges();
          }
        });
      }

      window.enableCommentMode = function enableCommentMode(container, commentBtn) {
        if (!container) return;
        
        let commentMode = false;
        
        function resetCommentMode() {
          commentMode = false;
          container.style.cursor = 'default';
          if (commentBtn) {
            commentBtn.textContent = commentBtn.textContent.replace('Cancel Comment', 'Comment');
            commentBtn.classList.remove('bg-red-100', 'text-red-800');
            commentBtn.classList.add('toolbar-action-btn');
          }
        }
        
        commentBtn.addEventListener('click', function() {
          commentMode = !commentMode;
          container.style.cursor = commentMode ? 'crosshair' : 'default';
          
          if (commentMode) {
            this.textContent = this.textContent.replace('Comment', 'Cancel Comment');
            this.classList.add('bg-red-100', 'text-red-800');
            this.classList.remove('toolbar-action-btn');
            showNotification('Comment mode active. Click on any paragraph to add a comment.', 'info');
          } else {
            resetCommentMode();
          }
        });
        
        // Add click handler for paragraphs in the container
        container.addEventListener('click', function(e) {
          if (!commentMode) return;
          
          // Find the closest paragraph element
          const paragraph = e.target.closest('.word-paragraph, p, div[data-paragraph-id]');
          if (paragraph) {
            const paragraphText = paragraph.textContent.trim();
            const paragraphId = paragraph.dataset.paragraphId || paragraph.id || 'para_' + Date.now();
            
            if (paragraphText.length > 0) {
              window.openParagraphCommentModal(paragraphId, paragraphText);
              resetCommentMode();
            }
          }
        });
      }

      // Generic addHighlight function for both main and fullscreen
      window.addHighlightGeneric = function addHighlightGeneric(selectedText, selectedRange, chapterId, container) {
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
              
              // Add click handler for commenting on highlights
              highlightSpan.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.openHighlightCommentModal(data.highlight_id, selectedText, window.currentChapterId);
              });
              
              // Add context menu for removing highlights
              highlightSpan.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                if (confirm('Remove this highlight?')) {
                  removeHighlight(data.highlight_id);
                  highlightSpan.remove();
                }
              });
              
              try {
                selectedRange.surroundContents(highlightSpan);
              } catch (e) {
                highlightSpan.textContent = selectedText;
                selectedRange.deleteContents();
                selectedRange.insertNode(highlightSpan);
              }
            }
            window.getSelection().removeAllRanges();
            showNotification('Text highlighted successfully!', 'success');
          } else {
            showNotification('Failed to add highlight: ' + data.error, 'error');
          }
        })
        .catch(error => {
          showNotification('Failed to add highlight: ' + error.message, 'error');
        });
      }

      // Add comment to a specific highlight
      window.addHighlightComment = function addHighlightComment(commentText, chapterId, highlightId) {
        if (!commentText || !chapterId || !highlightId) return;
        
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('chapter_id', chapterId);
        formData.append('comment_text', commentText);
        formData.append('highlight_id', highlightId);

        fetch('api/document_review.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification('Comment added to highlight successfully!', 'success');
            
            // Add visual indicator to the highlight
            const highlightElement = document.querySelector(`[data-highlight-id="${highlightId}"]`);
            if (highlightElement) {
              highlightElement.classList.add('has-comment');
              highlightElement.title = highlightElement.title + ' (Has comments)';
              
              // Add a small comment indicator
              if (!highlightElement.querySelector('.comment-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'comment-indicator';
                indicator.innerHTML = '💬';
                indicator.style.cssText = 'position: absolute; top: -8px; right: -8px; background: #3b82f6; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center; z-index: 10;';
                highlightElement.style.position = 'relative';
                highlightElement.appendChild(indicator);
              }
            }
            
            // Reload comments in sidebar if function exists and we're not in fullscreen
            if (typeof loadComments === 'function' && !document.querySelector('.document-fullscreen-modal.active')) {
              loadComments(chapterId);
            }
          } else {
            showNotification('Failed to add comment to highlight: ' + data.error, 'error');
          }
        })
        .catch(error => {
          showNotification('Failed to add comment to highlight: ' + error.message, 'error');
        });
      }

      // Generic addComment function for both main and fullscreen
      window.addCommentGeneric = function addCommentGeneric(commentText, chapterId, paragraphId = null) {
        if (!commentText || !chapterId) return;
        
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('chapter_id', chapterId);
        formData.append('comment_text', commentText);
        
        if (paragraphId) {
          formData.append('paragraph_id', paragraphId);
        }

        fetch('api/document_review.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification('Comment added successfully!', 'success');
            
            // Reload comments in sidebar if function exists and we're not in fullscreen
            if (typeof loadComments === 'function' && !document.querySelector('.document-fullscreen-modal.active')) {
              loadComments(chapterId);
            }
          } else {
            showNotification('Failed to add comment: ' + data.error, 'error');
          }
        })
        .catch(error => {
          showNotification('Failed to add comment: ' + error.message, 'error');
        });
      }

      // Open highlight comment modal (for commenting on highlights)
      window.openHighlightCommentModal = function openHighlightCommentModal(highlightId, highlightedText, chapterId) {
        // Remove existing modal if any
        const existingModal = document.getElementById('highlight-comment-modal');
        if (existingModal) {
          existingModal.remove();
        }
        
        // Check if we're in fullscreen mode
        const isFullscreen = document.querySelector('.document-fullscreen-modal.active') !== null;
        console.log('[Comment Modal] Opening in fullscreen mode:', isFullscreen);
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'highlight-comment-modal';
        // Use higher z-index for fullscreen mode
        const zIndexClass = isFullscreen ? 'z-[9999]' : 'z-50';
        modal.className = `fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center ${zIndexClass}`;
        modal.style.zIndex = isFullscreen ? '9999' : '50'; // Ensure it works even without Tailwind
        modal.innerHTML = `
          <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[80vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4">Comment on Highlight</h3>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Highlighted Text:</label>
              <div class="p-3 bg-yellow-100 rounded text-sm max-h-32 overflow-y-auto border border-yellow-300">
                <mark style="background-color: #fef3c7; padding: 2px 4px; border-radius: 3px;">${highlightedText}</mark>
              </div>
            </div>
            <div class="mb-4">
              <label for="highlight-comment-text" class="block text-sm font-medium text-gray-700 mb-2">Your Comment:</label>
              <textarea id="highlight-comment-text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your comment about this highlighted text..."></textarea>
            </div>
            <div class="flex justify-end space-x-2">
              <button id="cancel-highlight-comment" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
              <button id="save-highlight-comment" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Comment</button>
            </div>
          </div>
        `;
        
        document.body.appendChild(modal);
        
        // Focus the textarea
        const textarea = document.getElementById('highlight-comment-text');
        setTimeout(() => textarea.focus(), 100);
        
        // Add event listeners
        document.getElementById('cancel-highlight-comment').addEventListener('click', () => {
          modal.remove();
        });
        
        document.getElementById('save-highlight-comment').addEventListener('click', () => {
          const commentText = document.getElementById('highlight-comment-text').value.trim();
          if (commentText && chapterId) {
            window.addHighlightComment(commentText, chapterId, highlightId);
            modal.remove();
          } else {
            showNotification('Please enter a comment', 'warning');
          }
        });
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
          if (e.target === modal) {
            modal.remove();
          }
        });
        
        // Close on Escape key
        const handleEscape = (e) => {
          if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', handleEscape);
          }
        };
        document.addEventListener('keydown', handleEscape);
      }

      // Open paragraph comment modal (for use in fullscreen and main view)
      window.openParagraphCommentModal = function openParagraphCommentModal(paragraphId, paragraphText) {
        // Remove existing modal if any
        const existingModal = document.getElementById('paragraph-comment-modal');
        if (existingModal) {
          existingModal.remove();
        }
        
        // Check if we're in fullscreen mode
        const isFullscreen = document.querySelector('.document-fullscreen-modal.active') !== null;
        console.log('[Paragraph Comment Modal] Opening in fullscreen mode:', isFullscreen);
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'paragraph-comment-modal';
        // Use higher z-index for fullscreen mode
        const zIndexClass = isFullscreen ? 'z-[9999]' : 'z-50';
        modal.className = `fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center ${zIndexClass}`;
        modal.style.zIndex = isFullscreen ? '9999' : '50'; // Ensure it works even without Tailwind
        modal.innerHTML = `
          <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[80vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4">Add Comment to Paragraph</h3>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Selected Paragraph:</label>
              <div class="p-3 bg-gray-100 rounded text-sm max-h-32 overflow-y-auto border">${paragraphText.substring(0, 500)}${paragraphText.length > 500 ? '...' : ''}</div>
            </div>
            <div class="mb-4">
              <label for="paragraph-comment-text" class="block text-sm font-medium text-gray-700 mb-2">Your Comment:</label>
              <textarea id="paragraph-comment-text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your comment about this paragraph..."></textarea>
            </div>
            <div class="flex justify-end space-x-2">
              <button id="cancel-paragraph-comment" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
              <button id="save-paragraph-comment" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Comment</button>
            </div>
          </div>
        `;
        
        document.body.appendChild(modal);
        
        // Focus the textarea
        const textarea = document.getElementById('paragraph-comment-text');
        setTimeout(() => textarea.focus(), 100);
        
        // Add event listeners
        document.getElementById('cancel-paragraph-comment').addEventListener('click', () => {
          modal.remove();
        });
        
        document.getElementById('save-paragraph-comment').addEventListener('click', () => {
          const commentText = document.getElementById('paragraph-comment-text').value.trim();
          if (commentText && window.currentChapterId) {
            window.addCommentGeneric(commentText, window.currentChapterId, paragraphId);
            modal.remove();
          } else {
            showNotification('Please enter a comment', 'warning');
          }
        });
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
          if (e.target === modal) {
            modal.remove();
          }
        });
        
        // Close on Escape key
        const handleEscape = (e) => {
          if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', handleEscape);
          }
        };
        document.addEventListener('keydown', handleEscape);
      }

      // Function to check and update highlight comment indicators
      window.updateHighlightCommentIndicators = function updateHighlightCommentIndicators(chapterId) {
        if (!chapterId) return;
        
        fetch(`api/document_review.php?action=get_comments&chapter_id=${chapterId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.comments) {
              // Group comments by highlight_id
              const highlightComments = {};
              data.comments.forEach(comment => {
                if (comment.highlight_id) {
                  if (!highlightComments[comment.highlight_id]) {
                    highlightComments[comment.highlight_id] = [];
                  }
                  highlightComments[comment.highlight_id].push(comment);
                }
              });
              
              // Update visual indicators for highlights with comments
              Object.keys(highlightComments).forEach(highlightId => {
                const highlightElement = document.querySelector(`[data-highlight-id="${highlightId}"]`);
                if (highlightElement) {
                  highlightElement.classList.add('has-comment');
                  const commentCount = highlightComments[highlightId].length;
                  highlightElement.title = highlightElement.title + ` (${commentCount} comment${commentCount > 1 ? 's' : ''})`;
                  
                  // Add comment indicator if not already present
                  if (!highlightElement.querySelector('.comment-indicator')) {
                    const indicator = document.createElement('span');
                    indicator.className = 'comment-indicator';
                    indicator.innerHTML = '💬';
                    indicator.style.cssText = 'position: absolute; top: -8px; right: -8px; background: #3b82f6; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center; z-index: 10;';
                    highlightElement.style.position = 'relative';
                    highlightElement.appendChild(indicator);
                  }
                }
              });
            }
          })
          .catch(error => console.error('Error loading highlight comments:', error));
      };

      // Load highlights in fullscreen view with improved retry logic - make it globally accessible
      window.loadHighlightsInFullscreen = function(chapterId, attempt = 1, maxAttempts = 5) {
        if (!chapterId) return;
        
        console.log(`[Fullscreen] Loading highlights for chapter: ${chapterId} (attempt ${attempt}/${maxAttempts})`);
        
        // First check if content is available with better detection
        const possibleSelectors = [
          '#fullscreen-document-content-content',
          '#fullscreen-document-content .word-content',
          '#fullscreen-document-content'
        ];
        
        let hasContent = false;
        let bestContent = null;
        for (const selector of possibleSelectors) {
          const element = document.querySelector(selector);
          if (element) {
            const textLength = element.textContent.length;
            const hasChildren = element.children.length > 0;
            const hasActualText = textLength > 100 && !element.textContent.includes('Loading') && !element.textContent.includes('Error');
            
            console.log(`[Fullscreen] Checking ${selector}: text=${textLength}, children=${hasChildren}, valid=${hasActualText}`);
            
            if (hasActualText || (hasChildren && textLength > 50)) {
              hasContent = true;
              bestContent = element;
              console.log(`[Fullscreen] ✅ Found suitable content with ${selector}`);
              break;
            }
          }
        }
        
        if (!hasContent && attempt < maxAttempts) {
          console.log(`[Fullscreen] Content not ready yet, retrying in 1.5 seconds (attempt ${attempt}/${maxAttempts})`);
          setTimeout(() => window.loadHighlightsInFullscreen(chapterId, attempt + 1, maxAttempts), 1500);
          return;
        }
        
        if (!hasContent) {
          console.log('[Fullscreen] Content still not ready after max attempts, proceeding anyway');
        } else {
          console.log('[Fullscreen] ✅ Content ready, proceeding with highlight loading');
        }
        
        fetch(`api/document_review.php?action=get_highlights&chapter_id=${chapterId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.highlights) {
              console.log('[Fullscreen] Loaded', data.highlights.length, 'highlights');
              
              // Validate API response structure
              if (!Array.isArray(data.highlights)) {
                console.error('[Fullscreen] API returned non-array highlights:', data.highlights);
                return;
              }
              
              // Add debugging to identify problematic highlights
              data.highlights.forEach((highlight, index) => {
                console.log(`[Fullscreen] Highlight ${index + 1}:`, {
                  id: highlight.id,
                  text: highlight.highlighted_text ? highlight.highlighted_text.substring(0, 50) + '...' : 'UNDEFINED',
                  color: highlight.highlight_color,
                  adviser: highlight.adviser_name
                });
              });
              
              try {
                window.applyHighlightsToFullscreen(data.highlights);
              } catch (error) {
                console.error('[Fullscreen] Error applying highlights:', error);
                console.error('[Fullscreen] Problematic highlights data:', data.highlights);
              }
            } else {
              console.log('[Fullscreen] No highlights found or error:', data.error);
            }
          })
          .catch(error => console.error('Error loading highlights for fullscreen:', error));
          
        // Also load comments to mark paragraphs
        fetch(`api/document_review.php?action=get_comments&chapter_id=${chapterId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.comments) {
              console.log('[Fullscreen] Loaded', data.comments.length, 'comments');
              window.markCommentedParagraphsInFullscreen(data.comments);
            } else {
              console.log('[Fullscreen] No comments found or error:', data.error);
            }
          })
          .catch(error => console.error('Error loading comments for fullscreen:', error));
      };
      
      // Debug function to manually fix highlights in fullscreen
      window.debugFixFullscreenHighlights = function() {
        console.log('🔧 [DEBUG] Attempting to fix fullscreen highlights...');
        
        if (!window.currentChapterId) {
          console.error('❌ No current chapter ID found');
          return;
        }
        
        // Clear existing highlights first
        const existingHighlights = document.querySelectorAll('.highlight-marker, .fullscreen-highlight');
        console.log(`🔧 [DEBUG] Clearing ${existingHighlights.length} existing highlights`);
        existingHighlights.forEach(highlight => {
          const parent = highlight.parentNode;
          if (parent) {
            parent.insertBefore(document.createTextNode(highlight.textContent), highlight);
            parent.removeChild(highlight);
          }
        });
        
        // Force reload highlights
        console.log('🔧 [DEBUG] Force loading highlights...');
        window.loadHighlightsInFullscreen(window.currentChapterId, 1, 1);
        
        // Also try direct application after a delay
        setTimeout(() => {
          console.log('🔧 [DEBUG] Attempting direct highlight application...');
          fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
            .then(response => response.json())
            .then(data => {
              if (data.success && data.highlights) {
                console.log(`🔧 [DEBUG] Got ${data.highlights.length} highlights from API`);
                window.applyHighlightsToFullscreen(data.highlights);
              }
            })
            .catch(error => console.error('🔧 [DEBUG] Error:', error));
        }, 2000);
      };

      // Advanced debug function to inspect highlight visibility issues
      window.debugHighlightVisibility = function() {
        console.log('🔍 [VISIBILITY DEBUG] Checking highlight visibility...');
        
        const highlights = document.querySelectorAll('.highlight-marker, .fullscreen-highlight, mark[data-highlight-id]');
        console.log(`🔍 Found ${highlights.length} highlights in DOM`);
        
        highlights.forEach((highlight, index) => {
          const styles = window.getComputedStyle(highlight);
          const rect = highlight.getBoundingClientRect();
          
          console.log(`🔍 Highlight ${index + 1}:`, {
            element: highlight,
            text: highlight.textContent.substring(0, 30) + '...',
            classes: highlight.className,
            id: highlight.dataset.highlightId,
            visible: styles.visibility,
            opacity: styles.opacity,
            display: styles.display,
            backgroundColor: styles.backgroundColor,
            zIndex: styles.zIndex,
            position: styles.position,
            bounds: {
              width: rect.width,
              height: rect.height,
              top: rect.top,
              left: rect.left
            },
            inViewport: rect.top >= 0 && rect.left >= 0 && rect.bottom <= window.innerHeight && rect.right <= window.innerWidth
          });
          
          // Force visibility if hidden
          if (styles.visibility === 'hidden' || styles.opacity === '0' || styles.display === 'none') {
            console.log(`🔧 Fixing visibility for highlight ${index + 1}`);
            highlight.style.cssText += `
              visibility: visible !important;
              opacity: 1 !important;
              display: inline !important;
              background-color: #ffeb3b !important;
              z-index: 1000 !important;
            `;
          }
        });
        
        return highlights.length;
      };

      // Force apply highlights with better styling
      window.forceApplyHighlightsWithStyling = function() {
        console.log('🔧 [FORCE APPLY] Starting forced highlight application...');
        
        if (!window.currentChapterId) {
          console.error('❌ No current chapter ID found');
          return;
        }
        
        fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.highlights) {
              console.log(`🔧 [FORCE APPLY] Got ${data.highlights.length} highlights from API`);
              
              // Find fullscreen content
              const contentSelectors = [
                '#fullscreen-document-content-content',
                '#fullscreen-document-content .word-content',
                '#fullscreen-document-content'
              ];
              
              let contentElement = null;
              for (const selector of contentSelectors) {
                const element = document.querySelector(selector);
                if (element && element.textContent.length > 100) {
                  contentElement = element;
                  console.log(`🔧 [FORCE APPLY] Using content element: ${selector}`);
                  break;
                }
              }
              
              if (!contentElement) {
                console.error('🔧 [FORCE APPLY] No content element found');
                return;
              }
              
              // Clear existing highlights
              const existingHighlights = contentElement.querySelectorAll('.highlight-marker, .fullscreen-highlight, mark[data-highlight-id]');
              console.log(`🔧 [FORCE APPLY] Clearing ${existingHighlights.length} existing highlights`);
              existingHighlights.forEach(highlight => {
                const parent = highlight.parentNode;
                if (parent) {
                  parent.insertBefore(document.createTextNode(highlight.textContent), highlight);
                  parent.removeChild(highlight);
                }
              });
              
              // Force apply each highlight with explicit styling
              data.highlights.forEach((highlight, index) => {
                console.log(`🔧 [FORCE APPLY] Processing highlight ${index + 1}: "${highlight.highlighted_text}"`);
                
                const walker = document.createTreeWalker(
                  contentElement,
                  NodeFilter.SHOW_TEXT,
                  null,
                  false
                );
                
                let node;
                while ((node = walker.nextNode())) {
                  const text = node.textContent;
                  const highlightText = highlight.highlighted_text.trim();
                  const index = text.indexOf(highlightText);
                  
                  if (index !== -1) {
                    try {
                      console.log(`🔧 [FORCE APPLY] Found match, applying highlight...`);
                      
                      // Create highly visible highlight
                      const highlightSpan = document.createElement('mark');
                      highlightSpan.style.cssText = `
                        background-color: ${highlight.highlight_color || '#ffeb3b'} !important;
                        color: #000 !important;
                        padding: 3px 6px !important;
                        border-radius: 4px !important;
                        border: 2px solid #f59e0b !important;
                        position: relative !important;
                        cursor: pointer !important;
                        display: inline !important;
                        z-index: 1000 !important;
                        font-weight: bold !important;
                        visibility: visible !important;
                        opacity: 1 !important;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
                      `;
                      
                      highlightSpan.className = 'highlight-marker fullscreen-highlight force-applied';
                      highlightSpan.dataset.highlightId = highlight.id;
                      highlightSpan.title = `Highlighted by ${highlight.adviser_name} - Force Applied`;
                      
                      // Split text and apply highlight
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
                      
                      node.parentNode.removeChild(node);
                      
                      console.log(`🔧 [FORCE APPLY] ✅ Successfully applied highlight ${index + 1}`);
                      break;
                    } catch (error) {
                      console.error(`🔧 [FORCE APPLY] Error applying highlight ${index + 1}:`, error);
                    }
                  }
                }
              });
              
              // Verify highlights are visible
              setTimeout(() => {
                const appliedHighlights = document.querySelectorAll('.force-applied');
                console.log(`🔧 [FORCE APPLY] ✅ Force applied ${appliedHighlights.length} highlights`);
                
                appliedHighlights.forEach((highlight, index) => {
                  const styles = window.getComputedStyle(highlight);
                  console.log(`🔧 [FORCE APPLY] Highlight ${index + 1} styles:`, {
                    backgroundColor: styles.backgroundColor,
                    visibility: styles.visibility,
                    opacity: styles.opacity,
                    zIndex: styles.zIndex
                  });
                });
              }, 100);
              
            } else {
              console.error('🔧 [FORCE APPLY] No highlights found in API response');
            }
          })
                     .catch(error => console.error('🔧 [FORCE APPLY] Error:', error));
       };

      // Quick fix function to make existing highlights visible (works in both normal and fullscreen)
      window.quickFixHighlights = function() {
        console.log('⚡ [QUICK FIX] Checking and fixing all highlights...');
        
        // Check if we're in fullscreen mode
        const fullscreenModal = document.querySelector('.document-fullscreen-modal.active');
        const isFullscreen = fullscreenModal !== null;
        
        console.log(`⚡ [QUICK FIX] Mode: ${isFullscreen ? 'FULLSCREEN' : 'NORMAL'}`);
        
        let searchContainer = document;
        if (isFullscreen) {
          // In fullscreen mode, search within the fullscreen container
          const fullscreenContainers = [
            '#fullscreen-document-content-content',
            '#fullscreen-document-content .word-content',
            '#fullscreen-document-content'
          ];
          
          for (const selector of fullscreenContainers) {
            const container = document.querySelector(selector);
            if (container && container.textContent.length > 100) {
              searchContainer = container;
              console.log(`⚡ [QUICK FIX] Using fullscreen container: ${selector}`);
              break;
            }
          }
        }
        
        const highlights = searchContainer.querySelectorAll('[data-highlight-id], .highlight-marker, .fullscreen-highlight');
        console.log(`⚡ [QUICK FIX] Found ${highlights.length} highlights to check in ${isFullscreen ? 'fullscreen' : 'normal'} mode`);
        
        let fixedCount = 0;
        highlights.forEach((highlight, index) => {
          const styles = window.getComputedStyle(highlight);
          const isVisible = styles.visibility !== 'hidden' && styles.opacity !== '0' && styles.display !== 'none';
          const hasBackground = styles.backgroundColor !== 'rgba(0, 0, 0, 0)' && styles.backgroundColor !== 'transparent';
          
          console.log(`⚡ [QUICK FIX] Highlight ${index + 1}:`, {
            text: highlight.textContent.substring(0, 30) + '...',
            visible: isVisible,
            hasBackground: hasBackground,
            backgroundColor: styles.backgroundColor,
            opacity: styles.opacity,
            visibility: styles.visibility,
            zIndex: styles.zIndex
          });
          
          if (!isVisible || !hasBackground) {
            console.log(`⚡ [QUICK FIX] Fixing highlight ${index + 1}...`);
            highlight.style.cssText = `
              background-color: #ffeb3b !important;
              color: #000 !important;
              position: relative !important;
              cursor: pointer !important;
              display: inline !important;
              z-index: 1000 !important;
              padding: 2px 4px !important;
              border-radius: 3px !important;
              border: 1px solid rgba(0,0,0,0.2) !important;
              visibility: visible !important;
              opacity: 1 !important;
              font-weight: normal !important;
              text-decoration: none !important;
            `;
            fixedCount++;
          } else {
            // Even if it looks visible, ensure it has proper styling for fullscreen
            if (isFullscreen) {
              console.log(`⚡ [QUICK FIX] Enhancing fullscreen highlight ${index + 1}...`);
              highlight.style.cssText += `
                background-color: #ffeb3b !important;
                z-index: 1000 !important;
                padding: 2px 4px !important;
                border-radius: 3px !important;
                border: 1px solid rgba(0,0,0,0.2) !important;
                visibility: visible !important;
                opacity: 1 !important;
              `;
              fixedCount++;
            }
          }
        });
        
        console.log(`⚡ [QUICK FIX] ✅ Fixed ${fixedCount} out of ${highlights.length} highlights`);
        
        if (fixedCount === 0 && highlights.length === 0) {
          console.log('⚡ [QUICK FIX] No highlights found. Try running: forceApplyHighlightsWithStyling()');
        }
        
        // If we're in fullscreen and still no highlights, try to reload them
        if (isFullscreen && highlights.length === 0) {
          console.log('⚡ [QUICK FIX] No highlights found in fullscreen, attempting to reload...');
          if (window.currentChapterId) {
            window.loadHighlightsInFullscreen(window.currentChapterId);
          }
        }
        
        return fixedCount;
      };

      // Specific function for fullscreen highlight fixing
      window.fixFullscreenHighlights = function() {
        console.log('🔧 [FULLSCREEN FIX] Starting fullscreen highlight fix...');
        
        // Ensure we're in fullscreen mode
        const fullscreenModal = document.querySelector('.document-fullscreen-modal.active');
        if (!fullscreenModal) {
          console.error('❌ Not in fullscreen mode. Use quickFixHighlights() for normal mode.');
          return 0;
        }
        
        // Find fullscreen content container
        const fullscreenContainers = [
          '#fullscreen-document-content-content',
          '#fullscreen-document-content .word-content',
          '#fullscreen-document-content'
        ];
        
        let contentContainer = null;
        for (const selector of fullscreenContainers) {
          const container = document.querySelector(selector);
          if (container && container.textContent.length > 100) {
            contentContainer = container;
            console.log(`🔧 [FULLSCREEN FIX] Using container: ${selector}`);
            break;
          }
        }
        
        if (!contentContainer) {
          console.error('❌ No fullscreen content container found');
          return 0;
        }
        
        // Find all highlights in the container
        const highlights = contentContainer.querySelectorAll('[data-highlight-id], .highlight-marker, .fullscreen-highlight');
        console.log(`🔧 [FULLSCREEN FIX] Found ${highlights.length} highlights in fullscreen container`);
        
        let fixedCount = 0;
        highlights.forEach((highlight, index) => {
          console.log(`🔧 [FULLSCREEN FIX] Processing highlight ${index + 1}:`, {
            text: highlight.textContent.substring(0, 30) + '...',
            id: highlight.dataset.highlightId,
            classes: highlight.className
          });
          
          // Apply enhanced fullscreen styling
          highlight.style.cssText = `
            background-color: #ffeb3b !important;
            color: #000 !important;
            position: relative !important;
            cursor: pointer !important;
            display: inline !important;
            z-index: 1000 !important;
            padding: 3px 6px !important;
            border-radius: 4px !important;
            border: 2px solid #f59e0b !important;
            visibility: visible !important;
            opacity: 1 !important;
            font-weight: bold !important;
            text-decoration: none !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
          `;
          
          // Add a class to identify fixed highlights
          highlight.classList.add('fullscreen-fixed');
          
          fixedCount++;
        });
        
        console.log(`🔧 [FULLSCREEN FIX] ✅ Enhanced ${fixedCount} highlights for fullscreen visibility`);
        
        // If no highlights found, try to reload them
        if (highlights.length === 0) {
          console.log('🔧 [FULLSCREEN FIX] No highlights found, attempting to reload...');
          if (window.currentChapterId) {
            window.loadHighlightsInFullscreen(window.currentChapterId);
            
            // Try fixing again after reload
            setTimeout(() => {
              console.log('🔧 [FULLSCREEN FIX] Retrying after reload...');
              window.fixFullscreenHighlights();
            }, 2000);
          }
        }
        
        return fixedCount;
      };

      // NUCLEAR OPTION: Force highlights to appear in fullscreen with extreme styling
      window.forceFullscreenHighlights = function() {
        console.log('💥 [NUCLEAR] Forcing highlights to appear in fullscreen...');
        
        if (!window.currentChapterId) {
          console.error('❌ No current chapter ID');
          return;
        }
        
        // Get highlights from API
        fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.highlights && data.highlights.length > 0) {
              console.log(`💥 [NUCLEAR] Got ${data.highlights.length} highlights from API`);
              
              // Find ALL possible fullscreen content containers
              const possibleContainers = [
                '#fullscreen-document-content-content',
                '#fullscreen-document-content .word-content',
                '#fullscreen-document-content',
                '.document-fullscreen-modal .word-content',
                '.document-fullscreen-modal .word-document',
                '.document-fullscreen-modal .word-page'
              ];
              
              let targetContainer = null;
              for (const selector of possibleContainers) {
                const container = document.querySelector(selector);
                if (container && container.textContent.trim().length > 100) {
                  targetContainer = container;
                  console.log(`💥 [NUCLEAR] Using container: ${selector} (${container.textContent.length} chars)`);
                  break;
                }
              }
              
              if (!targetContainer) {
                console.error('💥 [NUCLEAR] No suitable container found!');
                console.log('💥 [NUCLEAR] Available containers:');
                possibleContainers.forEach(selector => {
                  const el = document.querySelector(selector);
                  console.log(`  - ${selector}: ${el ? `Found (${el.textContent.length} chars)` : 'Not found'}`);
                });
                return;
              }
              
              // Clear any existing highlights
              targetContainer.querySelectorAll('.highlight-marker, .fullscreen-highlight, [data-highlight-id]').forEach(el => {
                const parent = el.parentNode;
                if (parent) {
                  parent.insertBefore(document.createTextNode(el.textContent), el);
                  parent.removeChild(el);
                }
              });
              
              // Apply each highlight with extreme visibility
              let appliedCount = 0;
              data.highlights.forEach((highlight, index) => {
                console.log(`💥 [NUCLEAR] Processing highlight ${index + 1}: "${highlight.highlighted_text}"`);
                
                const walker = document.createTreeWalker(
                  targetContainer,
                  NodeFilter.SHOW_TEXT,
                  null,
                  false
                );
                
                let node;
                while ((node = walker.nextNode())) {
                  const text = node.textContent;
                  const searchText = highlight.highlighted_text.trim();
                  const index = text.indexOf(searchText);
                  
                  if (index !== -1) {
                    try {
                      console.log(`💥 [NUCLEAR] Found match! Applying extreme highlight...`);
                      
                      // Create EXTREME highlight element
                      const highlightEl = document.createElement('span');
                      highlightEl.style.cssText = `
                        background-color: #ffeb3b !important;
                        background-image: linear-gradient(45deg, #ffeb3b, #fdd835) !important;
                        color: #000 !important;
                        padding: 4px 8px !important;
                        margin: 0 2px !important;
                        border-radius: 6px !important;
                        border: 3px solid #f57c00 !important;
                        box-shadow: 0 4px 8px rgba(0,0,0,0.3) !important;
                        position: relative !important;
                        display: inline-block !important;
                        z-index: 9999 !important;
                        font-weight: bold !important;
                        font-size: 16px !important;
                        text-shadow: 1px 1px 2px rgba(255,255,255,0.8) !important;
                        cursor: pointer !important;
                        visibility: visible !important;
                        opacity: 1 !important;
                        transform: scale(1.05) !important;
                      `;
                      
                      highlightEl.className = 'nuclear-highlight';
                      highlightEl.dataset.highlightId = highlight.id;
                      highlightEl.title = `NUCLEAR HIGHLIGHT by ${highlight.adviser_name}`;
                      
                      // Add pulsing animation
                      highlightEl.style.animation = 'pulse 2s infinite';
                      
                      // Split text and insert highlight
                      const beforeText = text.substring(0, index);
                      const afterText = text.substring(index + searchText.length);
                      
                      if (beforeText) {
                        const beforeNode = document.createTextNode(beforeText);
                        node.parentNode.insertBefore(beforeNode, node);
                      }
                      
                      highlightEl.textContent = searchText;
                      node.parentNode.insertBefore(highlightEl, node);
                      
                      if (afterText) {
                        const afterNode = document.createTextNode(afterText);
                        node.parentNode.insertBefore(afterNode, node);
                      }
                      
                      node.parentNode.removeChild(node);
                      
                      appliedCount++;
                      console.log(`💥 [NUCLEAR] ✅ Applied extreme highlight ${appliedCount}`);
                      break;
                      
                    } catch (error) {
                      console.error(`💥 [NUCLEAR] Error applying highlight:`, error);
                    }
                  }
                }
              });
              
              // Add pulsing animation CSS if not exists
              if (!document.querySelector('#nuclear-highlight-styles')) {
                const style = document.createElement('style');
                style.id = 'nuclear-highlight-styles';
                style.textContent = `
                  @keyframes pulse {
                    0% { transform: scale(1.05); }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1.05); }
                  }
                  .nuclear-highlight {
                    animation: pulse 2s infinite !important;
                  }
                `;
                document.head.appendChild(style);
              }
              
              console.log(`💥 [NUCLEAR] ✅ Applied ${appliedCount} EXTREME highlights!`);
              
              if (appliedCount > 0) {
                // Show success message
                setTimeout(() => {
                  alert(`💥 NUCLEAR SUCCESS! Applied ${appliedCount} extreme highlights. They should be impossible to miss now!`);
                }, 500);
              }
              
            } else {
              console.error('💥 [NUCLEAR] No highlights found in API response');
            }
          })
          .catch(error => {
            console.error('💥 [NUCLEAR] API Error:', error);
          });
      };

      // Global debug function for fullscreen
      window.debugFullscreenLoading = function() {
        console.log('=== FULLSCREEN DEBUG INFO ===');
        console.log('Current Chapter ID:', window.currentChapterId);
        console.log('Current File ID:', window.currentFileId);
        
        // Check all possible content containers
        const possibleSelectors = [
          '#fullscreen-document-content-content',
          '#fullscreen-document-content .word-content',
          '#fullscreen-document-content'
        ];
        
        console.log('Checking fullscreen content containers:');
        possibleSelectors.forEach(selector => {
          const element = document.querySelector(selector);
          console.log(`- ${selector}:`, element);
          if (element) {
            console.log(`  - Text length: ${element.textContent.length}`);
            console.log(`  - Children count: ${element.children.length}`);
            console.log(`  - Sample text: "${element.textContent.substring(0, 100)}..."`);
          }
        });
                
        // Find the best content container for analysis
        let bestContent = null;
        for (const selector of possibleSelectors) {
          const element = document.querySelector(selector);
          if (element && (element.children.length > 0 || element.textContent.length > 100)) {
            bestContent = element;
            console.log(`Using ${selector} for detailed analysis`);
            break;
          }
        }
        
        if (bestContent) {
          console.log('Content innerHTML length:', bestContent.innerHTML.length);
          console.log('Content children count:', bestContent.children.length);
          
          const highlights = bestContent.querySelectorAll('.fullscreen-highlight, .highlight-marker');
          console.log('Existing highlights:', highlights.length);
          highlights.forEach((h, i) => console.log(`  Highlight ${i+1}:`, h.textContent.substring(0, 50)));
          
          const commentIndicators = bestContent.querySelectorAll('.comment-indicator');
          console.log('Existing comment indicators:', commentIndicators.length);
          
          const commentedParagraphs = bestContent.querySelectorAll('.commented');
          console.log('Commented paragraphs:', commentedParagraphs.length);
          
          const wordParagraphs = bestContent.querySelectorAll('.word-paragraph, [data-paragraph-id]');
          console.log('Total paragraphs found:', wordParagraphs.length);
        } else {
          console.log('No suitable content container found for analysis');
        }
        
        if (window.currentChapterId) {
          console.log('Testing API calls...');
          
          // Test highlights API
          fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
            .then(response => response.json())
            .then(data => {
              console.log('Highlights API response:', data);
            })
            .catch(error => console.error('Highlights API error:', error));
            
          // Test comments API
          fetch(`api/document_review.php?action=get_comments&chapter_id=${window.currentChapterId}`)
            .then(response => response.json())
            .then(data => {
              console.log('Comments API response:', data);
            })
            .catch(error => console.error('Comments API error:', error));
        }
        
        console.log('=== END DEBUG INFO ===');
             };
       
       // Global test function for comment modal in fullscreen
       window.testCommentModal = function() {
         console.log('[Test] Testing comment modal in fullscreen...');
         
         if (!window.currentChapterId) {
           alert('No chapter selected. Please select a chapter first.');
           return;
         }
         
         // Test with fake highlight data
         const testHighlightId = 'test_' + Date.now();
         const testHighlightText = 'This is a test highlighted text for testing the comment modal functionality in fullscreen mode.';
         
         console.log('[Test] Opening comment modal with test data...');
         console.log('[Test] Highlight ID:', testHighlightId);
         console.log('[Test] Chapter ID:', window.currentChapterId);
         
         if (typeof window.openHighlightCommentModal === 'function') {
           window.openHighlightCommentModal(testHighlightId, testHighlightText, window.currentChapterId);
           showNotification('Comment modal test launched! Modal should appear above fullscreen.', 'info');
         } else {
           showNotification('openHighlightCommentModal function not found!', 'error');
         }
       };
       
       // Global function to auto-load highlights and comments in fullscreen  
       function autoLoadFullscreenContent() {
         console.log('[Fullscreen Auto-Load] Starting intelligent auto-load...');
         
         if (!window.currentChapterId) {
           alert('No chapter selected. Please select a chapter first.');
           return;
         }
         
         // First, wait for content to be ready
         const waitForContent = (attempts = 0) => {
           const maxAttempts = 8;
           const possibleSelectors = [
             '#fullscreen-document-content-content',
             '#fullscreen-document-content .word-content',
             '#fullscreen-document-content .word-paragraph'
           ];
           
           let foundContent = false;
           for (const selector of possibleSelectors) {
             const element = document.querySelector(selector);
             if (element && (element.children.length > 0 || element.textContent.length > 100)) {
               console.log(`[Auto-Load] Found content with: ${selector}`);
               foundContent = true;
               break;
             }
           }
           
           if (foundContent) {
             console.log('[Auto-Load] Content ready, loading highlights and comments...');
             window.loadHighlightsInFullscreen(window.currentChapterId);
             
             // Provide feedback
             setTimeout(() => {
               const highlights = document.querySelectorAll('.fullscreen-highlight, .highlight-marker').length;
               const comments = document.querySelectorAll('.fullscreen-comment-indicator').length;
               showNotification(`Auto-load completed! Found ${highlights} highlights and ${comments} comments.`, 'success');
             }, 1000);
           } else if (attempts < maxAttempts) {
             console.log(`[Auto-Load] Content not ready, waiting... (${attempts + 1}/${maxAttempts})`);
             setTimeout(() => waitForContent(attempts + 1), 800);
           } else {
             console.log('[Auto-Load] Content not ready after max attempts, trying anyway...');
             window.loadHighlightsInFullscreen(window.currentChapterId);
             showNotification('Auto-load attempted, but content may not be fully ready.', 'warning');
           }
         };
         
                   waitForContent();
        }
        
        // Make it available globally
        window.autoLoadFullscreenContent = autoLoadFullscreenContent;
       
       // Global function to reload highlights and comments in fullscreen
       window.reloadFullscreenHighlightsAndComments = function() {
        if (window.currentChapterId) {
          console.log('[Fullscreen] Manually reloading highlights and comments...');
          
                     // Clear existing highlights and comment indicators
           const possibleSelectors = [
             '#fullscreen-document-content-content',
             '#fullscreen-document-content .word-content',
             '#fullscreen-document-content'
           ];
           
           let fullscreenContent = null;
           for (const selector of possibleSelectors) {
             const element = document.querySelector(selector);
             if (element) {
               fullscreenContent = element;
               break;
             }
           }
           
           if (fullscreenContent) {
             console.log('[Fullscreen] Clearing existing highlights and comments...');
             
             // Remove existing highlights
             fullscreenContent.querySelectorAll('.fullscreen-highlight, .highlight-marker').forEach(highlight => {
               const parent = highlight.parentNode;
               if (parent) {
                 parent.insertBefore(document.createTextNode(highlight.textContent), highlight);
                 parent.removeChild(highlight);
               }
             });
             
             // Remove existing comment indicators (but preserve WordViewer's built-in ones)
             fullscreenContent.querySelectorAll('.comment-indicator.fullscreen-comment-indicator').forEach(indicator => {
               indicator.remove();
             });
             
             // Remove commented class
             fullscreenContent.querySelectorAll('.commented').forEach(element => {
               element.classList.remove('commented');
             });
             
             console.log('[Fullscreen] Cleanup completed');
           }
          
          // Reload highlights and comments
          setTimeout(() => {
            window.loadHighlightsInFullscreen(window.currentChapterId);
          }, 100);
        }
      };

      // Apply highlights to fullscreen content - USING WORKING NORMAL VIEW ALGORITHM
      window.applyHighlightsToFullscreen = function(highlights) {
        console.log('[Fullscreen] === APPLY HIGHLIGHTS START ===');
        console.log('[Fullscreen] Applying', highlights.length, 'highlights to fullscreen');
        console.log('[Fullscreen] Current chapter ID:', window.currentChapterId);
        
        // Try multiple selectors to find the fullscreen content (improved priority order)
        const possibleSelectors = [
          '#fullscreen-document-content-content', // WordViewer creates this (most common)
          '#fullscreen-document-content .word-content', // Nested word content
          '#fullscreen-document-content',            // Direct container
          '.word-document .word-content',            // Word content in document
          '.word-document',                          // Document container
          '.chapter-content',                        // Legacy content
          '.prose'                                   // Text content
        ];

        let contentElement = null;
        console.log('[Fullscreen] 🔍 Searching for content elements...');
        
        for (const selector of possibleSelectors) {
          const element = document.querySelector(selector);
          if (element) {
            const textLength = element.textContent?.length || 0;
            console.log(`[Fullscreen] Found element with selector: ${selector}`);
            console.log(`[Fullscreen]   - Text length: ${textLength}`);
            console.log(`[Fullscreen]   - Has children: ${element.children.length}`);
            console.log(`[Fullscreen]   - Preview: "${element.textContent?.substring(0, 100)}..."`);
            
            // More flexible content detection
            if (textLength > 50 || element.children.length > 5) {
              contentElement = element;
              console.log('[Fullscreen] ✅ Using content element with selector:', selector);
              break;
            } else {
              console.log('[Fullscreen] ❌ Element too small, skipping');
            }
          } else {
            console.log(`[Fullscreen] ❌ Selector not found: ${selector}`);
          }
        }

        if (!contentElement) {
          console.error('[Fullscreen] ❌ No suitable content element found for highlight application');
          console.log('[Fullscreen] Available elements:', possibleSelectors.map(sel => ({
            selector: sel, 
            found: !!document.querySelector(sel),
            textLength: document.querySelector(sel)?.textContent?.length || 0
          })));
          
          // If no content found, try waiting for Word viewer to load
          console.log('[Fullscreen] 🔄 Content not ready, waiting for Word viewer...');
          setTimeout(() => {
            console.log('[Fullscreen] 🔄 Retrying highlight application after delay...');
            window.applyHighlightsToFullscreen(highlights); // Retry once after delay
          }, 2000);
          return;
        }
        
        console.log('[Fullscreen] ✅ Content element found:', contentElement);
        console.log('[Fullscreen] Content preview:', contentElement.textContent.substring(0, 100));
        
        // Clear existing highlights first to prevent duplicates
        const existingHighlights = contentElement.querySelectorAll('.highlight-marker');
        console.log('[Fullscreen] Removing', existingHighlights.length, 'existing highlights');
        existingHighlights.forEach(highlight => {
          const parent = highlight.parentNode;
          if (parent) {
            parent.insertBefore(document.createTextNode(highlight.textContent), highlight);
            parent.removeChild(highlight);
          }
        });
        
        let appliedCount = 0;
        highlights.forEach((highlight, index) => {
          console.log(`[Fullscreen] Applying highlight ${index + 1}/${highlights.length}:`, highlight.highlighted_text);
          const success = applyHighlightToFullscreenContentNew(highlight, contentElement);
          if (success) appliedCount++;
        });
        
        console.log(`[Fullscreen] ✅ Applied ${appliedCount}/${highlights.length} highlights successfully`);
        console.log('[Fullscreen] === APPLY HIGHLIGHTS END ===');
        
        // Show user feedback about highlight loading
        if (highlights.length > 0) {
          if (appliedCount === highlights.length) {
            console.log(`[Fullscreen] 🎉 All ${appliedCount} highlights loaded successfully!`);
            // Only show notification if we actually applied highlights
            if (appliedCount > 0) {
              setTimeout(() => {
                showNotification(`✅ [Fullscreen] Loaded ${appliedCount} highlight${appliedCount > 1 ? 's' : ''} successfully!`, 'success');
              }, 500);
            }
          } else if (appliedCount > 0) {
            console.log(`[Fullscreen] ⚠️ Partially loaded: ${appliedCount}/${highlights.length} highlights`);
            setTimeout(() => {
              showNotification(`⚠️ [Fullscreen] Loaded ${appliedCount}/${highlights.length} highlights. Some may be missing.`, 'warning');
            }, 500);
          } else {
            console.log(`[Fullscreen] ❌ Failed to load any highlights`);
            setTimeout(() => {
              showNotification(`❌ [Fullscreen] Failed to load highlights. Try: repairHighlights()`, 'error');
            }, 500);
          }
        }
      };

      // Apply individual highlight to fullscreen content (using exact same logic as normal view)
      function applyHighlightToFullscreenContentNew(highlight, contentElement) {
        // Validate highlight object
        if (!highlight || !highlight.id) {
          console.error('[Fullscreen] ❌ Invalid highlight object:', highlight);
          return false;
        }
        
        if (!highlight.highlighted_text) {
          console.error('[Fullscreen] ❌ Highlight missing text content:', highlight);
          return false;
        }
        
        // Check if this highlight already exists to prevent duplicates
        const existingHighlight = document.querySelector(`[data-highlight-id="${highlight.id}"]`);
        if (existingHighlight) {
          console.log('[Fullscreen] Highlight already exists, checking visibility:', highlight.id);
          
          // Check if existing highlight is visible
          const styles = window.getComputedStyle(existingHighlight);
          const isVisible = styles.visibility !== 'hidden' && styles.opacity !== '0' && styles.display !== 'none';
          const hasBackground = styles.backgroundColor !== 'rgba(0, 0, 0, 0)' && styles.backgroundColor !== 'transparent';
          
          console.log('[Fullscreen] Existing highlight styles:', {
            visibility: styles.visibility,
            opacity: styles.opacity,
            display: styles.display,
            backgroundColor: styles.backgroundColor,
            isVisible: isVisible,
            hasBackground: hasBackground
          });
          
          if (!isVisible || !hasBackground) {
            console.log('[Fullscreen] Existing highlight is not visible, fixing styles...');
            // Force visibility on existing highlight
            existingHighlight.style.cssText = `
              background-color: ${highlight.highlight_color || '#ffeb3b'} !important;
              color: inherit !important;
              position: relative !important;
              cursor: pointer !important;
              display: inline !important;
              z-index: 1000 !important;
              transition: all 0.2s ease !important;
              padding: 2px 4px !important;
              border-radius: 3px !important;
              border: 1px solid rgba(0,0,0,0.1) !important;
              visibility: visible !important;
              opacity: 1 !important;
            `;
            console.log('[Fullscreen] ✅ Fixed existing highlight visibility');
          }
          
          return true; // Consider this a success since it exists
        }
        
        // Find text nodes containing the highlighted text
        const walker = document.createTreeWalker(
          contentElement,
          NodeFilter.SHOW_TEXT,
          null,
          false
        );
        
        let node;
        let foundMatch = false;
        while ((node = walker.nextNode()) && !foundMatch) {
          const text = node.textContent || '';
          const highlightText = highlight.highlighted_text || '';
          
          if (!text || !highlightText) {
            continue; // Skip if either is empty
          }
          
          const index = text.indexOf(highlightText);
          
          if (index !== -1) {
            try {
              console.log(`[Fullscreen] ✅ Found text match for "${highlightText}" in node:`, text.substring(Math.max(0, index - 20), index + highlightText.length + 20));
              
              // Create highlight span with proper fullscreen styling
              const highlightSpan = document.createElement('mark');
              // Apply proper fullscreen highlight styling
              highlightSpan.style.cssText = `
                background-color: ${highlight.highlight_color || '#ffeb3b'} !important;
                color: inherit !important;
                position: relative !important;
                cursor: pointer !important;
                display: inline !important;
                z-index: 1 !important;
                transition: all 0.2s ease !important;
                padding: 2px 4px !important;
                border-radius: 3px !important;
                border: 1px solid rgba(0,0,0,0.1) !important;
              `;
              highlightSpan.className = 'highlight-marker fullscreen-highlight';
              highlightSpan.dataset.highlightId = highlight.id;
              highlightSpan.title = `Highlighted by ${highlight.adviser_name} - Click to comment, Right-click to remove`;
              
              // Add click handler for commenting on highlights
              highlightSpan.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.openHighlightCommentModal(highlight.id, highlight.highlighted_text, window.currentChapterId);
              });
              
              // Add context menu for removing highlights
              highlightSpan.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                if (confirm('Remove this highlight?')) {
                  window.removeHighlight(highlight.id);
                  // Reload highlights to refresh the view
                  setTimeout(() => {
                    if (window.currentChapterId) {
                      window.loadHighlights(window.currentChapterId);
                    }
                  }, 500);
                }
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
              console.log('[Fullscreen] ✅ Successfully applied fullscreen highlight to text');
              
              // Force a style refresh to ensure visibility in fullscreen mode
              setTimeout(() => {
                const appliedHighlight = document.querySelector(`[data-highlight-id="${highlight.id}"]`);
                if (appliedHighlight) {
                  appliedHighlight.style.opacity = '1';
                  appliedHighlight.style.visibility = 'visible';
                  // Ensure the background color is applied in fullscreen
                  appliedHighlight.style.backgroundColor = highlight.highlight_color || '#ffeb3b';
                  console.log('[Fullscreen] ✅ Highlight visibility and styling confirmed:', highlight.id);
                }
              }, 100);
              
              foundMatch = true; // Only apply to first occurrence
              return true; // Success
            } catch (e) {
              console.error('[Fullscreen] ❌ Error applying fullscreen highlight:', e);
              return false; // Failure
            }
          }
        }
        
        if (!foundMatch) {
          console.warn(`[Fullscreen] ⚠️ Could not find text "${highlight.highlighted_text}" in content for highlight ID ${highlight.id}`);
          return false; // Text not found
        }
        
        return false; // Should not reach here
      }

      // OLD FUNCTION - KEEPING FOR REFERENCE
      window.applyHighlightsToFullscreenOLD = function(highlights) {
        // Try multiple selectors to find the actual content container
        const possibleSelectors = [
          '#fullscreen-document-content-content', // WordViewer creates this
          '#fullscreen-document-content .word-content',
          '#fullscreen-document-content'
        ];
        
        let fullscreenContent = null;
        for (const selector of possibleSelectors) {
          const element = document.querySelector(selector);
          if (element && (element.children.length > 0 || element.textContent.length > 100)) {
            fullscreenContent = element;
            console.log(`[Fullscreen] Using content container: ${selector}`);
            break;
          }
        }
        
        if (!fullscreenContent) {
          console.log('[Fullscreen] No content found for applying highlights');
          return;
        }
        
        console.log(`[Fullscreen] Applying ${highlights.length} highlights to fullscreen content`);
        
        highlights.forEach(highlight => {
          // Validate highlight data first
          if (!highlight || !highlight.highlighted_text || !highlight.id) {
            console.warn('[Fullscreen] Invalid highlight data, skipping:', highlight);
            return;
          }
          
          try {
            // Check if this highlight already exists to prevent duplicates
            const existingHighlight = fullscreenContent.querySelector(`[data-highlight-id="${highlight.id}"]`);
            if (existingHighlight) {
              console.log('[Fullscreen] Highlight already exists, skipping:', highlight.id);
              return;
            }
            
            // Find text nodes containing the highlighted text
            const walker = document.createTreeWalker(
              fullscreenContent,
              NodeFilter.SHOW_TEXT,
              null,
              false
            );
            
            let node;
            let foundMatch = false;
            const highlightText = highlight.highlighted_text.trim();
            
            if (!highlightText) {
              console.warn('[Fullscreen] Empty highlight text, skipping:', highlight.id);
              return;
            }
            
            while ((node = walker.nextNode()) && !foundMatch) {
              const text = node.textContent;
              
              // Additional validation to prevent undefined/null text content
              if (!text || typeof text !== 'string') {
                continue;
              }
              
              console.log(`[Fullscreen] Searching for "${highlightText}" in text: "${text.substring(0, 100)}..."`);
              
              const index = text.indexOf(highlightText);
              
              if (index !== -1) {
                console.log(`[Fullscreen] ✅ Found match at index ${index} for highlight ${highlight.id}`);
                console.log(`[Fullscreen] Text context: "${text.substring(Math.max(0, index - 20), index + highlightText.length + 20)}"`);
                
                // Create highlight span
                const highlightSpan = document.createElement('mark');
                highlightSpan.style.cssText = `
                  background-color: ${highlight.highlight_color || '#ffeb3b'} !important;
                  padding: 2px 4px !important;
                  border-radius: 3px !important;
                  position: relative !important;
                  cursor: pointer !important;
                  display: inline !important;
                  z-index: 1 !important;
                  transition: all 0.2s ease !important;
                `;
                highlightSpan.className = 'highlight-marker fullscreen-highlight';
                highlightSpan.dataset.highlightId = highlight.id;
                highlightSpan.title = `Highlighted by ${highlight.adviser_name} - Click to comment, Right-click to remove`;
                
                // Add click handler for commenting on highlights
                highlightSpan.addEventListener('click', function(e) {
                  e.preventDefault();
                  e.stopPropagation();
                  console.log('[Fullscreen] Highlight clicked:', highlight.id);
                  
                  // Ensure the modal function exists
                  if (typeof window.openHighlightCommentModal === 'function') {
                    window.openHighlightCommentModal(highlight.id, highlight.highlighted_text, window.currentChapterId);
                  } else {
                    console.error('[Fullscreen] openHighlightCommentModal function not found, using fallback');
                    // Fallback: Create a simple modal manually with high z-index
                    const fallbackModal = document.createElement('div');
                    fallbackModal.id = 'fallback-comment-modal';
                    fallbackModal.style.cssText = `
                      position: fixed;
                      top: 0;
                      left: 0;
                      width: 100%;
                      height: 100%;
                      background: rgba(0,0,0,0.5);
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      z-index: 10000;
                    `;
                    fallbackModal.innerHTML = `
                      <div style="background: white; border-radius: 8px; padding: 24px; max-width: 500px; width: 90%;">
                        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: bold;">Comment on Highlight</h3>
                        <div style="margin-bottom: 16px;">
                          <label style="display: block; margin-bottom: 8px; font-weight: bold;">Highlighted Text:</label>
                          <div style="padding: 12px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 4px; font-size: 14px;">
                            ${highlight.highlighted_text}
                          </div>
                        </div>
                        <div style="margin-bottom: 16px;">
                          <label style="display: block; margin-bottom: 8px; font-weight: bold;">Your Comment:</label>
                          <textarea id="fallback-comment-text" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;" rows="4" placeholder="Enter your comment..."></textarea>
                        </div>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                          <button id="fallback-cancel" style="padding: 8px 16px; border: 1px solid #ccc; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                          <button id="fallback-save" style="padding: 8px 16px; border: none; background: #3b82f6; color: white; border-radius: 4px; cursor: pointer;">Save Comment</button>
                        </div>
                      </div>
                    `;
                    
                    document.body.appendChild(fallbackModal);
                    
                    // Focus textarea
                    setTimeout(() => {
                      document.getElementById('fallback-comment-text').focus();
                    }, 100);
                    
                    // Add event listeners
                    document.getElementById('fallback-cancel').addEventListener('click', () => {
                      fallbackModal.remove();
                    });
                    
                    document.getElementById('fallback-save').addEventListener('click', () => {
                      const comment = document.getElementById('fallback-comment-text').value.trim();
                      if (comment) {
                        // Save comment using the standard API
                        const formData = new FormData();
                        formData.append('action', 'add_comment');
                        formData.append('chapter_id', window.currentChapterId);
                        formData.append('highlight_id', highlight.id);
                        formData.append('comment_text', comment);
                        
                        fetch('api/document_review.php', {
                          method: 'POST',
                          body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                          if (data.success) {
                            showNotification('Comment added successfully!', 'success');
                            fallbackModal.remove();
                          } else {
                            showNotification('Failed to add comment: ' + data.error, 'error');
                          }
                        })
                        .catch(error => {
                          console.error('Error adding comment:', error);
                          showNotification('Error adding comment', 'error');
                        });
                      } else {
                        showNotification('Please enter a comment', 'warning');
                      }
                    });
                    
                    // Close on escape key
                    const handleEscape = (e) => {
                      if (e.key === 'Escape') {
                        fallbackModal.remove();
                        document.removeEventListener('keydown', handleEscape);
                      }
                    };
                    document.addEventListener('keydown', handleEscape);
                  }
                });
                
                // Add context menu for removing highlights
                highlightSpan.addEventListener('contextmenu', function(e) {
                  e.preventDefault();
                  if (confirm('Remove this highlight?')) {
                    window.removeHighlight(highlight.id);
                    // Reload highlights to refresh the fullscreen view
                    setTimeout(() => {
                      if (window.currentChapterId) {
                        window.loadHighlightsInFullscreen(window.currentChapterId);
                      }
                    }, 500);
                  }
                });
                
                try {
                  console.log(`[Fullscreen] Attempting DOM manipulation for highlight ${highlight.id}`);
                  
                  // Split the text node and wrap the highlighted portion
                  const beforeText = text.substring(0, index);
                  const afterText = text.substring(index + highlightText.length);
                  
                  console.log(`[Fullscreen] Before text: "${beforeText}"`);
                  console.log(`[Fullscreen] Highlight text: "${highlightText}"`);
                  console.log(`[Fullscreen] After text: "${afterText}"`);
                  
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
                  foundMatch = true; // Only apply to first occurrence
                  
                  console.log(`[Fullscreen] ✅ Successfully applied highlight ${highlight.id}`);
                  
                  // Verify the highlight is visible
                  setTimeout(() => {
                    const appliedHighlight = document.querySelector(`[data-highlight-id="${highlight.id}"]`);
                    if (appliedHighlight) {
                      const styles = window.getComputedStyle(appliedHighlight);
                      console.log(`[Fullscreen] Verification - Highlight ${highlight.id}:`, {
                        backgroundColor: styles.backgroundColor,
                        display: styles.display,
                        position: styles.position,
                        zIndex: styles.zIndex,
                        visibility: styles.visibility,
                        opacity: styles.opacity,
                        text: appliedHighlight.textContent.substring(0, 30)
                      });
                      
                      // Force style refresh if needed
                      if (styles.backgroundColor === 'rgba(0, 0, 0, 0)' || styles.backgroundColor === 'transparent') {
                        console.log(`[Fullscreen] ⚠️ Background color not applied, forcing style refresh for highlight ${highlight.id}`);
                        appliedHighlight.style.cssText = `
                          background-color: ${highlight.highlight_color || '#ffeb3b'} !important;
                          padding: 2px 4px !important;
                          border-radius: 3px !important;
                          position: relative !important;
                          cursor: pointer !important;
                          display: inline !important;
                          z-index: 1 !important;
                          transition: all 0.2s ease !important;
                        `;
                        appliedHighlight.setAttribute('style', appliedHighlight.style.cssText);
                      }
                    } else {
                      console.error(`[Fullscreen] ❌ Highlight ${highlight.id} not found in DOM after application`);
                    }
                  }, 100);
                } catch (e) {
                  console.error(`[Fullscreen] ❌ Error applying highlight ${highlight.id}:`, e);
                }
              } else {
                console.log(`[Fullscreen] ❌ No match found for "${highlightText}" in text: "${text.substring(0, 50)}..."`);
              }
            }
            
            // If exact match failed, try improved fuzzy matching
            if (!foundMatch) {
              console.log(`[Fullscreen] Exact match failed for highlight ${highlight.id}, trying improved fuzzy matching...`);
              
              // Use the improved forceApplyHighlight function instead
              if (window.forceApplyHighlight && window.forceApplyHighlight(highlight, fullscreenContent)) {
                foundMatch = true;
                console.log(`[Fullscreen] ✅ Applied highlight ${highlight.id} using improved matching`);
              } else {
                console.log(`[Fullscreen] ❌ Still no match found for highlight ${highlight.id} even with improved matching`);
              }
            }
            
            if (!foundMatch) {
              console.warn(`[Fullscreen] ⚠️ No match found for highlight ${highlight.id}: "${highlightText}"`);
              console.warn(`[Fullscreen] Consider checking the document content or highlight data.`);
            }
          } catch (error) {
            console.error('[Fullscreen] Error processing highlight:', highlight.id, error);
            console.error('[Fullscreen] Highlight data:', highlight);
          }
        });
      };

      // Mark paragraphs that have comments in fullscreen - make it globally accessible  
      window.markCommentedParagraphsInFullscreen = function(comments) {
        // Try multiple selectors to find the actual content container
        const possibleSelectors = [
          '#fullscreen-document-content-content', // WordViewer creates this
          '#fullscreen-document-content .word-content',
          '#fullscreen-document-content'
        ];
        
        let fullscreenContent = null;
        for (const selector of possibleSelectors) {
          const element = document.querySelector(selector);
          if (element && (element.children.length > 0 || element.textContent.length > 100)) {
            fullscreenContent = element;
            console.log(`[Fullscreen] Using content container for comments: ${selector}`);
            break;
          }
        }
        
        if (!fullscreenContent) {
          console.log('[Fullscreen] No content found for marking commented paragraphs');
          return;
        }
        
        console.log(`[Fullscreen] Marking ${comments.length} comments in fullscreen content`);
        
        // Group comments by paragraph
        const paragraphComments = {};
        comments.forEach(comment => {
          if (comment.paragraph_id) {
            if (!paragraphComments[comment.paragraph_id]) {
              paragraphComments[comment.paragraph_id] = [];
            }
            paragraphComments[comment.paragraph_id].push(comment);
          }
        });
        
        // Mark paragraphs with comments
        Object.keys(paragraphComments).forEach(paragraphId => {
          const paragraph = fullscreenContent.querySelector(`[data-paragraph-id="${paragraphId}"], #${paragraphId}`);
          if (paragraph) {
            paragraph.classList.add('commented');
            
            // Add comment indicator if it doesn't exist
            let indicator = paragraph.querySelector('.comment-indicator');
            if (!indicator) {
              indicator = document.createElement('div');
              indicator.className = 'comment-indicator fullscreen-comment-indicator';
              indicator.style.cssText = `
                position: absolute;
                right: 4px;
                top: 4px;
                width: 24px;
                height: 24px;
                background: #3b82f6;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 10;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
              `;
              indicator.innerHTML = '<i data-lucide="message-circle" class="w-3 h-3"></i>';
              
              // Make sure the paragraph has relative positioning
              paragraph.style.position = 'relative';
              paragraph.appendChild(indicator);
              
              // Add click handler to show comments
              indicator.addEventListener('click', (e) => {
                e.stopPropagation();
                console.log('[Fullscreen] Comment indicator clicked for paragraph:', paragraphId);
                
                // Ensure the modal function exists
                if (typeof showParagraphCommentsModal === 'function') {
                  showParagraphCommentsModal(paragraphId, paragraphComments[paragraphId]);
                } else {
                  // Fallback: Show comments in a simple alert
                  const commentsText = paragraphComments[paragraphId]
                    .map(c => `${c.adviser_name}: ${c.comment_text}`)
                    .join('\n\n');
                  alert(`Comments for this paragraph:\n\n${commentsText}`);
                }
              });
              
              console.log(`[Fullscreen] Comment indicator added to paragraph: ${paragraphId}`);
            } else {
              // Make sure existing indicator is visible
              indicator.style.display = 'flex';
              console.log(`[Fullscreen] Made existing comment indicator visible for paragraph: ${paragraphId}`);
            }
          }
        });
        
        // Refresh Lucide icons
        if (typeof lucide !== 'undefined') {
          lucide.createIcons();
        }
      };

      // Show comments for a specific paragraph
      function showParagraphCommentsModal(paragraphId, comments) {
        // Remove existing modal if any
        const existingModal = document.getElementById('paragraph-comments-view-modal');
        if (existingModal) {
          existingModal.remove();
        }
        
        // Check if we're in fullscreen mode
        const isFullscreen = document.querySelector('.document-fullscreen-modal.active') !== null;
        console.log('[Comments View Modal] Opening in fullscreen mode:', isFullscreen);
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'paragraph-comments-view-modal';
        // Use higher z-index for fullscreen mode
        const zIndexClass = isFullscreen ? 'z-[9999]' : 'z-50';
        modal.className = `fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center ${zIndexClass}`;
        modal.style.zIndex = isFullscreen ? '9999' : '50'; // Ensure it works even without Tailwind
        modal.innerHTML = `
          <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[80vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4">Comments for this Paragraph</h3>
            <div class="space-y-3 mb-4">
              ${comments.map(comment => `
                <div class="border rounded-lg p-3 bg-gray-50">
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
                </div>
              `).join('')}
            </div>
            <div class="flex justify-between">
              <button id="add-more-comments" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Add Another Comment</button>
              <button id="close-comments-view" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Close</button>
            </div>
          </div>
        `;
        
        document.body.appendChild(modal);
        
        // Add event listeners
        document.getElementById('close-comments-view').addEventListener('click', () => {
          modal.remove();
        });
        
        document.getElementById('add-more-comments').addEventListener('click', () => {
          modal.remove();
          const paragraph = document.querySelector(`[data-paragraph-id="${paragraphId}"], #${paragraphId}`);
          if (paragraph) {
            window.openParagraphCommentModal(paragraphId, paragraph.textContent.trim());
          }
        });
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
          if (e.target === modal) {
            modal.remove();
          }
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

      // Reload highlights button functionality  
      document.getElementById('reload-highlights-btn')?.addEventListener('click', function() {
        window.reloadHighlights();
      });

      // Quick fix highlights button functionality  
      document.getElementById('quick-fix-highlights-btn')?.addEventListener('click', function() {
        window.quickFixHighlights();
      });

      // Add debug text matching button
      document.getElementById('debug-text-matching-btn')?.addEventListener('click', function() {
        window.debugTextMatching();
      });

      // Remove all highlights button functionality
      document.getElementById('remove-highlights-btn')?.addEventListener('click', function() {
        if (!window.currentChapterId) {
          showNotification('Please select a chapter first', 'warning');
          return;
        }

        // First, get the actual highlight count from the server for the current chapter
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-1 animate-spin"></i><span class="hidden sm:inline">Loading...</span>';
        
        fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
          .then(response => response.json())
          .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="eraser" class="w-4 h-4 mr-1"></i><span class="hidden sm:inline">Clear All</span>';
            
            if (!data.success) {
              showNotification('Failed to check highlights', 'error');
              return;
            }
            
            const actualHighlights = data.highlights || [];
            
            if (actualHighlights.length === 0) {
              showNotification('No highlights found to remove', 'info');
              return;
            }

            if (confirm(`Are you sure you want to remove all ${actualHighlights.length} highlights from this chapter? This action cannot be undone.`)) {
              // Show loading state
              btn.disabled = true;
              btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-1 animate-spin"></i><span class="hidden sm:inline">Removing...</span>';
              
              // Use the actual highlight IDs from the server
              const highlightIds = actualHighlights.map(h => h.id);
              
              // Remove highlights one by one
              let removedCount = 0;
              let failedCount = 0;
              
              const removeNext = (index) => {
                if (index >= highlightIds.length) {
                  // All done
                  btn.disabled = false;
                  btn.innerHTML = '<i data-lucide="eraser" class="w-4 h-4 mr-1"></i><span class="hidden sm:inline">Clear All</span>';
                  
                  if (removedCount > 0) {
                    showNotification(`Successfully removed ${removedCount} highlights`, 'success');
                    // Reload highlights to refresh the view
                    setTimeout(() => {
                      if (window.currentChapterId) {
                        window.loadHighlights(window.currentChapterId);
                      }
                    }, 500);
                  }
                  if (failedCount > 0) {
                    showNotification(`Failed to remove ${failedCount} highlights`, 'error');
                  }
                  return;
                }
                
                const highlightId = highlightIds[index];
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
                    removedCount++;
                    // Remove from DOM immediately - find all instances of this highlight ID
                    const elements = document.querySelectorAll(`[data-highlight-id="${highlightId}"]`);
                    elements.forEach(element => {
                      if (element && element.parentNode) {
                        element.parentNode.insertBefore(document.createTextNode(element.textContent), element);
                        element.parentNode.removeChild(element);
                      }
                    });
                  } else {
                    failedCount++;
                    console.error(`Failed to remove highlight ${highlightId}:`, data.error);
                  }
                  // Continue to next highlight
                  removeNext(index + 1);
                })
                .catch(error => {
                  failedCount++;
                  console.error(`Error removing highlight ${highlightId}:`, error);
                  // Continue to next highlight
                  removeNext(index + 1);
                });
              };
              
              // Start removing highlights
              removeNext(0);
            }
          })
          .catch(error => {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="eraser" class="w-4 h-4 mr-1"></i><span class="hidden sm:inline">Clear All</span>';
            console.error('Error fetching highlights:', error);
            showNotification('Failed to check highlights', 'error');
          });
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
      
      // Enhanced function to aggressively find content and load highlights
      window.waitForWordViewerAndLoadHighlights = function(maxAttempts = 15) {
        let attempts = 0;
        
        const checkAndLoad = () => {
          attempts++;
          console.log(`🔄 Attempt ${attempts}/${maxAttempts} - Aggressively searching for content...`);
          
          // Comprehensive content detection
          const allPossibleSelectors = [
            '.word-content',
            '#adviser-word-viewer-content .word-content',
            '#adviser-word-viewer-content',
            '.word-document .word-content',
            '.word-document',
            '.word-page .word-content',
            '.word-page',
            '.word-viewer .word-content',
            '.word-viewer',
            '[id*="word-viewer"] .word-content',
            '[id*="word-viewer"]',
            '[class*="word-content"]',
            '[class*="word-document"]',
            '.chapter-content',
            '.prose',
            'div[contenteditable]',
            // Check for any div with substantial text content
            'div'
          ];
          
          let bestContent = null;
          let bestScore = 0;
          
          for (const selector of allPossibleSelectors) {
            try {
              const elements = document.querySelectorAll(selector);
              elements.forEach(element => {
                if (element && element.textContent) {
                  const textLength = element.textContent.trim().length;
                  const childCount = element.children.length;
                  
                  // Scoring system: prioritize elements with more text content
                  let score = textLength;
                  if (element.className.includes('word-content')) score += 1000;
                  if (element.className.includes('word-document')) score += 500;
                  if (element.id.includes('word-viewer')) score += 300;
                  if (childCount > 5) score += 100;
                  
                  console.log(`Candidate: ${selector} - Score: ${score}, Text: ${textLength}, Children: ${childCount}`);
                  
                  if (score > bestScore && textLength > 50) {
                    bestContent = element;
                    bestScore = score;
                    console.log(`🏆 New best candidate: ${selector} (Score: ${score})`);
                  }
                }
              });
            } catch (e) {
              // Skip invalid selectors
            }
          }
          
          if (bestContent) {
            console.log('✅ Found suitable content element, loading highlights...');
            console.log('Content preview:', bestContent.textContent.substring(0, 200));
            
            // Force load highlights with the found content
            setTimeout(() => {
              if (window.currentChapterId) {
                console.log('🚀 Forcing highlight load...');
                window.forceLoadHighlightsWithContent(bestContent);
              }
            }, 300);
          } else if (attempts < maxAttempts) {
            console.log(`⏳ No suitable content found yet, retrying in 1 second...`);
            setTimeout(checkAndLoad, 1000);
          } else {
            console.log('❌ Could not find suitable content after maximum attempts');
            console.log('📊 Available elements:');
            document.querySelectorAll('div, section, article').forEach((el, i) => {
              if (el.textContent && el.textContent.trim().length > 100) {
                console.log(`Option ${i}: ${el.tagName}.${el.className} - ${el.textContent.substring(0, 50)}...`);
              }
            });
            showNotification('⚠️ Could not find document content. Try refreshing the page.', 'warning');
          }
        };
        
        checkAndLoad();
      };

      // Force load highlights with a specific content element
      window.forceLoadHighlightsWithContent = function(contentElement) {
        console.log('🎯 Force loading highlights with specific content element...');
        
        if (!contentElement) {
          console.error('❌ No content element provided');
          return;
        }
        
        if (!window.currentChapterId) {
          console.error('❌ No chapter selected');
          return;
        }
        
        console.log('Content element:', contentElement);
        console.log('Content preview:', contentElement.textContent.substring(0, 100));
        
        // Fetch highlights and apply them directly
        fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
          .then(response => response.json())
          .then(data => {
            console.log('📊 Highlights API response:', data);
            
            if (data.success && data.highlights && data.highlights.length > 0) {
              console.log(`🎯 Applying ${data.highlights.length} highlights directly to content element...`);
              
              // Clear existing highlights first
              const existingHighlights = contentElement.querySelectorAll('.highlight-marker');
              console.log(`🧹 Removing ${existingHighlights.length} existing highlights`);
              existingHighlights.forEach(highlight => {
                const parent = highlight.parentNode;
                if (parent) {
                  parent.insertBefore(document.createTextNode(highlight.textContent), highlight);
                  parent.removeChild(highlight);
                }
              });
              
              // Apply each highlight
              let successCount = 0;
              data.highlights.forEach((highlight, index) => {
                console.log(`📌 Applying highlight ${index + 1}: "${highlight.highlighted_text}"`);
                
                if (window.forceApplyHighlight(highlight, contentElement)) {
                  successCount++;
                }
              });
              
              console.log(`✅ Successfully applied ${successCount}/${data.highlights.length} highlights`);
              
              if (successCount > 0) {
                showNotification(`✅ Applied ${successCount} highlights successfully!`, 'success');
              } else {
                showNotification(`⚠️ Found ${data.highlights.length} highlights but couldn't apply any. Text might not match.`, 'warning');
              }
              
            } else {
              console.log('ℹ️ No highlights found in database');
              showNotification('ℹ️ No highlights found for this chapter', 'info');
            }
          })
          .catch(error => {
            console.error('❌ Error loading highlights:', error);
            showNotification('❌ Error loading highlights: ' + error.message, 'error');
          });
      };

      // Force apply a single highlight with aggressive text matching
      window.forceApplyHighlight = function(highlight, contentElement) {
        if (!highlight || !highlight.highlighted_text || !highlight.id) {
          console.error('❌ Invalid highlight object:', highlight);
          return false;
        }
        
        // Check if already exists
        if (document.querySelector(`[data-highlight-id="${highlight.id}"]`)) {
          console.log('ℹ️ Highlight already exists, skipping:', highlight.id);
          return true;
        }
        
        const highlightText = highlight.highlighted_text.trim();
        console.log(`🔍 Searching for text: "${highlightText}"`);
        
        // Helper function to normalize text for better matching
        function normalizeText(text) {
          return text
            .replace(/\s+/g, ' ')  // Replace multiple spaces with single space
            .replace(/[\u00A0\u2000-\u200B\u2028\u2029]/g, ' ')  // Replace various Unicode spaces
            .replace(/[""'']/g, '"')  // Normalize quotes
            .replace(/[–—]/g, '-')  // Normalize dashes
            .trim();
        }
        
        // Helper function to create and apply highlight
        function createAndApplyHighlight(node, text, index, matchLength = highlightText.length) {
          try {
            console.log(`📍 Found match in text node: "${text.substring(Math.max(0, index - 20), index + matchLength + 20)}"`);
            
            // Create highlight span
            const highlightSpan = document.createElement('mark');
            highlightSpan.style.cssText = `
              background-color: ${highlight.highlight_color || '#ffeb3b'} !important;
              padding: 2px 4px !important;
              border-radius: 3px !important;
              position: relative !important;
              cursor: pointer !important;
              display: inline !important;
              z-index: 1 !important;
              transition: all 0.2s ease !important;
            `;
            highlightSpan.className = 'highlight-marker fullscreen-highlight';
            highlightSpan.dataset.highlightId = highlight.id;
            highlightSpan.title = `Highlighted by ${highlight.adviser_name || 'Adviser'} - Click to comment, Right-click to remove`;
            
            // Add event handlers
            highlightSpan.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              if (typeof window.openHighlightCommentModal === 'function') {
                window.openHighlightCommentModal(highlight.id, highlight.highlighted_text, window.currentChapterId);
              }
            });
            
            highlightSpan.addEventListener('contextmenu', function(e) {
              e.preventDefault();
              if (confirm('Remove this highlight?')) {
                window.removeHighlight(highlight.id);
                setTimeout(() => {
                  if (window.currentChapterId) {
                    window.loadHighlights(window.currentChapterId);
                  }
                }, 500);
              }
            });
            
            // Split text and insert highlight
            const beforeText = text.substring(0, index);
            const afterText = text.substring(index + matchLength);
            
            if (beforeText) {
              const beforeNode = document.createTextNode(beforeText);
              node.parentNode.insertBefore(beforeNode, node);
            }
            
            highlightSpan.textContent = text.substring(index, index + matchLength);
            node.parentNode.insertBefore(highlightSpan, node);
            
            if (afterText) {
              const afterNode = document.createTextNode(afterText);
              node.parentNode.insertBefore(afterNode, node);
            }
            
            node.parentNode.removeChild(node);
            console.log('✅ Successfully applied highlight');
            return true;
            
          } catch (e) {
            console.error('❌ Error applying highlight:', e);
            return false;
          }
        }
        
        // Method 1: Direct text content search
        if (contentElement.textContent.includes(highlightText)) {
          console.log('✅ Found text in content using direct match, applying highlight...');
          
          // Use TreeWalker to find text nodes
          const walker = document.createTreeWalker(
            contentElement,
            NodeFilter.SHOW_TEXT,
            null,
            false
          );
          
          let node;
          while (node = walker.nextNode()) {
            const text = node.textContent;
            
            // Additional validation to prevent undefined/null text content
            if (!text || typeof text !== 'string') {
              continue;
            }
            
            const index = text.indexOf(highlightText);
            if (index !== -1) {
              return createAndApplyHighlight(node, text, index);
            }
          }
        }
        
        // Method 2: Normalized text matching
        console.log(`⚠️ Exact text not found, trying normalized matching...`);
        const normalizedHighlight = normalizeText(highlightText);
        
        if (normalizeText(contentElement.textContent).includes(normalizedHighlight)) {
          console.log('✅ Found text with normalized matching');
          
          const walker = document.createTreeWalker(
            contentElement,
            NodeFilter.SHOW_TEXT,
            null,
            false
          );
          
          let node;
          while (node = walker.nextNode()) {
            const text = node.textContent;
            if (!text || typeof text !== 'string') continue;
            
            const normalizedText = normalizeText(text);
            const index = normalizedText.indexOf(normalizedHighlight);
            
            if (index !== -1) {
              // Find the original text position
              let originalIndex = 0;
              let normalizedIndex = 0;
              
              for (let i = 0; i < text.length && normalizedIndex < index; i++) {
                if (normalizeText(text.charAt(i)) === normalizedText.charAt(normalizedIndex)) {
                  normalizedIndex++;
                }
                originalIndex = i + 1;
              }
              
              return createAndApplyHighlight(node, text, originalIndex, normalizedHighlight.length);
            }
          }
        }
        
        // Method 3: Word-by-word matching for partial matches
        console.log('⚠️ Normalized matching failed, trying word-by-word matching...');
        const highlightWords = highlightText.split(/\s+/).filter(word => word.length > 2);
        
        if (highlightWords.length > 0) {
          const walker = document.createTreeWalker(
            contentElement,
            NodeFilter.SHOW_TEXT,
            null,
            false
          );
          
          let node;
          while (node = walker.nextNode()) {
            const text = node.textContent;
            if (!text || typeof text !== 'string') continue;
            
            // Check if at least 70% of words are present
            const matchingWords = highlightWords.filter(word => 
              text.toLowerCase().includes(word.toLowerCase())
            );
            
            if (matchingWords.length >= Math.ceil(highlightWords.length * 0.7)) {
              console.log(`✅ Found ${matchingWords.length}/${highlightWords.length} words matching`);
              
              // Find the best continuous match
              const textWords = text.split(/\s+/);
              let bestMatchIndex = -1;
              let bestMatchLength = 0;
              
              for (let i = 0; i < textWords.length; i++) {
                let matchCount = 0;
                for (let j = 0; j < highlightWords.length && i + j < textWords.length; j++) {
                  if (textWords[i + j].toLowerCase().includes(highlightWords[j].toLowerCase()) ||
                      highlightWords[j].toLowerCase().includes(textWords[i + j].toLowerCase())) {
                    matchCount++;
                  }
                }
                
                if (matchCount > bestMatchLength) {
                  bestMatchLength = matchCount;
                  bestMatchIndex = i;
                }
              }
              
              if (bestMatchIndex !== -1 && bestMatchLength > 0) {
                // Calculate character index
                const wordsBeforeMatch = textWords.slice(0, bestMatchIndex);
                const charIndex = wordsBeforeMatch.join(' ').length + (wordsBeforeMatch.length > 0 ? 1 : 0);
                const matchText = textWords.slice(bestMatchIndex, bestMatchIndex + bestMatchLength).join(' ');
                
                return createAndApplyHighlight(node, text, charIndex, matchText.length);
              }
            }
          }
        }
        
        // Method 4: Substring similarity matching
        console.log('⚠️ Word matching failed, trying substring similarity matching...');
        
        const walker = document.createTreeWalker(
          contentElement,
          NodeFilter.SHOW_TEXT,
          null,
          false
        );
        
        let bestMatch = null;
        let bestSimilarity = 0;
        let bestNode = null;
        let bestIndex = -1;
        
        // Simple similarity function
        function similarity(str1, str2) {
          const len1 = str1.length;
          const len2 = str2.length;
          const maxLen = Math.max(len1, len2);
          
          if (maxLen === 0) return 1.0;
          
          let matches = 0;
          const minLen = Math.min(len1, len2);
          
          for (let i = 0; i < minLen; i++) {
            if (str1.charAt(i).toLowerCase() === str2.charAt(i).toLowerCase()) {
              matches++;
            }
          }
          
          return matches / maxLen;
        }
        
        let node;
        while (node = walker.nextNode()) {
          const text = node.textContent;
          if (!text || typeof text !== 'string') continue;
          
          // Check substrings of similar length
          const targetLength = highlightText.length;
          const tolerance = Math.max(20, targetLength * 0.5);
          
          for (let i = 0; i <= text.length - targetLength + tolerance; i++) {
            const substring = text.substring(i, i + targetLength + tolerance);
            const sim = similarity(highlightText, substring);
            
            if (sim > bestSimilarity && sim > 0.6) {
              bestSimilarity = sim;
              bestMatch = substring;
              bestNode = node;
              bestIndex = i;
            }
          }
        }
        
        if (bestMatch && bestNode && bestIndex !== -1) {
          console.log(`✅ Found similarity match with ${Math.round(bestSimilarity * 100)}% similarity: "${bestMatch.substring(0, 50)}..."`);
          return createAndApplyHighlight(bestNode, bestNode.textContent, bestIndex, bestMatch.length);
        }
        
        console.log(`❌ Could not find text "${highlightText}" in content after all methods`);
        console.log('Available text preview:', contentElement.textContent.substring(0, 200) + '...');
        console.log('Highlight text length:', highlightText.length);
        console.log('Content text length:', contentElement.textContent.length);
        
        return false;
      };

      // Manual reload highlights function (for debugging/fallback)
      window.reloadHighlights = function() {
        if (window.currentChapterId) {
          console.log('Manual reload highlights triggered for chapter:', window.currentChapterId);
          showNotification('Reloading highlights...', 'info');
          
          // Clear existing highlights first
          const allHighlights = document.querySelectorAll('.highlight-marker');
          console.log(`Clearing ${allHighlights.length} existing highlights`);
          allHighlights.forEach(highlight => {
            const parent = highlight.parentNode;
            if (parent) {
              parent.insertBefore(document.createTextNode(highlight.textContent), highlight);
              parent.removeChild(highlight);
            }
          });
          
                    // Use smart waiting function to ensure Word viewer is ready
          console.log('🔄 Using smart waiting function...');
          window.waitForWordViewerAndLoadHighlights();
          
          // Check results after a reasonable delay
          setTimeout(() => {
            const newHighlights = document.querySelectorAll('.highlight-marker');
            if (newHighlights.length > 0) {
              showNotification(`✅ Loaded ${newHighlights.length} highlights successfully!`, 'success');
            } else {
              // Check if highlights exist in database but failed to apply
              fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
                .then(r => r.json())
                .then(data => {
                  if (data.success && data.highlights.length > 0) {
                    showNotification(`⚠️ Found ${data.highlights.length} highlights in database but failed to apply them. Try waitForWordViewerAndLoadHighlights()`, 'warning');
                    console.log('💡 Try: waitForWordViewerAndLoadHighlights()');
                  } else {
                    showNotification('ℹ️ No highlights found in database. Create some highlights first.', 'info');
                  }
                })
                .catch(() => {
                  showNotification('⚠️ Could not load highlights. Try debugHighlights() for more info.', 'warning');
                });
            }
          }, 5000); // Give more time for the smart function to work
        } else {
          console.log('No chapter selected for highlight reload');
          showNotification('Please select a chapter first', 'warning');
        }
      };
      
      // Debug function to test highlight system
      window.debugHighlights = function() {
        console.log('=== HIGHLIGHT DEBUG INFO ===');
        console.log('Current chapter ID:', window.currentChapterId);
        console.log('Available content elements:');
        
        const selectors = ['.word-content', '.chapter-content', '.word-document', '#adviser-word-viewer-content', '.prose'];
        selectors.forEach(selector => {
          const element = document.querySelector(selector);
          if (element) {
            console.log(`✅ ${selector}: Found, text length: ${element.textContent.length}`);
          } else {
            console.log(`❌ ${selector}: Not found`);
          }
        });
        
        const existingHighlights = document.querySelectorAll('.highlight-marker');
        console.log(`Existing highlights in DOM: ${existingHighlights.length}`);
        
        if (window.currentChapterId) {
          console.log('Testing API call...');
          fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
            .then(response => response.json())
            .then(data => {
              console.log('=== API RESPONSE DETAILED ===');
              console.log('Full API response:', data);
              if (data.success) {
                console.log(`Highlights in database: ${data.highlights.length}`);
                data.highlights.forEach((h, i) => {
                  console.log(`=== Highlight ${i + 1} Details ===`);
                  console.log('Full object:', h);
                  console.log('ID:', h.id);
                  console.log('Chapter ID:', h.chapter_id);
                  console.log('Adviser ID:', h.adviser_id);
                  console.log('Highlighted text:', h.highlighted_text);
                  console.log('Color:', h.highlight_color);
                  console.log('Start offset:', h.start_offset);
                  console.log('End offset:', h.end_offset);
                  console.log('Created at:', h.created_at);
                  console.log('Adviser name:', h.adviser_name);
                  
                  // Check for missing fields
                  if (!h.highlighted_text) {
                    console.error(`❌ Highlight ${i + 1} is missing highlighted_text!`);
                  }
                  if (!h.id) {
                    console.error(`❌ Highlight ${i + 1} is missing ID!`);
                  }
                });
              } else {
                console.error('API returned error:', data.error);
              }
              console.log('=== END API RESPONSE ===');
            })
            .catch(error => {
              console.error('API call failed:', error);
            });
        }
        
        console.log('=== END DEBUG INFO ===');
      };

      // Quick fix for immediate highlight loading
      window.quickFixHighlights = function() {
        console.log('🔧 QUICK FIX: Immediate highlight loading...');
        
        if (!window.currentChapterId) {
          showNotification('Please select a chapter first', 'warning');
          return;
        }
        
        showNotification('🔧 Quick fix in progress...', 'info');
        
        // Wait for Word viewer to finish loading if it's still loading
        let attempts = 0;
        const maxAttempts = 10;
        
        const waitAndApply = () => {
          attempts++;
          console.log(`Quick fix attempt ${attempts}/${maxAttempts}`);
          
          // Check for loading states
          const loadingElements = document.querySelectorAll('.word-loading, .loading, [class*="loading"]');
          if (loadingElements.length > 0) {
            console.log('Document still loading, waiting...');
            if (attempts < maxAttempts) {
              setTimeout(waitAndApply, 500);
              return;
            }
          }
          
          // Ultra-aggressive content detection
          const allElements = [...document.querySelectorAll('*')];
          const contentCandidates = allElements.filter(el => {
            if (!el.textContent) return false;
            
            const text = el.textContent.trim();
            const textLength = text.length;
            
            // Skip if too small or contains loading/error messages
            if (textLength < 100) return false;
            if (text.includes('Loading')) return false;
            if (text.includes('No content')) return false;
            if (text.includes('Select a chapter')) return false;
            if (text.includes('Document Processing Issue')) return false;
            
            // Skip if it's a highlight marker itself
            if (el.classList.contains('highlight-marker')) return false;
            
            // Prefer elements that look like Word content
            const hasWordClasses = el.className.includes('word-') || 
                                 el.className.includes('document') ||
                                 el.id.includes('word') ||
                                 el.id.includes('document');
            
            // Must have substantial text
            return textLength > 100;
          }).sort((a, b) => {
            // Sophisticated scoring system
            let scoreA = a.textContent.length;
            let scoreB = b.textContent.length;
            
            // Boost score for Word-related classes/IDs
            if (a.className.includes('word-content')) scoreA += 10000;
            if (b.className.includes('word-content')) scoreB += 10000;
            
            if (a.className.includes('word-')) scoreA += 5000;
            if (b.className.includes('word-')) scoreB += 5000;
            
            if (a.id.includes('word-viewer')) scoreA += 3000;
            if (b.id.includes('word-viewer')) scoreB += 3000;
            
            // Penalize elements with many children (likely containers)
            scoreA -= a.children.length * 10;
            scoreB -= b.children.length * 10;
            
            return scoreB - scoreA;
          });
          
          console.log(`Found ${contentCandidates.length} content candidates`);
          contentCandidates.slice(0, 5).forEach((el, i) => {
            console.log(`Candidate ${i + 1}: ${el.tagName}.${el.className}#${el.id} - ${el.textContent.length} chars`);
            console.log(`  Preview: "${el.textContent.substring(0, 100)}..."`);
          });
          
          if (contentCandidates.length > 0) {
            const targetElement = contentCandidates[0];
            console.log('🎯 Using best candidate:', targetElement);
            
            // Force load highlights directly into this element
            fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
              .then(response => response.json())
              .then(data => {
                if (data.success && data.highlights.length > 0) {
                  console.log(`📊 Loading ${data.highlights.length} highlights into target element`);
                  
                  // Clear existing highlights
                  targetElement.querySelectorAll('.highlight-marker').forEach(h => {
                    const parent = h.parentNode;
                    if (parent) {
                      parent.insertBefore(document.createTextNode(h.textContent), h);
                      parent.removeChild(h);
                    }
                  });
                  
                  // Apply each highlight with ultra-aggressive text matching
                  let successCount = 0;
                  data.highlights.forEach((highlight, index) => {
                    console.log(`Applying highlight ${index + 1}: "${highlight.highlighted_text}"`);
                    
                    if (highlight.highlighted_text && window.ultraAggressiveHighlightApply(highlight, targetElement)) {
                      successCount++;
                    }
                  });
                  
                  if (successCount > 0) {
                    showNotification(`✅ Quick fix successful! Applied ${successCount}/${data.highlights.length} highlights.`, 'success');
                  } else {
                    showNotification(`⚠️ Found ${data.highlights.length} highlights in database but could not apply to content. Text may have changed.`, 'warning');
                  }
                } else {
                  showNotification('ℹ️ No highlights found in database for this chapter.', 'info');
                }
              })
              .catch(error => {
                console.error('Quick fix error:', error);
                showNotification('❌ Quick fix failed: ' + error.message, 'error');
              });
          } else {
            showNotification('❌ No suitable content found. Document may not be loaded yet. Try waiting a moment and clicking Quick Fix again.', 'error');
            
            // Show detailed debug info
            console.log('🔍 Available elements with text content:');
            document.querySelectorAll('*').forEach((el, i) => {
              if (el.textContent && el.textContent.trim().length > 20) {
                console.log(`${i}: ${el.tagName}.${el.className}#${el.id} - "${el.textContent.substring(0, 50)}..."`);
              }
            });
          }
        };
        
        waitAndApply();
      };
      
      // Ultra-aggressive highlight apply function
      window.ultraAggressiveHighlightApply = function(highlight, contentElement) {
        if (!highlight || !highlight.highlighted_text || !contentElement) {
          return false;
        }
        
        const highlightText = highlight.highlighted_text.trim();
        const highlightId = highlight.id;
        
        // Check if already exists
        if (document.querySelector(`[data-highlight-id="${highlightId}"]`)) {
          console.log('Highlight already exists, skipping');
          return true;
        }
        
        console.log(`🔍 Ultra-aggressive search for: "${highlightText}"`);
        console.log(`Content preview: "${contentElement.textContent.substring(0, 200)}..."`);
        
        // Method 1: Exact text match
        if (contentElement.textContent.includes(highlightText)) {
          console.log('✅ Found exact text match');
          return window.applyHighlightWithTextWalker(highlight, contentElement);
        }
        
        // Method 2: Normalize whitespace and try again
        const normalizedContent = contentElement.textContent.replace(/\s+/g, ' ').trim();
        const normalizedHighlight = highlightText.replace(/\s+/g, ' ').trim();
        
        if (normalizedContent.includes(normalizedHighlight)) {
          console.log('✅ Found normalized text match');
          return window.applyHighlightWithTextWalker(highlight, contentElement);
        }
        
        // Method 3: Remove all punctuation and special characters
        const cleanContent = contentElement.textContent.replace(/[^\w\s]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
        const cleanHighlight = highlightText.replace(/[^\w\s]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
        
        if (cleanContent.includes(cleanHighlight)) {
          console.log('✅ Found clean text match (removed punctuation)');
          return window.applyHighlightWithTextWalker(highlight, contentElement);
        }
        
        // Method 4: Try partial matches (first 15 characters)
        const partialText = highlightText.substring(0, 15);
        if (contentElement.textContent.includes(partialText)) {
          console.log('✅ Found partial text match (15 chars)');
          return window.applyHighlightWithTextWalker(highlight, contentElement);
        }
        
        // Method 5: Try word-by-word matching (find at least 3 consecutive words)
        const highlightWords = highlightText.split(/\s+/).filter(w => w.length > 2);
        if (highlightWords.length >= 3) {
          for (let i = 0; i <= highlightWords.length - 3; i++) {
            const threeWords = highlightWords.slice(i, i + 3).join(' ');
            if (contentElement.textContent.includes(threeWords)) {
              console.log(`✅ Found 3-word match: "${threeWords}"`);
              return window.applyHighlightWithTextWalker(highlight, contentElement);
            }
          }
        }
        
        // Method 6: Fuzzy matching - check if most words are present
        if (highlightWords.length >= 2) {
          const wordsFound = highlightWords.filter(word => 
            contentElement.textContent.toLowerCase().includes(word.toLowerCase())
          );
          const matchRatio = wordsFound.length / highlightWords.length;
          
          if (matchRatio >= 0.7) { // 70% of words must match
            console.log(`✅ Found fuzzy match (${Math.round(matchRatio * 100)}% words match)`);
            return window.applyHighlightWithTextWalker(highlight, contentElement);
          }
        }
        
        // Method 7: Last resort - try to find similar text by length and first few characters
        const sentences = contentElement.textContent.split(/[.!?]\s+/);
        for (const sentence of sentences) {
          if (Math.abs(sentence.length - highlightText.length) < 20 && 
              sentence.substring(0, 10).toLowerCase() === highlightText.substring(0, 10).toLowerCase()) {
            console.log(`✅ Found similar sentence by length and start: "${sentence.substring(0, 50)}..."`);
            return window.applyHighlightWithTextWalker(highlight, contentElement);
          }
        }
        
        console.log('❌ No match found after all methods');
        console.log(`Highlight text: "${highlightText}"`);
        console.log(`Highlight words: [${highlightWords.join(', ')}]`);
        
        // Show what text IS available for debugging
        const availableText = contentElement.textContent.substring(0, 500);
        console.log(`Available content start: "${availableText}"`);
        
        return false;
      };
      
      // Fullscreen-specific highlight application function
      window.applyFullscreenHighlightDirect = function(highlight, contentElement) {
        if (!highlight || !highlight.highlighted_text || !contentElement) {
          console.log('[Fullscreen] Invalid highlight or content element');
          return false;
        }
        
        const highlightText = highlight.highlighted_text.trim();
        const highlightId = highlight.id;
        
        // Check if already exists
        if (document.querySelector(`[data-highlight-id="${highlightId}"]`)) {
          console.log('[Fullscreen] Highlight already exists, skipping');
          return true;
        }
        
        console.log(`[Fullscreen] Applying highlight: "${highlightText}"`);
        
        // Use TreeWalker to find text nodes in fullscreen content
        const walker = document.createTreeWalker(
          contentElement,
          NodeFilter.SHOW_TEXT,
          null,
          false
        );
        
        let currentNode;
        while (currentNode = walker.nextNode()) {
          const nodeText = currentNode.textContent;
          let index = -1;
          
          // Try multiple matching strategies
          index = nodeText.indexOf(highlightText);
          if (index === -1) {
            // Try case-insensitive
            index = nodeText.toLowerCase().indexOf(highlightText.toLowerCase());
          }
          if (index === -1) {
            // Try normalized whitespace
            const normalizedNode = nodeText.replace(/\s+/g, ' ').trim();
            const normalizedHighlight = highlightText.replace(/\s+/g, ' ').trim();
            const normalizedIndex = normalizedNode.toLowerCase().indexOf(normalizedHighlight.toLowerCase());
            if (normalizedIndex !== -1) {
              // Approximate the position in the original text
              index = Math.max(0, normalizedIndex);
            }
          }
          
          if (index !== -1) {
            try {
              const matchLength = Math.min(highlightText.length, nodeText.length - index);
              
              // Split the text node
              const before = nodeText.substring(0, index);
              const highlighted = nodeText.substring(index, index + matchLength);
              const after = nodeText.substring(index + matchLength);
              
              // Create fullscreen highlight span
              const highlightSpan = document.createElement('mark');
              highlightSpan.style.cssText = `
                background-color: ${highlight.highlight_color || '#ffeb3b'} !important;
                padding: 2px 4px !important;
                border-radius: 3px !important;
                position: relative !important;
                cursor: pointer !important;
                display: inline !important;
                z-index: 1 !important;
                transition: all 0.2s ease !important;
              `;
              highlightSpan.className = 'highlight-marker fullscreen-highlight';
              highlightSpan.dataset.highlightId = highlightId;
              highlightSpan.title = `Highlighted by ${highlight.adviser_name || 'Adviser'} - Click to view details`;
              highlightSpan.textContent = highlighted;
              
              // Add click handler for fullscreen highlights
              highlightSpan.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[Fullscreen] Highlight clicked:', highlightId);
                if (typeof window.openHighlightCommentModal === 'function') {
                  window.openHighlightCommentModal(highlightId, highlighted, window.currentChapterId);
                }
              });
              
              // Replace the text node
              const parent = currentNode.parentNode;
              if (before) parent.insertBefore(document.createTextNode(before), currentNode);
              parent.insertBefore(highlightSpan, currentNode);
              if (after) parent.insertBefore(document.createTextNode(after), currentNode);
              parent.removeChild(currentNode);
              
              console.log(`[Fullscreen] ✅ Highlight applied successfully: "${highlighted}"`);
              return true;
            } catch (error) {
              console.error('[Fullscreen] Error applying highlight:', error);
              return false;
            }
          }
        }
        
        console.log(`[Fullscreen] ❌ Could not find text "${highlightText}" in content`);
        return false;
      };

      // Text walker function for precise highlight application
      window.applyHighlightWithTextWalker = function(highlight, contentElement) {
        const highlightText = highlight.highlighted_text.trim();
        const walker = document.createTreeWalker(
          contentElement,
          NodeFilter.SHOW_TEXT,
          null,
          false
        );
        
        let currentNode;
        let attempts = [];
        
        // Try multiple matching strategies
        const matchingStrategies = [
          highlightText, // Exact match
          highlightText.replace(/\s+/g, ' '), // Normalized whitespace
          highlightText.replace(/[^\w\s]/g, '').replace(/\s+/g, ' '), // No punctuation
          highlightText.substring(0, 15), // Partial match
        ];
        
        // Also try 3-word combinations
        const words = highlightText.split(/\s+/).filter(w => w.length > 2);
        if (words.length >= 3) {
          for (let i = 0; i <= words.length - 3; i++) {
            matchingStrategies.push(words.slice(i, i + 3).join(' '));
          }
        }
        
        while (currentNode = walker.nextNode()) {
          const nodeText = currentNode.textContent;
          
          for (const strategy of matchingStrategies) {
            let index = -1;
            
            // Try exact match first
            index = nodeText.indexOf(strategy);
            
            // If no exact match, try case-insensitive
            if (index === -1) {
              const lowerNodeText = nodeText.toLowerCase();
              const lowerStrategy = strategy.toLowerCase();
              index = lowerNodeText.indexOf(lowerStrategy);
            }
            
            // If no case-insensitive match, try without punctuation
            if (index === -1) {
              const cleanNodeText = nodeText.replace(/[^\w\s]/g, '').replace(/\s+/g, ' ');
              const cleanStrategy = strategy.replace(/[^\w\s]/g, '').replace(/\s+/g, ' ');
              const cleanIndex = cleanNodeText.toLowerCase().indexOf(cleanStrategy.toLowerCase());
              if (cleanIndex !== -1) {
                // Approximate the position in the original text
                index = Math.max(0, cleanIndex);
              }
            }
            
            if (index !== -1) {
              try {
                // For fuzzy matches, use the original highlight text length
                const matchLength = strategy === highlightText ? highlightText.length : 
                                  Math.min(strategy.length, nodeText.length - index);
                
                // Split the text node
                const before = nodeText.substring(0, index);
                const highlighted = nodeText.substring(index, index + matchLength);
                const after = nodeText.substring(index + matchLength);
                
                // Create highlight span
                const highlightSpan = document.createElement('span');
                highlightSpan.className = 'highlight-marker';
                highlightSpan.style.backgroundColor = highlight.highlight_color || '#ffeb3b';
                highlightSpan.style.padding = '2px 0';
                highlightSpan.dataset.highlightId = highlight.id;
                highlightSpan.textContent = highlighted;
                
                // Replace the text node
                const parent = currentNode.parentNode;
                if (before) parent.insertBefore(document.createTextNode(before), currentNode);
                parent.insertBefore(highlightSpan, currentNode);
                if (after) parent.insertBefore(document.createTextNode(after), currentNode);
                parent.removeChild(currentNode);
                
                console.log(`✅ Highlight applied successfully using strategy: "${strategy}"`);
                console.log(`Highlighted text: "${highlighted}"`);
                return true;
              } catch (error) {
                console.error('Error applying highlight:', error);
                attempts.push(`Failed on strategy "${strategy}": ${error.message}`);
                continue;
              }
            }
          }
        }
        
        console.log('❌ All matching strategies failed');
        if (attempts.length > 0) {
          console.log('Attempts made:', attempts);
        }
        return false;
      };

      // Debug text matching function
      window.debugTextMatching = function() {
        console.log('🔍 DEBUG TEXT MATCHING - Starting comprehensive analysis...');
        
        if (!window.currentChapterId) {
          showNotification('Please select a chapter first', 'warning');
          return;
        }
        
        showNotification('🔍 Analyzing text matching...', 'info');
        
        // Get highlights from database
        fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
          .then(response => response.json())
          .then(data => {
            if (!data.success || !data.highlights.length) {
              showNotification('No highlights found in database', 'info');
              return;
            }
            
            console.log(`📊 Found ${data.highlights.length} highlights in database`);
            
            // Find content element
            const allElements = [...document.querySelectorAll('*')];
            const contentCandidates = allElements.filter(el => {
              if (!el.textContent) return false;
              const text = el.textContent.trim();
              return text.length > 100 && 
                     !text.includes('Loading') && 
                     !text.includes('No content') &&
                     !el.classList.contains('highlight-marker');
            }).sort((a, b) => {
              let scoreA = a.textContent.length;
              let scoreB = b.textContent.length;
              if (a.className.includes('word-content')) scoreA += 10000;
              if (b.className.includes('word-content')) scoreB += 10000;
              return scoreB - scoreA;
            });
            
            if (!contentCandidates.length) {
              console.log('❌ No content candidates found');
              showNotification('No content found for analysis', 'error');
              return;
            }
            
            const contentElement = contentCandidates[0];
            console.log('🎯 Using content element:', contentElement);
            console.log(`📝 Content length: ${contentElement.textContent.length} characters`);
            
            // Analyze each highlight
            data.highlights.forEach((highlight, index) => {
              console.log(`\n=== HIGHLIGHT ${index + 1} ANALYSIS ===`);
              console.log(`ID: ${highlight.id}`);
              console.log(`Original text: "${highlight.highlighted_text}"`);
              console.log(`Color: ${highlight.highlight_color}`);
              console.log(`Length: ${highlight.highlighted_text.length} characters`);
              
              // Show first 200 chars of content for comparison
              console.log(`\n📄 Document content preview:`);
              console.log(`"${contentElement.textContent.substring(0, 200)}..."`);
              
              // Test all matching methods
              const highlightText = highlight.highlighted_text.trim();
              const content = contentElement.textContent;
              
              console.log(`\n🔍 Testing matching methods:`);
              
              // Method 1: Exact match
              const exactMatch = content.includes(highlightText);
              console.log(`1. Exact match: ${exactMatch ? '✅' : '❌'}`);
              
              // Method 2: Normalized whitespace
              const normalizedContent = content.replace(/\s+/g, ' ').trim();
              const normalizedHighlight = highlightText.replace(/\s+/g, ' ').trim();
              const normalizedMatch = normalizedContent.includes(normalizedHighlight);
              console.log(`2. Normalized match: ${normalizedMatch ? '✅' : '❌'}`);
              
              // Method 3: Clean (no punctuation)
              const cleanContent = content.replace(/[^\w\s]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
              const cleanHighlight = highlightText.replace(/[^\w\s]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
              const cleanMatch = cleanContent.includes(cleanHighlight);
              console.log(`3. Clean match (no punctuation): ${cleanMatch ? '✅' : '❌'}`);
              
              // Method 4: Partial match
              const partialText = highlightText.substring(0, 15);
              const partialMatch = content.includes(partialText);
              console.log(`4. Partial match (15 chars): ${partialMatch ? '✅' : '❌'}`);
              if (partialMatch) {
                console.log(`   Partial text: "${partialText}"`);
              }
              
              // Method 5: Word analysis
              const highlightWords = highlightText.split(/\s+/).filter(w => w.length > 2);
              console.log(`5. Word analysis: ${highlightWords.length} significant words`);
              console.log(`   Words: [${highlightWords.join(', ')}]`);
              
              const wordsFound = highlightWords.filter(word => 
                content.toLowerCase().includes(word.toLowerCase())
              );
              const wordMatchRatio = wordsFound.length / highlightWords.length;
              console.log(`   Words found: [${wordsFound.join(', ')}]`);
              console.log(`   Match ratio: ${Math.round(wordMatchRatio * 100)}%`);
              console.log(`   Fuzzy match (70% threshold): ${wordMatchRatio >= 0.7 ? '✅' : '❌'}`);
              
              // Method 6: 3-word consecutive search
              if (highlightWords.length >= 3) {
                console.log(`6. 3-word consecutive search:`);
                let found3Words = false;
                for (let i = 0; i <= highlightWords.length - 3; i++) {
                  const threeWords = highlightWords.slice(i, i + 3).join(' ');
                  const found = content.includes(threeWords);
                  console.log(`   "${threeWords}": ${found ? '✅' : '❌'}`);
                  if (found) found3Words = true;
                }
                console.log(`   Any 3-word match: ${found3Words ? '✅' : '❌'}`);
              }
              
              // Method 7: Case-insensitive search
              const caseInsensitiveMatch = content.toLowerCase().includes(highlightText.toLowerCase());
              console.log(`7. Case-insensitive match: ${caseInsensitiveMatch ? '✅' : '❌'}`);
              
              // Show closest matches if no exact match
              if (!exactMatch) {
                console.log(`\n🔍 Searching for similar text...`);
                const sentences = content.split(/[.!?]\s+/);
                const similarities = sentences.map(sentence => {
                  const lengthDiff = Math.abs(sentence.length - highlightText.length);
                  const startMatch = sentence.substring(0, 10).toLowerCase() === highlightText.substring(0, 10).toLowerCase();
                  return { sentence, lengthDiff, startMatch, similarity: startMatch ? 1000 - lengthDiff : lengthDiff };
                }).sort((a, b) => b.similarity - a.similarity);
                
                console.log(`Most similar sentences:`);
                similarities.slice(0, 3).forEach((item, i) => {
                  console.log(`${i + 1}. "${item.sentence.substring(0, 50)}..." (length diff: ${item.lengthDiff})`);
                });
              }
              
              console.log(`=== END HIGHLIGHT ${index + 1} ===\n`);
            });
            
            console.log('🔍 DEBUG ANALYSIS COMPLETE');
            showNotification(`Debug complete! Check console for detailed analysis of ${data.highlights.length} highlights.`, 'info');
          })
          .catch(error => {
            console.error('Debug error:', error);
            showNotification('Debug failed: ' + error.message, 'error');
          });
      };

      // Emergency highlight loader - tries everything
      window.emergencyLoadHighlights = function() {
        console.log('🚨 EMERGENCY HIGHLIGHT LOADER - Trying everything...');
        showNotification('🚨 Emergency highlight loading...', 'info');
        
        if (!window.currentChapterId) {
          showNotification('Please select a chapter first', 'warning');
          return;
        }
        
        // Method 1: Use the smart function
        console.log('📍 Method 1: Smart waiting function');
        setTimeout(() => window.waitForWordViewerAndLoadHighlights(), 100);
        
        // Method 2: Direct content search after delay
        setTimeout(() => {
          console.log('📍 Method 2: Direct content search');
          const allDivs = [...document.querySelectorAll('div')].filter(div => 
            div.textContent && div.textContent.trim().length > 100
          ).sort((a, b) => b.textContent.length - a.textContent.length);
          
          if (allDivs.length > 0) {
            console.log(`Found ${allDivs.length} potential content divs, using the largest one`);
            window.forceLoadHighlightsWithContent(allDivs[0]);
          }
        }, 2000);
        
        // Method 3: Brute force after longer delay
        setTimeout(() => {
          console.log('📍 Method 3: Brute force application');
          // Get all elements with text
          const textElements = [...document.querySelectorAll('*')].filter(el => 
            el.textContent && 
            el.textContent.trim().length > 200 && 
            !el.querySelector('*') // Prefer leaf elements
          );
          
          if (textElements.length > 0) {
            console.log(`Found ${textElements.length} text elements, trying the first one`);
            window.forceLoadHighlightsWithContent(textElements[0]);
          }
        }, 4000);
        
        // Final status check
        setTimeout(() => {
          const highlightCount = document.querySelectorAll('.highlight-marker').length;
          if (highlightCount > 0) {
            showNotification(`🎉 Emergency loading successful! ${highlightCount} highlights applied.`, 'success');
          } else {
            showNotification('🚨 Emergency loading failed. Document content may not be available.', 'error');
            console.log('🔍 Available elements with text:');
            [...document.querySelectorAll('*')].forEach((el, i) => {
              if (el.textContent && el.textContent.trim().length > 50) {
                console.log(`${i}: ${el.tagName}.${el.className} - "${el.textContent.substring(0, 50)}..."`);
              }
            });
          }
        }, 6000);
      };
      
      // Function to clean up corrupted highlights
      window.cleanupCorruptedHighlights = function() {
        if (!window.currentChapterId) {
          console.error('No chapter selected');
          showNotification('Please select a chapter first', 'warning');
          return;
        }
        
        console.log('Checking for corrupted highlights...');
        showNotification('Checking for corrupted highlights...', 'info');
        
        fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const corruptedHighlights = data.highlights.filter(h => 
                !h.highlighted_text || !h.id || h.highlighted_text.trim() === ''
              );
              
              if (corruptedHighlights.length === 0) {
                console.log('✅ No corrupted highlights found');
                showNotification('No corrupted highlights found', 'success');
                return;
              }
              
              console.log(`Found ${corruptedHighlights.length} corrupted highlights:`, corruptedHighlights);
              
              if (confirm(`Found ${corruptedHighlights.length} corrupted highlights. Do you want to remove them?`)) {
                // Remove corrupted highlights one by one
                let removed = 0;
                const removeNext = (index) => {
                  if (index >= corruptedHighlights.length) {
                    showNotification(`✅ Removed ${removed} corrupted highlights`, 'success');
                    // Reload highlights after cleanup
                    setTimeout(() => window.reloadHighlights(), 500);
                    return;
                  }
                  
                  const highlight = corruptedHighlights[index];
                  if (highlight.id) {
                    fetch('api/document_review.php', {
                      method: 'POST',
                      body: new URLSearchParams({
                        action: 'remove_highlight',
                        highlight_id: highlight.id
                      })
                    })
                    .then(response => response.json())
                    .then(result => {
                      if (result.success) {
                        removed++;
                        console.log(`Removed corrupted highlight ${highlight.id}`);
                      }
                      removeNext(index + 1);
                    })
                    .catch(error => {
                      console.error('Error removing highlight:', error);
                      removeNext(index + 1);
                    });
                  } else {
                    removeNext(index + 1);
                  }
                };
                
                removeNext(0);
              }
            } else {
              console.error('Failed to fetch highlights for cleanup');
              showNotification('Failed to fetch highlights for cleanup', 'error');
            }
          })
          .catch(error => {
            console.error('Error during cleanup:', error);
            showNotification('Error during cleanup: ' + error.message, 'error');
          });
      };

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
      // Generate requirements summary HTML
      function generateRequirementsSummary(requirements) {
        if (!requirements || Object.keys(requirements).length === 0) {
          return `
            <div class="border rounded-lg p-3 mb-4 bg-blue-50">
              <h4 class="font-medium text-sm text-blue-700 mb-2 flex items-center">
                <i data-lucide="settings" class="w-4 h-4 mr-2"></i>
                Your Format Requirements
              </h4>
              <p class="text-xs text-blue-600">No custom requirements set. Using default formatting standards.</p>
            </div>
          `;
        }

        let requirementsHtml = `
          <div class="border rounded-lg p-3 mb-4 bg-purple-50">
            <h4 class="font-medium text-sm text-purple-700 mb-3 flex items-center">
              <i data-lucide="settings" class="w-4 h-4 mr-2"></i>
              Your Custom Format Requirements
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
        `;

        // Margins
        if (requirements.margins && Object.keys(requirements.margins).length > 0) {
          requirementsHtml += `
            <div class="bg-white p-2 rounded border">
              <h6 class="font-medium text-purple-700 mb-1">Margins</h6>
              <div class="space-y-1 text-purple-600">
                ${Object.entries(requirements.margins).map(([key, req]) => `
                  <div>${key.charAt(0).toUpperCase() + key.slice(1)}: ${req.value}${req.unit || ''}</div>
                `).join('')}
              </div>
            </div>
          `;
        }

        // Typography
        if (requirements.typography && Object.keys(requirements.typography).length > 0) {
          requirementsHtml += `
            <div class="bg-white p-2 rounded border">
              <h6 class="font-medium text-purple-700 mb-1">Typography</h6>
              <div class="space-y-1 text-purple-600">
                ${Object.entries(requirements.typography).map(([key, req]) => `
                  <div>${key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}: ${req.value}${req.unit || ''}</div>
                `).join('')}
              </div>
            </div>
          `;
        }

        // Spacing
        if (requirements.spacing && Object.keys(requirements.spacing).length > 0) {
          requirementsHtml += `
            <div class="bg-white p-2 rounded border">
              <h6 class="font-medium text-purple-700 mb-1">Spacing</h6>
              <div class="space-y-1 text-purple-600">
                ${Object.entries(requirements.spacing).map(([key, req]) => `
                  <div>${key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}: ${req.value}${req.unit || ''}</div>
                `).join('')}
              </div>
            </div>
          `;
        }

        // Page Setup
        if (requirements.page_setup && Object.keys(requirements.page_setup).length > 0) {
          requirementsHtml += `
            <div class="bg-white p-2 rounded border">
              <h6 class="font-medium text-purple-700 mb-1">Page Setup</h6>
              <div class="space-y-1 text-purple-600">
                ${Object.entries(requirements.page_setup).map(([key, req]) => `
                  <div>${key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}: ${req.value.charAt(0).toUpperCase() + req.value.slice(1)}</div>
                `).join('')}
              </div>
            </div>
          `;
        }

        requirementsHtml += `
            </div>
            <p class="text-xs text-purple-600 mt-2 italic">These are your personalized requirements. Document analysis is based on these standards.</p>
          </div>
        `;

        return requirementsHtml;
      }





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
              displayFormatAnalysis(data.analysis, data.file_info, data.requirements);
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

      window.displayFormatAnalysis = function(analysis, fileInfo, requirements = {}) {
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

          <!-- Custom Requirements Summary -->
          ${generateRequirementsSummary(requirements)}

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
        console.log('Removing highlight:', highlightId);
        
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
            console.log('Highlight removed successfully');
            showNotification('Highlight removed successfully!', 'success');
            
            // Remove highlight from DOM immediately for visual feedback
            const highlightElements = document.querySelectorAll(`[data-highlight-id="${highlightId}"]`);
            highlightElements.forEach(highlightElement => {
              if (highlightElement) {
                const parent = highlightElement.parentNode;
                if (parent) {
                  parent.insertBefore(document.createTextNode(highlightElement.textContent), highlightElement);
                  parent.removeChild(highlightElement);
                }
              }
            });
            
            // Also reload comments in case any were linked to this highlight
            if (window.currentChapterId && typeof loadComments === 'function') {
              loadComments(window.currentChapterId);
            }
          } else {
            console.error('Failed to remove highlight:', data.error);
            showNotification('Failed to remove highlight: ' + data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Error removing highlight:', error);
          showNotification('Failed to remove highlight: ' + error.message, 'error');
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

      // Paragraph comment modal functionality (legacy - using global window.openParagraphCommentModal now)
      // function openParagraphCommentModal(paragraphId, paragraphContent) {
      //   const paragraphIdInput = document.getElementById('paragraph-id-input');
      //   const paragraphTextPreview = document.getElementById('paragraph-text-preview');
      //   const paragraphCommentModal = document.getElementById('paragraph-comment-modal');
      //   
      //   if (paragraphIdInput && paragraphTextPreview && paragraphCommentModal) {
      //     paragraphIdInput.value = paragraphId;
      //     paragraphTextPreview.textContent = paragraphContent.substring(0, 300) + 
      //       (paragraphContent.length > 300 ? '...' : '');
      //     paragraphCommentModal.classList.remove('hidden');
      //   } else {
      //     console.error('One or more paragraph comment modal elements not found');
      //   }
      // }
      
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
      let currentArchivePage = 1;
      let currentActivityPage = 1;
      let activityLogsPerPage = 10;
      
      function loadActivityLogs() {
        const typeFilter = document.getElementById('activity-type-filter').value;
        const daysFilter = document.getElementById('activity-time-filter').value;
        const sortFilter = document.getElementById('activity-sort-filter').value;
        const [sortBy, sortOrder] = sortFilter.split(':');
        
        // Show loading state
        document.getElementById('activity-logs-list').innerHTML = `
          <div class="flex items-center justify-center py-8">
            <i data-lucide="loader" class="w-5 h-5 animate-spin mr-2"></i>
            Loading activity logs...
          </div>
        `;
        
        // Build query parameters
        const params = new URLSearchParams({
          action: 'activity_logs',
          sort_by: sortBy,
          sort_order: sortOrder,
          page: currentActivityPage,
          limit: activityLogsPerPage
        });
        
        if (daysFilter !== 'all') {
          params.append('days', daysFilter);
        }
        
        if (typeFilter) {
          params.append('event_type', typeFilter.toLowerCase().replace(' ', '_'));
        }
        
        fetch(`api/activity_logs_archive.php?${params}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const logs = data.logs || [];
              displayActivityLogs(logs);
              updateActivityLogsPagination(data.page, data.total_pages, data.total_count);
            } else {
              throw new Error(data.error || 'Failed to load logs');
            }
          })
          .catch(error => {
            console.error('Error loading activity logs:', error);
            document.getElementById('activity-logs-list').innerHTML = `
              <div class="text-center py-8 text-red-500">
                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-3"></i>
                <p>Failed to load activity logs: ${error.message}</p>
              </div>
            `;
            document.getElementById('activity-logs-pagination').classList.add('hidden');
            lucide.createIcons();
          });
      }
      
      function displayActivityLogs(logs) {
        const logsHtml = logs.length ? logs.map(log => {
          const details = log.details_parsed || {};
          return `
            <div class="flex items-start gap-3 p-4 border rounded-lg hover:bg-gray-50">
              <div class="mt-1">
                ${getActivityIcon(log.event_type)}
              </div>
              <div class="flex-1">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-medium">${formatEventType(log.event_type)}</p>
                    <p class="text-sm text-gray-600">${generateDescription(log)}</p>
                    ${details.chapter_title || details.student_name ? `
                      <div class="mt-1 text-xs text-gray-500">
                        ${details.chapter_title ? `<span class="font-medium">${details.chapter_title}</span>` : ''}
                        ${details.chapter_title && details.student_name ? ' • ' : ''}
                        ${details.student_name ? `<span>${details.student_name}</span>` : ''}
                      </div>
                    ` : ''}
                  </div>
                  <span class="text-xs text-gray-500">${formatDate(log.formatted_date)}</span>
                </div>
                <div class="mt-2 flex flex-wrap gap-1">
                  <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                    ${log.entity_type}
                  </span>
                  ${details.action ? `
                    <span class="text-xs px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full">
                      ${details.action.replace('_', ' ')}
                    </span>
                  ` : ''}
                </div>
              </div>
            </div>
          `;
        }).join('') : `
          <div class="text-center py-8 text-gray-500">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
            <p>No activity logs found</p>
          </div>
        `;
        
        document.getElementById('activity-logs-list').innerHTML = logsHtml;
        lucide.createIcons();
      }
      
      function updateActivityLogsPagination(currentPage, totalPages, totalCount) {
        const pagination = document.getElementById('activity-logs-pagination');
        const prevBtn = document.getElementById('activity-prev-page');
        const nextBtn = document.getElementById('activity-next-page');
        const pageInfo = document.getElementById('activity-page-info');
        const totalInfo = document.getElementById('activity-logs-total-info');
        
        // Update total info
        const startRecord = totalCount > 0 ? ((currentPage - 1) * activityLogsPerPage) + 1 : 0;
        const endRecord = Math.min(currentPage * activityLogsPerPage, totalCount);
        totalInfo.textContent = `Showing ${startRecord}-${endRecord} of ${totalCount} logs`;
        
        if (totalPages > 1) {
          pagination.classList.remove('hidden');
          pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
          
          prevBtn.disabled = currentPage === 1;
          nextBtn.disabled = currentPage === totalPages;
          
          // Update button styles based on disabled state
          if (currentPage === 1) {
            prevBtn.classList.add('opacity-50', 'cursor-not-allowed');
          } else {
            prevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
          }
          
          if (currentPage === totalPages) {
            nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
          } else {
            nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
          }
        } else {
          pagination.classList.add('hidden');
        }
      }
      
      function loadArchivedLogs() {
        const typeFilter = document.getElementById('archive-type-filter').value;
        const sortFilter = document.getElementById('archive-sort-filter').value;
        const dateFrom = document.getElementById('archive-date-from').value;
        const dateTo = document.getElementById('archive-date-to').value;
        const [sortBy, sortOrder] = sortFilter.split(':');
        
        // Show loading state
        document.getElementById('archived-logs-list').innerHTML = `
          <div class="flex items-center justify-center py-8">
            <i data-lucide="loader" class="w-5 h-5 animate-spin mr-2"></i>
            Loading archived logs...
          </div>
        `;
        
        // Build query parameters
        const params = new URLSearchParams({
          action: 'archived_logs',
          page: currentArchivePage,
          limit: 20,
          sort_by: sortBy,
          sort_order: sortOrder
        });
        
        if (typeFilter) params.append('event_type', typeFilter);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        fetch(`api/activity_logs_archive.php?${params}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              displayArchivedLogs(data.logs);
              updateArchivePagination(data.page, data.total_pages);
            } else {
              throw new Error(data.error || 'Failed to load archived logs');
            }
          })
          .catch(error => {
            console.error('Error loading archived logs:', error);
            document.getElementById('archived-logs-list').innerHTML = `
              <div class="text-center py-8 text-red-500">
                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-3"></i>
                <p>Failed to load archived logs: ${error.message}</p>
              </div>
            `;
            lucide.createIcons();
          });
      }
      
      function displayArchivedLogs(logs) {
        const logsHtml = logs.length ? logs.map(log => {
          const details = log.details_parsed || {};
          const archiveMetadata = log.archive_metadata_parsed || {};
          
          return `
            <div class="flex items-start gap-3 p-4 border rounded-lg hover:bg-gray-50">
              <input type="checkbox" class="archive-log-checkbox mt-4" value="${log.id}">
              <div class="mt-1">
                ${getActivityIcon(log.event_type)}
              </div>
              <div class="flex-1">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-medium">${formatEventType(log.event_type)}</p>
                    <p class="text-sm text-gray-600">${generateDescription(log)}</p>
                    <div class="mt-1 text-xs text-gray-500">
                      Original: ${formatDate(log.formatted_original_date)} • 
                      Archived: ${formatDate(log.formatted_archived_date)} • 
                      By: ${log.archived_by_name}
                    </div>
                    ${log.archive_reason ? `
                      <div class="mt-1">
                        <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                          ${log.archive_reason.replace('_', ' ')}
                        </span>
                      </div>
                    ` : ''}
                  </div>
                  <div class="text-right">
                    <button class="restore-log-btn text-xs px-2 py-1 bg-green-100 text-green-800 rounded hover:bg-green-200" data-log-id="${log.id}">
                      <i data-lucide="rotate-ccw" class="w-3 h-3 inline mr-1"></i>
                      Restore
                    </button>
                  </div>
                </div>
              </div>
            </div>
          `;
        }).join('') : `
          <div class="text-center py-8 text-gray-500">
            <i data-lucide="archive" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
            <p>No archived logs found</p>
          </div>
        `;
        
        document.getElementById('archived-logs-list').innerHTML = logsHtml;
        lucide.createIcons();
        
        // Add event listeners for restore buttons
        document.querySelectorAll('.restore-log-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            const logId = this.getAttribute('data-log-id');
            restoreLog([logId]);
          });
        });
      }
      
      function loadArchiveStatistics() {
        fetch('api/activity_logs_archive.php?action=archive_statistics')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const stats = data.statistics;
              
              document.getElementById('archive-total-count').textContent = stats.total_archived || 0;
              
              // Calculate this month's archives
              const thisMonth = stats.recent_activity ? 
                stats.recent_activity.reduce((sum, item) => sum + parseInt(item.count), 0) : 0;
              document.getElementById('archive-this-month').textContent = thisMonth;
              
              // Most common type
              const mostCommonType = stats.by_event_type && stats.by_event_type.length > 0 ? 
                formatEventType(stats.by_event_type[0].event_type) : '-';
              document.getElementById('archive-most-type').textContent = mostCommonType;
              
              // Oldest archive date
              const oldestDate = stats.oldest_log ? 
                formatDate(stats.oldest_log) : '-';
              document.getElementById('archive-oldest-date').textContent = oldestDate;
            }
          })
          .catch(error => {
            console.error('Error loading archive statistics:', error);
          });
      }
      
      function updateArchivePagination(currentPage, totalPages) {
        const pagination = document.getElementById('archive-pagination');
        const prevBtn = document.getElementById('archive-prev-page');
        const nextBtn = document.getElementById('archive-next-page');
        const pageInfo = document.getElementById('archive-page-info');
        
        if (totalPages > 1) {
          pagination.classList.remove('hidden');
          pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
          
          prevBtn.disabled = currentPage === 1;
          nextBtn.disabled = currentPage === totalPages;
          
          prevBtn.onclick = () => {
            if (currentPage > 1) {
              currentArchivePage = currentPage - 1;
              loadArchivedLogs();
            }
          };
          
          nextBtn.onclick = () => {
            if (currentPage < totalPages) {
              currentArchivePage = currentPage + 1;
              loadArchivedLogs();
            }
          };
        } else {
          pagination.classList.add('hidden');
        }
      }
      
      function clearLogs() {
        const daysSelect = document.getElementById('clear-days-select').value;
        const eventTypes = Array.from(document.querySelectorAll('.clear-activity-type:checked')).map(cb => cb.value);
        const reason = document.getElementById('clear-reason').value || 'manual_clear';
        
        if (!daysSelect && eventTypes.length === 0) {
          showNotification('Please select time period or activity types to clear', 'error');
          return;
        }
        
        const requestData = {
          action: 'clear_logs',
          reason: reason
        };
        
        if (daysSelect && daysSelect !== 'all') {
          requestData.days = parseInt(daysSelect);
        }
        
        if (eventTypes.length > 0) {
          requestData.event_types = eventTypes;
        }
        
        fetch('api/activity_logs_archive.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification(data.message, 'success');
            document.getElementById('clear-logs-modal').classList.add('hidden');
            currentActivityPage = 1; // Reset to first page after clearing logs
            loadActivityLogs(); // Reload logs
          } else {
            showNotification(data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Error clearing logs:', error);
          showNotification('Failed to clear logs', 'error');
        });
      }
      
      function restoreLog(logIds) {
        fetch('api/activity_logs_archive.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'restore_logs',
            log_ids: logIds
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification(data.message, 'success');
            loadArchivedLogs(); // Reload archived logs
          } else {
            showNotification(data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Error restoring logs:', error);
          showNotification('Failed to restore logs', 'error');
        });
      }
      
      function exportArchive() {
        const format = document.getElementById('export-format-select').value;
        const dateFrom = document.getElementById('export-date-from').value;
        const dateTo = document.getElementById('export-date-to').value;
        
        const requestData = {
          action: 'export_archive',
          format: format
        };
        
        if (dateFrom) requestData.date_from = dateFrom;
        if (dateTo) requestData.date_to = dateTo;
        
        fetch('api/activity_logs_archive.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Create download link
            const downloadLink = document.createElement('a');
            downloadLink.href = data.download_url;
            downloadLink.download = '';
            downloadLink.click();
            
            showNotification(`Export completed. Downloaded ${data.record_count} records.`, 'success');
            document.getElementById('export-archive-modal').classList.add('hidden');
          } else {
            showNotification(data.error, 'error');
          }
        })
        .catch(error => {
          console.error('Error exporting archive:', error);
          showNotification('Failed to export archive', 'error');
        });
      }
      
      function formatEventType(eventType) {
        return eventType.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
      }
      
      function generateDescription(log) {
        const details = log.details_parsed || {};
        
        switch (log.event_type) {
          case 'comment_activity':
            if (details.action === 'add_comment') {
              return `Added comment${details.comment_text_preview ? ': "' + details.comment_text_preview + '"' : ''}`;
            } else if (details.action === 'resolve_comment') {
              return 'Resolved comment';
            }
            break;
          case 'highlight_activity':
            if (details.action === 'add_highlight') {
              return `Added highlight${details.highlighted_text_preview ? ': "' + details.highlighted_text_preview + '"' : ''}`;
            } else if (details.action === 'remove_highlight') {
              return 'Removed highlight';
            }
            break;
          default:
            return details.description || log.event_type;
        }
        
        return log.event_type;
      }
      
      function getActivityIcon(type) {
        const iconMap = {
          'comment_activity': '<i data-lucide="message-square" class="w-5 h-5 text-indigo-600"></i>',
          'highlight_activity': '<i data-lucide="highlighter" class="w-5 h-5 text-yellow-600"></i>',
          'submission_activity': '<i data-lucide="file-plus" class="w-5 h-5 text-green-600"></i>',
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
      document.querySelector('[data-tab="activity-logs"]').addEventListener('click', function() {
        currentActivityPage = 1; // Reset to first page when switching to activity logs tab
        loadActivityLogs();
        
        // Hide dashboard header and reduce main content padding for activity logs
        document.getElementById('dashboard-header').style.display = 'none';
        document.querySelector('main').classList.add('activity-logs-mode');
      });
      
      // Add filter change handlers
      document.getElementById('activity-type-filter').addEventListener('change', function() {
        currentActivityPage = 1; // Reset to first page when filtering
        loadActivityLogs();
      });
      document.getElementById('activity-time-filter').addEventListener('change', function() {
        currentActivityPage = 1; // Reset to first page when filtering
        loadActivityLogs();
      });
      document.getElementById('activity-sort-filter').addEventListener('change', function() {
        currentActivityPage = 1; // Reset to first page when sorting
        loadActivityLogs();
      });
      
      // Activity logs pagination handlers
      document.getElementById('activity-prev-page').addEventListener('click', function() {
        if (currentActivityPage > 1) {
          currentActivityPage--;
          loadActivityLogs();
        }
      });
      
      document.getElementById('activity-next-page').addEventListener('click', function() {
        currentActivityPage++;
        loadActivityLogs();
      });
      
      document.getElementById('activity-logs-per-page').addEventListener('change', function() {
        activityLogsPerPage = parseInt(this.value);
        currentActivityPage = 1; // Reset to first page when changing page size
        loadActivityLogs();
      });
      
      // Archive filter handlers
      document.getElementById('archive-type-filter').addEventListener('change', loadArchivedLogs);
      document.getElementById('archive-sort-filter').addEventListener('change', loadArchivedLogs);
      document.getElementById('archive-date-from').addEventListener('change', loadArchivedLogs);
      document.getElementById('archive-date-to').addEventListener('change', loadArchivedLogs);
      
      // Clear logs button
      document.getElementById('clear-logs-btn').addEventListener('click', function() {
        document.getElementById('clear-logs-modal').classList.remove('hidden');
      });
      
      // View archive button
      document.getElementById('view-archive-btn').addEventListener('click', function() {
        document.getElementById('archive-section').classList.remove('hidden');
        document.querySelector('#activity-logs-content .bg-white.rounded-lg.shadow.p-6').classList.add('hidden');
        loadArchivedLogs();
        loadArchiveStatistics();
        
        // Maintain activity logs mode in archive view
        document.getElementById('dashboard-header').style.display = 'none';
        document.querySelector('main').classList.add('activity-logs-mode');
      });
      
      // Back to logs button
      document.getElementById('back-to-logs-btn').addEventListener('click', function() {
        document.getElementById('archive-section').classList.add('hidden');
        document.querySelector('#activity-logs-content .bg-white.rounded-lg.shadow.p-6').classList.remove('hidden');
        currentArchivePage = 1;
        
        // Maintain activity logs mode when going back
        document.getElementById('dashboard-header').style.display = 'none';
        document.querySelector('main').classList.add('activity-logs-mode');
      });
      
      // Clear logs modal handlers
      document.getElementById('cancel-clear-logs').addEventListener('click', function() {
        document.getElementById('clear-logs-modal').classList.add('hidden');
      });
      
      document.getElementById('confirm-clear-logs').addEventListener('click', clearLogs);
      
      document.getElementById('clear-all-types').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.clear-activity-type');
        checkboxes.forEach(cb => cb.checked = this.checked);
      });
      
      // Export archive button
      document.getElementById('export-archive-btn').addEventListener('click', function() {
        document.getElementById('export-archive-modal').classList.remove('hidden');
      });
      
      // Export modal handlers
      document.getElementById('cancel-export').addEventListener('click', function() {
        document.getElementById('export-archive-modal').classList.add('hidden');
      });
      
      document.getElementById('confirm-export').addEventListener('click', exportArchive);
      
      // Add refresh button functionality
      document.getElementById('refresh-document-list').addEventListener('click', loadAllStudentsForReview);
      
      // Load students initially if document review tab is active
      const currentTab = new URLSearchParams(window.location.search).get('tab');
      if (currentTab === 'document-review') {
        // Small delay to ensure DOM is ready
        setTimeout(loadAllStudentsForReview, 100);
      }
      
      // Also load students if document review tab is already active (default active tab)
      const activeTab = document.querySelector('.nav-link.active-tab');
      if (activeTab && activeTab.getAttribute('data-tab') === 'document-review') {
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
          
          // Check if we have a valid document loaded
          if (!window.currentFileId) {
            showNotification('No document loaded. Please select a chapter with uploaded files to view in fullscreen.', 'warning');
            return;
          }
          
          // Check if the document viewer contains actual content (not error messages)
          if (documentViewer && 
              documentViewer.innerHTML.trim() !== '' && 
              !documentViewer.innerHTML.includes('No files uploaded') &&
              !documentViewer.innerHTML.includes('Error Loading Document')) {
            // Create fullscreen view
            openFullscreenView(documentViewer.innerHTML, documentTitle);
          } else {
            showNotification('Cannot open fullscreen view. No valid document content available.', 'warning');
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

    // Debug function for fullscreen loading
    function debugFullscreenLoading() {
      console.log('[Debug] === FULLSCREEN DEBUG INFO ===');
      console.log('[Debug] Current file ID:', window.currentFileId);
      console.log('[Debug] Current chapter ID:', window.currentChapterId);
      console.log('[Debug] Fullscreen WordViewer instance:', fullscreenWordViewer);
      console.log('[Debug] Fullscreen content div:', document.getElementById('fullscreen-document-content'));
      
      if (fullscreenWordViewer) {
        console.log('[Debug] WordViewer container:', fullscreenWordViewer.container);
        console.log('[Debug] WordViewer containerId:', fullscreenWordViewer.containerId);
        console.log('[Debug] Content div ID should be:', fullscreenWordViewer.containerId + '-content');
        console.log('[Debug] Actual content div:', document.getElementById(fullscreenWordViewer.containerId + '-content'));
      }
      
      // Test direct API call
      if (window.currentFileId) {
        console.log('[Debug] Testing direct API call...');
        fetch(`api/extract_document_content.php?file_id=${window.currentFileId}`)
          .then(response => response.json())
          .then(data => {
            console.log('[Debug] Direct API response:', data);
          })
          .catch(error => {
            console.error('[Debug] Direct API error:', error);
          });
      }
    }

    // Force reload fullscreen function
    function forceReloadFullscreen() {
      console.log('[Force Reload] Starting force reload...');
      
      if (!window.currentFileId) {
        alert('No file selected. Please select a chapter first.');
        return;
      }
      
      if (!fullscreenWordViewer) {
        console.log('[Force Reload] No WordViewer instance, creating new one...');
        try {
          fullscreenWordViewer = new WordViewer('fullscreen-document-content', {
            showComments: true,
            showToolbar: false,
            allowZoom: true
          });
        } catch (error) {
          console.error('[Force Reload] Error creating WordViewer:', error);
          alert('Error creating document viewer: ' + error.message);
          return;
        }
      }
      
      console.log('[Force Reload] Forcing document load...');
      fullscreenWordViewer.loadDocument(window.currentFileId)
        .then(() => {
          console.log('[Force Reload] Document loaded successfully');
        })
        .catch(error => {
          console.error('[Force Reload] Error loading document:', error);
          alert('Error loading document: ' + error.message);
        });
    }

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
                <!-- Highlight Controls -->
                <div class="relative">
                  <button id="fullscreen-highlight-btn" class="toolbar-action-btn">
                    <i data-lucide="highlighter" class="w-4 h-4 mr-2"></i>Highlight
                  </button>
                  <div class="relative ml-2">
                    <button id="fullscreen-color-picker-btn" class="toolbar-action-btn p-2" title="Choose Highlight Color">
                      <div id="fullscreen-current-color" class="w-4 h-4 rounded-full border-2 border-white" style="background-color: #ffeb3b;"></div>
                    </button>
                    <div id="fullscreen-color-picker" class="absolute top-full left-0 mt-1 bg-white border rounded-lg shadow-lg p-3 hidden z-50">
                      <div class="grid grid-cols-4 gap-2">
                        <button class="fullscreen-color-option w-6 h-6 rounded-full border-2 border-gray-300" data-color="#ffeb3b" style="background-color: #ffeb3b;" title="Yellow"></button>
                        <button class="fullscreen-color-option w-6 h-6 rounded-full border-2 border-gray-300" data-color="#4ade80" style="background-color: #4ade80;" title="Green"></button>
                        <button class="fullscreen-color-option w-6 h-6 rounded-full border-2 border-gray-300" data-color="#60a5fa" style="background-color: #60a5fa;" title="Blue"></button>
                        <button class="fullscreen-color-option w-6 h-6 rounded-full border-2 border-gray-300" data-color="#f87171" style="background-color: #f87171;" title="Red"></button>
                        <button class="fullscreen-color-option w-6 h-6 rounded-full border-2 border-gray-300" data-color="#a78bfa" style="background-color: #a78bfa;" title="Purple"></button>
                        <button class="fullscreen-color-option w-6 h-6 rounded-full border-2 border-gray-300" data-color="#fb7185" style="background-color: #fb7185;" title="Pink"></button>
                        <button class="fullscreen-color-option w-6 h-6 rounded-full border-2 border-gray-300" data-color="#fbbf24" style="background-color: #fbbf24;" title="Orange"></button>
                        <button class="fullscreen-color-option w-6 h-6 rounded-full border-2 border-gray-300" data-color="#94a3b8" style="background-color: #94a3b8;" title="Gray"></button>
                      </div>
                    </div>
                  </div>
                </div>
                
                <button id="fullscreen-comment-btn" class="toolbar-action-btn">
                  <i data-lucide="message-circle" class="w-4 h-4 mr-2"></i>Comment
                </button>
                
                <button id="fullscreen-reload-highlights-btn" class="toolbar-action-btn" title="Reload Highlights">
                  <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>Reload Highlights
                </button>
                
                <!-- Quick Comment -->
                <div class="relative">
                  <button id="fullscreen-quick-comment-btn" class="toolbar-action-btn" title="Quick Comment">
                    <i data-lucide="message-circle-plus" class="w-4 h-4 mr-2"></i>Quick Comment
                  </button>
                </div>
                
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
            <div class="fullscreen-document">
              <div id="fullscreen-document-content"></div>
            </div>
            
            <!-- Quick Comment Modal -->
            <div id="fullscreen-quick-comment-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
              <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold mb-4">Add Quick Comment</h3>
                <div class="mb-4">
                  <label for="fullscreen-comment-text" class="block text-sm font-medium text-gray-700 mb-2">Comment:</label>
                  <textarea id="fullscreen-comment-text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your comment..."></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                  <button id="fullscreen-cancel-comment" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                  <button id="fullscreen-save-comment" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Comment</button>
                </div>
              </div>
            </div>
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

        // Copy download link and ensure it's updated
        const downloadBtn = document.getElementById('download-document-btn');
        const fullscreenDownloadBtn = fullscreenModal.querySelector('#fullscreen-download-btn');
        if (downloadBtn && fullscreenDownloadBtn) {
          fullscreenDownloadBtn.href = downloadBtn.href;
          console.log('[Fullscreen] Download link updated:', downloadBtn.href);
        } else {
          console.log('[Fullscreen] Download button not found or not properly linked');
        }

        // Setup fullscreen color picker functionality
        const fullscreenColorPickerBtn = fullscreenModal.querySelector('#fullscreen-color-picker-btn');
        const fullscreenColorPicker = fullscreenModal.querySelector('#fullscreen-color-picker');
        const fullscreenCurrentColor = fullscreenModal.querySelector('#fullscreen-current-color');

        if (fullscreenColorPickerBtn && fullscreenColorPicker) {
          fullscreenColorPickerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fullscreenColorPicker.classList.toggle('hidden');
          });

          // Close color picker when clicking outside
          document.addEventListener('click', function(e) {
            if (!fullscreenColorPicker.contains(e.target) && !fullscreenColorPickerBtn.contains(e.target)) {
              fullscreenColorPicker.classList.add('hidden');
            }
          });

          // Color selection
          const fullscreenColorOptions = fullscreenModal.querySelectorAll('.fullscreen-color-option');
          fullscreenColorOptions.forEach(option => {
            option.addEventListener('click', function() {
              const color = this.dataset.color;
              fullscreenCurrentColor.style.backgroundColor = color;
              fullscreenColorPicker.classList.add('hidden');
              
              // Update global highlight color
              window.currentHighlightColor = color;
              
              // Also update main color picker if it exists
              const mainCurrentColor = document.getElementById('current-color');
              if (mainCurrentColor) {
                mainCurrentColor.style.backgroundColor = color;
              }
            });
          });
        }

        // Setup reload highlights functionality
        const reloadHighlightsBtn = fullscreenModal.querySelector('#fullscreen-reload-highlights-btn');
        if (reloadHighlightsBtn) {
          reloadHighlightsBtn.addEventListener('click', function() {
            console.log('[Fullscreen] Manual highlight reload requested');
            if (window.currentChapterId) {
              // Clear existing highlights first
              const existingHighlights = fullscreenModal.querySelectorAll('.fullscreen-highlight, .highlight-marker');
              existingHighlights.forEach(h => {
                const parent = h.parentNode;
                parent.insertBefore(document.createTextNode(h.textContent), h);
                parent.removeChild(h);
                parent.normalize();
              });
              
              // Reload highlights
              window.loadHighlightsInFullscreen(window.currentChapterId);
              showNotification('Reloading highlights...', 'info');
            } else {
              showNotification('No chapter selected for highlighting', 'warning');
            }
          });
        }

        // Setup quick comment functionality
        const quickCommentBtn = fullscreenModal.querySelector('#fullscreen-quick-comment-btn');
        const quickCommentModal = fullscreenModal.querySelector('#fullscreen-quick-comment-modal');
        const cancelCommentBtn = fullscreenModal.querySelector('#fullscreen-cancel-comment');
        const saveCommentBtn = fullscreenModal.querySelector('#fullscreen-save-comment');
        const commentTextArea = fullscreenModal.querySelector('#fullscreen-comment-text');

        if (quickCommentBtn && quickCommentModal) {
          quickCommentBtn.addEventListener('click', function() {
            quickCommentModal.classList.remove('hidden');
            commentTextArea.focus();
          });

          cancelCommentBtn.addEventListener('click', function() {
            quickCommentModal.classList.add('hidden');
            commentTextArea.value = '';
          });

          saveCommentBtn.addEventListener('click', function() {
            const commentText = commentTextArea.value.trim();
            if (commentText && window.currentChapterId) {
              window.addCommentGeneric(commentText, window.currentChapterId);
              quickCommentModal.classList.add('hidden');
              commentTextArea.value = '';
            } else {
              showNotification('Please enter a comment and ensure a chapter is selected', 'warning');
            }
          });

          // Close modal when clicking outside
          quickCommentModal.addEventListener('click', function(e) {
            if (e.target === quickCommentModal) {
              quickCommentModal.classList.add('hidden');
              commentTextArea.value = '';
            }
          });
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
      
      // Note: Highlights are now loaded after document is ready in the WordViewer initialization above
      // This prevents race conditions between document loading and highlight application

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
      console.log('[Fullscreen] Fullscreen content div:', fullscreenContent);
      console.log('[Fullscreen] Current file ID:', window.currentFileId);
      
      if (window.currentFileId) {
        // Clear previous content
        fullscreenContent.innerHTML = '';
        console.log('[Fullscreen] Content cleared, initializing WordViewer...');
        
        // Initialize the WordViewer with proper error handling
        try {
          console.log('[Fullscreen] Creating new WordViewer instance...');
          fullscreenWordViewer = new WordViewer('fullscreen-document-content', {
            showComments: true,
            showToolbar: false,
            allowZoom: true
          });
          console.log('[Fullscreen] WordViewer instance created:', fullscreenWordViewer);
          
          // Load the document and THEN load highlights once content is ready
          if (window.currentFileId) {
            console.log('[Fullscreen] Loading document with file ID:', window.currentFileId);
            fullscreenWordViewer.loadDocument(window.currentFileId)
              .then(() => {
                console.log('[Fullscreen] Document loaded successfully, now loading highlights...');
                // Wait a bit more for rendering to complete
                setTimeout(() => {
                  if (window.currentChapterId) {
                    window.loadHighlightsInFullscreen(window.currentChapterId);
                  }
                }, 1000);
              })
              .catch(error => {
                console.error('[Fullscreen] Error loading document:', error);
              });
          }
          
          // Add automatic highlight loading system for fullscreen
          window.setupFullscreenAutomaticHighlightLoading = function() {
            if (!window.currentChapterId) return;
            
            console.log('🚀 [Fullscreen] Setting up ENHANCED automatic highlight loading for chapter:', window.currentChapterId);
            
            let fullscreenHighlightsLoaded = false; // Prevent duplicate loading
            
            // Function to load highlights when fullscreen content is ready
            const loadFullscreenHighlightsWhenReady = (source) => {
              if (fullscreenHighlightsLoaded) {
                console.log(`[Fullscreen-${source}] Highlights already loaded, skipping`);
                return;
              }
              
              console.log(`[Fullscreen-${source}] Loading highlights and comments`);
              fullscreenHighlightsLoaded = true;
              
              // More aggressive automatic loading for fullscreen
              const attemptFullscreenAutoLoad = (attempt = 1, maxAttempts = 5) => {
                console.log(`[Fullscreen-${source}] Auto-load attempt ${attempt}/${maxAttempts}`);
                
                // Find fullscreen content elements
                const fullscreenSelectors = [
                  '#fullscreen-document-content .word-content',
                  '#fullscreen-document-content-content',
                  '#fullscreen-document-content .word-paragraph',
                  '#fullscreen-document-content div'
                ];
                
                let bestFullscreenElement = null;
                let bestScore = 0;
                
                for (const selector of fullscreenSelectors) {
                  try {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(element => {
                      if (element && element.textContent) {
                        const textLength = element.textContent.trim().length;
                        if (textLength < 100) return;
                        
                        let score = textLength;
                        if (element.className.includes('word-content')) score += 10000;
                        if (element.className.includes('word-paragraph')) score += 5000;
                        if (element.id === 'fullscreen-document-content-content') score += 8000;
                        
                        if (score > bestScore && 
                            !element.textContent.includes('Loading') && 
                            !element.textContent.includes('Error')) {
                          bestFullscreenElement = element;
                          bestScore = score;
                        }
                      }
                    });
                  } catch (e) {
                    // Skip invalid selectors
                  }
                }
                
                if (bestFullscreenElement && window.currentChapterId) {
                  console.log(`[Fullscreen-${source}] Found content, applying highlights directly`);
                  
                  // Apply highlights directly to fullscreen content
                  fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
                    .then(response => response.json())
                    .then(data => {
                      if (data.success && data.highlights.length > 0) {
                        console.log(`[Fullscreen-${source}] Applying ${data.highlights.length} highlights to fullscreen`);
                        
                        // Clear existing highlights
                        bestFullscreenElement.querySelectorAll('.highlight-marker, .fullscreen-highlight').forEach(h => {
                          const parent = h.parentNode;
                          if (parent) {
                            parent.insertBefore(document.createTextNode(h.textContent), h);
                            parent.removeChild(h);
                          }
                        });
                        
                        // Apply highlights using fullscreen-specific method
                        let successCount = 0;
                        data.highlights.forEach(highlight => {
                          if (highlight.highlighted_text && window.applyFullscreenHighlightDirect(highlight, bestFullscreenElement)) {
                            successCount++;
                          }
                        });
                        
                        if (successCount > 0) {
                          console.log(`[Fullscreen-${source}] ✅ Auto-loaded ${successCount} highlights in fullscreen!`);
                        } else {
                          console.log(`[Fullscreen-${source}] ⚠️ Found highlights but could not apply them in fullscreen`);
                          if (attempt < maxAttempts) {
                            setTimeout(() => attemptFullscreenAutoLoad(attempt + 1, maxAttempts), 1000);
                          }
                        }
                      } else {
                        console.log(`[Fullscreen-${source}] No highlights found in database`);
                      }
                    })
                    .catch(error => {
                      console.error(`[Fullscreen-${source}] Error loading highlights:`, error);
                      if (attempt < maxAttempts) {
                        setTimeout(() => attemptFullscreenAutoLoad(attempt + 1, maxAttempts), 1000);
                      }
                    });
                } else {
                  console.log(`[Fullscreen-${source}] No fullscreen content found, retrying...`);
                  if (attempt < maxAttempts) {
                    setTimeout(() => attemptFullscreenAutoLoad(attempt + 1, maxAttempts), 1000);
                  } else {
                    console.log(`[Fullscreen-${source}] Max attempts reached, giving up`);
                  }
                }
              };
              
              // Start auto-loading with a small delay
              setTimeout(() => attemptFullscreenAutoLoad(), 300);
            };
            
            // Method 1: Promise-based loading for fullscreen
            fullscreenWordViewer.loadDocument(window.currentFileId).then(() => {
              console.log('[Fullscreen-Promise] Document loaded successfully');
              setTimeout(() => loadFullscreenHighlightsWhenReady('Promise'), 400);
            }).catch(error => {
              console.log('[Fullscreen-Promise] Document load failed:', error);
            });
            
            // Method 2: Enhanced MutationObserver for fullscreen
            const fullscreenObserver = new MutationObserver((mutations) => {
              for (let mutation of mutations) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                  const indicators = [
                    document.querySelector('#fullscreen-document-content .word-content'),
                    document.querySelector('#fullscreen-document-content .word-paragraph'),
                    document.querySelector('#fullscreen-document-content-content')
                  ].filter(el => el && el.textContent && el.textContent.trim().length > 100);
                  
                  if (indicators.length > 0) {
                    console.log('[Fullscreen-Observer] Content detected, triggering highlight load');
                    fullscreenObserver.disconnect();
                    setTimeout(() => loadFullscreenHighlightsWhenReady('Observer'), 300);
                    return;
                  }
                }
              }
            });
            
            const fullscreenObserverTarget = document.getElementById('fullscreen-document-content');
            if (fullscreenObserverTarget) {
              fullscreenObserver.observe(fullscreenObserverTarget, {
                childList: true,
                subtree: true,
                attributes: true
              });
            }
            
            // Method 3: Smart polling for fullscreen
            let fullscreenPollCount = 0;
            const maxFullscreenPolls = 20;
            const fullscreenSmartPoll = setInterval(() => {
              fullscreenPollCount++;
              
              const fullscreenContentElements = [
                document.querySelector('#fullscreen-document-content .word-content'),
                document.querySelector('#fullscreen-document-content-content'),
                document.querySelector('#fullscreen-document-content .word-paragraph'),
                ...document.querySelectorAll('#fullscreen-document-content div')
              ].filter(el => el && el.textContent && el.textContent.trim().length > 100);
              
              if (fullscreenContentElements.length > 0) {
                console.log(`[Fullscreen-SmartPoll] Content found after ${fullscreenPollCount} attempts`);
                clearInterval(fullscreenSmartPoll);
                fullscreenObserver.disconnect();
                setTimeout(() => loadFullscreenHighlightsWhenReady('SmartPoll'), 200);
              } else if (fullscreenPollCount >= maxFullscreenPolls) {
                console.log('[Fullscreen-SmartPoll] Max attempts reached, using fallback');
                clearInterval(fullscreenSmartPoll);
                fullscreenObserver.disconnect();
                setTimeout(() => loadFullscreenHighlightsWhenReady('SmartPollFallback'), 200);
              }
            }, 400);
            
            // Method 4: Final fallback for fullscreen
            setTimeout(() => {
              if (!fullscreenHighlightsLoaded) {
                console.log('[Fullscreen-FinalFallback] Force loading highlights after 10 seconds');
                loadFullscreenHighlightsWhenReady('FinalFallback');
              }
              clearInterval(fullscreenSmartPoll);
            }, 10000);
          };
          
          // Start fullscreen automatic highlight loading
          if (window.currentFileId && window.currentChapterId) {
            window.setupFullscreenAutomaticHighlightLoading();
          }
          
          // Add enhanced fullscreen monitoring for debugging
          window.debugFullscreenHighlights = function() {
            console.log('🔍 [Fullscreen Debug] Starting fullscreen highlight debugging...');
            
            // Wait a bit for content to load
            setTimeout(() => {
              const fullscreenContainer = document.getElementById('fullscreen-document-content');
              console.log('[Fullscreen Debug] Container found:', !!fullscreenContainer);
              
              if (fullscreenContainer) {
                console.log('[Fullscreen Debug] Container content length:', fullscreenContainer.textContent.length);
                console.log('[Fullscreen Debug] Container children:', fullscreenContainer.children.length);
                console.log('[Fullscreen Debug] Container preview:', fullscreenContainer.textContent.substring(0, 200));
                
                // Check specific selectors
                const selectors = [
                  '#fullscreen-document-content-content',
                  '#fullscreen-document-content .word-content',
                  '#fullscreen-document-content .word-document',
                  '#fullscreen-document-content .word-page'
                ];
                
                selectors.forEach(sel => {
                  const el = document.querySelector(sel);
                  console.log(`[Fullscreen Debug] ${sel}:`, !!el, el ? `(${el.textContent.length} chars)` : '');
                });
                
                // Try to load highlights manually
                if (window.currentChapterId) {
                  fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
                    .then(response => response.json())
                    .then(data => {
                      console.log('[Fullscreen Debug] Highlights in DB:', data.highlights?.length || 0);
                      if (data.success && data.highlights?.length > 0) {
                        console.log('[Fullscreen Debug] Attempting to apply highlights...');
                        
                        // Find best content element
                        let bestElement = document.querySelector('#fullscreen-document-content .word-content') ||
                                         document.querySelector('#fullscreen-document-content-content') ||
                                         document.querySelector('#fullscreen-document-content');
                        
                        if (bestElement) {
                          console.log('[Fullscreen Debug] Using element:', bestElement.tagName, bestElement.className);
                          
                          let applied = 0;
                          data.highlights.forEach(highlight => {
                            if (window.applyFullscreenHighlightDirect(highlight, bestElement)) {
                              applied++;
                            }
                          });
                          
                          console.log(`[Fullscreen Debug] Applied ${applied}/${data.highlights.length} highlights`);
                          
                          // Check if they're visible
                          setTimeout(() => {
                            const visibleHighlights = document.querySelectorAll('.fullscreen-highlight, .highlight-marker');
                            console.log('[Fullscreen Debug] Visible highlights in DOM:', visibleHighlights.length);
                            visibleHighlights.forEach((h, i) => {
                              console.log(`[Fullscreen Debug] Highlight ${i}:`, h.textContent, h.style.backgroundColor);
                            });
                          }, 100);
                        }
                      }
                    });
                }
              }
            }, 2000);
          };
          
          // Auto-run debug after a delay
          setTimeout(() => {
            if (window.currentChapterId) {
              window.debugFullscreenHighlights();
            }
          }, 3000);
          
          // Check if WordViewer was properly initialized
          if (!fullscreenWordViewer || !fullscreenWordViewer.container) {
            throw new Error('Failed to initialize WordViewer container');
          }
          
          // Add extra check for container content
          const fullscreenContentDiv = document.getElementById('fullscreen-document-content');
          if (!fullscreenContentDiv) {
            throw new Error('Fullscreen content container not found');
          }
          
                        // Load the document with proper async handling and timeout
              const loadWithTimeout = async () => {
                const timeoutPromise = new Promise((_, reject) => 
                  setTimeout(() => reject(new Error('Document loading timed out after 30 seconds')), 30000)
                );
                
                console.log('[Fullscreen] Starting loadWithTimeout function');
                
                // Add a fallback timer to force display after 20 seconds
                const fallbackTimer = setTimeout(() => {
                  console.log('[Fullscreen] Fallback timer triggered - forcing document display');
                  const contentDiv = document.getElementById('fullscreen-document-content-content');
                  if (contentDiv && contentDiv.innerHTML.includes('Loading document')) {
                    console.log('[Fullscreen] Attempting emergency fallback...');
                    
                    // Try to create a simple viewer
                    try {
                      contentDiv.innerHTML = `
                        <div class="text-center py-8">
                          <i data-lucide="file-text" class="w-16 h-16 mx-auto mb-4 text-blue-600"></i>
                          <h3 class="text-lg font-semibold mb-2">Document Loading Fallback</h3>
                          <p class="text-sm text-gray-600 mb-4">The document is taking longer than expected to load.</p>
                          <div class="space-y-2">
                            <button onclick="forceReloadFullscreen()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                              Try Again
                            </button>
                            <br>
                            <a href="api/download_file.php?file_id=${window.currentFileId}" 
                               class="inline-block px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" 
                               target="_blank">
                              Download Document
                            </a>
                          </div>
                        </div>
                      `;
                      lucide.createIcons();
                    } catch (error) {
                      console.error('[Fullscreen] Fallback failed:', error);
                    }
                  }
                }, 20000); // 20 second fallback
                
                try {
                  // Update title to show loading
                  const titleElement = fullscreenModal.querySelector('#fullscreen-document-title');
                  const originalTitle = titleElement.textContent;
                  titleElement.textContent = originalTitle + ' (Loading...)';
                  
                  console.log('[Fullscreen] Starting document load for file ID:', window.currentFileId);
                  await Promise.race([
                    fullscreenWordViewer.loadDocument(window.currentFileId),
                    timeoutPromise
                  ]);
                  
                  // Clear fallback timer if successful
                  clearTimeout(fallbackTimer);
                  
                  fullscreenLoadedFileId = window.currentFileId;
                  console.log('[Fullscreen] Document loaded successfully');
                  
                  // Trigger auto-load after successful document load
                  if (window.currentChapterId) {
                    setTimeout(() => {
                      console.log('[Fullscreen] Auto-triggering highlight/comment load after document load');
                      autoLoadFullscreenContent();
                    }, 800);
                  }
                  
                  // Restore original title
                  titleElement.textContent = originalTitle;
                  
                  // Enable highlight and comment functionality for fullscreen
                  const fullscreenHighlightBtn = document.getElementById('fullscreen-highlight-btn');
                  const fullscreenCommentBtn = document.getElementById('fullscreen-comment-btn');
                  const fullscreenDocContent = document.getElementById('fullscreen-document-content');
                  
                  console.log('[Fullscreen] Enabling highlight and comment functionality...');
                  console.log('[Fullscreen] Highlight button:', fullscreenHighlightBtn);
                  console.log('[Fullscreen] Comment button:', fullscreenCommentBtn);
                  console.log('[Fullscreen] Document content:', fullscreenDocContent);
                  
                  if (fullscreenHighlightBtn && fullscreenDocContent) {
                    console.log('[Fullscreen] Enabling highlight mode');
                    if (typeof window.enableHighlightMode === 'function') {
                      window.enableHighlightMode(fullscreenDocContent, fullscreenHighlightBtn);
                    } else {
                      console.error('[Fullscreen] enableHighlightMode function not found');
                    }
                  }
                  
                  if (fullscreenCommentBtn && fullscreenDocContent) {
                    console.log('[Fullscreen] Enabling comment mode');
                    if (typeof window.enableCommentMode === 'function') {
                      window.enableCommentMode(fullscreenDocContent, fullscreenCommentBtn);
                    } else {
                      console.error('[Fullscreen] enableCommentMode function not found');
                    }
                  }
                  
                  // Load existing highlights and comments for fullscreen view
                  if (window.currentChapterId) {
                                         // Wait for Word viewer to be fully loaded, then retry loading highlights
                     const loadHighlightsWithRetry = (attempts = 0) => {
                       const maxAttempts = 15; // Try up to 15 times
                       
                       // Try multiple selectors to find the content
                       const possibleSelectors = [
                         '#fullscreen-document-content-content', // WordViewer creates this
                         '#fullscreen-document-content .word-content',
                         '#fullscreen-document-content .word-paragraph',
                         '#fullscreen-document-content div[data-paragraph-id]'
                       ];
                       
                       let fullscreenContent = null;
                       let actualParagraphs = 0;
                       
                       for (const selector of possibleSelectors) {
                         const element = document.querySelector(selector);
                         if (element) {
                           fullscreenContent = element;
                           // Count actual content paragraphs, not loading/error messages
                           const paragraphs = element.querySelectorAll('.word-paragraph, [data-paragraph-id]');
                           actualParagraphs = paragraphs.length;
                           console.log(`[Fullscreen] Found content with selector: ${selector}, paragraphs: ${actualParagraphs}`);
                           break;
                         }
                       }
                       
                       // Check if we have actual document content (not just loading/error states)
                       const hasActualContent = fullscreenContent && (
                         actualParagraphs > 0 || 
                         (fullscreenContent.textContent && 
                          fullscreenContent.textContent.length > 100 && 
                          !fullscreenContent.textContent.includes('Loading document') &&
                          !fullscreenContent.textContent.includes('Document Processing Issue'))
                       );
                       
                       if (hasActualContent) {
                         // Content is loaded, now apply highlights and comments
                         console.log('[Fullscreen] Content found, loading highlights and comments...');
                         setTimeout(() => {
                           window.loadHighlightsInFullscreen(window.currentChapterId);
                           window.updateHighlightCommentIndicators(window.currentChapterId);
                         }, 200); // Small delay to ensure content is fully rendered
                       } else if (attempts < maxAttempts) {
                         // Content not ready yet, retry
                         console.log(`[Fullscreen] Content not ready, retrying... (${attempts + 1}/${maxAttempts})`);
                         if (fullscreenContent) {
                           console.log(`[Fullscreen] Current content text: "${fullscreenContent.textContent.substring(0, 100)}..."`);
                         }
                         setTimeout(() => loadHighlightsWithRetry(attempts + 1), 700);
                       } else {
                         console.log('[Fullscreen] Max attempts reached, trying one final load...');
                         // Final attempt - just try to load anyway
                         setTimeout(() => {
                           window.loadHighlightsInFullscreen(window.currentChapterId);
                           window.updateHighlightCommentIndicators(window.currentChapterId);
                         }, 500);
                       }
                     };
                     
                     // Start trying after document load
                     setTimeout(() => loadHighlightsWithRetry(), 1200);
                  }
              
                            } catch (error) {
                  console.error('Error loading document in fullscreen:', error);
                  
                  // Restore original title
                  const titleElement = fullscreenModal.querySelector('#fullscreen-document-title');
                  if (titleElement && titleElement.textContent.includes('(Loading...)')) {
                    titleElement.textContent = titleElement.textContent.replace(' (Loading...)', '');
                  }
                  
                  fullscreenContent.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                      <i data-lucide="alert-circle" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                      <p class="text-lg font-semibold mb-2">Error Loading Document</p>
                      <p class="text-sm mb-4">Failed to load document in fullscreen mode: ${error.message || 'Unknown error'}</p>
                      <div class="flex gap-2 justify-center">
                        <button onclick="loadWithTimeout()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Retry</button>
                        <button onclick="document.getElementById('close-fullscreen').click()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Close</button>
                      </div>
                    </div>
                  `;
                  lucide.createIcons();
                }
              };
              
              // Make loadWithTimeout available globally for retry button
              window.loadWithTimeout = loadWithTimeout;
              
              // Call the loading function
              loadWithTimeout();
            
        } catch (error) {
          console.error('Error initializing WordViewer in fullscreen:', error);
          fullscreenContent.innerHTML = `
            <div class="text-center py-8 text-gray-500">
              <i data-lucide="alert-circle" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
              <p class="text-lg font-semibold mb-2">Initialization Error</p>
              <p class="text-sm">Failed to initialize document viewer: ${error.message}</p>
              <button onclick="document.getElementById('close-fullscreen').click()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Close</button>
            </div>
          `;
          lucide.createIcons();
        }
      } else {
        fullscreenContent.innerHTML = `
          <div class="text-center py-8 text-gray-500">
            <i data-lucide="file-x" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
            <p class="text-lg font-semibold mb-2">No Document Available</p>
            <p class="text-sm">Please select a chapter with uploaded files to view in fullscreen.</p>
            <button onclick="document.getElementById('close-fullscreen').click()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Close</button>
          </div>
        `;
        fullscreenLoadedFileId = null;
        lucide.createIcons();
      }
    }
    
    // Quick action navigation function for enhanced empty state
    window.switchToTab = function(tabName) {
      console.log('Switching to tab:', tabName);
      
      // Remove active class from all nav items
      document.querySelectorAll('nav a').forEach(link => {
        link.classList.remove('bg-blue-600', 'text-white');
        link.classList.add('text-gray-600', 'hover:bg-gray-100');
      });
      
      // Hide all tab contents
      document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
      });
      
      // Show target tab and update navigation
      const targetLink = document.querySelector(`[data-tab="${tabName}"]`);
      const targetContent = document.getElementById(`${tabName}-content`);
      
      if (targetLink) {
        targetLink.classList.remove('text-gray-600', 'hover:bg-gray-100');
        targetLink.classList.add('bg-blue-600', 'text-white');
      }
      
      if (targetContent) {
        targetContent.classList.remove('hidden');
      }
      
      // Handle special cases
      switch(tabName) {
        case 'students':
          // Focus on student management
          if (typeof loadStudentStats === 'function') {
            loadStudentStats();
          }
          break;
        case 'feedback':
          // Focus on feedback management
          break;
        case 'reports':
          // Focus on reports and analytics
          break;
      }
      
      // Scroll to top for better UX
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    // Enhanced highlight repair function
    window.repairHighlights = function() {
      console.log('🔧 Starting highlight repair...');
      
      if (!window.currentChapterId) {
        console.log('❌ No chapter selected');
        return;
      }
      
      // Clear existing highlights
      const existingHighlights = document.querySelectorAll('.highlight-marker');
      console.log(`🧹 Clearing ${existingHighlights.length} existing highlights`);
      existingHighlights.forEach(highlight => {
        const parent = highlight.parentNode;
        if (parent) {
          parent.insertBefore(document.createTextNode(highlight.textContent), highlight);
          parent.removeChild(highlight);
        }
      });
      
      // Normalize text nodes
      const contentSelectors = ['.word-content', '.chapter-content', '.word-document', '#adviser-word-viewer-content'];
      contentSelectors.forEach(selector => {
        const element = document.querySelector(selector);
        if (element) {
          element.normalize();
        }
      });
      
      // Reload highlights with improved method
      console.log('🔄 Reloading highlights with improved matching...');
      fetch(`api/document_review.php?action=get_highlights&chapter_id=${window.currentChapterId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.highlights.length > 0) {
            console.log(`📋 Found ${data.highlights.length} highlights to repair`);
            
            // Find best content element
            let bestContent = null;
            let bestScore = 0;
            
            contentSelectors.forEach(selector => {
              const element = document.querySelector(selector);
              if (element) {
                const score = element.textContent.length;
                if (score > bestScore) {
                  bestScore = score;
                  bestContent = element;
                }
              }
            });
            
            if (bestContent) {
              console.log(`🎯 Using content element with ${bestContent.textContent.length} characters`);
              
              let successCount = 0;
              data.highlights.forEach(highlight => {
                if (window.forceApplyHighlight && window.forceApplyHighlight(highlight, bestContent)) {
                  successCount++;
                }
              });
              
              console.log(`✅ Successfully repaired ${successCount}/${data.highlights.length} highlights`);
              showNotification(`Repaired ${successCount}/${data.highlights.length} highlights`, 'success');
            } else {
              console.log('❌ No suitable content element found');
              showNotification('No suitable content found for highlighting', 'error');
            }
          } else {
            console.log('ℹ️ No highlights found to repair');
            showNotification('No highlights found to repair', 'info');
          }
        })
        .catch(error => {
          console.error('Error during repair:', error);
          showNotification('Error during highlight repair', 'error');
        });
    };
    
    // Initialize enhanced empty state
    document.addEventListener('DOMContentLoaded', function() {
      // Ensure icons are loaded for the enhanced empty state
      setTimeout(() => {
        lucide.createIcons();
      }, 100);
    });
  </script>

  <!-- Modern UI Framework -->
  <script src="assets/js/modern-ui.js"></script>

  <!-- Document Fullscreen Modal (will be created dynamically) -->

</body>
</html> 
</html> 