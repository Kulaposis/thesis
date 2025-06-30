-- Add specific student and assign them to the existing adviser with email advisers@example.com
-- For manual import into MySQL

-- First, get the ID of the existing adviser
SET @adviser_id = (SELECT id FROM `users` WHERE `email` = 'advisers@example.com' LIMIT 1);

-- Insert the specific student
INSERT INTO `users` (
  `email`, 
  `password`, 
  `full_name`, 
  `role`, 
  `student_id`, 
  `program`
) VALUES (
  'students@example.com', 
  '$2y$10$aed1zqtB9mfzO6nlbmSsyev7/AG396vnAN4uu/1dti6ZdGQ6BBIE.', -- Provided password hash
  'Dave Aban', 
  'student', 
  '22-0585-676', -- Specific student ID as requested
  'Computer Science'
);

-- Store ID of the inserted student for later use
SET @student_id = LAST_INSERT_ID();

-- Create a thesis for the student and assign them to the existing adviser
INSERT INTO `theses` (
  `student_id`, 
  `adviser_id`, 
  `title`, 
  `abstract`, 
  `status`, 
  `progress_percentage`
) VALUES (
  @student_id, 
  @adviser_id, 
  'Advanced Machine Learning Techniques for Natural Language Processing', 
  'This thesis explores innovative machine learning approaches to improve natural language processing tasks such as sentiment analysis, named entity recognition, and machine translation.', 
  'in_progress', 
  35
);

-- Store thesis ID
SET @thesis_id = LAST_INSERT_ID();

-- Create chapters for the student's thesis
INSERT INTO `chapters` (
  `thesis_id`, 
  `chapter_number`, 
  `title`, 
  `status`
) VALUES
  (@thesis_id, 1, 'Introduction', 'approved'),
  (@thesis_id, 2, 'Literature Review', 'submitted'),
  (@thesis_id, 3, 'Methodology', 'draft'),
  (@thesis_id, 4, 'Implementation Plan', 'draft');

-- Add timeline entries for the student's thesis
INSERT INTO `timeline` (
  `thesis_id`, 
  `milestone_name`, 
  `description`, 
  `due_date`, 
  `status`
) VALUES
  (@thesis_id, 'Proposal Defense', 'Initial defense of the thesis proposal', DATE_ADD(CURRENT_DATE, INTERVAL -30 DAY), 'completed'),
  (@thesis_id, 'Chapter 1-2 Submission', 'Submit introduction and literature review', DATE_ADD(CURRENT_DATE, INTERVAL -7 DAY), 'completed'),
  (@thesis_id, 'Methodology Presentation', 'Present research methodology to adviser', DATE_ADD(CURRENT_DATE, INTERVAL 21 DAY), 'pending'),
  (@thesis_id, 'Final Defense', 'Final thesis defense', DATE_ADD(CURRENT_DATE, INTERVAL 120 DAY), 'pending');

-- Add notifications for the student and adviser
INSERT INTO `notifications` (
  `user_id`, 
  `title`, 
  `message`, 
  `type`
) VALUES
  (@student_id, 'Adviser Assigned', 'Your thesis adviser has been assigned. Please schedule an initial meeting.', 'info'),
  (@student_id, 'Chapter 1 Approved', 'Your Introduction chapter has been approved by your adviser.', 'success'),
  (@student_id, 'Feedback Available', 'Your adviser has provided feedback on your Literature Review chapter.', 'info'),
  
  (@adviser_id, 'New Student Assigned', 'Dave Aban has been assigned as your advisee.', 'info'),
  (@adviser_id, 'Chapter Submitted', 'Dave Aban has submitted the Literature Review chapter for review.', 'info'); 