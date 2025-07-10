<?php
// Increase memory limit and execution time for document processing
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 60);

session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/thesis_functions.php';

// Enhanced error reporting
error_reporting(E_ALL);

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Function to send JSON response and exit
function sendResponse($data) {
    echo json_encode($data);
    exit;
}

// Function to log errors
function logError($message) {
    error_log("[Document Extractor] " . $message);
}

try {
    // Require login with timeout check
    $auth = new Auth();
    
    // Check if session is still valid
    if (!isset($_SESSION['user_id'])) {
        logError("Session expired or user not logged in");
        sendResponse(['success' => false, 'error' => 'Session expired. Please refresh the page and login again.']);
    }
    
    $auth->requireLogin();

    // Get current user
    $user = $auth->getCurrentUser();
    if (!$user) {
        logError("Failed to get current user");
        sendResponse(['success' => false, 'error' => 'Failed to verify user session. Please refresh and try again.']);
    }
    
    $thesisManager = new ThesisManager();

} catch (Exception $e) {
    logError("Auth error: " . $e->getMessage());
    sendResponse(['success' => false, 'error' => 'Authentication error. Please refresh the page and try again.']);
}

function extractWordContent($filePath) {
    $content = '';
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    // Debug: Log file path and extension
    error_log("Extracting content from: $filePath, Extension: $fileExtension");
    
    try {
        if ($fileExtension === 'docx') {
            // Extract from DOCX file
            if (!class_exists('ZipArchive')) {
                error_log("ZipArchive class not available");
                return ['success' => false, 'error' => 'ZipArchive extension not available'];
            }
            
            $zip = new ZipArchive();
            $result = $zip->open($filePath);
            
            if ($result === TRUE) {
                $xmlContent = $zip->getFromName('word/document.xml');
                if ($xmlContent !== false) {
                    if (!class_exists('DOMDocument')) {
                        error_log("DOMDocument class not available");
                        return ['success' => false, 'error' => 'DOMDocument extension not available'];
                    }
                    
                    $xml = new DOMDocument();
                    $loadResult = $xml->loadXML($xmlContent);
                    
                    if (!$loadResult) {
                        error_log("Failed to load XML content");
                        return ['success' => false, 'error' => 'Failed to parse document XML'];
                    }
                    
                    // Remove all XML tags and get plain text
                    $content = strip_tags($xml->textContent);
                    
                    // Clean up whitespace
                    $content = preg_replace('/\s+/', ' ', $content);
                    $content = trim($content);
                    
                    error_log("Extracted content length: " . strlen($content));
                    
                    // Split into paragraphs (basic approach)
                    $paragraphs = explode('. ', $content);
                    $structuredContent = [];
                    
                    foreach ($paragraphs as $index => $paragraph) {
                        if (!empty(trim($paragraph))) {
                            $structuredContent[] = [
                                'id' => 'para_' . ($index + 1),
                                'type' => 'paragraph',
                                'content' => trim($paragraph) . (substr(trim($paragraph), -1) !== '.' ? '.' : ''),
                                'level' => 0
                            ];
                        }
                    }
                    
                    error_log("Created " . count($structuredContent) . " paragraphs");
                    
                    return [
                        'success' => true,
                        'content' => $structuredContent,
                        'raw_text' => $content
                    ];
                } else {
                    error_log("Could not extract word/document.xml from zip file");
                    return ['success' => false, 'error' => 'Could not extract document content from DOCX file'];
                }
                $zip->close();
            } else {
                error_log("Failed to open zip file. Error code: $result");
                return ['success' => false, 'error' => 'Failed to open DOCX file as ZIP archive'];
            }
        } elseif ($fileExtension === 'doc') {
            // For older DOC files, we'll need a different approach
            // This is a simplified extraction that may not work perfectly
            if (!file_exists($filePath)) {
                error_log("DOC file does not exist: $filePath");
                return ['success' => false, 'error' => 'DOC file not found'];
            }
            
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                error_log("Failed to read DOC file content");
                return ['success' => false, 'error' => 'Failed to read DOC file'];
            }
            
            // Try to extract readable text (this is very basic)
            $content = preg_replace('/[^\x20-\x7E]/', ' ', $content);
            $content = preg_replace('/\s+/', ' ', $content);
            $content = trim($content);
            
            error_log("DOC content length after cleaning: " . strlen($content));
            
            if (!empty($content)) {
                $paragraphs = explode('. ', $content);
                $structuredContent = [];
                
                foreach ($paragraphs as $index => $paragraph) {
                    if (!empty(trim($paragraph)) && strlen(trim($paragraph)) > 10) {
                        $structuredContent[] = [
                            'id' => 'para_' . ($index + 1),
                            'type' => 'paragraph',
                            'content' => trim($paragraph) . (substr(trim($paragraph), -1) !== '.' ? '.' : ''),
                            'level' => 0
                        ];
                    }
                }
                
                error_log("DOC: Created " . count($structuredContent) . " paragraphs");
                
                return [
                    'success' => true,
                    'content' => $structuredContent,
                    'raw_text' => $content
                ];
            } else {
                return ['success' => false, 'error' => 'No readable content found in DOC file'];
            }
        } else {
            error_log("Unsupported file extension: $fileExtension");
            return ['success' => false, 'error' => 'Unsupported file format'];
        }
        
        return [
            'success' => false,
            'error' => 'Unable to extract content from this document format'
        ];
        
    } catch (Exception $e) {
        error_log("Exception in extractWordContent: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error processing document: ' . $e->getMessage()
        ];
    }
}

