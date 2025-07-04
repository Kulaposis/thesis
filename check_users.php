<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get all users
    $users = $pdo->query("SELECT id, full_name, email, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "All users in the system:\n";
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Name: {$user['full_name']}, Email: {$user['email']}, Role: {$user['role']}\n";
    }
    
    // Get adviser users specifically
    echo "\nAdviser users:\n";
    $advisers = $pdo->query("SELECT id, full_name, email FROM users WHERE role = 'adviser'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($advisers as $adviser) {
        echo "ID: {$adviser['id']}, Name: {$adviser['full_name']}, Email: {$adviser['email']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 