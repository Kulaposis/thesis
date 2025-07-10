<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$adminManager = new AdminManager();

// Check admin permissions
if (!$adminManager->isAdmin()) {
    // Get current user role for better debugging
    $currentUser = $adminManager->getCurrentUser();
    $userRole = $currentUser['role'] ?? 'unknown';
    
    // Log the access attempt
    error_log("Admin access denied for user ID: " . ($_SESSION['user_id'] ?? 'none') . " with role: " . $userRole);
    
    // Redirect based on user role
    if ($userRole === 'adviser') {
        header("Location: systemFunda.php");
    } else {
        header("Location: studentDashboard.php");
    }
    exit();
}

// Get system statistics
$stats = $adminManager->getSystemStatistics();
$analytics = $adminManager->getAdvancedAnalytics();
$announcements = $adminManager->getActiveAnnouncements();
$recentLogs = $adminManager->getAdminLogs(10);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_users':
            $filters = [
                'role' => $_POST['role'] ?? '',
                'search' => $_POST['search'] ?? '',
                'department' => $_POST['department'] ?? ''
            ];
            echo json_encode($adminManager->getAllUsers($filters));
            exit();
            
        case 'create_user':
            $userData = [
                'email' => $_POST['email'],
                'password' => $_POST['password'] ?? $adminManager->generateRandomPassword(),
                'full_name' => $_POST['full_name'],
                'role' => $_POST['role'],
                'student_id' => $_POST['student_id'] ?? null,
                'faculty_id' => $_POST['faculty_id'] ?? null,
                'program' => $_POST['program'] ?? null,
                'department' => $_POST['department'] ?? null
            ];
            $result = $adminManager->createUser($userData);
            if ($result !== false) {
                echo json_encode([
                    'success' => true, 
                    'user_id' => $result,
                    'password' => $userData['password'],
                    'message' => 'User created successfully! Password: ' . $userData['password']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create user']);
            }
            exit();
            
        case 'delete_user':
            $result = $adminManager->deleteUser($_POST['user_id']);
            echo json_encode(['success' => $result]);
            exit();
            
        case 'reset_password':
            $newPassword = $adminManager->resetUserPassword($_POST['user_id']);
            echo json_encode(['success' => $newPassword !== false, 'password' => $newPassword]);
            exit();
            
        case 'create_announcement':
            $announcementData = [
                'title' => $_POST['title'],
                'content' => $_POST['content'],
                'target_roles' => $_POST['target_roles'] ?? [],
                'target_departments' => $_POST['target_departments'] ?? [],
                'priority' => $_POST['priority'] ?? 'normal',
                'expires_at' => $_POST['expires_at'] ?? null
            ];
            $result = $adminManager->createAnnouncement($announcementData);
            echo json_encode(['success' => $result !== false, 'announcement_id' => $result]);
            exit();

        case 'get_adviser_workload':
            require_once 'includes/admin_functions.php';
            $adminManager = new AdminManager();
            $workload = $adminManager->getAdviserWorkload();
            echo json_encode($workload);
            exit();
            
        case 'get_user':
            $userId = $_POST['user_id'];
            $user = $adminManager->getUserById($userId);
            if ($user) {
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
            exit();
            
        case 'update_user':
            $userId = $_POST['user_id'];
            $userData = [
                'full_name' => $_POST['full_name'],
                'email' => $_POST['email'],
                'role' => $_POST['role'],
                'student_id' => $_POST['student_id'] ?? null,
                'faculty_id' => $_POST['faculty_id'] ?? null,
                'program' => $_POST['program'] ?? null,
                'department' => $_POST['department'] ?? null
            ];
            $result = $adminManager->updateUser($userId, $userData);
            echo json_encode(['success' => $result, 'message' => $result ? 'User updated successfully' : 'Failed to update user']);
            exit();
    }
}

// Get user info for header
require_once 'includes/thesis_functions.php';
$user = $adminManager->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Thesis Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/admin-dashboard.css">
</head>
<body>

<!-- Modern Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h1>
            <i data-lucide="shield-check" class="w-8 h-8"></i>
            Admin Panel
        </h1>
        <p class="text-sm opacity-90 mt-1">Thesis Management System</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="#" class="nav-item active" data-tab="overview">
            <i data-lucide="layout-dashboard"></i>
            Overview
        </a>
        <a href="#" class="nav-item" data-tab="users">
            <i data-lucide="users"></i>
            User Management
        </a>
        <a href="#" class="nav-item" data-tab="analytics">
            <i data-lucide="bar-chart-3"></i>
            Analytics
        </a>
        <a href="#" class="nav-item" data-tab="announcements">
            <i data-lucide="megaphone"></i>
            Announcements
        </a>
        <a href="#" class="nav-item" data-tab="settings">
            <i data-lucide="settings"></i>
            System Settings
        </a>
        <a href="#" class="nav-item" data-tab="logs">
            <i data-lucide="file-text"></i>
            Activity Logs
        </a>
    </nav>
    
    <!-- User Profile Section -->
    <div class="p-4 border-t border-gray-200 mt-auto">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div>
                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></p>
                <p class="text-sm text-gray-500">Administrator</p>
            </div>
        </div>
        <div class="mt-3 flex space-x-2">
            <a href="#" class="text-sm text-gray-600 hover:text-blue-600 transition-colors">Profile</a>
            <span class="text-gray-300">•</span>
            <a href="logout.php" class="text-sm text-gray-600 hover:text-red-600 transition-colors">Logout</a>
        </div>
    </div>
</aside>

<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle fixed top-4 left-4 z-50 lg:hidden bg-white p-2 rounded-lg shadow-lg">
    <i data-lucide="menu" class="w-6 h-6"></i>
</button>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

<!-- Top Header -->
<header class="fixed top-0 right-0 left-0 lg:left-64 bg-white border-b border-gray-200 z-30 shadow-sm">
    <div class="flex justify-between items-center px-8 py-4">
        <div class="flex items-center space-x-4">
            <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
        </div>
        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-2 bg-gray-100 rounded-lg px-3 py-2">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span class="text-sm font-medium">System Online</span>
            </div>
            <button class="relative p-2 text-gray-600 hover:text-blue-600 transition-colors">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="notification-badge absolute -top-1 -right-1 bg-blue-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
            </button>
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <span class="font-medium text-gray-900 text-base"><?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="main-content">
    <!-- Overview Tab -->
    <div id="overview-tab" class="tab-content">
        <div class="pt-24 pb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-10 gap-4">
                <div>
                    <h1 class="text-4xl font-extrabold text-gray-900 mb-2">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                    <p class="text-gray-500 text-lg font-normal mb-2">Here’s a quick look at your system today.</p>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Stats</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-7 mb-12">
                <!-- Total Students -->
                <div class="glass-card stat-card flex flex-col items-start px-6 py-7">
                    <div class="stat-icon mb-4"><i data-lucide="graduation-cap"></i></div>
                    <div class="stat-number text-4xl font-extrabold text-gray-900 mb-1"><?php echo $stats['users']['student'] ?? 0; ?></div>
                    <div class="stat-label text-base text-gray-500 font-medium mb-2">Total Students</div>
                    <div class="flex items-center text-sm text-green-600 gap-1 mt-1">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                        <span>+12% from last month</span>
                    </div>
                </div>
                <!-- Active Advisers -->
                <div class="glass-card stat-card flex flex-col items-start px-6 py-7">
                    <div class="stat-icon mb-4"><i data-lucide="user-check"></i></div>
                    <div class="stat-number text-4xl font-extrabold text-gray-900 mb-1"><?php echo $stats['users']['adviser'] ?? 0; ?></div>
                    <div class="stat-label text-base text-gray-500 font-medium mb-2">Active Advisers</div>
                    <div class="flex items-center text-sm text-green-600 gap-1 mt-1">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                        <span>+5% from last month</span>
                    </div>
                </div>
                <!-- Active Theses -->
                <div class="glass-card stat-card flex flex-col items-start px-6 py-7">
                    <div class="stat-icon mb-4"><i data-lucide="book-open"></i></div>
                    <div class="stat-number text-4xl font-extrabold text-gray-900 mb-1"><?php echo $stats['active_theses'] ?? 0; ?></div>
                    <div class="stat-label text-base text-gray-500 font-medium mb-2">Active Theses</div>
                    <div class="flex items-center text-sm text-green-600 gap-1 mt-1">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                        <span>+8% from last month</span>
                    </div>
                </div>
                <!-- Pending Reviews -->
                <div class="glass-card stat-card flex flex-col items-start px-6 py-7">
                    <div class="stat-icon mb-4"><i data-lucide="clock"></i></div>
                    <div class="stat-number text-4xl font-extrabold text-gray-900 mb-1"><?php echo $stats['pending_reviews'] ?? 0; ?></div>
                    <div class="stat-label text-base text-gray-500 font-medium mb-2">Pending Reviews</div>
                    <?php if (($stats['overdue_deadlines'] ?? 0) > 0): ?>
                    <div class="flex items-center text-sm text-red-600 gap-1 mt-1">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        <span><?php echo $stats['overdue_deadlines']; ?> overdue</span>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center text-sm text-gray-500 gap-1 mt-1">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        <span>All on track</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                <!-- Department Performance Chart -->
                <div class="chart-container">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Department Performance</h3>
                    <canvas id="departmentChart" width="400" height="200"></canvas>
                </div>
                <!-- Monthly Activity Chart -->
                <div class="chart-container">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Activity Trends</h3>
                    <canvas id="activityChart" width="400" height="200"></canvas>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Active Announcements -->
                <div class="glass-card p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Active Announcements</h3>
                        <button class="btn btn-primary btn-sm" onclick="adminDashboard.showCreateAnnouncementModal()">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            New
                        </button>
                    </div>
                    <div class="space-y-4">
                        <?php if (empty($announcements)): ?>
                            <div class="text-center py-8">
                                <i data-lucide="megaphone" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                                <p class="text-gray-500">No active announcements</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($announcements, 0, 5) as $announcement): ?>
                            <div class="border-l-4 border-blue-500 pl-4 py-3 bg-blue-50 rounded-r-lg">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></p>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo substr(htmlspecialchars($announcement['content']), 0, 100) . '...'; ?></p>
                                    <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                        <span class="badge badge-info"><?php echo ucfirst($announcement['priority']); ?></span>
                                        <span><?php echo date('M j, Y', strtotime($announcement['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Recent Admin Activity -->
                <div class="glass-card p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Admin Activity</h3>
                    <div class="space-y-4">
                        <?php if (empty($recentLogs)): ?>
                            <div class="text-center py-8">
                                <i data-lucide="activity" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                                <p class="text-gray-500">No recent activity</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($recentLogs, 0, 5) as $log): ?>
                            <div class="flex items-center space-x-4 py-3 border-b border-gray-100 last:border-b-0">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                    <?php echo strtoupper(substr($log['admin_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900">
                                        <span class="font-medium"><?php echo htmlspecialchars($log['admin_name']); ?></span>
                                        <span class="text-gray-600"><?php echo htmlspecialchars($log['action']); ?></span>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Management Tab -->
    <div id="users-tab" class="tab-content hidden">
        <div class="pt-24 pb-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">User Management</h1>
                <p class="text-gray-600">Manage system users, roles, and permissions.</p>
            </div>
            <div class="glass-card">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">System Users</h3>
                        <button onclick="adminDashboard.showCreateUserModal()" class="btn btn-primary btn-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Add User
                        </button>
                    </div>
                    <!-- Search and Filters -->
                    <div class="search-container mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <select id="roleFilter" class="search-input">
                                <option value="">All Roles</option>
                                <option value="student">Students</option>
                                <option value="adviser">Advisers</option>
                                <option value="admin">Admins</option>
                            </select>
                            <input type="text" id="searchFilter" placeholder="Search by name or email..." class="search-input">
                            <select id="departmentFilter" class="search-input">
                                <option value="">All Departments</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Technology">Information Technology</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div id="usersTable" class="overflow-x-auto">
                        <!-- Users table will be loaded here via JavaScript -->
                        <div class="text-center py-12 loading-indicator">
                            <div class="loading-indicator">
                                <i data-lucide="loader-2" class="w-8 h-8 text-gray-400 mx-auto mb-4 animate-spin"></i>
                                <p class="text-gray-500">Loading users...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Tab -->
    <div id="analytics-tab" class="tab-content hidden">
        <div class="pt-24 pb-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Analytics & Reports</h1>
                <p class="text-gray-600">Comprehensive insights into system performance and user activity.</p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Adviser Workload -->
                <div class="chart-container">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Adviser Workload</h3>
                    <div id="adviserWorkloadLoading" class="text-center py-6 hidden">
                        <i data-lucide="loader-2" class="w-8 h-8 text-gray-400 mx-auto mb-4 animate-spin"></i>
                        <p class="text-gray-500">Loading adviser workload...</p>
                    </div>
                    <canvas id="adviserWorkloadChart" width="400" height="200"></canvas>
                </div>
                <!-- Department Comparison -->
                <div class="glass-card p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Department Statistics</h3>
                    <div class="space-y-4">
                        <?php if (!empty($analytics['department_performance'])): ?>
                            <?php foreach ($analytics['department_performance'] as $dept): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($dept['department']); ?></h4>
                                    <span class="badge badge-success">Active</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div class="flex items-center">
                                        <i data-lucide="users" class="w-4 h-4 text-blue-500 mr-2"></i>
                                        <span class="text-gray-600">Students:</span>
                                        <span class="font-semibold ml-1"><?php echo $dept['student_count']; ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="trending-up" class="w-4 h-4 text-green-500 mr-2"></i>
                                        <span class="text-gray-600">Progress:</span>
                                        <span class="font-semibold ml-1"><?php echo round($dept['avg_progress'], 1); ?>%</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i data-lucide="bar-chart-3" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                                <p class="text-gray-500">No department data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Announcements Tab -->
    <div id="announcements-tab" class="tab-content hidden">
        <div class="pt-20">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">System Announcements</h1>
                <p class="text-gray-600">Create and manage system-wide announcements and notifications.</p>
            </div>
            
            <div class="glass-card">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-semibold text-gray-900">Active Announcements</h3>
                        <button onclick="adminDashboard.showCreateAnnouncementModal()" class="btn btn-primary">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            New Announcement
                        </button>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="space-y-4">
                        <?php if (empty($announcements)): ?>
                            <div class="text-center py-12">
                                <i data-lucide="megaphone" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                                <p class="text-gray-500">No announcements yet</p>
                                <button onclick="adminDashboard.showCreateAnnouncementModal()" class="btn btn-primary mt-4">
                                    Create First Announcement
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                        <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($announcement['content']); ?></p>
                                        <div class="mt-3 flex items-center space-x-4 text-sm">
                                            <span class="badge badge-info"><?php echo ucfirst($announcement['priority']); ?></span>
                                            <span class="text-gray-500">Created: <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex space-x-2">
                                        <button class="btn btn-warning btn-sm" data-tooltip="Edit">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" data-tooltip="Delete">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Tab -->
    <div id="settings-tab" class="tab-content hidden">
        <div class="pt-24 pb-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">System Settings</h1>
                <p class="text-gray-600">Configure system preferences and security settings.</p>
            </div>
            <div class="glass-card p-8 max-w-xl mx-auto">
                <form id="systemSettingsForm">
                    <div class="mb-6">
                        <label for="systemName" class="block text-gray-700 font-medium mb-2">System Name</label>
                        <input type="text" id="systemName" name="system_name" class="search-input" required>
                    </div>
                    <div class="mb-6">
                        <label for="contactEmail" class="block text-gray-700 font-medium mb-2">Contact Email</label>
                        <input type="email" id="contactEmail" name="contact_email" class="search-input" required>
                    </div>
                    <div class="mb-8">
                        <label for="theme" class="block text-gray-700 font-medium mb-2">Theme</label>
                        <select id="theme" name="theme" class="search-input">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                    <div id="settingsNotification" class="mt-4"></div>
                </form>
                <div id="settingsLoading" class="text-center py-6 hidden">
                    <i data-lucide="loader-2" class="w-8 h-8 text-gray-400 mx-auto mb-4 animate-spin"></i>
                    <p class="text-gray-500">Loading settings...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Logs Tab -->
    <div id="logs-tab" class="tab-content hidden">
        <div class="pt-20">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Activity Logs</h1>
                <p class="text-gray-600">Monitor system activity and admin actions.</p>
            </div>
            
            <div class="glass-card">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Admin Activity Logs</h3>
                </div>
                
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Target</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentLogs)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-8">
                                            <i data-lucide="file-text" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                                            <p class="text-gray-500">No activity logs available</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentLogs as $log): ?>
                                    <tr class="slide-in">
                                        <td>
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-semibold mr-3">
                                                    <?php echo strtoupper(substr($log['admin_name'], 0, 1)); ?>
                                                </div>
                                                <span class="font-medium"><?php echo htmlspecialchars($log['admin_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo htmlspecialchars($log['target_type']); ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals (Create User, Create Announcement, etc.) -->
<!-- These will be added in the next step -->

<!-- JavaScript -->
<script>
// Pass PHP data to JavaScript
window.departmentData = <?php echo json_encode($analytics['department_performance'] ?? []); ?>;
window.activityData = <?php echo json_encode($analytics['monthly_activity'] ?? []); ?>;
</script>
<script src="assets/js/admin-dashboard.js"></script>

</body>
</html>