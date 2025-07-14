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
    
    // Get adviser's format requirements
    $requirements = getAdviserRequirements($db, $user['id']);
    
    // Analyze the document
    $analysis = analyzeDocumentFormat($file, $requirements);
    
    echo json_encode([
        'success' => true,
        'file_info' => [
            'id' => $file['id'],
            'name' => $file['original_filename'],
            'size' => filesize($file['file_path']),
            'type' => $file['file_type']
        ],
        'analysis' => $analysis,
        'requirements' => $requirements
    ]);
    
} catch (Exception $e) {
    error_log("Document analysis error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Analysis failed: ' . $e->getMessage()]);
}

/**
 * Get adviser's format requirements
 */
function getAdviserRequirements($db, $adviserId) {
    $sql = "SELECT requirement_type, requirement_key, requirement_value, requirement_unit, is_enabled 
            FROM format_requirements 
            WHERE adviser_id = ? AND is_enabled = 1
            ORDER BY requirement_type, requirement_key";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$adviserId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize requirements by type
    $requirements = [
        'margins' => [],
        'typography' => [],
        'spacing' => [],
        'page_setup' => [],
        'structure' => []
    ];
    
    foreach ($results as $row) {
        $requirements[$row['requirement_type']][$row['requirement_key']] = [
            'value' => $row['requirement_value'],
            'unit' => $row['requirement_unit'],
            'enabled' => true
        ];
    }
    
    return $requirements;
}

/**
 * Basic document analysis for all file types
 */
function analyzeDocumentFormat($file, $requirements = []) {
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
            $analysis = analyzeDocxFormat($filePath, $analysis, $requirements);
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
function analyzeDocxFormat($filePath, $analysis, $requirements = []) {
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
    $analysis = analyzeBasicStructure($zip, $analysis, $requirements);
    $analysis = analyzePageSetup($zip, $analysis, $requirements);
    $analysis = analyzeDocumentElements($zip, $analysis, $requirements);
    
    $zip->close();
    return $analysis;
}

/**
 * Analyze basic document structure
 */
function analyzeBasicStructure($zip, $analysis, $requirements = []) {
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
function analyzePageSetup($zip, $analysis, $requirements = []) {
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
            
            // Check margins using adviser's requirements
            foreach ($margins as $side => $margin) {
                $marginInches = $margin / 72;
                
                // Check if this side has a requirement
                if (isset($requirements['margins'][$side])) {
                    $requiredMargin = floatval($requirements['margins'][$side]['value']);
                    $tolerance = 0.05; // 0.05 inch tolerance
                    
                    if ($marginInches < ($requiredMargin - $tolerance)) {
                        $issues[] = ucfirst($side) . ' margin too small (' . round($marginInches, 2) . '") - minimum ' . $requiredMargin . '" required';
                        $recommendations[] = 'Set ' . $side . ' margin to at least ' . $requiredMargin . ' inches';
                        $score -= 20;
                    } elseif ($marginInches < $requiredMargin) {
                        $issues[] = ucfirst($side) . ' margin slightly below requirement (' . round($marginInches, 2) . '") - ' . $requiredMargin . '" preferred';
                        $recommendations[] = 'Consider adjusting ' . $side . ' margin to exactly ' . $requiredMargin . ' inches';
                        $score -= 5;
                    }
                } else {
                    // Use default 1-inch minimum if no requirement set
                    $minMargin = 65; // ~0.9 inches with tolerance
                    if ($margin < $minMargin) {
                        $issues[] = ucfirst($side) . ' margin too small (' . round($marginInches, 2) . '") - minimum 1" recommended';
                        $recommendations[] = 'Set minimum 1-inch margins on all sides';
                        $score -= 15;
                    }
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
function analyzeDocumentElements($zip, $analysis, $requirements = []) {
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
    
    // Check page numbers based on adviser requirements
    if (isset($requirements['page_setup']['page_numbers'])) {
        $pageNumberReq = $requirements['page_setup']['page_numbers']['value'];
        
        if ($pageNumberReq === 'required' && !$hasPageNumbers) {
            $issues[] = 'Page numbers are required but not detected';
            $recommendations[] = 'Add page numbers as required by your adviser';
            $score -= 25;
        } elseif ($pageNumberReq === 'forbidden' && $hasPageNumbers) {
            $issues[] = 'Page numbers found but are not allowed per requirements';
            $recommendations[] = 'Remove page numbers as specified by your adviser';
            $score -= 15;
        }
    } else {
        // Default behavior - recommend page numbers
        if (!$hasPageNumbers) {
            $issues[] = 'Page numbers not detected (recommended for thesis documents)';
            $recommendations[] = 'Consider adding page numbers for better document navigation';
            $score -= 10;
        }
    }
    
    // Check header/footer requirements
    if (isset($requirements['page_setup']['header_footer'])) {
        $headerFooterReq = $requirements['page_setup']['header_footer']['value'];
        
        if ($headerFooterReq === 'required' && $headerFooterCount === 0) {
            $issues[] = 'Headers/footers are required but not found';
            $recommendations[] = 'Add headers or footers as required by your adviser';
            $score -= 20;
        } elseif ($headerFooterReq === 'forbidden' && $headerFooterCount > 0) {
            $issues[] = 'Headers/footers found but are not allowed per requirements';
            $recommendations[] = 'Remove headers and footers as specified by your adviser';
            $score -= 15;
        }
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