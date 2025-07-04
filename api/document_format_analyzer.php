<?php
/**
 * Document Format Analyzer API
 * Analyzes Word documents for thesis formatting compliance
 * Checks margins, fonts, spacing, headers, page numbers, etc.
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/auth.php';

// Require login
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Only advisers can analyze documents
if ($_SESSION['role'] !== 'adviser') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only advisers can analyze documents']);
    exit;
}

$user = $auth->getCurrentUser();
$file_id = $_GET['file_id'] ?? null;

if (!$file_id) {
    echo json_encode(['success' => false, 'error' => 'File ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get file information and verify access
    $sql = "SELECT f.*, c.thesis_id, t.adviser_id 
            FROM file_uploads f
            JOIN chapters c ON f.chapter_id = c.id
            JOIN theses t ON c.thesis_id = t.id
            WHERE f.id = :file_id AND t.adviser_id = :adviser_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id);
    $stmt->bindParam(':adviser_id', $user['id']);
    $stmt->execute();
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        echo json_encode(['success' => false, 'error' => 'File not found or access denied']);
        exit;
    }
    
    // Check if file exists
    if (!file_exists($file['file_path'])) {
        echo json_encode(['success' => false, 'error' => 'File not found on server']);
        exit;
    }
    
    // Analyze the document
    $analysis = analyzeDocumentFormat($file);
    
    echo json_encode([
        'success' => true,
        'file_info' => [
            'id' => $file['id'],
            'name' => $file['original_filename'],
            'size' => filesize($file['file_path']),
            'type' => $file['file_type']
        ],
        'analysis' => $analysis
    ]);
    
} catch (Exception $e) {
    error_log("Document analysis error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Analysis failed: ' . $e->getMessage()]);
}

/**
 * Basic document analysis for all file types
 */
function analyzeDocumentFormat($file) {
    $filePath = $file['file_path'];
    $fileName = strtolower($file['original_filename']);
    $fileSize = filesize($filePath);
    
    // Initialize analysis result
    $analysis = [
        'overall_score' => 0,
        'compliance_level' => 'poor',
        'total_issues' => 0,
        'critical_issues' => 0,
        'warnings' => 0,
        'categories' => []
    ];
    
    // Basic file analysis for all types
    $analysis = addBasicFileAnalysis($file, $analysis);
    
    // Check file type and perform specific analysis
    if (strpos($fileName, '.docx') !== false) {
        if (class_exists('ZipArchive')) {
            $analysis = analyzeDocxFormat($filePath, $analysis);
        } else {
            $analysis = addServerLimitationAnalysis($analysis);
        }
    } elseif (strpos($fileName, '.doc') !== false) {
        $analysis = analyzeDocFormat($filePath, $analysis);
    } else {
        $analysis['categories']['file_type'] = [
            'category' => 'File Type',
            'score' => 40,
            'status' => 'warning',
            'message' => 'Limited analysis for this file format',
            'issues' => ['Only .doc and .docx files can be fully analyzed'],
            'recommendations' => ['Convert file to .docx format for comprehensive formatting analysis']
        ];
    }
    
    // Calculate overall compliance
    $analysis = calculateOverallCompliance($analysis);
    
    return $analysis;
}

/**
 * Add basic file analysis
 */
function addBasicFileAnalysis($file, $analysis) {
    $fileSize = filesize($file['file_path']);
    $fileSizeMB = round($fileSize / (1024 * 1024), 2);
    
    $issues = [];
    $recommendations = [];
    $score = 100;
    
    // Check file size
    if ($fileSize > 50 * 1024 * 1024) { // > 50MB
        $issues[] = 'Very large file size (' . $fileSizeMB . 'MB) - may cause performance issues';
        $recommendations[] = 'Consider optimizing images and media to reduce file size';
        $score -= 15;
    } elseif ($fileSize > 20 * 1024 * 1024) { // > 20MB
        $issues[] = 'Large file size (' . $fileSizeMB . 'MB) - consider optimization';
        $recommendations[] = 'Optimize images while maintaining quality';
        $score -= 5;
    }
    
    // Check if file is too small (may indicate minimal content)
    if ($fileSize < 100 * 1024) { // < 100KB
        $issues[] = 'Small file size (' . $fileSizeMB . 'MB) - may indicate minimal content';
        $recommendations[] = 'Ensure document contains adequate content for thesis requirements';
        $score -= 10;
    }
    
    $status = $score >= 80 ? 'good' : ($score >= 60 ? 'warning' : 'error');
    
    $analysis['categories']['file_properties'] = [
        'category' => 'File Properties',
        'score' => max(0, $score),
        'status' => $status,
        'message' => count($issues) === 0 ? 'File properties are acceptable' : 'File property issues found',
        'issues' => $issues,
        'recommendations' => $recommendations,
        'details' => [
            'file_size_mb' => $fileSizeMB,
            'file_name' => $file['original_filename'],
            'file_type' => $file['file_type']
        ]
    ];
    
    return $analysis;
}

