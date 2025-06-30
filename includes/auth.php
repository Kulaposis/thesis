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
                    
                    // Get the role from the database
                    $userRole = $user['role'];
                    
                    return [
                        'success' => true,
                        'message' => 'Login successful',
                        'redirect' => $userRole === 'student' ? 'studentDashboard.php' : 'systemFunda.php'
                    ];
                } else {
                    return ['success' => false, 'message' => 'Invalid password'];
                }
            } else {
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
                return [
                    'success' => true,
                    'message' => 'Registration successful',
                    'redirect' => $data['role'] === 'student' ? 'studentDashboard.php' : 'systemFunda.php'
                ];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function logout() {
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
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
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
?> 