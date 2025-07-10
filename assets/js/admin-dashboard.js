// Modern Admin Dashboard JavaScript
class AdminDashboard {
    constructor() {
        this.currentTab = 'overview';
        this.isLoading = false;
        this.init();
    }

    init() {
        this.initializeComponents();
        this.bindEvents();
        this.loadInitialData();
        this.setupAnimations();
        this.initializeTabs();
        // System Settings events
        const settingsTab = document.getElementById('settings-tab');
        if (settingsTab) {
            settingsTab.addEventListener('show', () => this.loadSettings());
        }
        const settingsForm = document.getElementById('systemSettingsForm');
        if (settingsForm) {
            settingsForm.addEventListener('submit', (e) => this.saveSettings(e));
        }
    }

    initializeComponents() {
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Initialize charts
        this.initCharts();

        // Setup mobile menu
        this.setupMobileMenu();

        // Initialize tooltips
        this.initTooltips();

        // Setup real-time updates
        this.setupRealTimeUpdates();
    }

    bindEvents() {
        // Tab navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const tabName = item.getAttribute('data-tab');
                console.log('Nav item clicked:', tabName); // Debug log
                this.showTab(tabName);
            });
        });

        // Search and filter events
        this.setupSearchFilters();

        // Modal events
        this.setupModals();

        // Form submissions
        this.setupFormHandlers();

        // Keyboard shortcuts
        this.setupKeyboardShortcuts();
    }

    showTab(tabName) {
        console.log('Switching to tab:', tabName); // Debug log
        
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
            tab.classList.remove('fade-in');
        });
        
        // Remove active class from all nav items
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Show selected tab with animation
        const selectedTab = document.getElementById(tabName + '-tab');
        if (selectedTab) {
            selectedTab.classList.remove('hidden');
            setTimeout(() => {
                selectedTab.classList.add('fade-in');
            }, 10);
        } else {
            console.error('Tab not found:', tabName + '-tab'); // Debug log
        }
        
        // Add active class to selected nav item
        const activeNavItem = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeNavItem) {
            activeNavItem.classList.add('active');
        } else {
            console.error('Nav item not found for tab:', tabName); // Debug log
        }

        this.currentTab = tabName;
        this.loadTabData(tabName);

        if (tabName === 'settings') {
            const settingsTab = document.getElementById('settings-tab');
            if (settingsTab) {
                const event = new Event('show');
                settingsTab.dispatchEvent(event);
            }
        }
    }

    initializeTabs() {
        // Show overview tab by default
        this.showTab('overview');
        
        // Ensure overview nav item is active
        const overviewNavItem = document.querySelector('[data-tab="overview"]');
        if (overviewNavItem) {
            overviewNavItem.classList.add('active');
        }
    }

    loadTabData(tabName) {
        switch(tabName) {
            case 'users':
                this.loadUsers();
                break;
            case 'analytics':
                this.loadAnalytics();
                break;
            case 'announcements':
                this.loadAnnouncements();
                break;
            case 'logs':
                this.loadLogs();
                break;
            case 'settings':
                this.loadSettings();
                break;
        }
    }

    async loadAnnouncements() {
        // This method can be implemented to load announcements dynamically
        // For now, it's handled by PHP rendering
        console.log('Loading announcements...');
    }

    async loadAnalytics() {
        // Adviser Workload
        const loading = document.getElementById('adviserWorkloadLoading');
        const chartCanvas = document.getElementById('adviserWorkloadChart');
        if (loading) loading.classList.remove('hidden');
        if (chartCanvas) chartCanvas.style.display = 'none';
        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_adviser_workload'
            });
            const data = await response.json();
            if (Array.isArray(data) && data.length > 0) {
                this.renderAdviserWorkloadChart(data);
                if (chartCanvas) chartCanvas.style.display = '';
            } else {
                if (chartCanvas) {
                    chartCanvas.style.display = 'none';
                    loading.innerHTML = '<p class="text-gray-500">No adviser workload data available.</p>';
                }
            }
        } catch (error) {
            if (loading) loading.innerHTML = '<p class="text-red-500">Failed to load adviser workload.</p>';
        } finally {
            if (loading) loading.classList.add('hidden');
        }
    }

    renderAdviserWorkloadChart(data) {
        const ctx = document.getElementById('adviserWorkloadChart').getContext('2d');
        if (this._adviserWorkloadChart) {
            this._adviserWorkloadChart.destroy();
        }
        this._adviserWorkloadChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.adviser),
                datasets: [{
                    label: 'Workload',
                    data: data.map(d => d.workload),
                    backgroundColor: '#2563eb',
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.06)' },
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    async loadLogs() {
        // This method can be implemented to load logs dynamically
        // For now, it's handled by PHP rendering
        console.log('Loading logs...');
    }

    async loadSettings() {
        const loading = document.getElementById('settingsLoading');
        const form = document.getElementById('systemSettingsForm');
        if (loading) loading.classList.remove('hidden');
        if (form) form.classList.add('hidden');
        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_settings'
            });
            const settings = await response.json();
            document.getElementById('systemName').value = settings.system_name || '';
            document.getElementById('contactEmail').value = settings.contact_email || '';
            document.getElementById('theme').value = settings.theme || 'light';
        } catch (error) {
            this.showSettingsNotification('Failed to load settings.', 'error');
        } finally {
            if (loading) loading.classList.add('hidden');
            if (form) form.classList.remove('hidden');
        }
    }

    async saveSettings(e) {
        e.preventDefault();
        const form = document.getElementById('systemSettingsForm');
        const loading = document.getElementById('settingsLoading');
        if (loading) loading.classList.remove('hidden');
        if (form) form.classList.add('hidden');
        const formData = new FormData(form);
        formData.append('action', 'update_settings');
        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                this.showSettingsNotification('Settings saved successfully!', 'success');
            } else {
                this.showSettingsNotification('Failed to save settings.', 'error');
            }
        } catch (error) {
            this.showSettingsNotification('Failed to save settings.', 'error');
        } finally {
            if (loading) loading.classList.add('hidden');
            if (form) form.classList.remove('hidden');
        }
    }

    showSettingsNotification(message, type) {
        const notif = document.getElementById('settingsNotification');
        if (!notif) return;
        notif.innerHTML = `<div class='rounded px-4 py-2 ${type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}'>${message}</div>`;
        setTimeout(() => { notif.innerHTML = ''; }, 4000);
    }

    setupSearchFilters() {
        const searchInput = document.getElementById('searchFilter');
        const roleFilter = document.getElementById('roleFilter');
        const departmentFilter = document.getElementById('departmentFilter');

        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.loadUsers();
            }, 300));
        }

        if (roleFilter) {
            roleFilter.addEventListener('change', () => {
                this.loadUsers();
            });
        }

        if (departmentFilter) {
            departmentFilter.addEventListener('change', () => {
                this.loadUsers();
            });
        }
    }

    setupModals() {
        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                this.closeModal(e.target);
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    setupFormHandlers() {
        // Create user form
        const createUserForm = document.getElementById('createUserForm');
        if (createUserForm) {
            createUserForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createUser();
            });
        }

        // Create announcement form
        const createAnnouncementForm = document.getElementById('createAnnouncementForm');
        if (createAnnouncementForm) {
            createAnnouncementForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createAnnouncement();
            });
        }
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.getElementById('searchFilter');
                if (searchInput) {
                    searchInput.focus();
                }
            }

            // Ctrl/Cmd + N for new user
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                this.showCreateUserModal();
            }
        });
    }

    setupMobileMenu() {
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                if (overlay) {
                    overlay.classList.toggle('hidden');
                }
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.add('hidden');
            });
        }
    }

    initTooltips() {
        // Simple tooltip implementation
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target);
            });

            element.addEventListener('mouseleave', (e) => {
                this.hideTooltip();
            });
        });
    }

    showTooltip(element) {
        const tooltipText = element.getAttribute('data-tooltip');
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = tooltipText;
        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    }

    hideTooltip() {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    setupRealTimeUpdates() {
        // Update system health every 30 seconds
        setInterval(() => {
            this.updateSystemHealth();
        }, 30000);

        // Update notifications every minute
        setInterval(() => {
            this.updateNotifications();
        }, 60000);
    }

    async loadUsers() {
        const usersTable = document.getElementById('usersTable');
        if (!usersTable) return;
        usersTable.innerHTML = `<div class='text-center py-12 loading-indicator'><i data-lucide="loader-2" class="w-8 h-8 text-gray-400 mx-auto mb-4 animate-spin"></i><p class='text-gray-500'>Loading users...</p></div>`;

        const role = document.getElementById('roleFilter')?.value || '';
        const search = document.getElementById('searchFilter')?.value || '';
        const department = document.getElementById('departmentFilter')?.value || '';

        const formData = new FormData();
        formData.append('action', 'get_users');
        formData.append('role', role);
        formData.append('search', search);
        formData.append('department', department);

        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            });
            const users = await response.json();
            this.displayUsers(users);
        } catch (error) {
            usersTable.innerHTML = `<div class='text-center py-12'><p class='text-red-500'>Failed to load users.</p></div>`;
        }
    }

    displayUsers(users) {
        const usersTable = document.getElementById('usersTable');
        if (!usersTable) return;
        if (!users || users.length === 0) {
            usersTable.innerHTML = `<div class='text-center py-12'><p class='text-gray-500'>No users found.</p></div>`;
            return;
        }
        
        let html = `
            <table class='modern-table w-full'>
                <thead>
                    <tr>
                        <th>Avatar</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>ID</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        for (const user of users) {
            const userId = user.id || user.user_id;
            const avatarColor = this.getAvatarColor(user.role);
            const createdDate = new Date(user.created_at).toLocaleDateString();
            const studentId = user.student_id || '';
            const facultyId = user.faculty_id || '';
            const displayId = user.role === 'student' ? studentId : (user.role === 'adviser' ? facultyId : userId);
            
            html += `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td>
                        <div class="w-8 h-8 ${avatarColor} rounded-full flex items-center justify-center text-white font-semibold text-sm">
                            ${user.full_name.charAt(0).toUpperCase()}
                        </div>
                    </td>
                    <td>
                        <div class="font-medium text-gray-900">${user.full_name}</div>
                    </td>
                    <td>
                        <div class="text-gray-600">${user.email}</div>
                    </td>
                    <td>
                        <span class="badge ${this.getRoleBadgeClass(user.role)}">${this.capitalizeRole(user.role)}</span>
                    </td>
                    <td>
                        <div class="text-gray-600">${user.department || 'Not specified'}</div>
                    </td>
                    <td>
                        <div class="text-sm text-gray-500 font-mono">${displayId}</div>
                    </td>
                    <td>
                        <div class="text-sm text-gray-500">${createdDate}</div>
                    </td>
                    <td>
                        <div class="flex space-x-2">
                            <button class='btn btn-sm btn-secondary' title='Reset Password' onclick='adminDashboard.resetPassword(${userId})'>
                                <i data-lucide="refresh-ccw" class="w-4 h-4"></i>
                            </button>
                            <button class='btn btn-sm btn-warning' title='Edit User' onclick='adminDashboard.editUser(${userId})'>
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button class='btn btn-sm btn-danger' title='Delete User' onclick='adminDashboard.deleteUser(${userId})'>
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
        html += `</tbody></table>`;
        usersTable.innerHTML = html;
        lucide.createIcons();
    }

    getAvatarColor(role) {
        const colors = {
            'student': 'bg-blue-500',
            'adviser': 'bg-green-500',
            'admin': 'bg-purple-500',
            'super_admin': 'bg-red-500'
        };
        return colors[role] || 'bg-gray-500';
    }

    getRoleBadgeClass(role) {
        const classes = {
            'student': 'badge-info',
            'adviser': 'badge-success',
            'admin': 'badge-warning',
            'super_admin': 'badge-danger'
        };
        return classes[role] || 'badge-secondary';
    }

    capitalizeRole(role) {
        return role.replace('_', ' ').split(' ').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }

    async createUser() {
        const form = document.getElementById('createUserForm');
        const formData = new FormData(form);
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i>Creating...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                // Show success notification with password
                this.showPasswordModal(result.password, formData.get('full_name'), formData.get('email'));
                this.closeModal(document.querySelector('.modal-overlay'));
                this.loadUsers();
                form.reset();
            } else {
                this.showNotification(result.message || 'Error creating user', 'error');
            }
        } catch (error) {
            this.showNotification('Error creating user: ' + error.message, 'error');
        } finally {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            lucide.createIcons();
        }
    }

    showPasswordModal(password, name, email) {
        const modal = `
            <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="modal-content bg-white rounded-2xl p-6 w-full max-w-md mx-4">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="check" class="w-8 h-8 text-green-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900">User Created Successfully!</h3>
                        <p class="text-gray-600 mt-2">Please save the login credentials for ${name}</p>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email:</label>
                                <div class="mt-1 flex items-center justify-between bg-white px-3 py-2 border rounded">
                                    <span class="font-mono text-sm">${email}</span>
                                    <button onclick="adminDashboard.copyToClipboard('${email}')" class="text-gray-400 hover:text-gray-600">
                                        <i data-lucide="copy" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Password:</label>
                                <div class="mt-1 flex items-center justify-between bg-white px-3 py-2 border rounded">
                                    <span class="font-mono text-sm">${password}</span>
                                    <button onclick="adminDashboard.copyToClipboard('${password}')" class="text-gray-400 hover:text-gray-600">
                                        <i data-lucide="copy" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 mt-0.5 mr-3"></i>
                            <div class="text-sm text-amber-700">
                                <p class="font-medium">Important:</p>
                                <p>Make sure to securely share these credentials with the user. The password cannot be retrieved later.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-center">
                        <button onclick="adminDashboard.closeModal(this.closest('.modal-overlay'))" class="btn btn-primary">
                            <i data-lucide="check" class="w-4 h-4 mr-2"></i>
                            Got it
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modal);
        lucide.createIcons();
    }

    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showNotification('Copied to clipboard!', 'success');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showNotification('Copied to clipboard!', 'success');
        });
    }

    async editUser(userId) {
        // First, get user data
        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_user&user_id=${userId}`
            });
            const result = await response.json();
            
            if (result.success) {
                this.showEditUserModal(result.user);
            } else {
                this.showNotification('Error loading user data', 'error');
            }
        } catch (error) {
            this.showNotification('Error loading user data', 'error');
        }
    }

    showEditUserModal(user) {
        const modal = `
            <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="modal-content bg-white rounded-2xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold">Edit User</h3>
                        <button onclick="adminDashboard.closeModal(this.closest('.modal-overlay'))" class="text-gray-400 hover:text-gray-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <form id="editUserForm">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" value="${user.id}">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" name="full_name" required class="search-input" value="${user.full_name || ''}" placeholder="Enter full name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" name="email" required class="search-input" value="${user.email || ''}" placeholder="user@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                                <select name="role" required class="search-input" onchange="adminDashboard.toggleEditRoleFields(this.value)">
                                    <option value="">Select Role</option>
                                    <option value="student" ${user.role === 'student' ? 'selected' : ''}>Student</option>
                                    <option value="adviser" ${user.role === 'adviser' ? 'selected' : ''}>Adviser</option>
                                    <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                                </select>
                            </div>
                            
                            <!-- Student specific fields -->
                            <div id="edit-student-fields" class="${user.role !== 'student' ? 'hidden' : ''}">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Student ID</label>
                                    <input type="text" name="student_id" class="search-input" value="${user.student_id || ''}" placeholder="e.g., 2024001234">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Program</label>
                                    <select name="program" class="search-input">
                                        <option value="">Select Program</option>
                                        <option value="BS Computer Science" ${user.program === 'BS Computer Science' ? 'selected' : ''}>BS Computer Science</option>
                                        <option value="BS Information Technology" ${user.program === 'BS Information Technology' ? 'selected' : ''}>BS Information Technology</option>
                                        <option value="MS Computer Science" ${user.program === 'MS Computer Science' ? 'selected' : ''}>MS Computer Science</option>
                                        <option value="PhD Computer Science" ${user.program === 'PhD Computer Science' ? 'selected' : ''}>PhD Computer Science</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Adviser specific fields -->
                            <div id="edit-adviser-fields" class="${user.role !== 'adviser' ? 'hidden' : ''}">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Faculty ID</label>
                                    <input type="text" name="faculty_id" class="search-input" value="${user.faculty_id || ''}" placeholder="e.g., FAC2024001">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                                <select name="department" class="search-input">
                                    <option value="">Select Department</option>
                                    <option value="Computer Science" ${user.department === 'Computer Science' ? 'selected' : ''}>Computer Science</option>
                                    <option value="Information Technology" ${user.department === 'Information Technology' ? 'selected' : ''}>Information Technology</option>
                                    <option value="Mathematics" ${user.department === 'Mathematics' ? 'selected' : ''}>Mathematics</option>
                                    <option value="Engineering" ${user.department === 'Engineering' ? 'selected' : ''}>Engineering</option>
                                </select>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5 mr-3"></i>
                                    <div class="text-sm text-blue-700">
                                        <p class="font-medium mb-1">User Account</p>
                                        <p>Created: ${new Date(user.created_at).toLocaleDateString()}</p>
                                        <p>Use the "Reset Password" button to change the user's password.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="adminDashboard.closeModal(this.closest('.modal-overlay'))" class="btn btn-secondary">
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
        `;
        document.body.insertAdjacentHTML('beforeend', modal);
        lucide.createIcons();
        
        // Setup form submission
        document.getElementById('editUserForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateUser();
        });
    }

    toggleEditRoleFields(role) {
        const studentFields = document.getElementById('edit-student-fields');
        const adviserFields = document.getElementById('edit-adviser-fields');
        
        // Hide all role-specific fields first
        studentFields.classList.add('hidden');
        adviserFields.classList.add('hidden');
        
        // Show relevant fields based on role
        if (role === 'student') {
            studentFields.classList.remove('hidden');
        } else if (role === 'adviser') {
            adviserFields.classList.remove('hidden');
        }
    }

    async updateUser() {
        const form = document.getElementById('editUserForm');
        const formData = new FormData(form);
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i>Updating...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                this.showNotification('User updated successfully!', 'success');
                this.closeModal(document.querySelector('.modal-overlay'));
                this.loadUsers();
            } else {
                this.showNotification(result.message || 'Error updating user', 'error');
            }
        } catch (error) {
            this.showNotification('Error updating user: ' + error.message, 'error');
        } finally {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            lucide.createIcons();
        }
    }

    async createAnnouncement() {
        const form = document.getElementById('createAnnouncementForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                this.showNotification('Announcement created successfully!', 'success');
                this.closeModal(document.querySelector('.modal-overlay'));
                this.loadAnnouncements();
                form.reset();
            } else {
                this.showNotification('Error creating announcement', 'error');
            }
        } catch (error) {
            this.showNotification('Error creating announcement', 'error');
        }
    }

    async deleteUser(userId) {
        // First get user info to show who we're deleting
        try {
            const userResponse = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_user&user_id=${userId}`
            });
            const userResult = await userResponse.json();
            
            if (!userResult.success) {
                this.showNotification('Error loading user data', 'error');
                return;
            }
            
            const user = userResult.user;
            
            // Enhanced confirmation with user details
            if (!confirm(`⚠️ DELETE USER CONFIRMATION ⚠️

Are you absolutely sure you want to delete this user?

User: ${user.full_name}
Email: ${user.email}
Role: ${user.role}
Created: ${new Date(user.created_at).toLocaleDateString()}

❗ THIS ACTION CANNOT BE UNDONE ❗

This will permanently remove:
• User account and login access
• All associated data and history
• Any assigned thesis work (for students)
• Supervised theses (for advisers)

Type "DELETE" in the next prompt to confirm.`)) {
                return;
            }

            // Second confirmation requiring typed confirmation
            const confirmation = prompt(`To confirm deletion of ${user.full_name}, please type "DELETE" (all caps):`);
            if (confirmation !== 'DELETE') {
                this.showNotification('User deletion cancelled', 'info');
                return;
            }

            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_user&user_id=${userId}`
            });

            const result = await response.json();
            if (result.success) {
                this.showNotification(`User "${user.full_name}" has been permanently deleted`, 'success');
                this.loadUsers();
            } else {
                this.showNotification('Error deleting user: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            this.showNotification('Error deleting user: ' + error.message, 'error');
        }
    }

    async resetPassword(userId) {
        // First get user info to show who we're resetting
        try {
            const userResponse = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_user&user_id=${userId}`
            });
            const userResult = await userResponse.json();
            
            if (!userResult.success) {
                this.showNotification('Error loading user data', 'error');
                return;
            }
            
            const user = userResult.user;
            
            if (!confirm(`Are you sure you want to reset the password for ${user.full_name} (${user.email})?\\n\\nThis will generate a new random password that you'll need to share with the user.`)) {
                return;
            }

            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reset_password&user_id=${userId}`
            });

            const result = await response.json();
            if (result.success) {
                this.showPasswordResetModal(result.password, user.full_name, user.email);
            } else {
                this.showNotification('Error resetting password', 'error');
            }
        } catch (error) {
            this.showNotification('Error resetting password: ' + error.message, 'error');
        }
    }

    showPasswordResetModal(password, name, email) {
        const modal = `
            <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="modal-content bg-white rounded-2xl p-6 w-full max-w-md mx-4">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="key" class="w-8 h-8 text-green-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900">Password Reset Successful!</h3>
                        <p class="text-gray-600 mt-2">New password generated for ${name}</p>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email:</label>
                                <div class="mt-1 flex items-center justify-between bg-white px-3 py-2 border rounded">
                                    <span class="font-mono text-sm">${email}</span>
                                    <button onclick="adminDashboard.copyToClipboard('${email}')" class="text-gray-400 hover:text-gray-600">
                                        <i data-lucide="copy" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">New Password:</label>
                                <div class="mt-1 flex items-center justify-between bg-white px-3 py-2 border rounded">
                                    <span class="font-mono text-sm">${password}</span>
                                    <button onclick="adminDashboard.copyToClipboard('${password}')" class="text-gray-400 hover:text-gray-600">
                                        <i data-lucide="copy" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 mt-0.5 mr-3"></i>
                            <div class="text-sm text-amber-700">
                                <p class="font-medium">Important:</p>
                                <p>Please securely share the new password with the user. They should change it upon first login.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-center">
                        <button onclick="adminDashboard.closeModal(this.closest('.modal-overlay'))" class="btn btn-primary">
                            <i data-lucide="check" class="w-4 h-4 mr-2"></i>
                            Got it
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modal);
        lucide.createIcons();
    }

    initCharts() {
        // Department Performance Chart
        const deptCtx = document.getElementById('departmentChart');
        if (deptCtx) {
            const deptData = window.departmentData || [];
            const studentCounts = deptData.map(d => d.student_count);
            const maxCount = Math.max(...studentCounts, 0);
            // Next multiple of 5 above maxCount, at least 5
            const suggestedMax = Math.max(5, Math.ceil((maxCount + 1) / 5) * 5);
            new Chart(deptCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: deptData.map(d => d.department),
                    datasets: [{
                        label: 'Students',
                        data: studentCounts,
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: suggestedMax,
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
            });
        }

        // Activity Chart
        const activityCtx = document.getElementById('activityChart');
        if (activityCtx) {
            const activityData = window.activityData || [];
            new Chart(activityCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: activityData.map(d => d.month),
                    datasets: [{
                        label: 'Activity Count',
                        data: activityData.map(d => d.activity_count),
                        borderColor: 'rgba(79, 172, 254, 1)',
                        backgroundColor: 'rgba(79, 172, 254, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgba(79, 172, 254, 1)',
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
                        legend: {
                            display: false
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
            });
        }
    }

    showCreateUserModal() {
        const modal = `
            <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="modal-content bg-white rounded-2xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold">Create New User</h3>
                        <button onclick="adminDashboard.closeModal(this.closest('.modal-overlay'))" class="text-gray-400 hover:text-gray-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <form id="createUserForm">
                        <input type="hidden" name="action" value="create_user">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" name="full_name" required class="search-input" placeholder="Enter full name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" name="email" required class="search-input" placeholder="user@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                                <select name="role" required class="search-input" onchange="adminDashboard.toggleRoleFields(this.value)">
                                    <option value="">Select Role</option>
                                    <option value="student">Student</option>
                                    <option value="adviser">Adviser</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            
                            <!-- Student specific fields -->
                            <div id="student-fields" class="hidden">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Student ID</label>
                                    <input type="text" name="student_id" class="search-input" placeholder="e.g., 2024001234">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Program</label>
                                    <select name="program" class="search-input">
                                        <option value="">Select Program</option>
                                        <option value="BS Computer Science">BS Computer Science</option>
                                        <option value="BS Information Technology">BS Information Technology</option>
                                        <option value="MS Computer Science">MS Computer Science</option>
                                        <option value="PhD Computer Science">PhD Computer Science</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Adviser specific fields -->
                            <div id="adviser-fields" class="hidden">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Faculty ID</label>
                                    <input type="text" name="faculty_id" class="search-input" placeholder="e.g., FAC2024001">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                                <select name="department" class="search-input">
                                    <option value="">Select Department</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Information Technology">Information Technology</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Engineering">Engineering</option>
                                </select>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5 mr-3"></i>
                                    <div class="text-sm text-blue-700">
                                        <p class="font-medium mb-1">Password Generation</p>
                                        <p>A secure random password will be automatically generated for this user. The password will be displayed after creation.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="adminDashboard.closeModal(this.closest('.modal-overlay'))" class="btn btn-secondary">
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
        `;
        document.body.insertAdjacentHTML('beforeend', modal);
        lucide.createIcons();
    }

    toggleRoleFields(role) {
        const studentFields = document.getElementById('student-fields');
        const adviserFields = document.getElementById('adviser-fields');
        
        // Hide all role-specific fields first
        studentFields.classList.add('hidden');
        adviserFields.classList.add('hidden');
        
        // Show relevant fields based on role
        if (role === 'student') {
            studentFields.classList.remove('hidden');
        } else if (role === 'adviser') {
            adviserFields.classList.remove('hidden');
        }
    }

    showCreateAnnouncementModal() {
        const modal = `
            <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="modal-content bg-white rounded-2xl p-6 w-full max-w-md mx-4">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold">Create New Announcement</h3>
                        <button onclick="adminDashboard.closeModal(this.closest('.modal-overlay'))" class="text-gray-400 hover:text-gray-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <form id="createAnnouncementForm">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                <input type="text" name="title" required class="search-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                                <textarea name="content" required rows="4" class="search-input"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                                <select name="priority" class="search-input">
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="adminDashboard.closeModal(this.closest('.modal-overlay'))" class="btn btn-secondary">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Create Announcement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modal);
        lucide.createIcons();
    }

    closeModal(modal) {
        if (modal) {
            modal.remove();
        }
    }

    closeAllModals() {
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.remove();
        });
    }

    setLoading(loading) {
        this.isLoading = loading;
        const loadingElements = document.querySelectorAll('.loading-indicator');
        loadingElements.forEach(el => {
            el.classList.toggle('loading', loading);
        });
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300`;
        notification.textContent = message;

        const colors = {
            success: 'bg-green-500 text-white',
            error: 'bg-red-500 text-white',
            warning: 'bg-yellow-500 text-white',
            info: 'bg-blue-500 text-white'
        };

        notification.classList.add(colors[type]);
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }

    debounce(func, wait) {
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

    setupAnimations() {
        // Intersection Observer for fade-in animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        });

        document.querySelectorAll('.glass-card, .stat-card').forEach(el => {
            observer.observe(el);
        });
    }

    loadInitialData() {
        // Load initial data based on current tab
        this.loadTabData(this.currentTab);
    }

    async updateSystemHealth() {
        // Update system health indicator
        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_system_health'
            });
            const data = await response.json();
            this.updateHealthIndicator(data.health_percentage);
        } catch (error) {
            console.error('Error updating system health:', error);
        }
    }

    updateHealthIndicator(percentage) {
        const indicator = document.querySelector('.health-indicator');
        if (indicator) {
            indicator.style.width = percentage + '%';
            indicator.className = `health-indicator h-2 rounded-full transition-all duration-300 ${
                percentage > 80 ? 'bg-green-500' : 
                percentage > 60 ? 'bg-yellow-500' : 'bg-red-500'
            }`;
        }
    }

    async updateNotifications() {
        // Update notification count
        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_notifications'
            });
            const data = await response.json();
            this.updateNotificationBadge(data.count);
        } catch (error) {
            console.error('Error updating notifications:', error);
        }
    }

    updateNotificationBadge(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('hidden', count === 0);
        }
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.adminDashboard = new AdminDashboard();
});

// Export for global access
window.AdminDashboard = AdminDashboard; 