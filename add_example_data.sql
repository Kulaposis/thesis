-- Add example data for adviser and student assignment
-- For manual import into MySQL

-- Insert adviser with email advisers@example.com
INSERT INTO `users` (
  `email`, 
  `password`, 
  `full_name`, 
  `role`, 
  `faculty_id`, 
  `department`
) VALUES (
  'advisers@example.com', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is the hashed password 'password'
  'Dr. Example Adviser', 
  'adviser', 
  'FAC123', 
  'Computer Science'
);

-- Get the ID of the newly inserted adviser
SET @adviser_id = LAST_INSERT_ID();

-- Insert student with email students@example.com and ID number 6
-- Note: The student_id field in the users table is a VARCHAR that stores the student's ID number
INSERT INTO `users` (
  `email`, 
  `password`, 
  `full_name`, 
  `role`, 
  `student_id`, 
  `program`
) VALUES (
  'students@example.com', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is the hashed password 'password'
  'Example Student', 
  'student', 
  '6', -- Student ID number as requested
  'Information Technology'
);

-- Get the ID of the newly inserted student
SET @student_id = LAST_INSERT_ID();

-- Insert additional sample students assigned to the same adviser
INSERT INTO `users` (
  `email`, 
  `password`, 
  `full_name`, 
  `role`, 
  `student_id`, 
  `program`
) VALUES
  ('student1@example.com', 
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
   'John Smith', 
   'student', 
   '101', 
   'Computer Science'),
  ('student2@example.com', 
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
   'Maria Garcia', 
   'student', 
   '102', 
   'Information Technology'),
  ('student3@example.com', 
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
   'Ahmed Hassan', 
   'student', 
   '103', 
   'Computer Engineering');

-- Store IDs of additional students for later use
SET @student_id1 = LAST_INSERT_ID();
SET @student_id2 = @student_id1 + 1;
SET @student_id3 = @student_id1 + 2;

-- Create a thesis for the student and assign the adviser
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
  'Example Thesis Title', 
  'This is an example abstract for the thesis project. It describes the research topic and methodology in brief.', 
  'in_progress', 
  25
);

-- Get the ID of the newly inserted thesis
SET @thesis_id = LAST_INSERT_ID();

-- Create theses for additional students with the same adviser
INSERT INTO `theses` (
  `student_id`, 
  `adviser_id`, 
  `title`, 
  `abstract`, 
  `status`, 
  `progress_percentage`
) VALUES
  (@student_id1, 
   @adviser_id, 
   'Machine Learning Applications in Healthcare', 
   'This research explores how machine learning algorithms can be applied to improve healthcare outcomes and patient diagnosis.', 
   'in_progress', 
   40),
  (@student_id2, 
   @adviser_id, 
   'Cybersecurity Framework for IoT Devices', 
   'A comprehensive study on developing a security framework for Internet of Things devices in smart home environments.', 
   'for_review', 
   65),
  (@student_id3, 
   @adviser_id, 
   'Blockchain-based Supply Chain Management', 
   'Investigating the implementation of blockchain technology to enhance transparency and efficiency in supply chain management.', 
   'draft', 
   15);

-- Store thesis IDs for additional students
SET @thesis_id1 = LAST_INSERT_ID();
SET @thesis_id2 = @thesis_id1 + 1;
SET @thesis_id3 = @thesis_id1 + 2;

-- Create some initial chapters for the thesis
INSERT INTO `chapters` (
  `thesis_id`, 
  `chapter_number`, 
  `title`, 
  `status`
) VALUES
  (@thesis_id, 1, 'Introduction', 'submitted'),
  (@thesis_id, 2, 'Literature Review', 'draft'),
  (@thesis_id, 3, 'Methodology', 'draft');