/**
 * Simple DOCX analysis
 */
function analyzeDocxFormat($filePath, $analysis) {
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== TRUE) {
        $analysis['categories']['file_integrity'] = [
            'category' => 'File Integrity',
            'score' => 0,
            'status' => 'error',
            'message' => 'Unable to open document file',
            'issues' => ['File may be corrupted or password protected'],
            'recommendations' => ['Re-upload the document', 'Ensure file is not corrupted']
        ];
        return $analysis;
    }
    
    // Basic analysis
    $analysis = analyzeBasicStructure($zip, $analysis);
    $analysis = analyzePageSetup($zip, $analysis);
    $analysis = analyzeDocumentElements($zip, $analysis);
    
    $zip->close();
    return $analysis;
}

/**
 * Analyze basic document structure
 */
function analyzeBasicStructure($zip, $analysis) {
    $documentXml = $zip->getFromName('word/document.xml');
    if (!$documentXml) {
        $analysis['categories']['structure'] = [
            'category' => 'Document Structure',
            'score' => 0,
            'status' => 'error',
            'message' => 'Cannot read document structure',
            'issues' => ['Document XML not found'],
            'recommendations' => ['Re-save document in Word format']
        ];
        return $analysis;
    }
    
    $dom = new DOMDocument();
    $dom->loadXML($documentXml);
    
    $issues = [];
    $recommendations = [];
    $score = 100;
    $details = [];
    
    // Count basic elements
    $paragraphs = $dom->getElementsByTagName('p');
    $tables = $dom->getElementsByTagName('tbl');
    
    $details['paragraph_count'] = $paragraphs->length;
    $details['table_count'] = $tables->length;
    
    // Check for adequate content
    if ($paragraphs->length < 20) {
        $issues[] = 'Limited content detected (' . $paragraphs->length . ' paragraphs)';
        $recommendations[] = 'Ensure document contains substantial content for thesis standards';
        $score -= 20;
    }
    
    $status = $score >= 80 ? 'good' : ($score >= 60 ? 'warning' : 'error');
    
    $analysis['categories']['structure'] = [
        'category' => 'Document Structure',
        'score' => max(0, $score),
        'status' => $status,
        'message' => count($issues) === 0 ? 'Document structure adequate' : 'Structure issues found',
        'issues' => $issues,
        'recommendations' => $recommendations,
        'details' => $details
    ];
    
    return $analysis;
}

/**
 * Analyze page setup (simplified)
 */
function analyzePageSetup($zip, $analysis) {
    $documentXml = $zip->getFromName('word/document.xml');
    if (!$documentXml) {
        return $analysis;
    }
    
    $dom = new DOMDocument();
    $dom->loadXML($documentXml);
    
    $issues = [];
    $recommendations = [];
    $score = 100;
    $details = [];
    
    $sectPrs = $dom->getElementsByTagName('sectPr');
    
    foreach ($sectPrs as $sectPr) {
        // Check margins
        $pgMar = $sectPr->getElementsByTagName('pgMar')->item(0);
        if ($pgMar) {
            $margins = [
                'top' => intval($pgMar->getAttribute('w:top')) / 20,
                'right' => intval($pgMar->getAttribute('w:right')) / 20,
                'bottom' => intval($pgMar->getAttribute('w:bottom')) / 20,
                'left' => intval($pgMar->getAttribute('w:left')) / 20
            ];
            
            $details['margins_inches'] = [
                'top' => round($margins['top'] / 72, 2),
                'right' => round($margins['right'] / 72, 2),
                'bottom' => round($margins['bottom'] / 72, 2),
                'left' => round($margins['left'] / 72, 2)
            ];
            
            // Check margins (1 inch = 72 points)
            $minMargin = 65; // Allow small tolerance
            
            foreach ($margins as $side => $margin) {
                if ($margin < $minMargin) {
                    $issues[] = ucfirst($side) . ' margin too small (' . round($margin/72, 2) . '") - minimum 1" required';
                    $recommendations[] = 'Set minimum 1-inch margins on all sides';
                    $score -= 20;
                }
            }
        }
    }
    
    $status = $score >= 80 ? 'good' : ($score >= 60 ? 'warning' : 'error');
    
    $analysis['categories']['margins'] = [
        'category' => 'Margins & Layout',
        'score' => max(0, $score),
        'status' => $status,
        'message' => count($issues) === 0 ? 'Margins meet requirements' : 'Margin issues found',
        'issues' => $issues,
        'recommendations' => $recommendations,
        'details' => $details
    ];
    
    return $analysis;
}

