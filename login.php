<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect if already logged in
$auth = new Auth();
if ($auth->isLoggedIn()) {
    $role = $_SESSION['role'];
    $redirect = $role === 'student' ? 'studentDashboard.php' : 'systemFunda.php';
    header("Location: $redirect");
    exit();
}

// Handle form submissions
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login_submit'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // We don't need to pass a role parameter anymore
        // The Auth class will determine the role from the database
        $result = $auth->login($email, $password, null);
        if ($result['success']) {
            header("Location: " . $result['redirect']);
            exit();
        } else {
            $error_message = $result['message'];
        }
    } elseif (isset($_POST['register_submit'])) {
        $result = $auth->register($_POST);
        if ($result['success']) {
            header("Location: " . $result['redirect']);
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thesis Management System - Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    .auth-container {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    .auth-card {
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
    .input-field:focus {
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }
    .role-selector input:checked + label {
      border-color: #3b82f6;
      background-color: #eff6ff;
    }
    .toggle-form {
      transition: all 0.3s ease;
    }
    .alert {
      padding: 12px;
      margin-bottom: 16px;
      border-radius: 8px;
    }
    .alert-error {
      background-color: #fee2e2;
      border: 1px solid #fecaca;
      color: #dc2626;
    }
    .alert-success {
      background-color: #dcfce7;
      border: 1px solid #bbf7d0;
      color: #16a34a;
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center auth-container">
  <div class="w-full max-w-md mx-4">
    
    <?php if ($error_message): ?>
    <div class="alert alert-error">
      <i data-lucide="alert-circle" class="w-5 h-5 inline mr-2"></i>
      <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
    <div class="alert alert-success">
      <i data-lucide="check-circle" class="w-5 h-5 inline mr-2"></i>
      <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>

    <!-- Login Form -->
    <div id="loginForm" class="auth-card bg-white rounded-xl p-8">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Thesis Management System</h1>
        <p class="text-gray-600">Sign in to access your account</p>
      </div>
      
      <form method="POST" action="">
        <input type="hidden" name="login_submit" value="1">
        
        <div class="mb-4">
          <label for="loginEmail" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
          <input 
            type="email" 
            id="loginEmail" 
            name="email"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field focus:outline-none focus:border-blue-500" 
            placeholder="Enter your email"
            required
          >
        </div>
        
        <div class="mb-6">
          <label for="loginPassword" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input 
            type="password" 
            id="loginPassword" 
            name="password"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field focus:outline-none focus:border-blue-500" 
            placeholder="Enter your password"
            required
          >
        </div>
        
        <button 
          type="submit" 
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center"
        >
          <i data-lucide="log-in" class="w-5 h-5 mr-2"></i> Login
        </button>
        
        <div class="mt-4 text-center text-sm text-gray-600">
          Don't have an account? 
          <button 
            type="button" 
            id="showSignupBtn" 
            class="text-blue-600 hover:text-blue-800 font-medium"
          >
            Sign up
          </button>
        </div>
      </form>
    </div>
    
    <!-- Signup Form (hidden by default) -->
    <div id="signupForm" class="auth-card bg-white rounded-xl p-8 hidden toggle-form">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Create Account</h1>
        <p class="text-gray-600">Register as a student or adviser</p>
      </div>
      
      <form method="POST" action="">
        <input type="hidden" name="register_submit" value="1">
        
        <!-- Role Selection -->
        <div class="mb-6 role-selector">
          <p class="block text-sm font-medium text-gray-700 mb-2">I am a:</p>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <input type="radio" id="studentRole" name="role" value="student" class="hidden" checked>
              <label for="studentRole" class="block p-4 border border-gray-300 rounded-lg text-center cursor-pointer hover:border-blue-300 transition-colors duration-200">
                <i data-lucide="user" class="w-6 h-6 mx-auto mb-2 text-blue-600"></i>
                <span class="font-medium">Student</span>
              </label>
            </div>
            <div>
              <input type="radio" id="adviserRole" name="role" value="adviser" class="hidden">
              <label for="adviserRole" class="block p-4 border border-gray-300 rounded-lg text-center cursor-pointer hover:border-blue-300 transition-colors duration-200">
                <i data-lucide="graduation-cap" class="w-6 h-6 mx-auto mb-2 text-blue-600"></i>
                <span class="font-medium">Adviser</span>
              </label>
            </div>
          </div>
        </div>
        
        <!-- Student Fields (shown by default) -->
        <div id="studentFields">
          <div class="mb-4">
            <label for="studentId" class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
            <input 
              type="text" 
              id="studentId" 
              name="student_id"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field focus:outline-none focus:border-blue-500" 
              placeholder="Enter your student ID"
            >
          </div>
          
          <div class="mb-4">
            <label for="program" class="block text-sm font-medium text-gray-700 mb-1">Program</label>
            <select 
              id="program" 
              name="program"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field focus:outline-none focus:border-blue-500"
            >
              <option value="">Select your program</option>
              <option value="Computer Science">Computer Science</option>
              <option value="Information Technology">Information Technology</option>
              <option value="Engineering">Engineering</option>
              <option value="Business Administration">Business Administration</option>
            </select>
          </div>
        </div>
        
        <!-- Adviser Fields (hidden by default) -->
        <div id="adviserFields" class="hidden">
          <div class="mb-4">
            <label for="facultyId" class="block text-sm font-medium text-gray-700 mb-1">Faculty ID</label>
            <input 
              type="text" 
              id="facultyId" 
              name="faculty_id"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field focus:outline-none focus:border-blue-500" 
              placeholder="Enter your faculty ID"
            >
          </div>
          
          <div class="mb-4">
            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <select 
              id="department" 
              name="department"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field focus:outline-none focus:border-blue-500"
            >
              <option value="">Select your department</option>
              <option value="Computer Science">Computer Science</option>
              <option value="Information Technology">Information Technology</option>
              <option value="Engineering">Engineering</option>
              <option value="Business">Business</option>
            </select>
          </div>
        </div>
        
        <!-- Common Fields -->
        <div class="mb-4">
          <label for="fullName" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
          <input 
            type="text" 
            id="fullName" 
            name="full_name"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field focus:outline-none focus:border-blue-500" 
            placeholder="Enter your full name"
            required
          >
        </div>
        
        <div class="mb-4">
          <label for="signupEmail" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
          <input 
            type="email" 
            id="signupEmail" 
            name="email"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field focus:outline-none focus:border-blue-500" 
            placeholder="Enter your email"
            required
          >
        </div>
        
        <div class="mb-6">
          <label for="signupPassword" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input 
            type="password" 
            id="signupPassword" 
            name="password"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field focus:outline-none focus:border-blue-500" 
            placeholder="Create a password"
            required
          >
          <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
        </div>
        
        <button 
          type="submit" 
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center"
        >
          <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i> Create Account
        </button>
        
        <div class="mt-4 text-center text-sm text-gray-600">
          Already have an account? 
          <button 
            type="button" 
            id="showLoginBtn" 
            class="text-blue-600 hover:text-blue-800 font-medium"
          >
            Sign in
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Toggle between login and signup forms
    const showSignupBtn = document.getElementById('showSignupBtn');
    const showLoginBtn = document.getElementById('showLoginBtn');
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    
    showSignupBtn.addEventListener('click', () => {
      loginForm.classList.add('hidden');
      signupForm.classList.remove('hidden');
    });
    
    showLoginBtn.addEventListener('click', () => {
      signupForm.classList.add('hidden');
      loginForm.classList.remove('hidden');
    });
    
    // Toggle between student and adviser fields
    const studentRole = document.getElementById('studentRole');
    const adviserRole = document.getElementById('adviserRole');
    const studentFields = document.getElementById('studentFields');
    const adviserFields = document.getElementById('adviserFields');
    
    studentRole.addEventListener('change', () => {
      studentFields.classList.remove('hidden');
      adviserFields.classList.add('hidden');
      // Clear adviser fields
      document.getElementById('facultyId').value = '';
      document.getElementById('department').value = '';
    });
    
    adviserRole.addEventListener('change', () => {
      studentFields.classList.add('hidden');
      adviserFields.classList.remove('hidden');
      // Clear student fields
      document.getElementById('studentId').value = '';
      document.getElementById('program').value = '';
    });
  </script>
</body>
</html> 