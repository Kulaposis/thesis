-- Create Admin User SQL Script
-- This script creates a new admin user for the Thesis Management System

USE `thesis_management`;

-- --------------------------------------------------------
-- Create new admin user
-- --------------------------------------------------------

-- First, check if the user already exists
SELECT COUNT(*) as user_exists FROM users WHERE email = 'admins@edu.ph';

-- Insert the new admin user
INSERT INTO `users` (
    `email`, 
    `password`, 
    `full_name`, 
    `role`, 
    `faculty_id`, 
    `department`, 
    `created_at`, 
    `updated_at`
) VALUES (
    'admins@edu.ph',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is the hash for '12345678'
    'System Administrator',
    'super_admin',
    'ADMIN002',
    'Information Technology',
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `role` = VALUES(`role`),
    `updated_at` = NOW();

-- --------------------------------------------------------
-- Verify the user was created
-- --------------------------------------------------------

SELECT 
    id,
    email,
    full_name,
    role,
    faculty_id,
    department,
    created_at
FROM users 
WHERE email = 'admins@edu.ph';

-- --------------------------------------------------------
-- Show all admin users
-- --------------------------------------------------------

SELECT 
    id,
    email,
    full_name,
    role,
    created_at
FROM users 
WHERE role IN ('admin', 'super_admin')
ORDER BY created_at DESC;

-- --------------------------------------------------------
-- Login Credentials:
-- Email: admins@edu.ph
-- Password: 12345678
-- Role: super_admin
-- -------------------------------------------------------- 