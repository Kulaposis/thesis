<?php
// Simple test script
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "Testing function includes...\n";

require_once 'includes/auth.php';
echo "Auth included\n";

if (function_exists('isLoggedIn')) {
    echo "isLoggedIn function exists\n";
    echo "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n";
} else {
    echo "isLoggedIn function does NOT exist\n";
}

if (function_exists('isAdmin')) {
    echo "isAdmin function exists\n";
    echo "isAdmin(): " . (isAdmin() ? 'true' : 'false') . "\n";
} else {
    echo "isAdmin function does NOT exist\n";
}
?> 