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
            
        case 'get_login_logs':
            error_log("Admin Dashboard: get_login_logs request received");
            error_log("POST data: " . json_encode($_POST));
            
            $filters = [
                'user_role' => $_POST['user_role'] ?? '',
                'action_type' => $_POST['action_type'] ?? '',
                'date_from' => $_POST['date_from'] ?? '',
                'date_to' => $_POST['date_to'] ?? '',
                'user_search' => $_POST['user_search'] ?? ''
            ];
            $limit = $_POST['limit'] ?? 20;
            $page = $_POST['page'] ?? 1;
            
            error_log("Filters: " . json_encode($filters));
            error_log("Limit: " . $limit . ", Page: " . $page);
            
            $result = $adminManager->getLoginLogsWithPagination($limit, $page, $filters);
            
            if ($result !== false) {
                $response = [
                    'success' => true, 
                    'logs' => $result['logs'],
                    'pagination' => [
                        'current_page' => $result['current_page'],
                        'total_pages' => $result['total_pages'],
                        'total_count' => $result['total_count'],
                        'per_page' => $result['per_page']
                    ]
                ];
                error_log("Sending response with " . count($result['logs']) . " logs, page " . $result['current_page'] . " of " . $result['total_pages']);
            } else {
                $response = ['success' => false, 'error' => 'Failed to retrieve logs'];
                error_log("Login logs query failed");
            }
            
            echo json_encode($response);
            exit();
            
        case 'get_login_statistics':
            $days = $_POST['days'] ?? 30;
            $stats = $adminManager->getLoginStatistics($days);
            echo json_encode(['success' => true, 'statistics' => $stats]);
            exit();
            
        case 'get_admin_logs':
            $limit = $_POST['limit'] ?? 20;
            $page = $_POST['page'] ?? 1;
            $filters = [
                'admin_id' => $_POST['admin_id'] ?? '',
                'action' => $_POST['action'] ?? ''
            ];
            $result = $adminManager->getAdminLogsWithPagination($limit, $page, $filters);
            if ($result !== false) {
                echo json_encode([
                    'success' => true, 
                    'logs' => $result['logs'],
                    'pagination' => [
                        'current_page' => $result['current_page'],
                        'total_pages' => $result['total_pages'],
                        'total_count' => $result['total_count'],
                        'per_page' => $result['per_page']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to retrieve admin logs']);
            }
            exit();

        // Program Management AJAX handlers
        case 'get_programs':
            $filters = [
                'department' => $_POST['department'] ?? '',
                'search' => $_POST['search'] ?? '',
                'is_active' => $_POST['is_active'] ?? ''
            ];
            $programs = $adminManager->getAllPrograms($filters);
            echo json_encode(['success' => true, 'programs' => $programs]);
            exit();

        case 'get_program':
            $programId = $_POST['program_id'];
            $program = $adminManager->getProgramById($programId);
            if ($program) {
                echo json_encode(['success' => true, 'program' => $program]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Program not found']);
            }
            exit();

        case 'create_program':
            $programData = [
                'program_code' => $_POST['program_code'],
                'program_name' => $_POST['program_name'],
                'department' => $_POST['department'],
                'description' => $_POST['description'] ?? '',
                'duration_years' => $_POST['duration_years'] ?? 4,
                'total_units' => $_POST['total_units'] ?? 120,
                'is_active' => $_POST['is_active'] ?? 1
            ];
            $result = $adminManager->createProgram($programData);
            echo json_encode($result);
            exit();

        case 'update_program':
            $programId = $_POST['program_id'];
            $programData = [
                'program_code' => $_POST['program_code'],
                'program_name' => $_POST['program_name'],
                'department' => $_POST['department'],
                'description' => $_POST['description'] ?? '',
                'duration_years' => $_POST['duration_years'] ?? 4,
                'total_units' => $_POST['total_units'] ?? 120,
                'is_active' => $_POST['is_active'] ?? 1
            ];
            $result = $adminManager->updateProgram($programId, $programData);
            echo json_encode($result);
            exit();

        case 'delete_program':
            $programId = $_POST['program_id'];
            $result = $adminManager->deleteProgram($programId);
            echo json_encode($result);
            exit();

        case 'get_departments':
            $departments = $adminManager->getDepartments();
            echo json_encode(['success' => true, 'departments' => $departments]);
            exit();

        case 'get_program_statistics':
            $stats = $adminManager->getProgramStatistics();
            echo json_encode(['success' => true, 'statistics' => $stats]);
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
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
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
        <a href="#" class="nav-item" data-tab="programs">
            <i data-lucide="graduation-cap"></i>
            Programs
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
                    <p class="text-gray-500 text-lg font-normal mb-2">Here's a quick look at your system today.</p>
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
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="departmentChart" width="400" height="250"></canvas>
                    </div>
                </div>
                <!-- Monthly Activity Chart -->
                <div class="chart-container">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Activity Trends</h3>
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="activityChart" width="400" height="250"></canvas>
                    </div>
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
        <div class="pt-20">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">User Management</h1>
                    <p class="text-gray-600">Manage system users, roles, and permissions.</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="adminDashboard.showCreateUserModal()" class="btn btn-primary">
                        <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i>
                        Add New User
                    </button>
                    <button onclick="adminDashboard.loadUsers()" class="btn btn-secondary">
                        <i data-lucide="refresh-cw" class="w-5 h-5 mr-2"></i>
                        Refresh
                    </button>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Students</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalStudents">-</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <i data-lucide="graduation-cap" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Advisers</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalAdvisers">-</p>
                        </div>
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <i data-lucide="user-check" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Admins</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalAdmins">-</p>
                        </div>
                        <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                            <i data-lucide="shield" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Today</p>
                            <p class="text-2xl font-bold text-gray-900" id="activeToday">-</p>
                        </div>
                        <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center">
                            <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search and Filter -->
            <div class="glass-card p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
                        <input type="text" id="userSearch" placeholder="Name, email, or ID..." class="form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select id="roleFilter" class="form-select">
                            <option value="">All Roles</option>
                            <option value="student">Students</option>
                            <option value="adviser">Advisers</option>
                            <option value="admin">Admins</option>
                            <option value="super_admin">Super Admins</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <select id="departmentFilter" class="form-select">
                            <option value="">All Departments</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Business">Business</option>
                            <option value="Education">Education</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Program</label>
                        <select id="programFilter" class="form-select">
                            <option value="">All Programs</option>
                            <option value="BS Computer Science">BS Computer Science</option>
                            <option value="BS Information Technology">BS Information Technology</option>
                            <option value="MS Computer Science">MS Computer Science</option>
                            <option value="PhD Computer Science">PhD Computer Science</option>
                        </select>
                    </div>
                    <div class="flex items-end space-x-2">
                        <button onclick="adminDashboard.filterUsers()" class="btn btn-primary flex-1">
                            <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                            Filter
                        </button>
                        <button onclick="adminDashboard.clearUserFilters()" class="btn btn-secondary">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <div class="glass-card p-4 mb-6" id="bulkActionsPanel" style="display: none;">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">
                        <span id="selectedCount">0</span> user(s) selected
                    </span>
                    <div class="flex space-x-2">
                        <button onclick="adminDashboard.bulkResetPasswords()" class="btn btn-warning btn-sm">
                            <i data-lucide="key" class="w-4 h-4 mr-2"></i>
                            Reset Passwords
                        </button>
                        <button onclick="adminDashboard.bulkDeleteUsers()" class="btn btn-danger btn-sm">
                            <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                            Delete Selected
                        </button>
                        <button onclick="adminDashboard.clearSelection()" class="btn btn-secondary btn-sm">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="glass-card">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-semibold text-gray-900">System Users</h3>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" id="selectAllUsers" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-600">Select All</span>
                            </label>
                            <div class="text-sm text-gray-500">
                                <span id="userCount">0</span> users
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div id="usersLoading" class="text-center py-8 hidden">
                        <i data-lucide="loader-2" class="w-8 h-8 text-gray-400 mx-auto mb-4 animate-spin"></i>
                        <p class="text-gray-500">Loading users...</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="modern-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th width="40px">
                                        <input type="checkbox" id="selectAllUsersHeader" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Department/Program</th>
                                    <th>ID Number</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th width="120px">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <!-- Users will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Programs Tab -->
    <div id="programs-tab" class="tab-content hidden">
        <div class="pt-24 pb-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Program Management</h1>
                <p class="text-gray-600">Manage academic programs, departments, and program details.</p>
            </div>
            
            <!-- Program Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Programs</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalPrograms">-</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <i data-lucide="graduation-cap" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Programs</p>
                            <p class="text-2xl font-bold text-gray-900" id="activePrograms">-</p>
                        </div>
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Departments</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalDepartments">-</p>
                        </div>
                        <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                            <i data-lucide="building" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Students</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalStudents">-</p>
                        </div>
                        <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center">
                            <i data-lucide="users" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Program Management Interface -->
            <div class="glass-card">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-semibold text-gray-900">Programs</h3>
                        <button onclick="adminDashboard.showCreateProgramModal()" class="btn btn-primary">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Add Program
                        </button>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" id="programSearch" class="search-input" placeholder="Search programs...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select id="departmentFilter" class="form-select">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Programs Table -->
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="programsTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Programs will be populated here -->
                            </tbody>
                        </table>
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
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="adviserWorkloadChart" width="400" height="250"></canvas>
                    </div>
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
                <p class="text-gray-600">Monitor system activity, login sessions, and admin actions.</p>
            </div>
            
            <!-- Login Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Today's Logins</p>
                            <p class="text-2xl font-bold text-gray-900" id="todayLogins">-</p>
                        </div>
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <i data-lucide="log-in" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Sessions</p>
                            <p class="text-2xl font-bold text-gray-900" id="activeSessions">-</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <i data-lucide="users" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Failed Attempts</p>
                            <p class="text-2xl font-bold text-gray-900" id="failedAttempts">-</p>
                        </div>
                        <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                            <i data-lucide="shield-alert" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Avg Session Duration</p>
                            <p class="text-2xl font-bold text-gray-900" id="avgDuration">-</p>
                        </div>
                        <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                            <i data-lucide="clock" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs for different log types -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button class="log-tab-btn active" data-log-tab="login-logs">
                            <i data-lucide="user-check" class="w-4 h-4 mr-2"></i>
                            Login Logs
                        </button>
                        <button class="log-tab-btn" data-log-tab="admin-logs">
                            <i data-lucide="shield" class="w-4 h-4 mr-2"></i>
                            Admin Activity
                        </button>
                    </nav>
                </div>
            </div>
            
            <!-- Login Logs Tab -->
            <div id="login-logs-content" class="log-tab-content">
                <!-- Recent Admin Activity Card -->
                <div class="glass-card p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Admin Activity</h3>
                    <div id="recentAdminActivity"></div>
                </div>
                <div class="glass-card">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-gray-900">User Login/Logout Activity</h3>
                            <div class="flex space-x-2">
                                <button onclick="adminDashboard.loadLoginLogs()" class="btn btn-primary btn-sm">
                                    <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                                    Refresh
                                </button>
                                <button onclick="window.open('debug_admin_logs.php', '_blank')" class="btn btn-info btn-sm">
                                    <i data-lucide="bug" class="w-4 h-4 mr-2"></i>
                                    Debug
                                </button>
                                <button onclick="adminDashboard.exportLoginLogs()" class="btn btn-secondary btn-sm">
                                    <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                    Export
                                </button>
                            </div>
                        </div>
                        
                        <!-- Filters -->
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">User Role</label>
                                <select id="loginLogRoleFilter" class="form-select">
                                    <option value="">All Roles</option>
                                    <option value="student">Students</option>
                                    <option value="adviser">Advisers</option>
                                    <option value="admin">Admins</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Action Type</label>
                                <select id="loginLogActionFilter" class="form-select">
                                    <option value="">All Actions</option>
                                    <option value="login">Login</option>
                                    <option value="logout">Logout</option>
                                    <option value="login_failed">Failed Login</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                                <input type="date" id="loginLogDateFrom" class="form-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                                <input type="date" id="loginLogDateTo" class="form-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search User</label>
                                <input type="text" id="loginLogUserSearch" placeholder="Name or email..." class="form-input">
                            </div>
                        </div>
                        
                        <div class="flex space-x-3">
                            <button onclick="adminDashboard.filterLoginLogs()" class="btn btn-primary btn-sm">
                                <i data-lucide="filter" class="w-4 h-4 mr-2"></i>
                                Apply Filters
                            </button>
                            <button onclick="adminDashboard.clearLoginLogFilters()" class="btn btn-secondary btn-sm">
                                <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                                Clear
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div id="loginLogsLoading" class="text-center py-8 hidden">
                            <i data-lucide="loader-2" class="w-8 h-8 text-gray-400 mx-auto mb-4 animate-spin"></i>
                            <p class="text-gray-500">Loading login logs...</p>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="modern-table" id="loginLogsTable">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                        <th>IP Address</th>
                                        <th>Browser</th>
                                        <th>Login Time</th>
                                        <th>Logout Time</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody id="loginLogsTableBody">
                                    <!-- Login logs will be populated here -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Login Logs Pagination -->
                        <div id="loginLogsPagination" class="flex justify-center items-center mt-6 space-x-2 hidden">
                            <button id="loginLogsPrevPage" class="btn btn-secondary btn-sm">
                                <i data-lucide="chevron-left" class="w-4 h-4 mr-1"></i>
                                Previous
                            </button>
                            <div id="loginLogsPageInfo" class="text-sm text-gray-600 mx-4">
                                Page <span id="loginLogsCurrentPage">1</span> of <span id="loginLogsTotalPages">1</span>
                                (<span id="loginLogsTotalCount">0</span> total records)
                            </div>
                            <button id="loginLogsNextPage" class="btn btn-secondary btn-sm">
                                Next
                                <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Admin Logs Tab -->
            <div id="admin-logs-content" class="log-tab-content hidden">
                <div class="glass-card">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-semibold text-gray-900">Admin Activity Logs</h3>
                            <button onclick="adminDashboard.loadAdminLogs()" class="btn btn-primary btn-sm">
                                <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                                Refresh
                            </button>
                        </div>
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
                                <tbody id="adminLogsTableBody">
                                    <!-- Admin logs will be populated here by JS -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Admin Logs Pagination -->
                        <div id="adminLogsPagination" class="flex justify-center items-center mt-6 space-x-2 hidden">
                            <button id="adminLogsPrevPage" class="btn btn-secondary btn-sm">
                                <i data-lucide="chevron-left" class="w-4 h-4 mr-1"></i>
                                Previous
                            </button>
                            <div id="adminLogsPageInfo" class="text-sm text-gray-600 mx-4">
                                Page <span id="adminLogsCurrentPage">1</span> of <span id="adminLogsTotalPages">1</span>
                                (<span id="adminLogsTotalCount">0</span> total records)
                            </div>
                            <button id="adminLogsNextPage" class="btn btn-secondary btn-sm">
                                Next
                                <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Management Modals -->

<!-- Create User Modal -->
<div id="createUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Create New User</h3>
                    <button onclick="adminDashboard.closeModal('createUserModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <form id="createUserForm" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="md:col-span-2">
                        <h4 class="text-md font-semibold text-gray-800 mb-4">Basic Information</h4>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="full_name" required class="form-input">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" name="email" required class="form-input">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                        <select name="role" id="createUserRole" required class="form-select">
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="adviser">Adviser</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" placeholder="Leave blank for auto-generated" class="form-input">
                        <p class="text-xs text-gray-500 mt-1">Leave blank to auto-generate a secure password</p>
                    </div>
                    
                    <!-- Role-specific fields -->
                    <div class="md:col-span-2">
                        <h4 class="text-md font-semibold text-gray-800 mb-4">Role-specific Information</h4>
                    </div>
                    
                    <!-- Student fields -->
                    <div id="studentFields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6" style="display: none;">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Student ID</label>
                            <input type="text" name="student_id" class="form-input">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Program</label>
                            <select name="program" class="form-select">
                                <option value="">Select Program</option>
                                <option value="BS Computer Science">BS Computer Science</option>
                                <option value="BS Information Technology">BS Information Technology</option>
                                <option value="BS Engineering">BS Engineering</option>
                                <option value="MS Computer Science">MS Computer Science</option>
                                <option value="PhD Computer Science">PhD Computer Science</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select name="department" class="form-select">
                                <option value="">Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Business">Business</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Adviser fields -->
                    <div id="adviserFields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6" style="display: none;">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Faculty ID</label>
                            <input type="text" name="faculty_id" class="form-input">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select name="adviser_department" class="form-select">
                                <option value="">Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Business">Business</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                            <input type="text" name="specialization" placeholder="e.g., Machine Learning, Software Engineering" class="form-input">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" onclick="adminDashboard.closeModal('createUserModal')" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i>
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Edit User</h3>
                    <button onclick="adminDashboard.closeModal('editUserModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <form id="editUserForm" class="p-6">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="md:col-span-2">
                        <h4 class="text-md font-semibold text-gray-800 mb-4">Basic Information</h4>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="full_name" id="editFullName" required class="form-input">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" name="email" id="editEmail" required class="form-input">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                        <select name="role" id="editUserRole" required class="form-select">
                            <option value="student">Student</option>
                            <option value="adviser">Adviser</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    
                    <!-- Role-specific fields -->
                    <div class="md:col-span-2">
                        <h4 class="text-md font-semibold text-gray-800 mb-4">Role-specific Information</h4>
                    </div>
                    
                    <!-- Student fields -->
                    <div id="editStudentFields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6" style="display: none;">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Student ID</label>
                            <input type="text" name="student_id" id="editStudentId" class="form-input">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Program</label>
                            <select name="program" id="editProgram" class="form-select">
                                <option value="">Select Program</option>
                                <option value="BS Computer Science">BS Computer Science</option>
                                <option value="BS Information Technology">BS Information Technology</option>
                                <option value="BS Engineering">BS Engineering</option>
                                <option value="MS Computer Science">MS Computer Science</option>
                                <option value="PhD Computer Science">PhD Computer Science</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select name="department" id="editDepartment" class="form-select">
                                <option value="">Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Business">Business</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Adviser fields -->
                    <div id="editAdviserFields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6" style="display: none;">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Faculty ID</label>
                            <input type="text" name="faculty_id" id="editFacultyId" class="form-input">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select name="adviser_department" id="editAdviserDepartment" class="form-select">
                                <option value="">Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Business">Business</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" onclick="adminDashboard.closeModal('editUserModal')" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Password Display Modal -->
<div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">User Password</h3>
                    <button onclick="adminDashboard.closeModal('passwordModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="key" class="w-8 h-8 text-green-600"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Password Generated</h4>
                    <p class="text-gray-600">Please save this password securely</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">User:</p>
                            <p class="font-semibold" id="passwordUserName">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Email:</p>
                            <p class="font-semibold" id="passwordUserEmail">-</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600 mb-2">Password:</p>
                    <div class="flex items-center justify-between">
                        <code class="text-lg font-mono text-blue-900" id="generatedPassword">-</code>
                        <button onclick="adminDashboard.copyPassword()" class="btn btn-secondary btn-sm">
                            <i data-lucide="copy" class="w-4 h-4 mr-1"></i>
                            Copy
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button onclick="adminDashboard.closeModal('passwordModal')" class="btn btn-primary">
                        Got it
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete User</h3>
                    <p class="text-gray-600">Are you sure you want to delete this user? This action cannot be undone.</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <p class="font-semibold" id="deleteUserName">-</p>
                    <p class="text-sm text-gray-600" id="deleteUserEmail">-</p>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button onclick="adminDashboard.closeModal('deleteUserModal')" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button onclick="adminDashboard.confirmDeleteUser()" class="btn btn-danger">
                        <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                        Delete User
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Program Management Modals -->

<!-- Create Program Modal -->
<div id="createProgramModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900">Add New Program</h3>
                    <button onclick="adminDashboard.closeModal('createProgramModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                
                <form id="createProgramForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Program Code *</label>
                            <input type="text" name="program_code" class="form-input" required placeholder="e.g., BSIT">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Program Name *</label>
                            <input type="text" name="program_name" class="form-input" required placeholder="e.g., Bachelor of Science in Information Technology">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                            <input type="text" name="department" class="form-input" required placeholder="e.g., College of Computer Studies">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Duration (Years) *</label>
                            <input type="number" name="duration_years" class="form-input" required min="1" max="10" value="4">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total Units *</label>
                            <input type="number" name="total_units" class="form-input" required min="1" value="120">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" class="form-textarea" rows="3" placeholder="Brief description of the program..."></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="adminDashboard.closeModal('createProgramModal')" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                            Create Program
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Program Modal -->
<div id="editProgramModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900">Edit Program</h3>
                    <button onclick="adminDashboard.closeModal('editProgramModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                
                <form id="editProgramForm">
                    <input type="hidden" name="program_id" id="editProgramId">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Program Code *</label>
                            <input type="text" name="program_code" id="editProgramCode" class="form-input" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Program Name *</label>
                            <input type="text" name="program_name" id="editProgramName" class="form-input" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                            <input type="text" name="department" id="editProgramDepartment" class="form-input" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Duration (Years) *</label>
                            <input type="number" name="duration_years" id="editProgramDuration" class="form-input" required min="1" max="10">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total Units *</label>
                            <input type="number" name="total_units" id="editProgramUnits" class="form-input" required min="1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="is_active" id="editProgramStatus" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="editProgramDescription" class="form-textarea" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="adminDashboard.closeModal('editProgramModal')" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                            Update Program
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Program Confirmation Modal -->
<div id="deleteProgramModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Program</h3>
                    <p class="text-gray-600">Are you sure you want to delete this program? This action cannot be undone.</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <p class="font-semibold" id="deleteProgramName">-</p>
                    <p class="text-sm text-gray-600" id="deleteProgramCode">-</p>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button onclick="adminDashboard.closeModal('deleteProgramModal')" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button onclick="adminDashboard.confirmDeleteProgram()" class="btn btn-danger">
                        <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                        Delete Program
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
// Pass PHP data to JavaScript
window.departmentData = <?php echo json_encode($analytics['department_performance'] ?? []); ?>;
window.activityData = <?php echo json_encode($analytics['monthly_activity'] ?? []); ?>;

// Debug analytics data
console.log('Analytics data loaded from PHP:');
console.log('Department performance:', window.departmentData);
console.log('Monthly activity:', window.activityData);
console.log('Analytics object from PHP:', <?php echo json_encode($analytics); ?>);

// Ensure we have fallback data if needed
if (!window.departmentData || window.departmentData.length === 0) {
    console.log('No department data from PHP, using fallback data');
    window.departmentData = [
        {department: 'Computer Science', student_count: 25, avg_progress: 75.5},
        {department: 'Information Technology', student_count: 18, avg_progress: 82.3},
        {department: 'Engineering', student_count: 15, avg_progress: 68.7},
        {department: 'Business', student_count: 12, avg_progress: 91.2}
    ];
}

if (!window.activityData || window.activityData.length === 0) {
    console.log('No activity data from PHP, using fallback data');
    const currentDate = new Date();
    window.activityData = [];
    for (let i = 5; i >= 0; i--) {
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
        window.activityData.push({
            month_name: date.toLocaleDateString('en-US', { month: 'short' }),
            activity_count: Math.floor(Math.random() * 40) + 20
        });
    }
}

// Direct chart initialization - Force charts to load immediately
console.log('Starting direct chart initialization...');

function forceInitializeCharts() {
    // Prevent multiple simultaneous calls
    if (window.chartsInitializing) {
        console.log('Charts already initializing, skipping...');
        return;
    }
    
    window.chartsInitializing = true;
    console.log('Force initialize charts called');
    console.log('Chart.js available:', typeof Chart !== 'undefined');
    console.log('Department data:', window.departmentData);
    console.log('Activity data:', window.activityData);
    
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded!');
        window.chartsInitializing = false;
        return;
    }
    
    // Destroy existing charts first
    if (window.departmentChartInstance) {
        console.log('Destroying existing department chart');
        window.departmentChartInstance.destroy();
        window.departmentChartInstance = null;
    }
    if (window.activityChartInstance) {
        console.log('Destroying existing activity chart');
        window.activityChartInstance.destroy();
        window.activityChartInstance = null;
    }
    
    // Force create department chart
    setTimeout(() => {
        const deptCanvas = document.getElementById('departmentChart');
        if (deptCanvas && window.departmentData && window.departmentData.length > 0) {
            console.log('Creating department chart directly...');
            
            const labels = window.departmentData.map(dept => dept.department || 'No Department');
            const studentCounts = window.departmentData.map(dept => parseInt(dept.student_count));
            const avgProgress = window.departmentData.map(dept => parseFloat(dept.avg_progress));
            
            try {
                window.departmentChartInstance = new Chart(deptCanvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Students',
                            data: studentCounts,
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        }, {
                            label: 'Average Progress (%)',
                            data: avgProgress,
                            type: 'line',
                            backgroundColor: 'rgba(34, 197, 94, 0.2)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 2,
                            fill: false,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Department Performance Overview'
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Number of Students'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Average Progress (%)'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                                max: 100
                            }
                        }
                    }
                });
                console.log('Department chart created successfully!');
            } catch (error) {
                console.error('Error creating department chart:', error);
            }
        }
        
        // Force create activity chart
        const actCanvas = document.getElementById('activityChart');
        if (actCanvas && window.activityData && window.activityData.length > 0) {
            console.log('Creating activity chart directly...');
            
            const labels = window.activityData.map(item => item.month_name);
            const activityCounts = window.activityData.map(item => parseInt(item.activity_count));
            
            try {
                window.activityChartInstance = new Chart(actCanvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'System Activity',
                            data: activityCounts,
                            backgroundColor: 'rgba(168, 85, 247, 0.2)',
                            borderColor: 'rgb(168, 85, 247)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: 'rgb(168, 85, 247)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Monthly Activity Trends'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Activity Count'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Month'
                                }
                            }
                        }
                    }
                });
                console.log('Activity chart created successfully!');
            } catch (error) {
                console.error('Error creating activity chart:', error);
            }
        }
        
        // Reset the flag after both charts are processed
        window.chartsInitializing = false;
    }, 500);
}

// Try multiple initialization approaches
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', forceInitializeCharts);
} else {
    forceInitializeCharts();
}

// Additional attempt with delay only if charts aren't already created
setTimeout(() => {
    if (!window.departmentChartInstance && !window.activityChartInstance) {
        forceInitializeCharts();
    }
}, 1000);

// Global function for manual testing
window.forceCharts = forceInitializeCharts;
</script>
<script src="assets/js/admin-dashboard.js"></script>

</body>
</html>