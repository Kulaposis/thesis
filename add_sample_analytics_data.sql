-- Sample data for testing analytics charts in the admin dashboard
-- This file adds sample data if tables exist but are empty

-- Add sample users for different departments
INSERT IGNORE INTO users (id, email, password, full_name, role, department, student_id, faculty_id, program, created_at) VALUES
(101, 'john.cs@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', 'student', 'Computer Science', 'CS2021001', NULL, 'BS Computer Science', '2024-01-15 10:00:00'),
(102, 'jane.cs@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Doe', 'student', 'Computer Science', 'CS2021002', NULL, 'BS Computer Science', '2024-01-20 10:00:00'),
(103, 'mike.it@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Johnson', 'student', 'Information Technology', 'IT2021001', NULL, 'BS Information Technology', '2024-02-01 10:00:00'),
(104, 'sarah.it@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Wilson', 'student', 'Information Technology', 'IT2021002', NULL, 'BS Information Technology', '2024-02-05 10:00:00'),
(105, 'david.eng@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Brown', 'student', 'Engineering', 'ENG2021001', NULL, 'BS Engineering', '2024-02-10 10:00:00'),
(106, 'emma.bus@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma Davis', 'student', 'Business', 'BUS2021001', NULL, 'BS Business Administration', '2024-02-15 10:00:00'),
(107, 'prof.smith@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Robert Smith', 'adviser', 'Computer Science', NULL, 'FAC001', NULL, '2023-08-01 09:00:00'),
(108, 'prof.johnson@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Lisa Johnson', 'adviser', 'Information Technology', NULL, 'FAC002', NULL, '2023-08-01 09:00:00'),
(109, 'prof.brown@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Michael Brown', 'adviser', 'Engineering', NULL, 'FAC003', NULL, '2023-08-01 09:00:00'),
(110, 'prof.davis@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Emily Davis', 'adviser', 'Business', NULL, 'FAC004', NULL, '2023-08-01 09:00:00');

-- Add sample theses
INSERT IGNORE INTO theses (id, student_id, adviser_id, title, abstract, status, progress_percentage, created_at, updated_at) VALUES
(101, 101, 107, 'Machine Learning Applications in Healthcare', 'This thesis explores the use of machine learning algorithms in healthcare diagnosis and treatment recommendation systems.', 'in_progress', 75.5, '2024-01-20 10:00:00', '2024-06-15 10:00:00'),
(102, 102, 107, 'Blockchain Technology for Secure Data Management', 'A comprehensive study on implementing blockchain technology for secure and transparent data management systems.', 'in_progress', 68.3, '2024-01-25 10:00:00', '2024-06-10 10:00:00'),
(103, 103, 108, 'Cloud Computing Security Framework', 'Development of a comprehensive security framework for cloud computing environments.', 'in_progress', 82.7, '2024-02-05 10:00:00', '2024-06-12 10:00:00'),
(104, 104, 108, 'Mobile Application Development Best Practices', 'A study on modern mobile application development methodologies and best practices.', 'in_progress', 91.2, '2024-02-10 10:00:00', '2024-06-18 10:00:00'),
(105, 105, 109, 'Sustainable Engineering Solutions', 'Research on sustainable engineering practices and their environmental impact.', 'in_progress', 58.9, '2024-02-15 10:00:00', '2024-06-08 10:00:00'),
(106, 106, 110, 'Digital Marketing Analytics', 'Analysis of digital marketing strategies using big data analytics.', 'approved', 100.0, '2024-02-20 10:00:00', '2024-06-20 10:00:00');

-- Add sample chapters
INSERT IGNORE INTO chapters (id, thesis_id, chapter_number, title, status, submitted_at, created_at) VALUES
(101, 101, 1, 'Introduction', 'approved', '2024-03-01 10:00:00', '2024-02-28 10:00:00'),
(102, 101, 2, 'Literature Review', 'approved', '2024-04-01 10:00:00', '2024-03-28 10:00:00'),
(103, 101, 3, 'Methodology', 'submitted', '2024-05-01 10:00:00', '2024-04-28 10:00:00'),
(104, 102, 1, 'Introduction', 'approved', '2024-03-05 10:00:00', '2024-03-02 10:00:00'),
(105, 102, 2, 'Literature Review', 'submitted', '2024-04-05 10:00:00', '2024-04-02 10:00:00'),
(106, 103, 1, 'Introduction', 'approved', '2024-03-10 10:00:00', '2024-03-07 10:00:00'),
(107, 103, 2, 'Literature Review', 'approved', '2024-04-10 10:00:00', '2024-04-07 10:00:00'),
(108, 103, 3, 'Methodology', 'approved', '2024-05-10 10:00:00', '2024-05-07 10:00:00'),
(109, 104, 1, 'Introduction', 'approved', '2024-03-15 10:00:00', '2024-03-12 10:00:00'),
(110, 104, 2, 'Literature Review', 'approved', '2024-04-15 10:00:00', '2024-04-12 10:00:00'),
(111, 104, 3, 'Methodology', 'approved', '2024-05-15 10:00:00', '2024-05-12 10:00:00'),
(112, 104, 4, 'Results and Discussion', 'submitted', '2024-06-15 10:00:00', '2024-06-12 10:00:00');

