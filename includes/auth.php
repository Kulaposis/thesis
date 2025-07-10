<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function login($email, $password, $role = null) {
        try {
            $sql = "SELECT id, email, password, full_name, role, student_id, faculty_id, program, department 
                    FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['password'])) {
                    // Store user data in session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['student_id'] = $user['student_id'];
                    $_SESSION['faculty_id'] = $user['faculty_id'];
                    $_SESSION['program'] = $user['program'];
                    $_SESSION['department'] = $user['department'];
                    $_SESSION['logged_in'] = true;
                    
                    // Log successful login
                    $this->logUserActivity($user['id'], $user['role'], 'login');
                    
                    // Get the role from the database
                    $userRole = $user['role'];
                    
                    $redirect = 'login.php';
                    if ($userRole === 'student') {
                        $redirect = 'studentDashboard.php';
                    } elseif ($userRole === 'adviser') {
                        $redirect = 'systemFunda.php';
                    } elseif ($userRole === 'admin' || $userRole === 'super_admin') {
                        $redirect = 'admin_dashboard.php';
                    }
                    return [
                        'success' => true,
                        'message' => 'Login successful',
                        'redirect' => $redirect
                    ];
                } else {
                    // Log failed login attempt
                    $this->logUserActivity($user['id'], $user['role'], 'login_failed');
                    return ['success' => false, 'message' => 'Invalid password'];
                }
            } else {
                // Log failed login attempt for unknown email
                $this->logUserActivity(null, 'unknown', 'login_failed', ['email' => $email]);
                return ['success' => false, 'message' => 'User not found'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function register($data) {
        try {
            // Check if email already exists
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert new user
            $sql = "INSERT INTO users (email, password, full_name, role, student_id, faculty_id, program, department) 
                    VALUES (:email, :password, :full_name, :role, :student_id, :faculty_id, :program, :department)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':role', $data['role']);
            
            // Create temporary variables for nullable fields
            $studentId = $data['student_id'] ?? null;
            $facultyId = $data['faculty_id'] ?? null;
            $program = $data['program'] ?? null;
            $department = $data['department'] ?? null;
            
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':faculty_id', $facultyId);
            $stmt->bindParam(':program', $program);
            $stmt->bindParam(':department', $department);

            if ($stmt->execute()) {
                $redirect = 'login.php';
                if ($data['role'] === 'student') {
                    $redirect = 'studentDashboard.php';
                } elseif ($data['role'] === 'adviser') {
                    $redirect = 'systemFunda.php';
                } elseif ($data['role'] === 'admin' || $data['role'] === 'super_admin') {
                    $redirect = 'admin_dashboard.php';
                }
                return [
                    'success' => true,
                    'message' => 'Registration successful',
                    'redirect' => $redirect
                ];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function logout() {
        // Log the logout before destroying session
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            $this->logUserActivity($_SESSION['user_id'], $_SESSION['role'], 'logout');
        }
        
        session_destroy();
        header("Location: login.php");
        exit();
    }

    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }

    public function requireRole($role) {
        $this->requireLogin();
        if ($_SESSION['role'] !== $role) {
            header("Location: login.php");
            exit();
        }
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['email'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role'],
                'student_id' => $_SESSION['student_id'],
                'faculty_id' => $_SESSION['faculty_id'],
                'program' => $_SESSION['program'],
                'department' => $_SESSION['department']
            ];
        }
        return null;
    }

    private function logUserActivity($userId, $role, $actionType, $additionalData = []) {
        try {
            // Include the AdminManager class for logging
            require_once __DIR__ . '/admin_functions.php';
            $adminManager = new AdminManager();
            
            if ($userId) {
                $adminManager->logUserLogin($userId, $role, $actionType, $additionalData);
            } else {
                // For failed login attempts with unknown users, log with a special entry
                $ipAddress = $this->getUserIpAddress();
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $browserInfo = $this->parseBrowserInfo($userAgent);
                
                $query = "INSERT INTO login_logs (user_id, user_role, action_type, ip_address, user_agent, browser_info, login_time) 
                         VALUES (NULL, :user_role, :action_type, :ip_address, :user_agent, :browser_info, :login_time)";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':user_role', $role);
                $stmt->bindParam(':action_type', $actionType);
                $stmt->bindParam(':ip_address', $ipAddress);
                $stmt->bindParam(':user_agent', $userAgent);
                $stmt->bindParam(':browser_info', $browserInfo);
                $stmt->bindParam(':login_time', date('Y-m-d H:i:s'));
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Error logging user activity: " . $e->getMessage());
        }
    }

    private function getUserIpAddress() {
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
}

// Handle AJAX requests ONLY if this file is directly accessed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && 
    (strpos($_SERVER['REQUEST_URI'], 'auth.php') !== false || 
     strpos($_SERVER['SCRIPT_NAME'], 'auth.php') !== false)) {
    header('Content-Type: application/json');
    
    $auth = new Auth();
    $response = [];

    switch ($_POST['action']) {
        case 'login':
            $response = $auth->login($_POST['email'], $_POST['password'], $_POST['role']);
            break;
            
        case 'register':
            $response = $auth->register($_POST);
            break;
            
        case 'logout':
            $auth->logout();
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }

    echo json_encode($response);
    exit();
}

/**
 * Get user by ID
 * 
 * @param PDO $conn Database connection
 * @param int $userId User ID
 * @return array|null User data or null if not found
 */
function getUserById($conn, $userId) {
    $query = "SELECT id, email, full_name, role, student_id, faculty_id, program, department 
              FROM users 
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user ?: null;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if current user is admin
 * 
 * @return bool True if user is admin
 */
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = $_SESSION['role'] ?? '';
    return in_array($role, ['admin', 'super_admin']);
}

/**
 * Check if current user is super admin
 * 
 * @return bool True if user is super admin
 */
function isSuperAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    return ($_SESSION['role'] ?? '') === 'super_admin';
}
?> 