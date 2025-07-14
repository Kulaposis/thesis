-- Create table for adviser custom format requirements
USE thesis_management;

-- Create format_requirements table
CREATE TABLE IF NOT EXISTS `format_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adviser_id` int(11) NOT NULL,
  `requirement_type` varchar(50) NOT NULL,
  `requirement_key` varchar(100) NOT NULL,
  `requirement_value` text NOT NULL,
  `requirement_unit` varchar(20) DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_adviser_requirement` (`adviser_id`, `requirement_type`, `requirement_key`),
  KEY `adviser_id` (`adviser_id`),
  KEY `requirement_type` (`requirement_type`),
  FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default format requirements for all advisers
INSERT IGNORE INTO `format_requirements` (`adviser_id`, `requirement_type`, `requirement_key`, `requirement_value`, `requirement_unit`, `is_enabled`)
SELECT 
    id as adviser_id,
    'margins' as requirement_type,
    'top' as requirement_key,
    '1.0' as requirement_value,
    'inches' as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'margins' as requirement_type,
    'bottom' as requirement_key,
    '1.0' as requirement_value,
    'inches' as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'margins' as requirement_type,
    'left' as requirement_key,
    '1.0' as requirement_value,
    'inches' as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'margins' as requirement_type,
    'right' as requirement_key,
    '1.0' as requirement_value,
    'inches' as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'typography' as requirement_type,
    'font_family' as requirement_key,
    'Times New Roman' as requirement_value,
    NULL as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'typography' as requirement_type,
    'font_size' as requirement_key,
    '12' as requirement_value,
    'pt' as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'spacing' as requirement_type,
    'line_spacing' as requirement_key,
    '2.0' as requirement_value,
    'lines' as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'spacing' as requirement_type,
    'paragraph_spacing' as requirement_key,
    '0' as requirement_value,
    'pt' as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'page_setup' as requirement_type,
    'page_numbers' as requirement_key,
    'required' as requirement_value,
    NULL as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'page_setup' as requirement_type,
    'header_footer' as requirement_key,
    'optional' as requirement_value,
    NULL as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'structure' as requirement_type,
    'title_page' as requirement_key,
    'required' as requirement_value,
    NULL as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser'

UNION ALL

SELECT 
    id as adviser_id,
    'structure' as requirement_type,
    'table_of_contents' as requirement_key,
    'required' as requirement_value,
    NULL as requirement_unit,
    1 as is_enabled
FROM users WHERE role = 'adviser';

-- Show the inserted requirements
SELECT 
    u.full_name as adviser_name,
    fr.requirement_type,
    fr.requirement_key,
    fr.requirement_value,
    fr.requirement_unit,
    fr.is_enabled
FROM format_requirements fr
JOIN users u ON fr.adviser_id = u.id
WHERE u.role = 'adviser'
ORDER BY u.full_name, fr.requirement_type, fr.requirement_key
LIMIT 20; 