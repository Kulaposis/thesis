<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect if already logged in
$auth = new Auth();
if ($auth->isLoggedIn()) {
    $role = $_SESSION['role'];
    $redirect = 'login.php';
    if ($role === 'student') {
        $redirect = 'studentDashboard.php';
    } elseif ($role === 'adviser') {
        $redirect = 'systemFunda.php';
    } elseif ($role === 'admin' || $role === 'super_admin') {
        $redirect = 'admin_dashboard.php';
    }
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
  <link rel="stylesheet" href="assets/css/modern-ui.css">
  <style>
    .auth-container {
      background: linear-gradient(135deg, var(--gray-25) 0%, var(--primary-50) 100%);
      min-height: 100vh;
      position: relative;
      overflow: hidden;
    }
    
    .auth-container::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
      animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
      0%, 100% { transform: rotate(0deg) translateX(0); }
      50% { transform: rotate(180deg) translateX(20px); }
    }
    
    .auth-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: var(--shadow-xl);
      position: relative;
      z-index: 10;
    }
    
    [data-theme="dark"] .auth-card {
      background: rgba(31, 41, 55, 0.95);
      border: 1px solid rgba(107, 114, 128, 0.2);
    }
    
    .role-card {
      border: 2px solid transparent;
      background: linear-gradient(white, white) padding-box,
                  linear-gradient(135deg, var(--gray-200), var(--gray-300)) border-box;
      transition: all var(--transition-normal);
    }
    
    .role-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }
    
    .role-card.selected {
      background: linear-gradient(white, white) padding-box,
                  linear-gradient(135deg, var(--primary-500), var(--primary-600)) border-box;
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }
    
    .logo-container {
      background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 2rem;
      box-shadow: var(--shadow-lg);
      animation: pulse-glow 2s infinite;
    }
    
    @keyframes pulse-glow {
      0%, 100% { box-shadow: var(--shadow-lg), 0 0 0 0 rgba(59, 130, 246, 0.4); }
      50% { box-shadow: var(--shadow-lg), 0 0 0 10px rgba(59, 130, 246, 0); }
    }
    
    .form-input {
      transition: all var(--transition-fast);
      background: rgba(255, 255, 255, 0.8);
    }
    
    .form-input:focus {
      background: white;
      transform: translateY(-1px);
      box-shadow: var(--shadow-md), 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    [data-theme="dark"] .form-input {
      background: rgba(55, 65, 81, 0.8);
      color: var(--gray-100);
    }
    
    [data-theme="dark"] .form-input:focus {
      background: var(--gray-700);
    }
    
    .btn-login {
      background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
      box-shadow: var(--shadow-md);
      position: relative;
      overflow: hidden;
    }
    
    .btn-login::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.6s;
    }
    
    .btn-login:hover::before {
      left: 100%;
    }
    
    .toggle-text {
      background: linear-gradient(135deg, var(--primary-600), var(--primary-400));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 600;
    }
  </style>
