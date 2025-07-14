-- Add missing columns to users table for profile functionality
USE thesis_management;

-- Add specialization and bio columns to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS specialization VARCHAR(255) NULL AFTER department,
ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER specialization;

-- Verify the columns were added
DESCRIBE users;

-- Show sample of updated table structure
SELECT 
    id, email, full_name, role, faculty_id, department, specialization, bio 
FROM users 
LIMIT 3; 