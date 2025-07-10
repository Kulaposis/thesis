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
        let html = `<table class='modern-table w-full'><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Actions</th></tr></thead><tbody>`;
        for (const user of users) {
            html += `<tr><td>${user.full_name}</td><td>${user.email}</td><td>${user.role}</td><td>${user.department || ''}</td><td>
                <button class='btn btn-sm btn-secondary' title='Reset Password' onclick='adminDashboard.resetPassword(${user.user_id})'><i data-lucide="refresh-ccw" class="w-4 h-4"></i></button>
                <button class='btn btn-sm btn-danger' title='Delete User' onclick='adminDashboard.deleteUser(${user.user_id})'><i data-lucide="trash-2" class="w-4 h-4"></i></button>
            </td></tr>`;
        }
        html += `</tbody></table>`;
        usersTable.innerHTML = html;
        lucide.createIcons();
    }

    async createUser() {
        const form = document.getElementById('createUserForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                this.showNotification('User created successfully!', 'success');
                this.closeModal(document.querySelector('.modal-overlay'));
                this.loadUsers();
                form.reset();
            } else {
                this.showNotification('Error creating user', 'error');
            }
        } catch (error) {
            this.showNotification('Error creating user', 'error');
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
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_user&user_id=${userId}`
            });

            const result = await response.json();
            if (result.success) {
                this.showNotification('User deleted successfully', 'success');
                this.loadUsers();
            } else {
                this.showNotification('Error deleting user', 'error');
            }
        } catch (error) {
            this.showNotification('Error deleting user', 'error');
        }
    }

    async resetPassword(userId) {
        if (!confirm('Are you sure you want to reset this user\'s password?')) {
            return;
        }

        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reset_password&user_id=${userId}`
            });

            const result = await response.json();
            if (result.success) {
                this.showNotification(`Password reset successful! New password: ${result.password}`, 'success');
            } else {
                this.showNotification('Error resetting password', 'error');
            }
        } catch (error) {
            this.showNotification('Error resetting password', 'error');
        }
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
                <div class="modal-content bg-white rounded-2xl p-6 w-full max-w-md mx-4">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold">Create New User</h3>
                        <button onclick="adminDashboard.closeModal(this.closest('.modal-overlay'))" class="text-gray-400 hover:text-gray-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <form id="createUserForm">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                <input type="text" name="full_name" required class="search-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" required class="search-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                <select name="role" required class="search-input">
                                    <option value="">Select Role</option>
                                    <option value="student">Student</option>
                                    <option value="adviser">Adviser</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                                <select name="department" class="search-input">
                                    <option value="">Select Department</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Information Technology">Information Technology</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="adminDashboard.closeModal(this.closest('.modal-overlay'))" class="btn btn-secondary">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
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