<?php
require_once __DIR__ . '/../config/database.php';

class ThesisManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Get student's thesis
    public function getStudentThesis($student_id) {
        try {
            $sql = "SELECT t.*, u.full_name as adviser_name 
                    FROM theses t 
                    LEFT JOIN users u ON t.adviser_id = u.id 
                    WHERE t.student_id = :student_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get all theses for adviser
    public function getAdviserTheses($adviser_id) {
        try {
            $sql = "SELECT t.*, u.full_name as student_name, u.student_id, u.id as student_user_id, u.program 
                    FROM theses t 
                    JOIN users u ON t.student_id = u.id 
                    WHERE t.adviser_id = :adviser_id 
                    ORDER BY t.updated_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':adviser_id', $adviser_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get chapters for a thesis
    public function getThesisChapters($thesis_id) {
        try {
            $sql = "SELECT * FROM chapters WHERE thesis_id = :thesis_id ORDER BY chapter_number";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':thesis_id', $thesis_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get feedback for a chapter
    public function getChapterFeedback($chapter_id) {
        try {
            $sql = "SELECT f.*, u.full_name as adviser_name 
                    FROM feedback f 
                    JOIN users u ON f.adviser_id = u.id 
                    WHERE f.chapter_id = :chapter_id 
                    ORDER BY f.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Create new thesis
    public function createThesis($student_id, $title, $abstract = null) {
        try {
            $sql = "INSERT INTO theses (student_id, title, abstract) VALUES (:student_id, :title, :abstract)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':abstract', $abstract);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update thesis
    public function updateThesis($thesis_id, $data) {
        try {
            $sql = "UPDATE theses SET title = :title, abstract = :abstract, status = :status 
                    WHERE id = :thesis_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':thesis_id', $thesis_id);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':abstract', $data['abstract']);
            $stmt->bindParam(':status', $data['status']);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Create/Update chapter
    public function saveChapter($thesis_id, $chapter_number, $title, $content = null) {
        try {
            $sql = "INSERT INTO chapters (thesis_id, chapter_number, title, content) 
                    VALUES (:thesis_id, :chapter_number, :title, :content)
                    ON DUPLICATE KEY UPDATE title = :title, content = :content";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':thesis_id', $thesis_id);
            $stmt->bindParam(':chapter_number', $chapter_number);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $success = $stmt->execute();
            
            if ($success) {
                // Update thesis progress
                $this->updateProgress($thesis_id);
            }
            
            return $success;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Submit chapter for review
    public function submitChapter($chapter_id) {
        try {
            // Get thesis_id for this chapter
            $sql = "SELECT thesis_id FROM chapters WHERE id = :chapter_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $thesis_id = $result['thesis_id'] ?? null;
            
            if (!$thesis_id) {
                return false;
            }
            
            // Update chapter status
            $sql = "UPDATE chapters SET status = 'submitted', submitted_at = NOW() WHERE id = :chapter_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $success = $stmt->execute();
            
            if ($success) {
                // Update thesis progress
                $this->updateProgress($thesis_id);
            }
            
            return $success;
        } catch (PDOException $e) {
            return false;
        }
    }


    // DAVE DAVE DAE
    // Add feedback
    public function addFeedback($chapter_id, $adviser_id, $feedback_text, $feedback_type = 'comment') {
        try {
            // Log the parameters with more detail
            error_log("=== START addFeedback ===");
            error_log("Adding feedback - Chapter ID: $chapter_id (type: " . gettype($chapter_id) . ")");
            error_log("Adviser ID: $adviser_id (type: " . gettype($adviser_id) . ")");
            error_log("Feedback type: $feedback_type");
            error_log("Feedback text length: " . strlen($feedback_text));
            
            // Test database connection
            if (!$this->db) {
                error_log("Error: Database connection is null");
                return false;
            }
            
            // Verify the chapter exists with more details
            $sql = "SELECT id, thesis_id, title, status FROM chapters WHERE id = :chapter_id";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Error: Failed to prepare chapter lookup statement");
                return false;
            }
            
            $stmt->bindParam(':chapter_id', $chapter_id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Error executing chapter lookup: " . implode(", ", $stmt->errorInfo()));
                return false;
            }
            
            $chapterData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$chapterData) {
                error_log("Error: Chapter ID $chapter_id not found in database");
                return false;
            }
            
            error_log("Chapter found: " . json_encode($chapterData));
            
            // Verify the adviser exists - fixed query to properly check adviser role
            $sql = "SELECT id, full_name, role FROM users WHERE id = :adviser_id AND role = 'adviser'";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Error: Failed to prepare adviser lookup statement");
                return false;
            }
            
            $stmt->bindParam(':adviser_id', $adviser_id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Error executing adviser lookup: " . implode(", ", $stmt->errorInfo()));
                return false;
            }
            
            $adviserData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$adviserData) {
                error_log("Error: Adviser ID $adviser_id not found in database or not an adviser");
                return false;
            }
            
            error_log("Adviser found: " . json_encode($adviserData));
            
            // Validate feedback type
            $validTypes = ['comment', 'revision', 'approval'];
            if (!in_array($feedback_type, $validTypes)) {
                error_log("Error: Invalid feedback type '$feedback_type'. Valid types: " . implode(", ", $validTypes));
                return false;
            }
            
            // Insert the feedback
            $sql = "INSERT INTO feedback (chapter_id, adviser_id, feedback_text, feedback_type) 
                    VALUES (:chapter_id, :adviser_id, :feedback_text, :feedback_type)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Error: Failed to prepare feedback insert statement");
                return false;
            }
            
            $stmt->bindParam(':chapter_id', $chapter_id, PDO::PARAM_INT);
            $stmt->bindParam(':adviser_id', $adviser_id, PDO::PARAM_INT);
            $stmt->bindParam(':feedback_text', $feedback_text, PDO::PARAM_STR);
            $stmt->bindParam(':feedback_type', $feedback_type, PDO::PARAM_STR);
            
            error_log("Executing feedback insert query...");
            $result = $stmt->execute();
            
            if ($result) {
                $insertedId = $this->db->lastInsertId();
                error_log("Feedback added successfully with ID: $insertedId");
                error_log("=== END addFeedback SUCCESS ===");
                return true;
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Error executing feedback insert statement: " . implode(", ", $errorInfo));
                error_log("SQL State: " . $errorInfo[0] . ", Error Code: " . $errorInfo[1] . ", Message: " . $errorInfo[2]);
                error_log("=== END addFeedback FAILURE ===");
                return false;
            }
        } catch (PDOException $e) {
            error_log("PDOException in addFeedback: " . $e->getMessage());
            error_log("Error Code: " . $e->getCode());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("=== END addFeedback EXCEPTION ===");
            return false;
        } catch (Exception $e) {
            error_log("General Exception in addFeedback: " . $e->getMessage());
            error_log("=== END addFeedback GENERAL EXCEPTION ===");
            return false;
        }
    }

    // Approve/Reject chapter
    public function reviewChapter($chapter_id, $status, $feedback = null, $adviser_id = null) {
        try {
            $this->db->beginTransaction();
            
            // Get thesis_id for this chapter
            $sql = "SELECT thesis_id FROM chapters WHERE id = :chapter_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $thesis_id = $result['thesis_id'] ?? null;
            
            if (!$thesis_id) {
                $this->db->rollBack();
                return false;
            }
            
            // Update chapter status
            $sql = "UPDATE chapters SET status = :status";
            if ($status === 'approved') {
                $sql .= ", approved_at = NOW()";
            }
            $sql .= " WHERE id = :chapter_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->execute();

            // Add feedback if provided
            if ($feedback && $adviser_id) {
                $feedback_type = $status === 'approved' ? 'approval' : 'revision';
                $this->addFeedback($chapter_id, $adviser_id, $feedback, $feedback_type);
            }
            
            // Update thesis progress
            $this->updateProgress($thesis_id);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Get thesis timeline
    public function getThesisTimeline($thesis_id) {
        try {
            $sql = "SELECT * FROM timeline WHERE thesis_id = :thesis_id ORDER BY due_date";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':thesis_id', $thesis_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Update thesis progress
    public function updateProgress($thesis_id) {
        try {
            // Calculate progress based on approved chapters
            $sql = "SELECT COUNT(*) as total_chapters, 
                           COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_chapters,
                           COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted_chapters
                    FROM chapters WHERE thesis_id = :thesis_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':thesis_id', $thesis_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $progress = 0;
            if ($result['total_chapters'] > 0) {
                // Calculate progress: approved chapters are worth 100%, submitted chapters are worth 50%
                $progress = round((($result['approved_chapters'] + ($result['submitted_chapters'] * 0.5)) / $result['total_chapters']) * 100);
            }

            // Update thesis progress
            $sql = "UPDATE theses SET progress_percentage = :progress WHERE id = :thesis_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progress', $progress);
            $stmt->bindParam(':thesis_id', $thesis_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // File upload handling
    public function uploadFile($chapter_id, $file) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $stored_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $stored_filename;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            try {
                $sql = "INSERT INTO file_uploads (chapter_id, original_filename, stored_filename, file_path, file_size, file_type) 
                        VALUES (:chapter_id, :original_filename, :stored_filename, :file_path, :file_size, :file_type)";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':chapter_id', $chapter_id);
                $stmt->bindParam(':original_filename', $file['name']);
                $stmt->bindParam(':stored_filename', $stored_filename);
                $stmt->bindParam(':file_path', $file_path);
                $stmt->bindParam(':file_size', $file['size']);
                $stmt->bindParam(':file_type', $file['type']);
                
                if ($stmt->execute()) {
                    return ['success' => true, 'file_id' => $this->db->lastInsertId()];
                }
            } catch (PDOException $e) {
                unlink($file_path); // Remove uploaded file if database insert fails
                return ['success' => false, 'message' => 'Database error'];
            }
        }
        return ['success' => false, 'message' => 'File upload failed'];
    }

    // Get dashboard statistics
    public function getStudentStats($student_id) {
        try {
            $sql = "SELECT 
                        COUNT(c.id) as total_chapters,
                        COUNT(CASE WHEN c.status = 'submitted' THEN 1 END) as submitted_chapters,
                        COUNT(CASE WHEN c.status = 'approved' THEN 1 END) as approved_chapters,
                        COUNT(CASE WHEN c.status = 'rejected' THEN 1 END) as rejected_chapters,
                        t.progress_percentage
                    FROM theses t
                    LEFT JOIN chapters c ON t.id = c.thesis_id
                    WHERE t.student_id = :student_id
                    GROUP BY t.id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total_chapters' => 0,
                'submitted_chapters' => 0,
                'approved_chapters' => 0,
                'rejected_chapters' => 0,
                'progress_percentage' => 0
            ];
        } catch (PDOException $e) {
            return [
                'total_chapters' => 0,
                'submitted_chapters' => 0,
                'approved_chapters' => 0,
                'rejected_chapters' => 0,
                'progress_percentage' => 0
            ];
        }
    }

    public function getAdviserStats($adviser_id) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT t.id) as total_theses,
                        COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) as in_progress,
                        COUNT(CASE WHEN c.status = 'submitted' THEN 1 END) as for_review,
                        COUNT(CASE WHEN t.status = 'approved' THEN 1 END) as approved
                    FROM theses t
                    LEFT JOIN chapters c ON t.id = c.thesis_id
                    WHERE t.adviser_id = :adviser_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':adviser_id', $adviser_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total_theses' => 0,
                'in_progress' => 0,
                'for_review' => 0,
                'approved' => 0
            ];
        } catch (PDOException $e) {
            return [
                'total_theses' => 0,
                'in_progress' => 0,
                'for_review' => 0,
                'approved' => 0
            ];
        }
    }

    // Add highlight to document
    public function addHighlight($chapter_id, $adviser_id, $start_offset, $end_offset, $highlighted_text, $highlight_color = '#ffeb3b') {
        try {
            $sql = "INSERT INTO document_highlights (chapter_id, adviser_id, start_offset, end_offset, highlighted_text, highlight_color) 
                    VALUES (:chapter_id, :adviser_id, :start_offset, :end_offset, :highlighted_text, :highlight_color)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->bindParam(':adviser_id', $adviser_id);
            $stmt->bindParam(':start_offset', $start_offset);
            $stmt->bindParam(':end_offset', $end_offset);
            $stmt->bindParam(':highlighted_text', $highlighted_text);
            $stmt->bindParam(':highlight_color', $highlight_color);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Add comment to document
    public function addDocumentComment($chapter_id, $adviser_id, $comment_text, $highlight_id = null, $start_offset = null, $end_offset = null, $position_x = null, $position_y = null, $metadata = null) {
        try {
            $sql = "INSERT INTO document_comments (chapter_id, adviser_id, comment_text, highlight_id, start_offset, end_offset, position_x, position_y, metadata) 
                    VALUES (:chapter_id, :adviser_id, :comment_text, :highlight_id, :start_offset, :end_offset, :position_x, :position_y, :metadata)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->bindParam(':adviser_id', $adviser_id);
            $stmt->bindParam(':comment_text', $comment_text);
            $stmt->bindParam(':highlight_id', $highlight_id);
            $stmt->bindParam(':start_offset', $start_offset);
            $stmt->bindParam(':end_offset', $end_offset);
            $stmt->bindParam(':position_x', $position_x);
            $stmt->bindParam(':position_y', $position_y);
            $stmt->bindParam(':metadata', $metadata);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get highlights for a chapter
    public function getChapterHighlights($chapter_id) {
        try {
            $sql = "SELECT h.*, u.full_name as adviser_name 
                    FROM document_highlights h 
                    JOIN users u ON h.adviser_id = u.id 
                    WHERE h.chapter_id = :chapter_id 
                    ORDER BY h.start_offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get comments for a chapter
    public function getChapterComments($chapter_id) {
        try {
            $sql = "SELECT c.*, u.full_name as adviser_name, h.highlighted_text, c.metadata 
                    FROM document_comments c 
                    JOIN users u ON c.adviser_id = u.id 
                    LEFT JOIN document_highlights h ON c.highlight_id = h.id 
                    WHERE c.chapter_id = :chapter_id AND c.status = 'active'
                    ORDER BY c.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Remove highlight
    public function removeHighlight($highlight_id, $adviser_id) {
        try {
            $sql = "DELETE FROM document_highlights WHERE id = :highlight_id AND adviser_id = :adviser_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':highlight_id', $highlight_id);
            $stmt->bindParam(':adviser_id', $adviser_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Resolve comment
    public function resolveComment($comment_id, $adviser_id) {
        try {
            $sql = "UPDATE document_comments SET status = 'resolved' WHERE id = :comment_id AND adviser_id = :adviser_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':comment_id', $comment_id);
            $stmt->bindParam(':adviser_id', $adviser_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get chapter with its highlights and comments
    public function getChapterForReview($chapter_id) {
        try {
            $sql = "SELECT c.*, t.title as thesis_title, u.full_name as student_name 
                    FROM chapters c 
                    JOIN theses t ON c.thesis_id = t.id 
                    JOIN users u ON t.student_id = u.id 
                    WHERE c.id = :chapter_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->execute();
            
            $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($chapter) {
                $chapter['highlights'] = $this->getChapterHighlights($chapter_id);
                $chapter['comments'] = $this->getChapterComments($chapter_id);
                $chapter['files'] = $this->getChapterFiles($chapter_id);
            }
            
            return $chapter;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Update chapter's file path with the uploaded file
    public function updateChapterFilePath($chapter_id, $file_id) {
        try {
            $this->db->beginTransaction();
            
            // Get the file path from file_uploads table
            $sql = "SELECT file_path FROM file_uploads WHERE id = :file_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':file_id', $file_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Update the chapter's file_path field and set status to submitted
                $sql = "UPDATE chapters SET file_path = :file_path, status = 'submitted', submitted_at = NOW() WHERE id = :chapter_id";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':file_path', $result['file_path']);
                $stmt->bindParam(':chapter_id', $chapter_id);
                $success = $stmt->execute();
                
                if ($success) {
                    // Get thesis_id for this chapter to update progress
                    $sql = "SELECT thesis_id FROM chapters WHERE id = :chapter_id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':chapter_id', $chapter_id);
                    $stmt->execute();
                    $chapterData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($chapterData && isset($chapterData['thesis_id'])) {
                        // Update thesis progress
                        $this->updateProgress($chapterData['thesis_id']);
                    }
                    
                    $this->db->commit();
                    return true;
                }
            }
            
            $this->db->rollBack();
            return false;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    // Get uploaded files for a chapter
    public function getChapterFiles($chapter_id) {
        try {
            $sql = "SELECT * FROM file_uploads WHERE chapter_id = :chapter_id ORDER BY uploaded_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get chapter by ID
    public function getChapterById($chapter_id) {
        try {
            $sql = "SELECT * FROM chapters WHERE id = :chapter_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':chapter_id', $chapter_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get thesis by ID
    public function getThesisById($thesis_id) {
        try {
            $sql = "SELECT * FROM theses WHERE id = :thesis_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':thesis_id', $thesis_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get feedback by ID
    public function getFeedbackById($feedback_id) {
        try {
            $sql = "SELECT * FROM feedback WHERE id = :feedback_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':feedback_id', $feedback_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get all feedback for a student
    public function getStudentAllFeedback($student_id) {
        try {
            $sql = "SELECT f.*, c.title as chapter_title, c.chapter_number, u.full_name as adviser_name 
                    FROM feedback f 
                    JOIN chapters c ON f.chapter_id = c.id 
                    JOIN theses t ON c.thesis_id = t.id 
                    JOIN users u ON f.adviser_id = u.id 
                    WHERE t.student_id = :student_id 
                    ORDER BY f.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get all feedback given by an adviser
    public function getAdviserAllFeedback($adviser_id) {
        try {
            $sql = "SELECT f.*, c.title as chapter_title, c.chapter_number, 
                    u.full_name as student_name, t.title as thesis_title 
                    FROM feedback f 
                    JOIN chapters c ON f.chapter_id = c.id 
                    JOIN theses t ON c.thesis_id = t.id 
                    JOIN users u ON t.student_id = u.id 
                    WHERE f.adviser_id = :adviser_id 
                    ORDER BY f.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':adviser_id', $adviser_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Delete feedback
    public function deleteFeedback($feedback_id) {
        try {
            $sql = "DELETE FROM feedback WHERE id = :feedback_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':feedback_id', $feedback_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?> 