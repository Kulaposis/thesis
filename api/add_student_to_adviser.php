<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';

// Require adviser login
$auth = new Auth();
$auth->requireRole('adviser');

// Get current user
$user = $auth->getCurrentUser();
$thesisManager = new ThesisManager();

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Validate request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_programs') {
    require_once '../config/database.php';
    $db = new Database();
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare('SELECT program_code, program_name, department FROM programs WHERE is_active = 1 ORDER BY department, program_name');
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'programs' => $programs]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get database connection
$db = new Database();
$pdo = $db->getConnection();

// Handle existing student assignment
if (!empty($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    $adviser_id = $user['id'];
    
    try {
        // Check if student exists and is not already assigned
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :student_id AND role = 'student'");
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            $response['message'] = 'Student not found';
            echo json_encode($response);
            exit;
        }
        
        // Check if student already has a thesis with this adviser
        $stmt = $pdo->prepare("SELECT * FROM theses WHERE student_id = :student_id AND adviser_id = :adviser_id");
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':adviser_id', $adviser_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $response['message'] = 'Student is already assigned to you';
            echo json_encode($response);
            exit;
        }
        
        // Create thesis if title is provided
        if (!empty($_POST['thesis_title'])) {
            $title = $_POST['thesis_title'];
            $abstract = $_POST['thesis_abstract'] ?? null;
            
            $stmt = $pdo->prepare("INSERT INTO theses (student_id, adviser_id, title, abstract, status, progress_percentage) 
                                  VALUES (:student_id, :adviser_id, :title, :abstract, 'draft', 0)");
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':adviser_id', $adviser_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':abstract', $abstract);
            $stmt->execute();
            
            $thesis_id = $pdo->lastInsertId();
            
            // Create default chapters
            $default_chapters = [
                ['chapter_number' => 1, 'title' => 'Introduction'],
                ['chapter_number' => 2, 'title' => 'Literature Review'],
                ['chapter_number' => 3, 'title' => 'Methodology'],
                ['chapter_number' => 4, 'title' => 'Results and Discussion'],
                ['chapter_number' => 5, 'title' => 'Conclusion']
            ];
            
            foreach ($default_chapters as $chapter) {
                $stmt = $pdo->prepare("INSERT INTO chapters (thesis_id, chapter_number, title, status) 
                                      VALUES (:thesis_id, :chapter_number, :title, 'draft')");
                $stmt->bindParam(':thesis_id', $thesis_id);
                $stmt->bindParam(':chapter_number', $chapter['chapter_number']);
                $stmt->bindParam(':title', $chapter['title']);
                $stmt->execute();
            }
        } else {
            // Just assign the student to the adviser without creating a thesis
            $stmt = $pdo->prepare("INSERT INTO theses (student_id, adviser_id, title, status, progress_percentage) 
                                  VALUES (:student_id, :adviser_id, 'Untitled Thesis', 'draft', 0)");
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':adviser_id', $adviser_id);
            $stmt->execute();
        }
        
        // Create notifications
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) 
                              VALUES (:student_id, 'Adviser Assigned', 'You have been assigned to an adviser. Please schedule an initial meeting.', 'info')");
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) 
                              VALUES (:adviser_id, 'New Student Assigned', :message, 'info')");
        $stmt->bindParam(':adviser_id', $adviser_id);
        $message = $student['full_name'] . ' has been assigned as your advisee.';
        $stmt->bindParam(':message', $message);
        $stmt->execute();
        
        $response['success'] = true;
        $response['message'] = 'Student successfully assigned to you';
        $response['redirect'] = '../systemFunda.php?tab=students';
        
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} 
// Handle new student registration
else if (!empty($_POST['full_name']) && !empty($_POST['email'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $student_id_number = $_POST['new_student_id'] ?? '';
    $program = $_POST['program'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Generate password if not provided
    if (empty($password)) {
        $password = bin2hex(random_bytes(4)); // 8 character random password
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $response['message'] = 'Email already exists';
            echo json_encode($response);
            exit;
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Create new student
        $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, role, student_id, program) 
                              VALUES (:email, :password, :full_name, 'student', :student_id, :program)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':student_id', $student_id_number);
        $stmt->bindParam(':program', $program);
        $stmt->execute();
        
        $new_student_id = $pdo->lastInsertId();
        
        // Create thesis if title is provided
        if (!empty($_POST['thesis_title'])) {
            $title = $_POST['thesis_title'];
            $abstract = $_POST['thesis_abstract'] ?? null;
            
            $stmt = $pdo->prepare("INSERT INTO theses (student_id, adviser_id, title, abstract, status, progress_percentage) 
                                  VALUES (:student_id, :adviser_id, :title, :abstract, 'draft', 0)");
            $stmt->bindParam(':student_id', $new_student_id);
            $stmt->bindParam(':adviser_id', $user['id']);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':abstract', $abstract);
            $stmt->execute();
            
            $thesis_id = $pdo->lastInsertId();
            
            // Create default chapters
            $default_chapters = [
                ['chapter_number' => 1, 'title' => 'Introduction'],
                ['chapter_number' => 2, 'title' => 'Literature Review'],
                ['chapter_number' => 3, 'title' => 'Methodology'],
                ['chapter_number' => 4, 'title' => 'Results and Discussion'],
                ['chapter_number' => 5, 'title' => 'Conclusion']
            ];
            
            foreach ($default_chapters as $chapter) {
                $stmt = $pdo->prepare("INSERT INTO chapters (thesis_id, chapter_number, title, status) 
                                      VALUES (:thesis_id, :chapter_number, :title, 'draft')");
                $stmt->bindParam(':thesis_id', $thesis_id);
                $stmt->bindParam(':chapter_number', $chapter['chapter_number']);
                $stmt->bindParam(':title', $chapter['title']);
                $stmt->execute();
            }
        } else {
            // Just assign the student to the adviser without creating a thesis
            $stmt = $pdo->prepare("INSERT INTO theses (student_id, adviser_id, title, status, progress_percentage) 
                                  VALUES (:student_id, :adviser_id, 'Untitled Thesis', 'draft', 0)");
            $stmt->bindParam(':student_id', $new_student_id);
            $stmt->bindParam(':adviser_id', $user['id']);
            $stmt->execute();
        }
        
        // Create notifications
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) 
                              VALUES (:student_id, 'Adviser Assigned', 'You have been assigned to an adviser. Please schedule an initial meeting.', 'info')");
        $stmt->bindParam(':student_id', $new_student_id);
        $stmt->execute();
        
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) 
                              VALUES (:adviser_id, 'New Student Assigned', :message, 'info')");
        $stmt->bindParam(':adviser_id', $user['id']);
        $message = $full_name . ' has been assigned as your advisee.';
        $stmt->bindParam(':message', $message);
        $stmt->execute();
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = 'New student created and assigned to you. Temporary password: ' . $password;
        $response['redirect'] = '../systemFunda.php?tab=students';
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Missing required fields';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 