-- Create chapters for additional students' theses
INSERT INTO `chapters` (
  `thesis_id`, 
  `chapter_number`, 
  `title`, 
  `status`
) VALUES
  (@thesis_id1, 1, 'Introduction', 'approved'),
  (@thesis_id1, 2, 'Literature Review', 'approved'),
  (@thesis_id1, 3, 'Methodology', 'submitted'),
  (@thesis_id1, 4, 'Implementation', 'draft'),
  
  (@thesis_id2, 1, 'Introduction', 'approved'),
  (@thesis_id2, 2, 'Background', 'approved'),
  (@thesis_id2, 3, 'System Design', 'approved'),
  (@thesis_id2, 4, 'Implementation', 'submitted'),
  (@thesis_id2, 5, 'Evaluation', 'draft'),
  
  (@thesis_id3, 1, 'Introduction', 'submitted'),
  (@thesis_id3, 2, 'Literature Review', 'draft');

-- Add a timeline entry for the thesis
INSERT INTO `timeline` (
  `thesis_id`, 
  `milestone_name`, 
  `description`, 
  `due_date`, 
  `status`
) VALUES (
  @thesis_id, 
  'Proposal Defense', 
  'Initial defense of the thesis proposal', 
  DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), 
  'pending'
);

-- Add timeline entries for additional students' theses
INSERT INTO `timeline` (
  `thesis_id`, 
  `milestone_name`, 
  `description`, 
  `due_date`, 
  `status`
) VALUES
  (@thesis_id1, 'Proposal Defense', 'Initial defense of the thesis proposal', DATE_ADD(CURRENT_DATE, INTERVAL -60 DAY), 'completed'),
  (@thesis_id1, 'Midterm Presentation', 'Present progress and preliminary findings', DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY), 'in_progress'),
  (@thesis_id1, 'Final Defense', 'Final thesis defense', DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY), 'pending'),
  
  (@thesis_id2, 'Proposal Defense', 'Initial defense of the thesis proposal', DATE_ADD(CURRENT_DATE, INTERVAL -90 DAY), 'completed'),
  (@thesis_id2, 'Midterm Presentation', 'Present progress and preliminary findings', DATE_ADD(CURRENT_DATE, INTERVAL -30 DAY), 'completed'),
  (@thesis_id2, 'Final Defense', 'Final thesis defense', DATE_ADD(CURRENT_DATE, INTERVAL 20 DAY), 'in_progress'),
  
  (@thesis_id3, 'Proposal Defense', 'Initial defense of the thesis proposal', DATE_ADD(CURRENT_DATE, INTERVAL 45 DAY), 'pending');

-- Add a notification for the student
INSERT INTO `notifications` (
  `user_id`, 
  `title`, 
  `message`, 
  `type`
) VALUES (
  @student_id, 
  'Adviser Assigned', 
  'Dr. Example Adviser has been assigned as your thesis adviser.', 
  'info'
);

-- Add a notification for the adviser
INSERT INTO `notifications` (
  `user_id`, 
  `title`, 
  `message`, 
  `type`
) VALUES (
  @adviser_id, 
  'New Student Assigned', 
  'You have been assigned as the adviser for Example Student.', 
  'info'
);

-- Add notifications for additional students and adviser interactions
INSERT INTO `notifications` (
  `user_id`, 
  `title`, 
  `message`, 
  `type`
) VALUES
  (@student_id1, 'Feedback Available', 'Your adviser has provided feedback on your methodology chapter.', 'info'),
  (@student_id2, 'Chapter Approved', 'Your implementation chapter has been approved by your adviser.', 'success'),
  (@student_id2, 'Defense Scheduled', 'Your final defense has been scheduled for next month.', 'info'),
  (@student_id3, 'Deadline Reminder', 'Your proposal defense is due in 45 days.', 'warning'),
  
  (@adviser_id, 'Chapter Submitted', 'John Smith has submitted Chapter 3 for review.', 'info'),
  (@adviser_id, 'Defense Preparation', 'Maria Garcia\'s final defense is scheduled in 20 days.', 'info'),
  (@adviser_id, 'New Thesis Draft', 'Ahmed Hassan has started working on a new thesis draft.', 'info'); 