/**
 * Analyze document elements
 */
function analyzeDocumentElements($zip, $analysis) {
    $issues = [];
    $recommendations = [];
    $score = 100;
    $details = [];
    
    // Check for headers/footers
    $headerFooterCount = 0;
    $hasPageNumbers = false;
    
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        if (strpos($filename, 'word/header') !== false || strpos($filename, 'word/footer') !== false) {
            $headerFooterCount++;
            
            $xml = $zip->getFromName($filename);
            if ($xml && (strpos($xml, 'PAGE') !== false || strpos($xml, 'fldChar') !== false)) {
                $hasPageNumbers = true;
            }
        }
    }
    
    $details['header_footer_files'] = $headerFooterCount;
    $details['has_page_numbers'] = $hasPageNumbers;
    
    if (!$hasPageNumbers) {
        $issues[] = 'Page numbers not detected';
        $recommendations[] = 'Add page numbers (required for thesis documents)';
        $score -= 25;
    }
    
    // Check for images
    $imageCount = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        if (strpos($filename, 'word/media/') !== false) {
            $imageCount++;
        }
    }
    
    $details['image_count'] = $imageCount;
    
    $status = $score >= 80 ? 'good' : ($score >= 60 ? 'warning' : 'error');
    
    $analysis['categories']['elements'] = [
        'category' => 'Document Elements',
        'score' => max(0, $score),
        'status' => $status,
        'message' => count($issues) === 0 ? 'Essential elements present' : 'Missing required elements',
        'issues' => $issues,
        'recommendations' => $recommendations,
        'details' => $details
    ];
    
    return $analysis;
}

/**
 * Server limitation analysis
 */
function addServerLimitationAnalysis($analysis) {
    $analysis['categories']['limitation'] = [
        'category' => 'Analysis Limitation',
        'score' => 60,
        'status' => 'warning',
        'message' => 'Limited formatting analysis available',
        'issues' => ['Server configuration limits detailed DOCX analysis'],
        'recommendations' => [
            'Manual formatting review recommended',
            'Check margins: 1-inch minimum',
            'Check font: Times New Roman 12pt recommended',
            'Check spacing: Double spacing for body text',
            'Ensure page numbers are present'
        ]
    ];
    
    return $analysis;
}

/**
 * DOC format analysis
 */
function analyzeDocFormat($filePath, $analysis) {
    $analysis['categories']['format'] = [
        'category' => 'File Format',
        'score' => 70,
        'status' => 'warning',
        'message' => 'Legacy Word format detected',
        'issues' => ['DOC format limits analysis capabilities'],
        'recommendations' => [
            'Convert to DOCX format for better analysis',
            'Manually verify: 1-inch margins',
            'Manually verify: Times New Roman 12pt font',
            'Manually verify: Double spacing',
            'Manually verify: Page numbers present'
        ]
    ];
    
    return $analysis;
}

/**
 * Calculate overall compliance
 */
function calculateOverallCompliance($analysis) {
    $totalScore = 0;
    $categoryCount = 0;
    $totalIssues = 0;
    $criticalIssues = 0;
    $warnings = 0;
    
    foreach ($analysis['categories'] as $category) {
        $totalScore += $category['score'];
        $categoryCount++;
        
        $issueCount = count($category['issues']);
        $totalIssues += $issueCount;
        
        if ($category['status'] === 'error') {
            $criticalIssues += $issueCount;
        } elseif ($category['status'] === 'warning') {
            $warnings += $issueCount;
        }
    }
    
    $overallScore = $categoryCount > 0 ? round($totalScore / $categoryCount) : 0;
    
    if ($overallScore >= 85) {
        $complianceLevel = 'excellent';
    } elseif ($overallScore >= 75) {
        $complianceLevel = 'good';
    } elseif ($overallScore >= 60) {
        $complianceLevel = 'fair';
    } else {
        $complianceLevel = 'poor';
    }
    
    $analysis['overall_score'] = $overallScore;
    $analysis['compliance_level'] = $complianceLevel;
    $analysis['total_issues'] = $totalIssues;
    $analysis['critical_issues'] = $criticalIssues;
    $analysis['warnings'] = $warnings;
    
    return $analysis;
}
?> 