-- Add sample feedback
INSERT IGNORE INTO feedback (id, chapter_id, adviser_id, feedback_text, feedback_type, created_at) VALUES
(101, 101, 107, 'Great introduction! Well structured and clearly written. Please add more recent references.', 'general', '2024-03-02 14:00:00'),
(102, 102, 107, 'Excellent literature review. Very comprehensive coverage of the topic.', 'general', '2024-04-02 14:00:00'),
(103, 103, 107, 'The methodology section needs more detail on the experimental setup. Please revise.', 'revision_required', '2024-05-02 14:00:00'),
(104, 104, 107, 'Good introduction but needs better organization of the main points.', 'general', '2024-03-06 14:00:00'),
(105, 106, 108, 'Very good introduction. Clear problem statement and objectives.', 'general', '2024-03-11 14:00:00'),
(106, 107, 108, 'Literature review is thorough and well-organized.', 'general', '2024-04-11 14:00:00'),
(107, 108, 108, 'Methodology is well-designed and appropriate for the research objectives.', 'general', '2024-05-11 14:00:00');

-- Add some additional students for better analytics
INSERT IGNORE INTO users (id, email, password, full_name, role, department, student_id, program, created_at) VALUES
(111, 'alex.cs1@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alex Chen', 'student', 'Computer Science', 'CS2021003', 'BS Computer Science', '2024-03-01 10:00:00'),
(112, 'maria.cs2@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Garcia', 'student', 'Computer Science', 'CS2021004', 'BS Computer Science', '2024-03-05 10:00:00'),
(113, 'tom.it1@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tom Wilson', 'student', 'Information Technology', 'IT2021003', 'BS Information Technology', '2024-03-10 10:00:00'),
(114, 'lisa.it2@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa Anderson', 'student', 'Information Technology', 'IT2021004', 'BS Information Technology', '2024-03-15 10:00:00'),
(115, 'james.eng1@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James Rodriguez', 'student', 'Engineering', 'ENG2021002', 'BS Engineering', '2024-03-20 10:00:00'),
(116, 'anna.bus1@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna Thompson', 'student', 'Business', 'BUS2021002', 'BS Business Administration', '2024-03-25 10:00:00'),
(117, 'peter.edu1@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Peter Lee', 'student', 'Education', 'EDU2021001', 'BS Education', '2024-04-01 10:00:00');

-- Add more theses for better analytics
INSERT IGNORE INTO theses (id, student_id, adviser_id, title, status, progress_percentage, created_at, updated_at) VALUES
(107, 111, 107, 'Artificial Intelligence in Education', 'in_progress', 45.0, '2024-03-05 10:00:00', '2024-06-01 10:00:00'),
(108, 112, 107, 'Cybersecurity Frameworks', 'in_progress', 62.5, '2024-03-10 10:00:00', '2024-06-05 10:00:00'),
(109, 113, 108, 'Internet of Things Security', 'in_progress', 38.7, '2024-03-15 10:00:00', '2024-05-30 10:00:00'),
(110, 114, 108, 'Big Data Analytics Platform', 'in_progress', 73.2, '2024-03-20 10:00:00', '2024-06-08 10:00:00'),
(111, 115, 109, 'Renewable Energy Systems', 'in_progress', 55.8, '2024-03-25 10:00:00', '2024-06-02 10:00:00'),
(112, 116, 110, 'E-commerce Strategy Analysis', 'in_progress', 67.4, '2024-03-30 10:00:00', '2024-06-06 10:00:00'),
(113, 117, 110, 'Educational Technology Impact', 'in_progress', 42.1, '2024-04-05 10:00:00', '2024-05-28 10:00:00');

-- Display a summary of inserted data
SELECT 'Sample data inserted successfully!' as message;
SELECT 
    'Users by Role' as summary,
    role,
    COUNT(*) as count 
FROM users 
WHERE id >= 101 
GROUP BY role;

SELECT 
    'Users by Department' as summary,
    department,
    COUNT(*) as count 
FROM users 
WHERE id >= 101 AND department IS NOT NULL 
GROUP BY department;

SELECT 
    'Theses by Status' as summary,
    status,
    COUNT(*) as count 
FROM theses 
WHERE id >= 101 
GROUP BY status; 