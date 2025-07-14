-- Create programs table for thesis management system
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) UNIQUE NOT NULL,
    program_name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    description TEXT NULL,
    duration_years INT DEFAULT 4,
    total_units INT DEFAULT 120,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some sample programs
INSERT INTO programs (program_code, program_name, department, description, duration_years, total_units) VALUES
('BSIT', 'Bachelor of Science in Information Technology', 'College of Computer Studies', 'A program focused on information technology, software development, and computer systems.', 4, 120),
('BSCS', 'Bachelor of Science in Computer Science', 'College of Computer Studies', 'A program focused on computer science, algorithms, and software engineering.', 4, 120),
('BSIS', 'Bachelor of Science in Information Systems', 'College of Computer Studies', 'A program focused on information systems, business processes, and technology management.', 4, 120),
('BSCE', 'Bachelor of Science in Civil Engineering', 'College of Engineering', 'A program focused on civil engineering, infrastructure, and construction.', 5, 150),
('BSEE', 'Bachelor of Science in Electrical Engineering', 'College of Engineering', 'A program focused on electrical engineering, electronics, and power systems.', 5, 150),
('BSME', 'Bachelor of Science in Mechanical Engineering', 'College of Engineering', 'A program focused on mechanical engineering, machines, and manufacturing.', 5, 150),
('BSBA', 'Bachelor of Science in Business Administration', 'College of Business', 'A program focused on business administration, management, and entrepreneurship.', 4, 120),
('BSA', 'Bachelor of Science in Accountancy', 'College of Business', 'A program focused on accountancy, auditing, and financial management.', 4, 120),
('BSN', 'Bachelor of Science in Nursing', 'College of Health Sciences', 'A program focused on nursing, healthcare, and patient care.', 4, 120),
('BSPharm', 'Bachelor of Science in Pharmacy', 'College of Health Sciences', 'A program focused on pharmacy, drug development, and pharmaceutical care.', 4, 120);

-- Add index for better performance
CREATE INDEX idx_programs_department ON programs(department);
CREATE INDEX idx_programs_active ON programs(is_active); 