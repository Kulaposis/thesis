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
    // USER MANAGEMENT
    // ========================================

    public function getAllUsers($filters = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Build WHERE clause based on filters
            if (!empty($filters['role'])) {
                $where_conditions[] = "role = :role";
                $params[':role'] = $filters['role'];
            }
            
            if (!empty($filters['search'])) {
                $where_conditions[] = "(full_name LIKE :search OR email LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            if (!empty($filters['department'])) {
                $where_conditions[] = "department = :department";
                $params[':department'] = $filters['department'];
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT id, email, full_name, role, student_id, faculty_id, program, department, created_at, updated_at 
                     FROM users {$where_clause} ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting users: " . $e->getMessage());
            return false;
        }
    }

    public function createUser($userData) {
        try {
            $query = "INSERT INTO users (email, password, full_name, role, student_id, faculty_id, program, department) 
                     VALUES (:email, :password, :full_name, :role, :student_id, :faculty_id, :program, :department)";
            
            $stmt = $this->conn->prepare($query);
            
            $hashed_password = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $userData['full_name']);
            $stmt->bindParam(':role', $userData['role']);
            $stmt->bindParam(':student_id', $userData['student_id']);
            $stmt->bindParam(':faculty_id', $userData['faculty_id']);
            $stmt->bindParam(':program', $userData['program']);
            $stmt->bindParam(':department', $userData['department']);
            
            if ($stmt->execute()) {
                $user_id = $this->conn->lastInsertId();
                $this->logAdminAction('create_user', 'user', $user_id, $userData);
                return $user_id;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser($userId, $userData) {
        try {
            $query = "UPDATE users SET full_name = :full_name, role = :role, student_id = :student_id, 
                     faculty_id = :faculty_id, program = :program, department = :department WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->bindParam(':full_name', $userData['full_name']);
            $stmt->bindParam(':role', $userData['role']);
            $stmt->bindParam(':student_id', $userData['student_id']);
            $stmt->bindParam(':faculty_id', $userData['faculty_id']);
            $stmt->bindParam(':program', $userData['program']);
            $stmt->bindParam(':department', $userData['department']);
            
            if ($stmt->execute()) {
                $this->logAdminAction('update_user', 'user', $userId, $userData);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($userId) {
        try {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            
            if ($stmt->execute()) {
                $this->logAdminAction('delete_user', 'user', $userId);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }

    // ========================================
    // BULK OPERATIONS
    // ========================================

    public function bulkUserOperation($userIds, $operation, $data = []) {
        try {
            $this->conn->beginTransaction();
            
            foreach ($userIds as $userId) {
                switch ($operation) {
                    case 'delete':
                        $this->deleteUser($userId);
                        break;
                    case 'update_role':
                        $this->updateUser($userId, ['role' => $data['new_role']]);
                        break;
                    case 'reset_password':
                        $this->resetUserPassword($userId);
                        break;
                }
            }
            
            $this->conn->commit();
            $this->logAdminAction('bulk_operation', 'users', null, [
                'operation' => $operation,
                'user_count' => count($userIds),
                'data' => $data
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error performing bulk operation: " . $e->getMessage());
            return false;
        }
    }

    public function resetUserPassword($userId, $newPassword = null) {
        try {
            if (!$newPassword) {
                $newPassword = $this->generateRandomPassword();
            }
            
            $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':id', $userId);
            
            if ($stmt->execute()) {
                $this->logAdminAction('reset_password', 'user', $userId);
                return $newPassword; // Return the plain password for admin to share with user
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error resetting password: " . $e->getMessage());
            return false;
        }
    }

    private function generateRandomPassword($length = 8) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
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
}
?>