<?php
require_once __DIR__ . '/../config/database.php';

class AdminManager {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // ========================================
    // SYSTEM STATISTICS & OVERVIEW
    // ========================================

    public function getSystemStatistics() {
        try {
            $stats = [];
            
            // Get user counts by role
            $query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($userStats as $stat) {
                $stats['users'][$stat['role']] = $stat['count'];
            }
            
            // Get total active theses
            $query = "SELECT COUNT(*) as count FROM theses WHERE status != 'draft'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['active_theses'] = $stmt->fetch()['count'];
            
            // Get pending reviews
            $query = "SELECT COUNT(*) as count FROM chapters WHERE status = 'submitted'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['pending_reviews'] = $stmt->fetch()['count'];
            
            // Get overdue deadlines
            $query = "SELECT COUNT(*) as count FROM timeline WHERE due_date < CURDATE() AND status != 'completed'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['overdue_deadlines'] = $stmt->fetch()['count'];
            
            // Get system health (basic version)
            $stats['system_health'] = $this->getSystemHealth();
            
            // Get recent activity (last 7 days)
            $query = "SELECT COUNT(*) as count FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['recent_activity'] = $stmt->fetch()['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting system statistics: " . $e->getMessage());
            return false;
        }
    }

    public function getSystemHealth() {
        try {
            $health = [];
            
            // Database connectivity
            $health['database'] = $this->conn ? 'good' : 'critical';
            
            // File upload directory
            $upload_dir = '../uploads/';
            $health['uploads_dir'] = (is_dir($upload_dir) && is_writable($upload_dir)) ? 'good' : 'warning';
            
            // Recent errors (check if there are many recent errors)
            $query = "SELECT COUNT(*) as count FROM admin_logs WHERE action LIKE '%error%' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $error_count = $stmt->fetch()['count'];
            $health['error_rate'] = $error_count > 10 ? 'warning' : 'good';
            
            // Calculate overall health percentage
            $good_count = array_count_values($health)['good'] ?? 0;
            $total_checks = count($health);
            $health['overall_percentage'] = round(($good_count / $total_checks) * 100, 1);
            
            return $health;
            
        } catch (Exception $e) {
            error_log("Error checking system health: " . $e->getMessage());
            return ['overall_percentage' => 0, 'database' => 'critical'];
        }
    }

    // ========================================
    // LOGIN/LOGOUT LOGGING
    // ========================================

    public function logUserLogin($userId, $role, $actionType = 'login', $additionalData = []) {
        try {
            // Get user's IP address
            $ipAddress = $this->getUserIpAddress();
            
            // Get user agent
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Parse browser info
            $browserInfo = $this->parseBrowserInfo($userAgent);
            
            $query = "INSERT INTO login_logs (user_id, user_role, action_type, ip_address, user_agent, browser_info, login_time) 
                     VALUES (:user_id, :user_role, :action_type, :ip_address, :user_agent, :browser_info, :login_time)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':user_role', $role);
            $stmt->bindParam(':action_type', $actionType);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':user_agent', $userAgent);
            $stmt->bindParam(':browser_info', $browserInfo);
            $stmt->bindParam(':login_time', date('Y-m-d H:i:s'));
            
            if ($stmt->execute()) {
                $loginLogId = $this->conn->lastInsertId();
                
                // Store login log ID in session for logout tracking
                if ($actionType === 'login') {
                    $_SESSION['login_log_id'] = $loginLogId;
                }
                
                return $loginLogId;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error logging user login: " . $e->getMessage());
            return false;
        }
    }

    public function logUserLogout($userId = null, $role = null) {
        try {
            // Get user info from session if not provided
            if (!$userId && isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $user = $this->getCurrentUser();
                $role = $user['role'] ?? 'unknown';
            }
            
            if (!$userId) {
                return false;
            }
            
            // Get the login log ID from session
            $loginLogId = $_SESSION['login_log_id'] ?? null;
            
            if ($loginLogId) {
                // Update the existing login record with logout time
                $query = "UPDATE login_logs SET 
                         logout_time = :logout_time,
                         session_duration = TIMESTAMPDIFF(SECOND, login_time, :logout_time2),
                         updated_at = CURRENT_TIMESTAMP
                         WHERE id = :login_log_id AND user_id = :user_id";
                
                $logoutTime = date('Y-m-d H:i:s');
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':logout_time', $logoutTime);
                $stmt->bindParam(':logout_time2', $logoutTime);
                $stmt->bindParam(':login_log_id', $loginLogId);
                $stmt->bindParam(':user_id', $userId);
                
                $stmt->execute();
                
                // Clear the login log ID from session
                unset($_SESSION['login_log_id']);
            } else {
                // Create a new logout log entry if no login record found
                $this->logUserLogin($userId, $role, 'logout');
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error logging user logout: " . $e->getMessage());
            return false;
        }
    }

    public function getLoginLogs($limit = 100, $filters = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Build WHERE clause based on filters
            if (!empty($filters['user_role'])) {
                $where_conditions[] = "ll.user_role = :user_role";
                $params[':user_role'] = $filters['user_role'];
            }
            
            if (!empty($filters['action_type'])) {
                $where_conditions[] = "ll.action_type = :action_type";
                $params[':action_type'] = $filters['action_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "DATE(ll.created_at) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "DATE(ll.created_at) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            if (!empty($filters['user_search'])) {
                $where_conditions[] = "(u.full_name LIKE :user_search OR u.email LIKE :user_search)";
                $params[':user_search'] = '%' . $filters['user_search'] . '%';
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT 
                        ll.*,
                        u.full_name,
                        u.email,
                        u.student_id,
                        u.faculty_id,
                        CASE 
                            WHEN ll.session_duration IS NOT NULL THEN 
                                CONCAT(
                                    FLOOR(ll.session_duration / 3600), 'h ',
                                    FLOOR((ll.session_duration % 3600) / 60), 'm ',
                                    (ll.session_duration % 60), 's'
                                )
                            ELSE 'Active/Unknown'
                        END as formatted_duration
                     FROM login_logs ll
                     JOIN users u ON ll.user_id = u.id
                     {$where_clause}
                     ORDER BY ll.created_at DESC
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting login logs: " . $e->getMessage());
            return false;
        }
    }

    public function getLoginStatistics($days = 30) {
        try {
            $stats = [];
            
            // Get login counts by role for the last X days
            $query = "SELECT 
                        user_role,
                        action_type,
                        DATE(created_at) as login_date,
                        COUNT(*) as count
                     FROM login_logs 
                     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                     GROUP BY user_role, action_type, DATE(created_at)
                     ORDER BY login_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            $stats['daily_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total unique users logged in today
            $query = "SELECT COUNT(DISTINCT user_id) as count 
                     FROM login_logs 
                     WHERE DATE(created_at) = CURDATE() 
                     AND action_type = 'login'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['unique_users_today'] = $stmt->fetch()['count'];
            
            // Get average session duration by role
            $query = "SELECT 
                        user_role,
                        AVG(session_duration) as avg_duration,
                        COUNT(*) as session_count
                     FROM login_logs 
                     WHERE session_duration IS NOT NULL 
                     AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                     GROUP BY user_role";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            $stats['session_durations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get current active sessions (logged in but not logged out)
            $query = "SELECT COUNT(*) as count 
                     FROM login_logs ll1
                     WHERE ll1.action_type = 'login' 
                     AND ll1.logout_time IS NULL
                     AND NOT EXISTS (
                         SELECT 1 FROM login_logs ll2 
                         WHERE ll2.user_id = ll1.user_id 
                         AND ll2.id > ll1.id
                     )";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['active_sessions'] = $stmt->fetch()['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting login statistics: " . $e->getMessage());
            return false;
        }
    }

    private function getUserIpAddress() {
        // Check for various IP address headers
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }

    private function parseBrowserInfo($userAgent) {
        // Simple browser detection
        $browsers = [
            'Chrome' => '/Chrome\/([0-9.]+)/',
            'Firefox' => '/Firefox\/([0-9.]+)/',
            'Safari' => '/Safari\/([0-9.]+)/',
            'Edge' => '/Edge\/([0-9.]+)/',
            'Opera' => '/Opera\/([0-9.]+)/',
            'Internet Explorer' => '/MSIE ([0-9.]+)/'
        ];
        
        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent, $matches)) {
                return $browser . ' ' . $matches[1];
            }
        }
        
        return 'Unknown Browser';
    }

    // ========================================
    // USER MANAGEMENT
    // ========================================

    public function getAllUsers($filters = []) {
        try {
            // Check if students and advisers tables exist
            $tableExists = [];
            $stmt = $this->conn->query("SHOW TABLES LIKE 'students'");
            $tableExists['students'] = $stmt->rowCount() > 0;
            
            $stmt = $this->conn->query("SHOW TABLES LIKE 'advisers'");
            $tableExists['advisers'] = $stmt->rowCount() > 0;
            
            // Check if is_active column exists
            $stmt = $this->conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
            $hasIsActive = $stmt->rowCount() > 0;
            
            // Check if created_at column exists
            $stmt = $this->conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
            $hasCreatedAt = $stmt->rowCount() > 0;
            
            $sql = "
                SELECT 
                    u.id,
                    u.full_name,
                    u.email,
                    u.role,
                    " . ($hasCreatedAt ? "u.created_at," : "NOW() as created_at,") . "
                    " . ($hasIsActive ? "u.is_active," : "1 as is_active,") . "
                    u.student_id,
                    u.program,
                    u.department,
                    u.faculty_id,
                    ll.login_time as last_login,
                    ll.logout_time as last_logout,
                    CASE 
                        WHEN ll.login_time IS NOT NULL AND ll.logout_time IS NULL THEN 'online'
                        WHEN DATE(ll.login_time) = CURDATE() THEN 'active_today'
                        ELSE 'offline'
                    END as status
                FROM users u
                LEFT JOIN (
                    SELECT 
                        user_id, 
                        MAX(CASE WHEN action_type = 'login' THEN login_time END) as login_time,
                        MAX(CASE WHEN action_type = 'logout' THEN login_time END) as logout_time
                    FROM login_logs
                    GROUP BY user_id
                ) ll ON u.id = ll.user_id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Add filters
            if (!empty($filters['role'])) {
                $sql .= " AND u.role = ?";
                $params[] = $filters['role'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR s.student_id LIKE ? OR a.faculty_id LIKE ?)";
                $searchPattern = "%{$filters['search']}%";
                $params[] = $searchPattern;
                $params[] = $searchPattern;
                $params[] = $searchPattern;
                $params[] = $searchPattern;
            }
            
            if (!empty($filters['department'])) {
                $sql .= " AND (s.department = ? OR a.department = ?)";
                $params[] = $filters['department'];
                $params[] = $filters['department'];
            }
            
            if (!empty($filters['program'])) {
                $sql .= " AND s.program = ?";
                $params[] = $filters['program'];
            }
            
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'active') {
                    $sql .= " AND u.is_active = 1";
                } elseif ($filters['status'] === 'inactive') {
                    $sql .= " AND u.is_active = 0";
                }
            }
            
            $sql .= " ORDER BY u.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format user data
            foreach ($users as &$user) {
                $user['is_active'] = (bool)$user['is_active'];
                
                // Format display name
                if (empty($user['full_name'])) {
                    $user['display_name'] = $user['email'];
                } else {
                    $user['display_name'] = $user['full_name'];
                }
                
                // Format role display
                $user['role_display'] = $this->formatRole($user['role']);
                
                // Set default thesis progress for students
                if ($user['role'] === 'student') {
                    $user['progress_percentage'] = 0; // Default value since we don't have thesis_progress column
                }
                
                // Clean up null values
                $user['student_id'] = $user['student_id'] ?: null;
                $user['faculty_id'] = $user['faculty_id'] ?: null;
                $user['program'] = $user['program'] ?: null;
                $user['department'] = $user['department'] ?: null;
            }
            
            return $users;
            
        } catch (Exception $e) {
            error_log("Error getting users: " . $e->getMessage());
            return false;
        }
    }

    public function createUser($userData) {
        try {
            $this->conn->beginTransaction();
            
            // Validate required fields
            $requiredFields = ['full_name', 'email', 'role'];
            foreach ($requiredFields as $field) {
                if (empty($userData[$field])) {
                    return ['success' => false, 'message' => "Field '{$field}' is required"];
                }
            }
            
            // Validate email format
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Check if email already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$userData['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Generate password if not provided
            $password = !empty($userData['password']) ? $userData['password'] : $this->generateSecurePassword();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Create user
            $stmt = $this->conn->prepare("
                INSERT INTO users (full_name, email, password, role, created_at, is_active)
                VALUES (?, ?, ?, ?, NOW(), 1)
            ");
            $stmt->execute([
                $userData['full_name'],
                $userData['email'],
                $hashedPassword,
                $userData['role']
            ]);
            
            $userId = $this->conn->lastInsertId();
            
            // Create role-specific records
            if ($userData['role'] === 'student') {
                $stmt = $this->conn->prepare("
                    INSERT INTO students (user_id, student_id, program, department, thesis_progress, created_at)
                    VALUES (?, ?, ?, ?, 0, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $userData['student_id'] ?? null,
                    $userData['program'] ?? null,
                    $userData['department'] ?? null
                ]);
            } elseif ($userData['role'] === 'adviser') {
                $stmt = $this->conn->prepare("
                    INSERT INTO advisers (user_id, faculty_id, department, specialization, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $userData['faculty_id'] ?? null,
                    $userData['adviser_department'] ?? $userData['department'] ?? null,
                    $userData['specialization'] ?? null
                ]);
            }
            
            $this->conn->commit();
            
            // Log admin action
            $this->logAdminAction('create_user', 'user', $userId, $userData);
            
            $result = [
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $userId
            ];
            
            // Include password if it was generated
            if (empty($userData['password'])) {
                $result['password'] = $password;
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()];
        }
    }

    public function getUserById($userId) {
        try {
            $query = "SELECT id, email, full_name, role, student_id, faculty_id, program, department, created_at, updated_at 
                     FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting user by ID: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser($userId, $userData) {
        try {
            $this->conn->beginTransaction();
            
            // Validate required fields
            $requiredFields = ['full_name', 'email', 'role'];
            foreach ($requiredFields as $field) {
                if (empty($userData[$field])) {
                    return ['success' => false, 'message' => "Field '{$field}' is required"];
                }
            }
            
            // Validate email format
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Check if email exists for other users
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$userData['email'], $userId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Update user
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, role = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $userData['full_name'],
                $userData['email'],
                $userData['role'],
                $userId
            ]);
            
            // Update role-specific records
            if ($userData['role'] === 'student') {
                // Delete from advisers if exists
                $this->conn->prepare("DELETE FROM advisers WHERE user_id = ?")->execute([$userId]);
                
                // Update or insert student record
                $stmt = $this->conn->prepare("
                    INSERT INTO students (user_id, student_id, program, department, thesis_progress, created_at)
                    VALUES (?, ?, ?, ?, 0, NOW())
                    ON DUPLICATE KEY UPDATE
                    student_id = VALUES(student_id),
                    program = VALUES(program),
                    department = VALUES(department)
                ");
                $stmt->execute([
                    $userId,
                    $userData['student_id'] ?? null,
                    $userData['program'] ?? null,
                    $userData['department'] ?? null
                ]);
            } elseif ($userData['role'] === 'adviser') {
                // Delete from students if exists
                $this->conn->prepare("DELETE FROM students WHERE user_id = ?")->execute([$userId]);
                
                // Update or insert adviser record
                $stmt = $this->conn->prepare("
                    INSERT INTO advisers (user_id, faculty_id, department, specialization, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    faculty_id = VALUES(faculty_id),
                    department = VALUES(department),
                    specialization = VALUES(specialization)
                ");
                $stmt->execute([
                    $userId,
                    $userData['faculty_id'] ?? null,
                    $userData['adviser_department'] ?? $userData['department'] ?? null,
                    $userData['specialization'] ?? null
                ]);
            } else {
                // Remove from role-specific tables if changing to admin
                $this->conn->prepare("DELETE FROM students WHERE user_id = ?")->execute([$userId]);
                $this->conn->prepare("DELETE FROM advisers WHERE user_id = ?")->execute([$userId]);
            }
            
            $this->conn->commit();
            
            // Log admin action
            $this->logAdminAction('update_user', 'user', $userId, $userData);
            
            return ['success' => true, 'message' => 'User updated successfully'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()];
        }
    }

    public function deleteUser($userId) {
        try {
            // Check if user exists
            $stmt = $this->conn->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Prevent deleting yourself
            if ($userId == $_SESSION['user_id']) {
                return ['success' => false, 'message' => 'Cannot delete your own account'];
            }
            
            $this->conn->beginTransaction();
            
            // Delete from role-specific tables first (foreign key constraints)
            $this->conn->prepare("DELETE FROM students WHERE user_id = ?")->execute([$userId]);
            $this->conn->prepare("DELETE FROM advisers WHERE user_id = ?")->execute([$userId]);
            
            // Delete user
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $this->conn->commit();
            
            // Log admin action
            $this->logAdminAction('delete_user', 'user', $userId);
            
            return ['success' => true, 'message' => 'User deleted successfully'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()];
        }
    }

    // ========================================
    // BULK OPERATIONS
    // ========================================

    public function bulkUserOperation($userIds, $operation, $data = []) {
        try {
            if (empty($userIds) || !is_array($userIds)) {
                return ['success' => false, 'message' => 'User IDs are required'];
            }
            
            $this->conn->beginTransaction();
            $count = 0;
            $results = [];
            
            switch ($operation) {
                case 'delete':
                    // Prevent deleting yourself
                    if (in_array($_SESSION['user_id'], $userIds)) {
                        return ['success' => false, 'message' => 'Cannot delete your own account'];
                    }
                    
                    foreach ($userIds as $userId) {
                        $result = $this->deleteUser($userId);
                        if ($result['success']) {
                            $count++;
                        }
                    }
                    
                    $this->conn->commit();
                    return [
                        'success' => true,
                        'message' => "$count user(s) deleted successfully",
                        'count' => $count
                    ];
                    
                case 'reset_password':
                    $passwords = [];
                    
                    foreach ($userIds as $userId) {
                        // Get user info
                        $stmt = $this->conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch();
                        
                        if ($user) {
                            $result = $this->resetUserPassword($userId);
                            if ($result['success']) {
                                $passwords[] = [
                                    'user_id' => $userId,
                                    'name' => $user['full_name'],
                                    'email' => $user['email'],
                                    'password' => $result['password']
                                ];
                                $count++;
                            }
                        }
                    }
                    
                    $this->conn->commit();
                    return [
                        'success' => true,
                        'message' => "Passwords reset for $count user(s)",
                        'count' => $count,
                        'passwords' => $passwords
                    ];
                    
                case 'update_role':
                    if (empty($data['new_role'])) {
                        return ['success' => false, 'message' => 'New role is required'];
                    }
                    
                    foreach ($userIds as $userId) {
                        $result = $this->updateUser($userId, ['role' => $data['new_role']]);
                        if ($result['success']) {
                            $count++;
                        }
                    }
                    
                    $this->conn->commit();
                    return [
                        'success' => true,
                        'message' => "$count user(s) role updated successfully",
                        'count' => $count
                    ];
                    
                default:
                    return ['success' => false, 'message' => 'Invalid operation'];
            }
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error performing bulk operation: ' . $e->getMessage()];
        }
    }

    public function resetUserPassword($userId, $newPassword = null) {
        try {
            // Check if user exists
            $stmt = $this->conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Generate new password if not provided
            if (!$newPassword) {
                $newPassword = $this->generateSecurePassword();
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            // Log admin action
            $this->logAdminAction('reset_password', 'user', $userId);
            
            return [
                'success' => true,
                'message' => 'Password reset successfully',
                'password' => $newPassword
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error resetting password: ' . $e->getMessage()];
        }
    }

    public function generateSecurePassword($length = 12) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }

    // ========================================
    // ANALYTICS & REPORTING
    // ========================================

    public function getAdvancedAnalytics() {
        try {
            $analytics = [];
            
            // Department performance
            $query = "SELECT 
                        u.department,
                        COUNT(DISTINCT CASE WHEN u.role = 'student' THEN u.id END) as student_count,
                        COUNT(DISTINCT t.id) as thesis_count,
                        AVG(t.progress_percentage) as avg_progress,
                        COUNT(CASE WHEN t.status = 'approved' THEN 1 END) as completed_theses
                      FROM users u
                      LEFT JOIN theses t ON u.id = t.student_id
                      WHERE u.department IS NOT NULL
                      GROUP BY u.department";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $analytics['department_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Monthly activity trends
            $query = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as activity_count
                      FROM notifications 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                      ORDER BY month";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $analytics['monthly_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adviser workload
            $query = "SELECT 
                        u.full_name as adviser_name,
                        COUNT(t.id) as supervised_theses,
                        AVG(t.progress_percentage) as avg_student_progress,
                        COUNT(CASE WHEN t.status = 'approved' THEN 1 END) as completed_supervisions
                      FROM users u
                      LEFT JOIN theses t ON u.id = t.adviser_id
                      WHERE u.role = 'adviser'
                      GROUP BY u.id, u.full_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $analytics['adviser_workload'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $analytics;
            
        } catch (Exception $e) {
            error_log("Error getting advanced analytics: " . $e->getMessage());
            return false;
        }
    }

    public function getAdviserWorkload() {
        $db = $this->getDb(); // Adjust if your DB connection method is different
        $sql = "SELECT u.full_name AS adviser, COUNT(s.id) AS workload
                FROM users u
                LEFT JOIN students s ON s.adviser_id = u.id
                WHERE u.role = 'adviser'
                GROUP BY u.id";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ========================================
    // ANNOUNCEMENTS & COMMUNICATION
    // ========================================

    public function createAnnouncement($data) {
        try {
            $query = "INSERT INTO announcements (title, content, target_roles, target_departments, priority, expires_at, created_by) 
                     VALUES (:title, :content, :target_roles, :target_departments, :priority, :expires_at, :created_by)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':content', $data['content']);
            $stmt->bindParam(':target_roles', json_encode($data['target_roles']));
            $stmt->bindParam(':target_departments', json_encode($data['target_departments']));
            $stmt->bindParam(':priority', $data['priority']);
            $stmt->bindParam(':expires_at', $data['expires_at']);
            $stmt->bindParam(':created_by', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $announcement_id = $this->conn->lastInsertId();
                $this->logAdminAction('create_announcement', 'announcement', $announcement_id, $data);
                return $announcement_id;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error creating announcement: " . $e->getMessage());
            return false;
        }
    }

    public function getActiveAnnouncements($role = null, $department = null) {
        try {
            $query = "SELECT * FROM announcements 
                     WHERE is_active = 1 
                     AND (expires_at IS NULL OR expires_at > NOW())
                     ORDER BY priority DESC, created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting announcements: " . $e->getMessage());
            return false;
        }
    }

    // ========================================
    // SYSTEM SETTINGS
    // ========================================

    public function getSystemSettings() {
        try {
            $query = "SELECT * FROM system_settings ORDER BY setting_key";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $settings = [];
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $setting) {
                $value = $setting['setting_value'];
                
                // Convert based on type
                switch ($setting['setting_type']) {
                    case 'boolean':
                        $value = $value === 'true';
                        break;
                    case 'number':
                        $value = is_numeric($value) ? (float)$value : $value;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }
                
                $settings[$setting['setting_key']] = [
                    'value' => $value,
                    'type' => $setting['setting_type'],
                    'description' => $setting['description']
                ];
            }
            
            return $settings;
            
        } catch (Exception $e) {
            error_log("Error getting system settings: " . $e->getMessage());
            return false;
        }
    }

    public function updateSystemSetting($key, $value) {
        try {
            $query = "UPDATE system_settings SET setting_value = :value WHERE setting_key = :key";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':key', $key);
            
            if ($stmt->execute()) {
                $this->logAdminAction('update_setting', 'system_setting', null, ['key' => $key, 'value' => $value]);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error updating system setting: " . $e->getMessage());
            return false;
        }
    }

    // ========================================
    // ADMIN LOGGING
    // ========================================

    public function logAdminAction($action, $target_type = null, $target_id = null, $details = null) {
        try {
            if (!isset($_SESSION['user_id'])) {
                return false;
            }
            
            $query = "INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address, user_agent) 
                     VALUES (:admin_id, :action, :target_type, :target_id, :details, :ip_address, :user_agent)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':admin_id', $_SESSION['user_id']);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':target_type', $target_type);
            $stmt->bindParam(':target_id', $target_id);
            $stmt->bindParam(':details', json_encode($details));
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Error logging admin action: " . $e->getMessage());
            return false;
        }
    }

    public function getAdminLogs($limit = 50, $filters = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($filters['admin_id'])) {
                $where_conditions[] = "admin_id = :admin_id";
                $params[':admin_id'] = $filters['admin_id'];
            }
            
            if (!empty($filters['action'])) {
                $where_conditions[] = "action LIKE :action";
                $params[':action'] = '%' . $filters['action'] . '%';
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT al.*, u.full_name as admin_name 
                     FROM admin_logs al
                     LEFT JOIN users u ON al.admin_id = u.id
                     {$where_clause}
                     ORDER BY al.created_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            
            foreach ($params as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting admin logs: " . $e->getMessage());
            return false;
        }
    }

    // ========================================
    // UTILITY FUNCTIONS
    // ========================================

    public function isAdmin($userId = null) {
        if (!$userId) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        
        if (!$userId) {
            return false;
        }
        
        try {
            $query = "SELECT role FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            $role = $stmt->fetch()['role'] ?? '';
            return in_array($role, ['admin', 'super_admin']);
            
        } catch (Exception $e) {
            return false;
        }
    }

    public function isSuperAdmin($userId = null) {
        if (!$userId) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        
        if (!$userId) {
            return false;
        }
        
        try {
            $query = "SELECT role FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            $role = $stmt->fetch()['role'] ?? '';
            return $role === 'super_admin';
            
        } catch (Exception $e) {
            return false;
        }
    }

    public function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        try {
            $query = "SELECT id, email, full_name, role, student_id, faculty_id, program, department 
                     FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting current user: " . $e->getMessage());
            return null;
        }
    }

    private function formatRole($role) {
        $roles = [
            'student' => 'Student',
            'adviser' => 'Adviser',
            'admin' => 'Admin',
            'super_admin' => 'Super Admin'
        ];
        return $roles[$role] ?? ucfirst($role);
    }
}
?>