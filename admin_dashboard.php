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
    header("Location: studentDashboard.php"); // Redirect to appropriate dashboard
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
                'password' => $_POST['password'],
                'full_name' => $_POST['full_name'],
                'role' => $_POST['role'],
                'student_id' => $_POST['student_id'] ?? null,
                'faculty_id' => $_POST['faculty_id'] ?? null,
                'program' => $_POST['program'] ?? null,
                'department' => $_POST['department'] ?? null
            ];
            $result = $adminManager->createUser($userData);
            echo json_encode(['success' => $result !== false, 'user_id' => $result]);
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
    }
}

// Get user info for header
require_once 'includes/thesis_functions.php';
$thesisManager = new ThesisManager();
$user = $thesisManager->getUserById($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Thesis Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">

<!-- Navigation -->
<nav class="bg-white shadow-lg border-b-2 border-blue-500">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center space-x-4">
                <div class="text-2xl font-bold text-blue-600">📚 Admin Panel</div>
                <div class="text-sm text-gray-500">Thesis Management System</div>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- System Health Indicator -->
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full <?php echo $stats['system_health']['overall_percentage'] > 80 ? 'bg-green-500' : ($stats['system_health']['overall_percentage'] > 60 ? 'bg-yellow-500' : 'bg-red-500'); ?>"></div>
                    <span class="text-sm text-gray-600">System <?php echo $stats['system_health']['overall_percentage']; ?>%</span>
                </div>
                
                <!-- User Menu -->
                <div class="relative">
                    <div class="flex items-center space-x-3 cursor-pointer" onclick="toggleUserMenu()">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($user['full_name']); ?></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
                    </div>
                    
                    <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile Settings</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">System Logs</a>
                        <div class="border-t border-gray-100"></div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 py-8">
    
    <!-- Tab Navigation -->
    <div class="mb-8">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('overview')" class="tab-button active py-2 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 inline mr-2"></i>Overview
                </button>
                <button onclick="showTab('users')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                    <i data-lucide="users" class="w-4 h-4 inline mr-2"></i>User Management
                </button>
                <button onclick="showTab('analytics')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                    <i data-lucide="bar-chart-3" class="w-4 h-4 inline mr-2"></i>Analytics
                </button>
                <button onclick="showTab('announcements')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                    <i data-lucide="megaphone" class="w-4 h-4 inline mr-2"></i>Announcements
                </button>
                <button onclick="showTab('settings')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                    <i data-lucide="settings" class="w-4 h-4 inline mr-2"></i>System Settings
                </button>
                <button onclick="showTab('logs')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                    <i data-lucide="file-text" class="w-4 h-4 inline mr-2"></i>Activity Logs
                </button>
            </nav>
        </div>
    </div>

    <!-- Overview Tab -->
    <div id="overview-tab" class="tab-content">
        <!-- System Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Students -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i data-lucide="graduation-cap" class="w-6 h-6 text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Students</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['users']['student'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <!-- Active Advisers -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i data-lucide="user-check" class="w-6 h-6 text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Advisers</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['users']['adviser'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <!-- Active Theses -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i data-lucide="book-open" class="w-6 h-6 text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Theses</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['active_theses']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Pending Reviews -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i data-lucide="clock" class="w-6 h-6 text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending Reviews</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['pending_reviews']; ?></p>
                        <?php if ($stats['overdue_deadlines'] > 0): ?>
                        <p class="text-xs text-red-600"><?php echo $stats['overdue_deadlines']; ?> overdue</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Department Performance Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Department Performance</h3>
                <canvas id="departmentChart" width="400" height="200"></canvas>
            </div>

            <!-- Monthly Activity Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Activity Trends</h3>
                <canvas id="activityChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Active Announcements -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Active Announcements</h3>
                <div class="space-y-3">
                    <?php if (empty($announcements)): ?>
                        <p class="text-gray-500 text-sm">No active announcements</p>
                    <?php else: ?>
                        <?php foreach (array_slice($announcements, 0, 5) as $announcement): ?>
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo substr(htmlspecialchars($announcement['content']), 0, 100) . '...'; ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($announcement['created_at'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Admin Activity -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Admin Activity</h3>
                <div class="space-y-3">
                    <?php if (empty($recentLogs)): ?>
                        <p class="text-gray-500 text-sm">No recent activity</p>
                    <?php else: ?>
                        <?php foreach (array_slice($recentLogs, 0, 5) as $log): ?>
                        <div class="flex items-center space-x-3 py-2">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900">
                                    <span class="font-medium"><?php echo htmlspecialchars($log['admin_name']); ?></span>
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </p>
                                <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- User Management Tab -->
    <div id="users-tab" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">User Management</h3>
                    <button onclick="showCreateUserModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>Add User
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <select id="roleFilter" class="rounded-md border-gray-300">
                        <option value="">All Roles</option>
                        <option value="student">Students</option>
                        <option value="adviser">Advisers</option>
                        <option value="admin">Admins</option>
                    </select>
                    
                    <input type="text" id="searchFilter" placeholder="Search by name or email..." class="rounded-md border-gray-300">
                    
                    <select id="departmentFilter" class="rounded-md border-gray-300">
                        <option value="">All Departments</option>
                        <option value="Computer Science">Computer Science</option>
                        <option value="Information Technology">Information Technology</option>
                    </select>
                </div>
            </div>
            
            <div class="p-6">
                <div id="usersTable" class="overflow-x-auto">
                    <!-- Users table will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Tab -->
    <div id="analytics-tab" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Adviser Workload -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Adviser Workload</h3>
                <canvas id="adviserWorkloadChart" width="400" height="200"></canvas>
            </div>
            
            <!-- Department Comparison -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Department Statistics</h3>
                <div class="space-y-4">
                    <?php if (!empty($analytics['department_performance'])): ?>
                        <?php foreach ($analytics['department_performance'] as $dept): ?>
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($dept['department']); ?></h4>
                            <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Students:</span>
                                    <span class="font-medium"><?php echo $dept['student_count']; ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Avg Progress:</span>
                                    <span class="font-medium"><?php echo round($dept['avg_progress'], 1); ?>%</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Announcements Tab -->
    <div id="announcements-tab" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">System Announcements</h3>
                    <button onclick="showCreateAnnouncementModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>New Announcement
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="space-y-4">
                    <?php foreach ($announcements as $announcement): ?>
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($announcement['content']); ?></p>
                                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                                    <span>Priority: <?php echo ucfirst($announcement['priority']); ?></span>
                                    <span>Created: <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Tab -->
    <div id="settings-tab" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Settings</h3>
            <p class="text-gray-600">System settings management will be implemented here.</p>
        </div>
    </div>

    <!-- Activity Logs Tab -->
    <div id="logs-tab" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Admin Activity Logs</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($log['admin_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($log['action']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($log['target_type']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals (Create User, Create Announcement, etc.) -->
<!-- These will be added in the next step -->

<!-- JavaScript -->
<script>
// Initialize Lucide icons
lucide.createIcons();

// Tab functionality
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.remove('hidden');
    
    // Add active class to selected button
    event.target.classList.add('active', 'border-blue-500', 'text-blue-600');
    event.target.classList.remove('border-transparent', 'text-gray-500');
}

// User menu toggle
function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    menu.classList.toggle('hidden');
}

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadUsers();
});

function initCharts() {
    // Department Performance Chart
    const deptCtx = document.getElementById('departmentChart').getContext('2d');
    const deptData = <?php echo json_encode($analytics['department_performance'] ?? []); ?>;
    
    new Chart(deptCtx, {
        type: 'bar',
        data: {
            labels: deptData.map(d => d.department),
            datasets: [{
                label: 'Students',
                data: deptData.map(d => d.student_count),
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Monthly Activity Chart
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    const activityData = <?php echo json_encode($analytics['monthly_activity'] ?? []); ?>;
    
    new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: activityData.map(d => d.month),
            datasets: [{
                label: 'Activity Count',
                data: activityData.map(d => d.activity_count),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// User management functions
function loadUsers() {
    const filters = {
        role: document.getElementById('roleFilter')?.value || '',
        search: document.getElementById('searchFilter')?.value || '',
        department: document.getElementById('departmentFilter')?.value || ''
    };
    
    fetch('admin_dashboard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_users&' + new URLSearchParams(filters)
    })
    .then(response => response.json())
    .then(users => {
        displayUsers(users);
    })
    .catch(error => {
        console.error('Error loading users:', error);
    });
}

function displayUsers(users) {
    const tableContainer = document.getElementById('usersTable');
    
    let html = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    users.forEach(user => {
        html += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${user.full_name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.email}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ${user.role}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.department || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="resetPassword(${user.id})" class="text-blue-600 hover:text-blue-900 mr-3">Reset Password</button>
                    <button onclick="deleteUser(${user.id})" class="text-red-600 hover:text-red-900">Delete</button>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    tableContainer.innerHTML = html;
}

// Event listeners for filters
document.addEventListener('DOMContentLoaded', function() {
    ['roleFilter', 'searchFilter', 'departmentFilter'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', loadUsers);
            if (element.type === 'text') {
                element.addEventListener('input', debounce(loadUsers, 300));
            }
        }
    });
});

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Modal functions (will be implemented in next step)
function showCreateUserModal() {
    alert('Create user modal will be implemented in the next step');
}

function showCreateAnnouncementModal() {
    alert('Create announcement modal will be implemented in the next step');
}

function resetPassword(userId) {
    if (confirm('Are you sure you want to reset this user\'s password?')) {
        fetch('admin_dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reset_password&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Password reset successful! New password: ${data.password}`);
            } else {
                alert('Error resetting password');
            }
        });
    }
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch('admin_dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_user&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User deleted successfully');
                loadUsers();
            } else {
                alert('Error deleting user');
            }
        });
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.relative')) {
        document.getElementById('userMenu').classList.add('hidden');
    }
});
</script>

</body>
</html>