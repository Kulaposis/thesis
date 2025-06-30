-- Test data for adviser dashboard
-- For manual import into MySQL

-- Insert test adviser if not exists
INSERT INTO `users` (
  `email`, 
  `password`, 
  `full_name`, 
  `role`, 
  `faculty_id`, 
  `department`
) 
SELECT 
  'test_adviser@example.com', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is 'password'
  'Dr. Test Adviser', 
  'adviser', 
  'TEST001', 
  'Computer Science'
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `users` WHERE `email` = 'test_adviser@example.com'
);

-- Get the ID of the test adviser
SET @adviser_id = (SELECT id FROM `users` WHERE `email` = 'test_adviser@example.com' LIMIT 1);

-- Insert unassigned test students (not assigned to any adviser)
INSERT INTO `users` (
  `email`, 
  `password`, 
  `full_name`, 
  `role`, 
  `student_id`, 
  `program`
) 
SELECT 
  'unassigned1@example.com', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
  'Unassigned Student 1', 
  'student', 
  'U001', 
  'Computer Science'
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `users` WHERE `email` = 'unassigned1@example.com'
);

INSERT INTO `users` (
  `email`, 
  `password`, 
  `full_name`, 
  `role`, 
  `student_id`, 
  `program`
) 
SELECT 
  'unassigned2@example.com', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
  'Unassigned Student 2', 
  'student', 
  'U002', 
  'Information Technology'
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `users` WHERE `email` = 'unassigned2@example.com'
);

-- Insert a student already assigned to the test adviser
INSERT INTO `users` (
  `email`, 
  `password`, 
  `full_name`, 
  `role`, 
  `student_id`, 
  `program`
) 
SELECT 
  'assigned1@example.com', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
  'Assigned Student 1', 
  'student', 
  'A001', 
  'Computer Engineering'
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `users` WHERE `email` = 'assigned1@example.com'
);

-- Get the ID of the assigned student
SET @student_id = (SELECT id FROM `users` WHERE `email` = 'assigned1@example.com' LIMIT 1);

-- Create a thesis for the assigned student
INSERT INTO `theses` (
  `student_id`, 
  `adviser_id`, 
  `title`, 
  `abstract`, 
  `status`, 
  `progress_percentage`
)
SELECT
  @student_id, 
  @adviser_id, 
  'Test Thesis Title', 
  'This is a test thesis abstract for testing the adviser dashboard functionality.', 
  'in_progress', 
  25
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `theses` WHERE `student_id` = @student_id AND `adviser_id` = @adviser_id
);

-- Get the thesis ID
SET @thesis_id = (SELECT id FROM `theses` WHERE `student_id` = @student_id AND `adviser_id` = @adviser_id LIMIT 1);

-- Create chapters for the test thesis
INSERT INTO `chapters` (
  `thesis_id`, 
  `chapter_number`, 
  `title`, 
  `status`
)
SELECT
  @thesis_id, 1, 'Introduction', 'approved'
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `chapters` WHERE `thesis_id` = @thesis_id AND `chapter_number` = 1
);

INSERT INTO `chapters` (
  `thesis_id`, 
  `chapter_number`, 
  `title`, 
  `status`
)
SELECT
  @thesis_id, 2, 'Literature Review', 'submitted'
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `chapters` WHERE `thesis_id` = @thesis_id AND `chapter_number` = 2
);

INSERT INTO `chapters` (
  `thesis_id`, 
  `chapter_number`, 
  `title`, 
  `status`
)
SELECT
  @thesis_id, 3, 'Methodology', 'draft'
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `chapters` WHERE `thesis_id` = @thesis_id AND `chapter_number` = 3
);

-- Add notifications
INSERT INTO `notifications` (
  `user_id`, 
  `title`, 
  `message`, 
  `type`
)
VALUES
  (@adviser_id, 'Test Notification', 'This is a test notification for the adviser dashboard.', 'info'),
  (@student_id, 'Test Notification', 'This is a test notification for the student.', 'info');

-- Display login information
SELECT 'Test adviser login:' AS 'Info', 'test_adviser@example.com' AS 'Email', 'password' AS 'Password';
SELECT 'Unassigned student 1 login:' AS 'Info', 'unassigned1@example.com' AS 'Email', 'password' AS 'Password';
SELECT 'Unassigned student 2 login:' AS 'Info', 'unassigned2@example.com' AS 'Email', 'password' AS 'Password';
SELECT 'Assigned student login:' AS 'Info', 'assigned1@example.com' AS 'Email', 'password' AS 'Password'; 