// Check if file ID is provided
if (!isset($_GET['file_id']) || empty($_GET['file_id'])) {
    sendResponse(['success' => false, 'error' => 'File ID is required']);
}

$file_id = intval($_GET['file_id']);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get file information
    $sql = "SELECT f.*, c.thesis_id, t.student_id 
            FROM file_uploads f
            JOIN chapters c ON f.chapter_id = c.id
            JOIN theses t ON c.thesis_id = t.id
            WHERE f.id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id);
    $stmt->execute();
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        sendResponse(['success' => false, 'error' => 'File not found']);
    }
    
    // Check permissions
    $is_owner = ($user['role'] == 'student' && $file['student_id'] == $user['id']);
    
    // For advisers, check if they are assigned to this thesis
    $is_adviser = false;
    if ($user['role'] == 'adviser') {
        $sql = "SELECT id FROM theses WHERE id = :thesis_id AND adviser_id = :adviser_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':thesis_id', $file['thesis_id']);
        $stmt->bindParam(':adviser_id', $user['id']);
        $stmt->execute();
        $is_adviser = ($stmt->rowCount() > 0);
    }
    
    $is_admin = ($user['role'] == 'admin');
    
    if (!$is_owner && !$is_adviser && !$is_admin) {
        sendResponse(['success' => false, 'error' => 'You do not have permission to access this file']);
    }
    
    // Check if file exists
    if (!file_exists($file['file_path'])) {
        sendResponse(['success' => false, 'error' => 'File not found on server']);
    }
    
    // Check if it's a Word document
    $fileType = $file['file_type'];
    $fileName = strtolower($file['original_filename']);
    
    // Debug: Log the file type and name
    error_log("Word Viewer Debug - File Type: " . $fileType . ", File Name: " . $fileName);
    
    // Check by MIME type and file extension (same logic as in loadChapter)
    $isWordByMimeType = $fileType === 'application/msword' || 
                        $fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ||
                        strpos($fileType, 'word') !== false || 
                        strpos($fileType, 'document') !== false;
    
    $isWordByExtension = substr($fileName, -4) === '.doc' || substr($fileName, -5) === '.docx';
    
    // Debug: Log the detection results
    error_log("Word Viewer Debug - MIME Type Match: " . ($isWordByMimeType ? 'true' : 'false') . ", Extension Match: " . ($isWordByExtension ? 'true' : 'false'));
    
    if (!$isWordByMimeType && !$isWordByExtension) {
        sendResponse(['success' => false, 'error' => 'This file is not a Word document. Detected type: ' . $fileType]);
    }
    
    // Check if ZipArchive is available for DOCX files
    $fileExtension = strtolower(pathinfo($file['original_filename'], PATHINFO_EXTENSION));
    if ($fileExtension === 'docx' && !class_exists('ZipArchive')) {
        // Provide a fallback response when ZipArchive is not available
        sendResponse([
            'success' => true,
            'file_info' => [
                'id' => $file['id'],
                'name' => $file['original_filename'],
                'type' => $file['file_type'],
                'uploaded_at' => $file['uploaded_at']
            ],
            'content' => [
                [
                    'id' => 'para_1',
                    'type' => 'paragraph',
                    'content' => 'Word document viewer is not fully available on this server (ZipArchive extension missing).',
                    'level' => 0
                ],
                [
                    'id' => 'para_2',
                    'type' => 'paragraph',
                    'content' => 'File: ' . $file['original_filename'],
                    'level' => 0
                ],
                [
                    'id' => 'para_3',
                    'type' => 'paragraph',
                    'content' => 'Size: ' . (file_exists($file['file_path']) ? number_format(filesize($file['file_path'])) . ' bytes' : 'Unknown'),
                    'level' => 0
                ],
                [
                    'id' => 'para_4',
                    'type' => 'paragraph',
                    'content' => 'To view this document, please download it using the download button.',
                    'level' => 0
                ]
            ],
            'raw_text' => 'Word document content extraction not available due to missing server extension.',
            'server_limitation' => true
        ]);
    }
    
    // Extract content
    $result = extractWordContent($file['file_path']);
    
    if ($result['success']) {
        sendResponse([
            'success' => true,
            'file_info' => [
                'id' => $file['id'],
                'name' => $file['original_filename'],
                'type' => $file['file_type'],
                'uploaded_at' => $file['uploaded_at']
            ],
            'content' => $result['content'],
            'raw_text' => $result['raw_text']
        ]);
    } else {
        sendResponse($result);
    }
    
} catch (PDOException $e) {
    logError("Database error: " . $e->getMessage());
    sendResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    logError("General error: " . $e->getMessage());
    sendResponse(['success' => false, 'error' => 'Error processing document: ' . $e->getMessage()]);
}
?> 