</head>
  <body class="min-h-screen flex items-center justify-center auth-container">
    <div class="w-full max-w-md mx-4">
      
      <?php if ($error_message): ?>
      <div class="mb-6 p-4 rounded-lg border-l-4 border-red-500 bg-red-50 text-red-800 fade-in">
        <div class="flex items-center">
          <i data-lucide="alert-circle" class="w-5 h-5 mr-3 text-red-500"></i>
          <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($success_message): ?>
      <div class="mb-6 p-4 rounded-lg border-l-4 border-green-500 bg-green-50 text-green-800 fade-in">
        <div class="flex items-center">
          <i data-lucide="check-circle" class="w-5 h-5 mr-3 text-green-500"></i>
          <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
      </div>
      <?php endif; ?>

      <!-- Login Form -->
      <div id="loginForm" class="auth-card rounded-2xl p-8 fade-in">
        <div class="text-center mb-8">
          <div class="logo-container">
            <i data-lucide="graduation-cap" class="w-10 h-10 text-white"></i>
          </div>
          <h1 class="display-3 text-gradient mb-2">Thesis Management</h1>
          <p class="body-sm text-gray-600">Sign in to access your account</p>
        </div>
      
              <form method="POST" action="" class="space-y-6">
          <input type="hidden" name="login_submit" value="1">
          
          <div class="form-group">
            <label for="loginEmail" class="form-label">Email Address</label>
            <input 
              type="email" 
              id="loginEmail" 
              name="email"
              class="form-input focus-ring" 
              placeholder="Enter your email"
              required
            >
          </div>
          
          <div class="form-group">
            <label for="loginPassword" class="form-label">Password</label>
            <input 
              type="password" 
              id="loginPassword" 
              name="password"
              class="form-input focus-ring" 
              placeholder="Enter your password"
              required
            >
          </div>
          
          <button 
            type="submit" 
            class="btn btn-primary btn-lg w-full btn-login hover-lift"
          >
            <i data-lucide="log-in" class="w-5 h-5"></i>
            <span>Sign In</span>
          </button>
          
          <div class="text-center body-sm text-gray-600">
            Don't have an account? 
            <button 
              type="button" 
              id="showSignupBtn" 
              class="toggle-text hover:underline ml-1"
            >
              Create one here
            </button>
          </div>
      </form>
    </div>
    
          <!-- Signup Form (hidden by default) -->
      <div id="signupForm" class="auth-card rounded-2xl p-8 hidden slide-up">
        <div class="text-center mb-8">
          <div class="logo-container">
            <i data-lucide="user-plus" class="w-10 h-10 text-white"></i>
          </div>
          <h1 class="display-3 text-gradient mb-2">Join Us</h1>
          <p class="body-sm text-gray-600">Create your account to get started</p>
        </div>
        
        <form method="POST" action="" class="space-y-6">
          <input type="hidden" name="register_submit" value="1">
          
          <!-- Role Selection -->
          <div class="form-group">
            <label class="form-label mb-4">I am a:</label>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <input type="radio" id="studentRole" name="role" value="student" class="hidden" checked>
                <label for="studentRole" class="role-card selected p-6 rounded-xl text-center cursor-pointer hover-lift block">
                  <i data-lucide="user" class="w-8 h-8 mx-auto mb-3 text-primary-600"></i>
                  <span class="font-semibold text-gray-800">Student</span>
                  <p class="text-xs text-gray-500 mt-1">Submit and track thesis</p>
                </label>
              </div>
              <div>
                <input type="radio" id="adviserRole" name="role" value="adviser" class="hidden">
                <label for="adviserRole" class="role-card p-6 rounded-xl text-center cursor-pointer hover-lift block">
                  <i data-lucide="graduation-cap" class="w-8 h-8 mx-auto mb-3 text-primary-600"></i>
                  <span class="font-semibold text-gray-800">Adviser</span>
                  <p class="text-xs text-gray-500 mt-1">Guide and review thesis</p>
                </label>
              </div>
            </div>
          </div>
        
                  <!-- Student Fields (shown by default) -->
          <div id="studentFields" class="space-y-4">
            <div class="form-group">
              <label for="studentId" class="form-label">Student ID</label>
              <input 
                type="text" 
                id="studentId" 
                name="student_id"
                class="form-input focus-ring" 
                placeholder="Enter your student ID"
              >
            </div>
            
            <div class="form-group">
              <label for="program" class="form-label">Program</label>
              <select 
                id="program" 
                name="program"
                class="form-input focus-ring"
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
          <div id="adviserFields" class="hidden space-y-4">
            <div class="form-group">
              <label for="facultyId" class="form-label">Faculty ID</label>
              <input 
                type="text" 
                id="facultyId" 
                name="faculty_id"
                class="form-input focus-ring" 
                placeholder="Enter your faculty ID"
              >
            </div>
            
            <div class="form-group">
              <label for="department" class="form-label">Department</label>
              <select 
                id="department" 
                name="department"
                class="form-input focus-ring"
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
          <div class="form-group">
            <label for="fullName" class="form-label">Full Name</label>
            <input 
              type="text" 
              id="fullName" 
              name="full_name"
              class="form-input focus-ring" 
              placeholder="Enter your full name"
              required
            >
          </div>
          
          <div class="form-group">
            <label for="signupEmail" class="form-label">Email Address</label>
            <input 
              type="email" 
              id="signupEmail" 
              name="email"
              class="form-input focus-ring" 
              placeholder="Enter your email"
              required
            >
          </div>
          
          <div class="form-group">
            <label for="signupPassword" class="form-label">Password</label>
            <input 
              type="password" 
              id="signupPassword" 
              name="password"
              class="form-input focus-ring" 
              placeholder="Create a password"
              required
            >
            <p class="caption mt-2">Minimum 8 characters required</p>
          </div>
          
          <button 
            type="submit" 
            class="btn btn-primary btn-lg w-full hover-lift"
          >
            <i data-lucide="user-plus" class="w-5 h-5"></i>
            <span>Create Account</span>
          </button>
          
          <div class="text-center body-sm text-gray-600">
            Already have an account? 
            <button 
              type="button" 
              id="showLoginBtn" 
              class="toggle-text hover:underline ml-1"
            >
              Sign in here
            </button>
          </div>
      </form>
    </div>
  </div>

  <script src="assets/js/modern-ui.js"></script>
  <script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Toggle between login and signup forms with enhanced animations
    const showSignupBtn = document.getElementById('showSignupBtn');
    const showLoginBtn = document.getElementById('showLoginBtn');
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    
    function showSignup() {
      loginForm.style.transform = 'translateX(-100%)';
      loginForm.style.opacity = '0';
      
      setTimeout(() => {
        loginForm.classList.add('hidden');
        signupForm.classList.remove('hidden');
        signupForm.style.transform = 'translateX(100%)';
        signupForm.style.opacity = '0';
        
        setTimeout(() => {
          signupForm.style.transform = 'translateX(0)';
          signupForm.style.opacity = '1';
        }, 50);
      }, 300);
    }
    
    function showLogin() {
      signupForm.style.transform = 'translateX(100%)';
      signupForm.style.opacity = '0';
      
      setTimeout(() => {
        signupForm.classList.add('hidden');
        loginForm.classList.remove('hidden');
        loginForm.style.transform = 'translateX(-100%)';
        loginForm.style.opacity = '0';
        
        setTimeout(() => {
          loginForm.style.transform = 'translateX(0)';
          loginForm.style.opacity = '1';
        }, 50);
      }, 300);
    }
    
    showSignupBtn.addEventListener('click', showSignup);
    showLoginBtn.addEventListener('click', showLogin);
    
    // Enhanced role selection with visual feedback
    const studentRole = document.getElementById('studentRole');
    const adviserRole = document.getElementById('adviserRole');
    const studentFields = document.getElementById('studentFields');
    const adviserFields = document.getElementById('adviserFields');
    const studentLabel = document.querySelector('label[for="studentRole"]');
    const adviserLabel = document.querySelector('label[for="adviserRole"]');
    
    function updateRoleSelection() {
      if (studentRole.checked) {
        studentLabel.classList.add('selected');
        adviserLabel.classList.remove('selected');
        studentFields.classList.remove('hidden');
        adviserFields.classList.add('hidden');
        
        // Clear adviser fields
        document.getElementById('facultyId').value = '';
        document.getElementById('department').value = '';
        
        // Animate fields
        studentFields.style.opacity = '0';
        studentFields.style.transform = 'translateY(20px)';
        setTimeout(() => {
          studentFields.style.opacity = '1';
          studentFields.style.transform = 'translateY(0)';
        }, 100);
      } else {
        adviserLabel.classList.add('selected');
        studentLabel.classList.remove('selected');
        studentFields.classList.add('hidden');
        adviserFields.classList.remove('hidden');
        
        // Clear student fields
        document.getElementById('studentId').value = '';
        document.getElementById('program').value = '';
        
        // Animate fields
        adviserFields.style.opacity = '0';
        adviserFields.style.transform = 'translateY(20px)';
        setTimeout(() => {
          adviserFields.style.opacity = '1';
          adviserFields.style.transform = 'translateY(0)';
        }, 100);
      }
    }
    
    studentRole.addEventListener('change', updateRoleSelection);
    adviserRole.addEventListener('change', updateRoleSelection);
    
    // Add form validation feedback
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
      form.addEventListener('submit', function(e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && submitBtn.setLoading) {
          submitBtn.setLoading(true);
          
          // Reset loading state after a delay (for demo purposes)
          setTimeout(() => {
            submitBtn.setLoading(false);
          }, 2000);
        }
      });
    });
    
    // Add input focus animations
    const inputs = document.querySelectorAll('.form-input');
    inputs.forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
        this.style.boxShadow = '0 10px 25px rgba(59, 130, 246, 0.15)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = '';
        this.style.boxShadow = '';
      });
    });
    
    // Initialize theme from localStorage if available
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      document.documentElement.setAttribute('data-theme', savedTheme);
    }
  </script>
</body>
</html> 