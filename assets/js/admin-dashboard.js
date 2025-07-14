// Enhanced Admin Dashboard - User Management
class AdminDashboard {
    constructor() {
        this.selectedUsers = new Set();
        this.currentUsersPage = 1;
        this.usersPerPage = 10;
        this.allUsers = [];
        this.filteredUsers = [];
        this.currentUserForDelete = null;
        this.init();
    }

    init() {
        console.log('Initializing Enhanced Admin Dashboard...');
        
        // Initialize tabs
        this.initTabs();
        
        // Initialize modal functionality
        this.initModals();
        
        // Initialize user management
        this.initUserManagement();
        
        // Load initial data
        this.loadDashboardData();
        
        // Initialize charts on overview tab (default tab)
        this.waitForChartJSAndInitialize();
        
        // Initialize event listeners
        this.initEventListeners();
        
        console.log('Admin Dashboard initialized successfully');
    }

    initTabs() {
        const tabButtons = document.querySelectorAll('.nav-item');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTab = button.getAttribute('data-tab');
                
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.add('hidden'));
                
                // Add active class to clicked button and show corresponding content
                button.classList.add('active');
                const targetContent = document.getElementById(targetTab + '-tab');
                if (targetContent) {
                    targetContent.classList.remove('hidden');
                    
                    // Load data for specific tabs
                    if (targetTab === 'users') {
                        this.loadUsers();
                    } else if (targetTab === 'logs') {
                        this.loadLoginLogs();
                        // Load recent admin activity for the card at the top
                        this.loadRecentAdminActivity();
                        // Also load admin logs for the admin logs tab
                        setTimeout(() => {
                            this.loadAdminLogs();
                        }, 100);
                    } else if (targetTab === 'analytics') {
                        setTimeout(() => {
                            this.loadAnalytics();
                        }, 100);
                    } else if (targetTab === 'announcements') {
                        this.loadAnnouncements();
                    } else if (targetTab === 'overview') {
                        // Re-initialize charts when switching back to overview
                        console.log('Overview tab activated, initializing charts...');
                        setTimeout(() => {
                            this.initializeCharts();
                        }, 200);
                    }
                }
            });
        });

        // Also handle log-tab-btn for login logs (for Recent Admin Activity)
        const loginLogsTabBtn = document.querySelector('.log-tab-btn[data-log-tab="login-logs"]');
        if (loginLogsTabBtn) {
            loginLogsTabBtn.addEventListener('click', () => {
                this.loadRecentAdminActivity();
            });
        }
        
        console.log('Tabs initialized successfully');
    }

    initModals() {
        // Role change handler for create user modal
        const createUserRole = document.getElementById('createUserRole');
        if (createUserRole) {
            createUserRole.addEventListener('change', (e) => {
                this.toggleRoleFields(e.target.value, 'create');
            });
        }

        // Role change handler for edit user modal
        const editUserRole = document.getElementById('editUserRole');
        if (editUserRole) {
            editUserRole.addEventListener('change', (e) => {
                this.toggleRoleFields(e.target.value, 'edit');
            });
        }

        // Form submissions
        const createUserForm = document.getElementById('createUserForm');
        if (createUserForm) {
            createUserForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createUser();
            });
        }

        const editUserForm = document.getElementById('editUserForm');
        if (editUserForm) {
            editUserForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.updateUser();
            });
        }
    }

    initUserManagement() {
        // Select all checkboxes
        const selectAllUsers = document.getElementById('selectAllUsers');
        const selectAllUsersHeader = document.getElementById('selectAllUsersHeader');
        
        if (selectAllUsers) {
            selectAllUsers.addEventListener('change', (e) => {
                this.toggleAllUsers(e.target.checked);
            });
        }
        
        if (selectAllUsersHeader) {
            selectAllUsersHeader.addEventListener('change', (e) => {
                this.toggleAllUsers(e.target.checked);
            });
        }
    }

    initEventListeners() {
        // Search and filter inputs
        const userSearch = document.getElementById('userSearch');
        if (userSearch) {
            userSearch.addEventListener('input', this.debounce(() => {
                this.filterUsers();
            }, 300));
        }
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

    toggleRoleFields(role, mode) {
        const prefix = mode === 'create' ? '' : 'edit';
        const studentFields = document.getElementById(`${prefix}StudentFields`);
        const adviserFields = document.getElementById(`${prefix}AdviserFields`);

        // Hide all role-specific fields
        if (studentFields) studentFields.style.display = 'none';
        if (adviserFields) adviserFields.style.display = 'none';

        // Show relevant fields based on role
        if (role === 'student' && studentFields) {
            studentFields.style.display = 'grid';
        } else if (role === 'adviser' && adviserFields) {
            adviserFields.style.display = 'grid';
        }
    }

    async loadDashboardData() {
        try {
            console.log('Loading dashboard data...');
            
            // Load users for stats
            await this.loadUsers();
            
            // Update user stats
            this.updateUserStats();
            
            // Initialize charts if on overview tab
            if (document.querySelector('.nav-item.active')?.getAttribute('data-tab') === 'overview') {
                this.initializeCharts();
            }
            
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            this.showNotification('Error loading dashboard data', 'error');
        }
    }

    async loadUsers() {
        const usersLoading = document.getElementById('usersLoading');
        const usersTableBody = document.getElementById('usersTableBody');
        
        try {
            console.log('Loading users...');
            
            if (usersLoading) usersLoading.classList.remove('hidden');
            
            const response = await fetch('api/admin_users.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Users loaded:', data);

            if (data.success) {
                this.allUsers = data.users || [];
                this.filteredUsers = [...this.allUsers];
                this.renderUsers();
                this.updateUserStats();
            } else {
                throw new Error(data.message || 'Failed to load users');
            }

        } catch (error) {
            console.error('Error loading users:', error);
            this.showNotification('Error loading users: ' + error.message, 'error');
            
            if (usersTableBody) {
                usersTableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-8">
                            <i data-lucide="alert-circle" class="w-12 h-12 text-red-400 mx-auto mb-4"></i>
                            <p class="text-red-500">Error loading users</p>
                            <button onclick="adminDashboard.loadUsers()" class="btn btn-primary btn-sm mt-4">
                                Try Again
                            </button>
                        </td>
                    </tr>
                `;
            }
        } finally {
            if (usersLoading) usersLoading.classList.add('hidden');
        }
    }

    renderUsers() {
        const usersTableBody = document.getElementById('usersTableBody');
        const userCount = document.getElementById('userCount');
        
        if (!usersTableBody) return;

        if (this.filteredUsers.length === 0) {
            usersTableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-8">
                        <i data-lucide="users" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                        <p class="text-gray-500">No users found</p>
                        <button onclick="adminDashboard.showCreateUserModal()" class="btn btn-primary btn-sm mt-4">
                            Add First User
                        </button>
                    </td>
                </tr>
            `;
            return;
        }

        const startIndex = (this.currentUsersPage - 1) * this.usersPerPage;
        const endIndex = startIndex + this.usersPerPage;
        const usersToShow = this.filteredUsers.slice(startIndex, endIndex);

        usersTableBody.innerHTML = usersToShow.map(user => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td>
                    <input type="checkbox" 
                           class="user-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                           value="${user.id}" 
                           onchange="adminDashboard.toggleUserSelection(${user.id}, this.checked)">
                </td>
                <td>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-semibold mr-3">
                            ${(user.full_name || user.name || 'U').charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">${this.escapeHtml(user.full_name || user.name || 'Unknown')}</p>
                            <p class="text-sm text-gray-500">${this.escapeHtml(user.email || '')}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-${this.getRoleBadgeClass(user.role)}">
                        ${this.formatRole(user.role)}
                    </span>
                </td>
                <td>
                    <div>
                        <p class="font-medium text-gray-900">${this.escapeHtml(user.department || '-')}</p>
                        <p class="text-sm text-gray-500">${this.escapeHtml(user.program || '')}</p>
                    </div>
                </td>
                <td>
                    <span class="font-mono text-sm">${this.escapeHtml(user.student_id || user.faculty_id || '-')}</span>
                </td>
                <td>
                    <span class="badge badge-${user.is_active ? 'success' : 'danger'}">
                        ${user.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <span class="text-sm text-gray-600">
                        ${user.last_login ? this.formatDate(user.last_login) : 'Never'}
                    </span>
                </td>
                <td>
                    <div class="flex space-x-1">
                        <button onclick="adminDashboard.editUser(${user.id})" 
                                class="btn btn-warning btn-sm" 
                                title="Edit User">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button onclick="adminDashboard.resetUserPassword(${user.id})" 
                                class="btn btn-info btn-sm" 
                                title="Reset Password">
                            <i data-lucide="key" class="w-4 h-4"></i>
                        </button>
                        <button onclick="adminDashboard.deleteUser(${user.id})" 
                                class="btn btn-danger btn-sm" 
                                title="Delete User">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Update count
        if (userCount) {
            userCount.textContent = this.filteredUsers.length;
        }

        // Re-initialize Lucide icons
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    updateUserStats() {
        const students = this.allUsers.filter(u => u.role === 'student');
        const advisers = this.allUsers.filter(u => u.role === 'adviser');
        const admins = this.allUsers.filter(u => u.role === 'admin' || u.role === 'super_admin');
        const activeToday = this.allUsers.filter(u => {
            if (!u.last_login) return false;
            const today = new Date().toDateString();
            const loginDate = new Date(u.last_login).toDateString();
            return loginDate === today;
        });

        this.updateStat('totalStudents', students.length);
        this.updateStat('totalAdvisers', advisers.length);
        this.updateStat('totalAdmins', admins.length);
        this.updateStat('activeToday', activeToday.length);
    }

    updateStat(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = value;
        }
    }

    getRoleBadgeClass(role) {
        const classes = {
            'student': 'primary',
            'adviser': 'success',
            'admin': 'warning',
            'super_admin': 'danger'
        };
        return classes[role] || 'secondary';
    }

    formatRole(role) {
        if (!role) return 'Unknown';
        if (role === 'student') return 'Student';
        if (role === 'adviser') return 'Adviser';
        if (role === 'admin') return 'Admin';
        return role.charAt(0).toUpperCase() + role.slice(1);
    }

    formatDate(dateString) {
        if (!dateString) return 'Never';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    filterUsers() {
        const searchTerm = document.getElementById('userSearch')?.value.toLowerCase() || '';
        const roleFilter = document.getElementById('roleFilter')?.value || '';
        const departmentFilter = document.getElementById('departmentFilter')?.value || '';
        const programFilter = document.getElementById('programFilter')?.value || '';

        this.filteredUsers = this.allUsers.filter(user => {
            const matchesSearch = !searchTerm || 
                (user.full_name || user.name || '').toLowerCase().includes(searchTerm) ||
                (user.email || '').toLowerCase().includes(searchTerm) ||
                (user.student_id || '').toLowerCase().includes(searchTerm) ||
                (user.faculty_id || '').toLowerCase().includes(searchTerm);

            const matchesRole = !roleFilter || user.role === roleFilter;
            const matchesDepartment = !departmentFilter || user.department === departmentFilter;
            const matchesProgram = !programFilter || user.program === programFilter;

            return matchesSearch && matchesRole && matchesDepartment && matchesProgram;
        });

        this.currentUsersPage = 1;
        this.renderUsers();
    }

    clearUserFilters() {
        // Clear all filter inputs
        const inputs = ['userSearch', 'roleFilter', 'departmentFilter', 'programFilter'];
        inputs.forEach(id => {
            const element = document.getElementById(id);
            if (element) element.value = '';
        });

        // Reset filtered users and re-render
        this.filteredUsers = [...this.allUsers];
        this.currentUsersPage = 1;
        this.renderUsers();
    }

    toggleUserSelection(userId, checked) {
        // Convert to number to ensure consistency
        const numericUserId = parseInt(userId);
        if (checked) {
            this.selectedUsers.add(numericUserId);
        } else {
            this.selectedUsers.delete(numericUserId);
        }

        this.updateBulkActionsPanel();
        this.updateSelectAllCheckboxes();
    }

    toggleAllUsers(checked) {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
            const userId = parseInt(checkbox.value);
            if (checked) {
                this.selectedUsers.add(userId);
            } else {
                this.selectedUsers.delete(userId);
            }
        });

        this.updateBulkActionsPanel();
    }

    updateBulkActionsPanel() {
        const panel = document.getElementById('bulkActionsPanel');
        const selectedCount = document.getElementById('selectedCount');

        if (panel && selectedCount) {
            if (this.selectedUsers.size > 0) {
                panel.style.display = 'block';
                selectedCount.textContent = this.selectedUsers.size;
            } else {
                panel.style.display = 'none';
            }
        }
    }

    updateSelectAllCheckboxes() {
        const selectAllUsers = document.getElementById('selectAllUsers');
        const selectAllUsersHeader = document.getElementById('selectAllUsersHeader');
        const checkboxes = document.querySelectorAll('.user-checkbox');
        
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        const someChecked = Array.from(checkboxes).some(cb => cb.checked);

        [selectAllUsers, selectAllUsersHeader].forEach(checkbox => {
            if (checkbox) {
                checkbox.checked = allChecked;
                checkbox.indeterminate = someChecked && !allChecked;
            }
        });
    }

    clearSelection() {
        this.selectedUsers.clear();
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = false);
        this.updateBulkActionsPanel();
        this.updateSelectAllCheckboxes();
    }

    // Modal Management
    showCreateUserModal() {
        const modal = document.getElementById('createUserModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Reset form
            const form = document.getElementById('createUserForm');
            if (form) {
                form.reset();
                this.toggleRoleFields('', 'create');
            }
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    }

    async createUser() {
        try {
            const form = document.getElementById('createUserForm');
            const formData = new FormData(form);
            
            // Convert to JSON
            const userData = {};
            formData.forEach((value, key) => {
                userData[key] = value;
            });

            console.log('Creating user:', userData);

            const response = await fetch('api/admin_users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'create',
                    ...userData
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('User created successfully!', 'success');
                this.closeModal('createUserModal');
                
                // Show password if generated
                if (data.password) {
                    this.showPasswordModal(userData.full_name, userData.email, data.password);
                }
                
                // Reload users
                await this.loadUsers();
            } else {
                throw new Error(data.message || 'Failed to create user');
            }

        } catch (error) {
            console.error('Error creating user:', error);
            this.showNotification('Error creating user: ' + error.message, 'error');
        }
    }

    async editUser(userId) {
        try {
            // Find user data - convert to number to ensure proper comparison
            const numericUserId = parseInt(userId);
            const user = this.allUsers.find(u => parseInt(u.id) === numericUserId);
            if (!user) {
                throw new Error('User not found');
            }

            // Populate edit form
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editFullName').value = user.full_name || user.name || '';
            document.getElementById('editEmail').value = user.email || '';
            document.getElementById('editUserRole').value = user.role || '';

            // Populate role-specific fields
            if (user.role === 'student') {
                document.getElementById('editStudentId').value = user.student_id || '';
                document.getElementById('editProgram').value = user.program || '';
                document.getElementById('editDepartment').value = user.department || '';
            } else if (user.role === 'adviser') {
                document.getElementById('editFacultyId').value = user.faculty_id || '';
                document.getElementById('editAdviserDepartment').value = user.department || '';
            }

            // Show appropriate fields
            this.toggleRoleFields(user.role, 'edit');

            // Show modal
            const modal = document.getElementById('editUserModal');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

        } catch (error) {
            console.error('Error loading user for edit:', error);
            this.showNotification('Error loading user data', 'error');
        }
    }

    async updateUser() {
        try {
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);
            
            // Convert to JSON
            const userData = {};
            formData.forEach((value, key) => {
                userData[key] = value;
            });

            console.log('Updating user:', userData);

            const response = await fetch('api/admin_users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update',
                    ...userData
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('User updated successfully!', 'success');
                this.closeModal('editUserModal');
                
                // Reload users and admin logs
                await this.loadUsers();
                this.loadAdminLogs();
            } else {
                throw new Error(data.message || 'Failed to update user');
            }

        } catch (error) {
            console.error('Error updating user:', error);
            this.showNotification('Error updating user: ' + error.message, 'error');
        }
    }

    deleteUser(userId) {
        // Find user data - convert to number to ensure proper comparison
        const numericUserId = parseInt(userId);
        const user = this.allUsers.find(u => parseInt(u.id) === numericUserId);
        if (!user) {
            this.showNotification('User not found', 'error');
            return;
        }

        // Set current user for deletion
        this.currentUserForDelete = userId;

        // Populate modal
        document.getElementById('deleteUserName').textContent = user.full_name || user.name || 'Unknown';
        document.getElementById('deleteUserEmail').textContent = user.email || '';

        // Show modal
        const modal = document.getElementById('deleteUserModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    async confirmDeleteUser() {
        if (!this.currentUserForDelete) return;

        try {
            console.log('Deleting user:', this.currentUserForDelete);

            const response = await fetch('api/admin_users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    user_id: this.currentUserForDelete
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('User deleted successfully!', 'success');
                this.closeModal('deleteUserModal');
                this.currentUserForDelete = null;
                
                // Reload users and admin logs
                await this.loadUsers();
                this.loadAdminLogs();
            } else {
                throw new Error(data.message || 'Failed to delete user');
            }

        } catch (error) {
            console.error('Error deleting user:', error);
            this.showNotification('Error deleting user: ' + error.message, 'error');
        }
    }

    async resetUserPassword(userId) {
        try {
            // Convert userId to number to ensure proper comparison
            const numericUserId = parseInt(userId);
            const user = this.allUsers.find(u => parseInt(u.id) === numericUserId);
            
            if (!user) {
                throw new Error('User not found');
            }

            const confirmed = confirm(`Are you sure you want to reset the password for ${user.full_name || user.name || 'this user'}?`);
            if (!confirmed) return;

            console.log('Resetting password for user:', userId);

            const response = await fetch('api/admin_users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'reset_password',
                    user_id: userId
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Password reset successfully!', 'success');
                
                // Show new password and refresh admin logs
                if (data.password) {
                    this.showPasswordModal(user.full_name || user.name, user.email, data.password);
                }
                this.loadAdminLogs();
            } else {
                throw new Error(data.message || 'Failed to reset password');
            }

        } catch (error) {
            console.error('Error resetting password:', error);
            this.showNotification('Error resetting password: ' + error.message, 'error');
        }
    }

    async bulkResetPasswords() {
        if (this.selectedUsers.size === 0) {
            this.showNotification('Please select users first', 'warning');
            return;
        }

        const confirmed = confirm(`Are you sure you want to reset passwords for ${this.selectedUsers.size} selected user(s)?`);
        if (!confirmed) return;

        try {
            console.log('Bulk resetting passwords for users:', Array.from(this.selectedUsers));

            const response = await fetch('api/admin_users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'bulk_reset_passwords',
                    user_ids: Array.from(this.selectedUsers)
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification(`Passwords reset for ${data.count} user(s)!`, 'success');
                this.clearSelection();
                
                // Show passwords if provided and refresh admin logs
                if (data.passwords && data.passwords.length > 0) {
                    this.showBulkPasswordsModal(data.passwords);
                }
                this.loadAdminLogs();
            } else {
                throw new Error(data.message || 'Failed to reset passwords');
            }

        } catch (error) {
            console.error('Error bulk resetting passwords:', error);
            this.showNotification('Error resetting passwords: ' + error.message, 'error');
        }
    }

    async bulkDeleteUsers() {
        if (this.selectedUsers.size === 0) {
            this.showNotification('Please select users first', 'warning');
            return;
        }

        const confirmed = confirm(`Are you sure you want to DELETE ${this.selectedUsers.size} selected user(s)? This action cannot be undone!`);
        if (!confirmed) return;

        try {
            console.log('Bulk deleting users:', Array.from(this.selectedUsers));

            const response = await fetch('api/admin_users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'bulk_delete',
                    user_ids: Array.from(this.selectedUsers)
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification(`${data.count} user(s) deleted successfully!`, 'success');
                this.clearSelection();
                
                // Reload users and admin logs
                await this.loadUsers();
                this.loadAdminLogs();
            } else {
                throw new Error(data.message || 'Failed to delete users');
            }

        } catch (error) {
            console.error('Error bulk deleting users:', error);
            this.showNotification('Error deleting users: ' + error.message, 'error');
        }
    }

    showPasswordModal(userName, userEmail, password) {
        document.getElementById('passwordUserName').textContent = userName || 'Unknown';
        document.getElementById('passwordUserEmail').textContent = userEmail || '';
        document.getElementById('generatedPassword').textContent = password;

        const modal = document.getElementById('passwordModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    copyPassword() {
        const passwordElement = document.getElementById('generatedPassword');
        if (passwordElement) {
            navigator.clipboard.writeText(passwordElement.textContent).then(() => {
                this.showNotification('Password copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Error copying password:', err);
                this.showNotification('Failed to copy password', 'error');
            });
        }
    }

    showNotification(message, type = 'info') {
        console.log(`${type.toUpperCase()}: ${message}`);
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${this.getNotificationClasses(type)}`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i data-lucide="${this.getNotificationIcon(type)}" class="w-5 h-5 mr-3"></i>
                <span>${this.escapeHtml(message)}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Re-initialize Lucide icons
        if (window.lucide) {
            window.lucide.createIcons();
        }

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    getNotificationClasses(type) {
        const classes = {
            'success': 'bg-green-500 text-white',
            'error': 'bg-red-500 text-white',
            'warning': 'bg-yellow-500 text-white',
            'info': 'bg-blue-500 text-white'
        };
        return classes[type] || classes.info;
    }

    getNotificationIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'alert-circle',
            'warning': 'alert-triangle',
            'info': 'info'
        };
        return icons[type] || icons.info;
    }

    // Login Logs functionality
    async loadLoginLogs() {
        const logsTableBody = document.getElementById('loginLogsTableBody');
        if (logsTableBody) logsTableBody.innerHTML = '';
        try {
            console.log('Loading login logs...');
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_login_logs&limit=50'
            });
            const data = await response.json();
            console.log('Login logs loaded:', data);
            if (data.success) {
                this.renderLoginLogs(data.logs);
                // Force-populate Recent Admin Activity with the same logs
                this.renderRecentAdminActivity(data.logs);
            } else {
                this.renderLoginLogs([]);
                this.renderRecentAdminActivity([]);
            }
        } catch (error) {
            console.error('Error loading login logs:', error);
            this.renderLoginLogs([]);
            this.renderRecentAdminActivity([]);
        }
    }

    renderLoginLogs(logs) {
        console.log('Rendering logs:', logs);
        const logsTableBody = document.getElementById('loginLogsTableBody');
        if (!logsTableBody) return;
        // DEBUG: Test row removed, now render real logs
        if (logs.length === 0) {
            logsTableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-8">
                        <i data-lucide="file-text" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                        <p class="text-gray-500">No login logs found</p>
                    </td>
                </tr>
            `;
            return;
        }
        logsTableBody.innerHTML = logs.map(log => `
            <tr>
                <td>
                    <div class="flex items-center">
                        <div class="user-avatar">
                            ${log.full_name ? log.full_name.charAt(0).toUpperCase() : '?'}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">${this.escapeHtml(log.full_name || 'Unknown')}</p>
                            <p class="text-sm text-gray-500">${this.escapeHtml(log.email || '')}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge ${this.getRoleBadgeClass(log.user_role)}">
                        ${this.formatRole(log.user_role || 'unknown')}
                    </span>
                </td>
                <td>
                    <span class="badge ${log.action_type === 'login' ? 'badge-success' : log.action_type === 'logout' ? 'badge-warning' : log.action_type === 'login_failed' ? 'badge-danger' : 'badge-secondary'}">
                        ${log.action_type === 'login' ? 'Login' : log.action_type === 'logout' ? 'Logout' : log.action_type === 'login_failed' ? 'Failed Login' : (log.action_type || 'Unknown')}
                    </span>
                </td>
                <td>
                    <div>
                        <p class="text-sm text-gray-900">${this.escapeHtml(log.ip_address || 'Unknown')}</p>
                    </div>
                </td>
                <td>
                    <span class="text-sm text-gray-900">${this.escapeHtml(log.browser_info || log.user_agent || 'Unknown')}</span>
                </td>
                <td>
                    <span class="text-sm text-gray-900">${log.login_time ? this.formatDate(log.login_time) : '-'}</span>
                </td>
                <td>
                    <span class="text-sm text-gray-900">${log.logout_time ? this.formatDate(log.logout_time) : '-'}</span>
                </td>
                <td>
                    <span class="text-sm text-gray-900">${log.formatted_duration || '-'}</span>
                </td>
            </tr>
        `).join('');
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    filterLoginLogs() {
        // Get filter values from the UI
        const userRole = document.getElementById('loginLogRoleFilter').value;
        const actionType = document.getElementById('loginLogActionFilter').value;
        const dateFrom = document.getElementById('loginLogDateFrom').value;
        const dateTo = document.getElementById('loginLogDateTo').value;
        const userSearch = document.getElementById('loginLogUserSearch').value;

        // Build form data for the AJAX request
        const params = new URLSearchParams();
        params.append('action', 'get_login_logs');
        params.append('user_role', userRole);
        params.append('action_type', actionType);
        params.append('date_from', dateFrom);
        params.append('date_to', dateTo);
        params.append('user_search', userSearch);
        params.append('limit', 100);

        // Show loading spinner
        const logsLoading = document.getElementById('loginLogsLoading');
        if (logsLoading) logsLoading.classList.remove('hidden');

        fetch('admin_dashboard.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.renderLoginLogs(data.logs || []);
            } else {
                throw new Error(data.error || 'Failed to load login logs');
            }
        })
        .catch(error => {
            this.showNotification('Error loading login logs: ' + error.message, 'error');
        })
        .finally(() => {
            if (logsLoading) logsLoading.classList.add('hidden');
        });
    }

    clearLoginLogFilters() {
        document.getElementById('loginLogRoleFilter').value = '';
        document.getElementById('loginLogActionFilter').value = '';
        document.getElementById('loginLogDateFrom').value = '';
        document.getElementById('loginLogDateTo').value = '';
        document.getElementById('loginLogUserSearch').value = '';
        this.loadLoginLogs();
    }

    // Analytics functionality
    async loadAnalytics() {
        console.log('Loading analytics data...');
        
        try {
            // Initialize charts - this will automatically load adviser workload if on analytics tab
            console.log('Initializing charts from loadAnalytics');
            this.initializeCharts();
            
            console.log('Analytics data loaded successfully');
            this.showNotification('Analytics data loaded', 'info');
        } catch (error) {
            console.error('Error loading analytics:', error);
            this.showNotification('Error loading analytics: ' + error.message, 'error');
        }
    }

    waitForChartJSAndInitialize() {
        // Check if Chart.js is loaded
        if (typeof Chart !== 'undefined') {
            console.log('Chart.js is available, initializing charts...');
            // Ensure DOM is ready and visible
            if (document.readyState === 'complete') {
                setTimeout(() => {
                    this.initializeCharts();
                    this.loadAdviserWorkload();
                }, 100);
            } else {
                setTimeout(() => {
                    this.initializeCharts();
                    this.loadAdviserWorkload();
                }, 1000);
            }
        } else {
            console.log('Chart.js not yet available, waiting...');
            setTimeout(() => {
                this.waitForChartJSAndInitialize();
            }, 100);
        }
    }

    initializeCharts() {
        console.log('Initializing charts...');
        console.log('Department data:', window.departmentData);
        console.log('Activity data:', window.activityData);
        console.log('Chart.js available:', typeof Chart !== 'undefined');
        
        // Check which tab is currently active
        const overviewTab = document.getElementById('overview-tab');
        const analyticsTab = document.getElementById('analytics-tab');
        
        // Destroy existing charts if they exist
        this.destroyExistingCharts();
        
        // Initialize charts based on active tab
        if (overviewTab && !overviewTab.classList.contains('hidden')) {
            console.log('Overview tab is active, initializing overview charts...');
            // Initialize Department Performance Chart
            this.initDepartmentChart();
            // Initialize Monthly Activity Chart
            this.initActivityChart();
        }
        
        if (analyticsTab && !analyticsTab.classList.contains('hidden')) {
            console.log('Analytics tab is active, initializing analytics charts...');
            // Initialize adviser workload chart
            this.loadAdviserWorkload();
        }
    }

    destroyExistingCharts() {
        // Destroy existing charts to prevent conflicts
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
        if (window.adviserWorkloadChartInstance) {
            console.log('Destroying existing adviser workload chart');
            window.adviserWorkloadChartInstance.destroy();
            window.adviserWorkloadChartInstance = null;
        }
    }

    initDepartmentChart() {
        console.log('Initializing department chart...');
        const ctx = document.getElementById('departmentChart');
        if (!ctx) {
            console.error('Department chart canvas not found!');
            return;
        }
        console.log('Department chart canvas found:', ctx);

        // Check if canvas is visible and has dimensions
        const canvasRect = ctx.getBoundingClientRect();
        console.log('Canvas dimensions:', canvasRect);
        
        if (canvasRect.width === 0 || canvasRect.height === 0) {
            console.warn('Canvas has zero dimensions, retrying in 500ms...');
            setTimeout(() => this.initDepartmentChart(), 500);
            return;
        }

        const departmentData = window.departmentData || [];
        console.log('Department data for chart:', departmentData);

        const labels = departmentData.map(dept => dept.department || 'No Department');
        const studentCounts = departmentData.map(dept => parseInt(dept.student_count));
        const avgProgress = departmentData.map(dept => parseFloat(dept.avg_progress));

                    try {
                console.log('Creating department chart with data:', { labels, studentCounts, avgProgress });
                
                window.departmentChartInstance = new Chart(ctx, {
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
                        },
                        legend: {
                            display: true,
                            position: 'top'
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

    initActivityChart() {
        console.log('Initializing activity chart...');
        const ctx = document.getElementById('activityChart');
        if (!ctx) {
            console.error('Activity chart canvas not found!');
            return;
        }
        console.log('Activity chart canvas found:', ctx);

        // Check if canvas is visible and has dimensions
        const canvasRect = ctx.getBoundingClientRect();
        console.log('Activity canvas dimensions:', canvasRect);
        
        if (canvasRect.width === 0 || canvasRect.height === 0) {
            console.warn('Activity canvas has zero dimensions, retrying in 500ms...');
            setTimeout(() => this.initActivityChart(), 500);
            return;
        }

        const activityData = window.activityData || [];
        console.log('Activity data for chart:', activityData);

        this.renderActivityChart(ctx, activityData);
    }

    renderActivityChart(ctx, data) {
        const labels = data.map(item => item.month_name || 'Unknown');
        const activityCounts = data.map(item => parseInt(item.activity_count) || 0);

        try {
            console.log('Creating activity chart with data:', { labels, activityCounts });
            
            window.activityChartInstance = new Chart(ctx, {
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
                    },
                    legend: {
                        display: true,
                        position: 'top'
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

    async loadAdviserWorkload() {
        console.log('loadAdviserWorkload called');
        try {
            console.log('Making API call to get adviser workload');
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=get_adviser_workload'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Adviser workload loaded:', data);

            if (data && Array.isArray(data)) {
                console.log('Rendering adviser workload chart with data:', data);
                this.renderAdviserWorkloadChart(data);
            } else {
                console.log('No valid data received, rendering with fallback data');
                this.renderAdviserWorkloadChart([]);
            }

        } catch (error) {
            console.error('Error loading adviser workload:', error);
            // Show error in the chart container
            const chartContainer = document.getElementById('adviserWorkloadChart');
            if (chartContainer) {
                chartContainer.parentElement.innerHTML = `
                    <div class="text-center py-8">
                        <i data-lucide="alert-circle" class="w-12 h-12 text-red-400 mx-auto mb-4"></i>
                        <p class="text-red-500">Error loading adviser workload</p>
                    </div>
                `;
                if (window.lucide) window.lucide.createIcons();
            }
        }
    }

    renderAdviserWorkloadChart(data) {
        console.log('renderAdviserWorkloadChart called with data:', data);
        const ctx = document.getElementById('adviserWorkloadChart');
        if (!ctx) {
            console.error('adviserWorkloadChart canvas not found');
            return;
        }
        console.log('Adviser workload chart canvas found:', ctx);

        // Check if canvas is visible and has dimensions
        const canvasRect = ctx.getBoundingClientRect();
        console.log('Adviser workload canvas dimensions:', canvasRect);
        
        if (canvasRect.width === 0 || canvasRect.height === 0) {
            console.warn('Adviser workload canvas has zero dimensions, retrying in 500ms...');
            setTimeout(() => this.renderAdviserWorkloadChart(data), 500);
            return;
        }

        // Destroy existing chart if it exists
        if (window.adviserWorkloadChartInstance) {
            console.log('Destroying existing adviser workload chart');
            window.adviserWorkloadChartInstance.destroy();
            window.adviserWorkloadChartInstance = null;
        }

        // If no data, create sample data for demonstration
        if (data.length === 0) {
            data = [
                {adviser_name: 'Dr. John Smith', supervised_theses: 8},
                {adviser_name: 'Dr. Sarah Johnson', supervised_theses: 6},
                {adviser_name: 'Dr. Michael Brown', supervised_theses: 5},
                {adviser_name: 'Dr. Emily Davis', supervised_theses: 4},
                {adviser_name: 'Dr. David Wilson', supervised_theses: 3}
            ];
        }

        const labels = data.map(adviser => adviser.adviser_name || 'Unknown');
        const workloadData = data.map(adviser => parseInt(adviser.supervised_theses) || 0);

        try {
            console.log('Creating adviser workload chart with data:', { labels, workloadData });
            
            window.adviserWorkloadChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: workloadData,
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(168, 85, 247, 0.8)',
                            'rgba(236, 72, 153, 0.8)',
                            'rgba(14, 165, 233, 0.8)',
                            'rgba(132, 204, 22, 0.8)',
                            'rgba(251, 146, 60, 0.8)',
                            'rgba(156, 163, 175, 0.8)'
                        ],
                        borderColor: [
                            'rgb(239, 68, 68)',
                            'rgb(245, 158, 11)',
                            'rgb(34, 197, 94)',
                            'rgb(59, 130, 246)',
                            'rgb(168, 85, 247)',
                            'rgb(236, 72, 153)',
                            'rgb(14, 165, 233)',
                            'rgb(132, 204, 22)',
                            'rgb(251, 146, 60)',
                            'rgb(156, 163, 175)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Adviser Workload Distribution'
                        },
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return `${label}: ${value} ${value === 1 ? 'student' : 'students'}`;
                                }
                            }
                        }
                    }
                }
            });
            
            console.log('Adviser workload chart created successfully');
        } catch (error) {
            console.error('Error creating adviser workload chart:', error);
        }
    }

    // Manual chart refresh function for debugging
    refreshCharts() {
        console.log('Manual chart refresh triggered');
        this.destroyExistingCharts();
        setTimeout(() => {
            this.initializeCharts();
        }, 100);
    }

    // Debug function to test adviser workload chart specifically
    debugAdviserWorkloadChart() {
        console.log('Debug: Testing adviser workload chart...');
        console.log('Chart.js available:', typeof Chart !== 'undefined');
        
        const ctx = document.getElementById('adviserWorkloadChart');
        console.log('Canvas element:', ctx);
        
        if (ctx) {
            const rect = ctx.getBoundingClientRect();
            console.log('Canvas dimensions:', rect);
            
            // Force render with sample data
            const sampleData = [
                {adviser_name: 'Dr. John Smith', supervised_theses: 8},
                {adviser_name: 'Dr. Sarah Johnson', supervised_theses: 6},
                {adviser_name: 'Dr. Michael Brown', supervised_theses: 5}
            ];
            
            console.log('Forcing render with sample data:', sampleData);
            this.renderAdviserWorkloadChart(sampleData);
        } else {
            console.error('Canvas element not found!');
        }
    }

    // Admin logs functionality
    async loadAdminLogs() {
        try {
            console.log('Loading admin logs...');
            
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_admin_logs'
            });

            const data = await response.json();
            
            if (data.success) {
                this.renderAdminLogs(data.logs);
            } else {
                throw new Error(data.message || 'Failed to load admin logs');
            }
            
        } catch (error) {
            console.error('Error loading admin logs:', error);
            this.showNotification('Error loading admin logs: ' + error.message, 'error');
        }
    }

    renderAdminLogs(logs) {
        const adminLogsTableBody = document.querySelector('#admin-logs-content tbody');
        if (!adminLogsTableBody) {
            console.error('Admin logs table body not found');
            return;
        }

        if (!logs || logs.length === 0) {
            adminLogsTableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-8">
                        <i data-lucide="file-text" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                        <p class="text-gray-500">No activity logs available</p>
                    </td>
                </tr>
            `;
            return;
        }

        adminLogsTableBody.innerHTML = logs.map(log => `
            <tr class="slide-in">
                <td>
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-semibold mr-3">
                            ${log.admin_name ? log.admin_name.charAt(0).toUpperCase() : 'A'}
                        </div>
                        <span class="font-medium">${this.escapeHtml(log.admin_name || 'Unknown')}</span>
                    </div>
                </td>
                <td>${this.escapeHtml(log.action)}</td>
                <td>
                    <span class="badge badge-info">${this.escapeHtml(log.target_type)}</span>
                </td>
                <td>${this.formatDate(log.created_at)}</td>
            </tr>
        `).join('');

        // Re-initialize Lucide icons for new content
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    async loadRecentAdminActivity() {
        try {
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_admin_logs&limit=10'
            });
            const data = await response.json();
            console.log('Recent Admin Activity AJAX response:', data); // DEBUG
            if (data.success) {
                this.renderRecentAdminActivity(data.logs || []);
            } else {
                this.renderRecentAdminActivity([]);
            }
        } catch (error) {
            console.error('Error in loadRecentAdminActivity:', error); // DEBUG
            this.renderRecentAdminActivity([]);
        }
    }

    renderRecentAdminActivity(logs) {
        console.log('Rendering recent admin activity logs:', logs); // DEBUG
        const container = document.getElementById('recentAdminActivity');
        if (!container) return;
        if (!logs.length) {
            container.innerHTML = '<div class="text-gray-500">No recent activity</div>';
            return;
        }
        container.innerHTML = logs.map(log => `
            <div class="flex items-center space-x-4 py-3 border-b border-gray-100 last:border-b-0">
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                    ${log.full_name ? log.full_name.charAt(0).toUpperCase() : 'U'}
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-900">
                        <span class="font-medium">${this.escapeHtml(log.full_name || 'Unknown')}</span>
                        <span class="text-gray-600">${this.escapeHtml(log.action_type || log.action || 'Action')}</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">${this.formatDate(log.created_at || log.login_time)}</p>
                </div>
            </div>
        `).join('');
    }

    // Announcements functionality
    async loadAnnouncements() {
        console.log('Loading announcements...');
        // Basic implementation - can be expanded later
        this.showNotification('Announcements tab loaded', 'info');
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminDashboard = new AdminDashboard();
    
    // Add global chart refresh function for debugging
    window.refreshCharts = () => {
        if (window.adminDashboard) {
            window.adminDashboard.refreshCharts();
        } else {
            console.error('Admin dashboard not initialized');
        }
    };
    
    // Add debug function for adviser workload chart
    window.debugAdviserChart = () => {
        if (window.adminDashboard) {
            window.adminDashboard.debugAdviserWorkloadChart();
        } else {
            console.error('Admin dashboard not initialized');
        }
    };
    
    // Initialize mobile menu
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    if (mobileMenuToggle && sidebar && sidebarOverlay) {
        mobileMenuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('translate-x-0');
            sidebarOverlay.classList.toggle('hidden');
        });
        
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('translate-x-0');
            sidebarOverlay.classList.add('hidden');
        });
    }
    
    // Initialize Lucide icons
    if (window.lucide) {
        window.lucide.createIcons();
    }
    
    // Additional chart initialization after everything is loaded
    setTimeout(() => {
        if (window.adminDashboard && typeof Chart !== 'undefined') {
            console.log('Secondary chart initialization attempt...');
            window.adminDashboard.refreshCharts();
        }
    }, 2000);
}); 