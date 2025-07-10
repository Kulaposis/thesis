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
                    } else if (targetTab === 'analytics') {
                        this.loadAnalytics();
                    } else if (targetTab === 'announcements') {
                        this.loadAnnouncements();
                    }
                }
            });
        });
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
        const roles = {
            'student': 'Student',
            'adviser': 'Adviser',
            'admin': 'Admin',
            'super_admin': 'Super Admin'
        };
        return roles[role] || role;
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
        if (checked) {
            this.selectedUsers.add(userId);
        } else {
            this.selectedUsers.delete(userId);
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
            // Find user data
            const user = this.allUsers.find(u => u.id === userId);
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
                
                // Reload users
                await this.loadUsers();
            } else {
                throw new Error(data.message || 'Failed to update user');
            }

        } catch (error) {
            console.error('Error updating user:', error);
            this.showNotification('Error updating user: ' + error.message, 'error');
        }
    }

    deleteUser(userId) {
        // Find user data
        const user = this.allUsers.find(u => u.id === userId);
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
                
                // Reload users
                await this.loadUsers();
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
            const user = this.allUsers.find(u => u.id === userId);
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
                
                // Show new password
                if (data.password) {
                    this.showPasswordModal(user.full_name || user.name, user.email, data.password);
                }
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
                
                // Show passwords if provided
                if (data.passwords && data.passwords.length > 0) {
                    this.showBulkPasswordsModal(data.passwords);
                }
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
                
                // Reload users
                await this.loadUsers();
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
        const logsLoading = document.getElementById('logsLoading');
        const logsTableBody = document.getElementById('logsTableBody');
        
        try {
            console.log('Loading login logs...');
            
            if (logsLoading) logsLoading.classList.remove('hidden');
            
            const response = await fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=get_login_logs&limit=50'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Login logs loaded:', data);

            if (data.success) {
                this.renderLoginLogs(data.logs || []);
            } else {
                throw new Error(data.error || 'Failed to load login logs');
            }

        } catch (error) {
            console.error('Error loading login logs:', error);
            this.showNotification('Error loading login logs: ' + error.message, 'error');
            
            if (logsTableBody) {
                logsTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-8">
                            <i data-lucide="alert-circle" class="w-12 h-12 text-red-400 mx-auto mb-4"></i>
                            <p class="text-red-500">Error loading login logs</p>
                            <button onclick="adminDashboard.loadLoginLogs()" class="btn btn-primary btn-sm mt-4">
                                Try Again
                            </button>
                        </td>
                    </tr>
                `;
            }
        } finally {
            if (logsLoading) logsLoading.classList.add('hidden');
        }
    }

    renderLoginLogs(logs) {
        const logsTableBody = document.getElementById('logsTableBody');
        
        if (!logsTableBody) return;

        if (logs.length === 0) {
            logsTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-8">
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
                            ${log.user_name ? log.user_name.charAt(0).toUpperCase() : '?'}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">${this.escapeHtml(log.user_name || 'Unknown')}</p>
                            <p class="text-sm text-gray-500">${this.escapeHtml(log.user_email || '')}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge ${this.getRoleBadgeClass(log.user_role)}">
                        ${this.formatRole(log.user_role)}
                    </span>
                </td>
                <td>
                    <span class="badge ${log.action_type === 'login' ? 'badge-success' : 'badge-warning'}">
                        ${log.action_type === 'login' ? 'Login' : 'Logout'}
                    </span>
                </td>
                <td>
                    <div>
                        <p class="text-sm text-gray-900">${this.escapeHtml(log.ip_address || 'Unknown')}</p>
                        <p class="text-xs text-gray-500">${this.escapeHtml(log.user_agent || '').substring(0, 50)}${log.user_agent && log.user_agent.length > 50 ? '...' : ''}</p>
                    </div>
                </td>
                <td>
                    <span class="text-sm text-gray-900">${this.formatDate(log.created_at)}</span>
                </td>
                <td>
                    <span class="badge ${log.status === 'success' ? 'badge-success' : 'badge-danger'}">
                        ${log.status === 'success' ? 'Success' : 'Failed'}
                    </span>
                </td>
            </tr>
        `).join('');

        // Re-initialize Lucide icons
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    // Analytics functionality
    async loadAnalytics() {
        console.log('Loading analytics data...');
        // Analytics data is already loaded on page load via PHP
        // This function can be used to refresh analytics data if needed
        this.showNotification('Analytics data loaded', 'info');
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
}); 