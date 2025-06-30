-- Add sample students under the supervision of the existing adviser with email advisers@example.com
-- For manual import into MySQL

-- First, get the ID of the existing adviser
SET @adviser_id = (SELECT id FROM `users` WHERE `email` = 'advisers@example.com' LIMIT 1);

-- Insert sample students to be supervised by the adviser
INSERT INTO `users` (
  `email`, 
  `password`, 
  `full_name`, 
  `role`, 
  `student_id`, 
  `program`
) VALUES
  ('student1@example.com', 
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is the hashed password 'password'
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

-- Store IDs of the inserted students for later use
SET @student_id1 = LAST_INSERT_ID();
SET @student_id2 = @student_id1 + 1;
SET @student_id3 = @student_id1 + 2;

-- Create theses for the students and assign them to the existing adviser
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

-- Store thesis IDs for the students
SET @thesis_id1 = LAST_INSERT_ID();
SET @thesis_id2 = @thesis_id1 + 1;
SET @thesis_id3 = @thesis_id1 + 2;

-- Create chapters for the students' theses
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

-- Add timeline entries for the students' theses
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

-- Add notifications for the students and adviser interactions
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