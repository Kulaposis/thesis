<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->query('SELECT COUNT(*) as count FROM users');
$count = $stmt->fetch()['count'];
echo "User count: $count\n";

$stmt = $conn->query('SELECT id, email, full_name, role FROM users LIMIT 5');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Users:\n";
foreach ($users as $user) {
    echo "- ID: {$user['id']}, Email: {$user['email']}, Name: {$user['full_name']}, Role: {$user['role']}\n";
}
?> 