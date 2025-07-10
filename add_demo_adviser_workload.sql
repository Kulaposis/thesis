-- Add demo advisers
INSERT INTO users (full_name, role) VALUES
  ('Dr. Alice Adviser', 'adviser'),
  ('Prof. Bob Adviser', 'adviser');

-- Get their IDs (adjust if your DB does not support LAST_INSERT_ID for multiple rows)
SET @adviser1 = (SELECT id FROM users WHERE full_name = 'Dr. Alice Adviser' ORDER BY id DESC LIMIT 1);
SET @adviser2 = (SELECT id FROM users WHERE full_name = 'Prof. Bob Adviser' ORDER BY id DESC LIMIT 1);

-- Add demo students assigned to advisers
INSERT INTO students (full_name, adviser_id) VALUES
  ('Student One', @adviser1),
  ('Student Two', @adviser1),
  ('Student Three', @adviser2); 