<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Initialize auth and get current user
$auth = new Auth();
$user = $auth->getCurrentUser();

if (!$user || $user['role'] !== 'adviser') {
    echo json_encode(['success' => false, 'error' => 'Only advisers can manage format requirements']);
    exit;
}

$db = new Database();
$pdo = $db->getConnection();

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_requirements':
            getAdviserRequirements($pdo, $user['id']);
            break;
            
        case 'save_requirements':
            saveAdviserRequirements($pdo, $user['id'], $_POST);
            break;
            
        case 'reset_to_defaults':
            resetToDefaults($pdo, $user['id']);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Format requirements error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to process request: ' . $e->getMessage()]);
}

/**
 * Get adviser's format requirements
 */
function getAdviserRequirements($pdo, $adviserId) {
    $sql = "SELECT requirement_type, requirement_key, requirement_value, requirement_unit, is_enabled 
            FROM format_requirements 
            WHERE adviser_id = ? 
            ORDER BY requirement_type, requirement_key";
    
    $stmt = $pdo->prepare($sql);
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
            'enabled' => (bool)$row['is_enabled']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'requirements' => $requirements
    ]);
}

/**
 * Save adviser's format requirements
 */
function saveAdviserRequirements($pdo, $adviserId, $data) {
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Parse the requirements data
        $requirements = [
            // Margins
            'margins' => [
                'top' => ['value' => $data['margin_top'] ?? '1.0', 'unit' => 'inches', 'enabled' => isset($data['enable_margin_top'])],
                'bottom' => ['value' => $data['margin_bottom'] ?? '1.0', 'unit' => 'inches', 'enabled' => isset($data['enable_margin_bottom'])],
                'left' => ['value' => $data['margin_left'] ?? '1.0', 'unit' => 'inches', 'enabled' => isset($data['enable_margin_left'])],
                'right' => ['value' => $data['margin_right'] ?? '1.0', 'unit' => 'inches', 'enabled' => isset($data['enable_margin_right'])]
            ],
            // Typography
            'typography' => [
                'font_family' => ['value' => $data['font_family'] ?? 'Times New Roman', 'unit' => null, 'enabled' => isset($data['enable_font_family'])],
                'font_size' => ['value' => $data['font_size'] ?? '12', 'unit' => 'pt', 'enabled' => isset($data['enable_font_size'])],
                'font_style' => ['value' => $data['font_style'] ?? 'normal', 'unit' => null, 'enabled' => isset($data['enable_font_style'])]
            ],
            // Spacing
            'spacing' => [
                'line_spacing' => ['value' => $data['line_spacing'] ?? '2.0', 'unit' => 'lines', 'enabled' => isset($data['enable_line_spacing'])],
                'paragraph_spacing' => ['value' => $data['paragraph_spacing'] ?? '0', 'unit' => 'pt', 'enabled' => isset($data['enable_paragraph_spacing'])],
                'indentation' => ['value' => $data['indentation'] ?? '0.5', 'unit' => 'inches', 'enabled' => isset($data['enable_indentation'])]
            ],
            // Page Setup
            'page_setup' => [
                'page_numbers' => ['value' => $data['page_numbers'] ?? 'required', 'unit' => null, 'enabled' => isset($data['enable_page_numbers'])],
                'header_footer' => ['value' => $data['header_footer'] ?? 'optional', 'unit' => null, 'enabled' => isset($data['enable_header_footer'])],
                'page_size' => ['value' => $data['page_size'] ?? 'A4', 'unit' => null, 'enabled' => isset($data['enable_page_size'])],
                'orientation' => ['value' => $data['orientation'] ?? 'portrait', 'unit' => null, 'enabled' => isset($data['enable_orientation'])]
            ],
            // Structure
            'structure' => [
                'title_page' => ['value' => $data['title_page'] ?? 'required', 'unit' => null, 'enabled' => isset($data['enable_title_page'])],
                'table_of_contents' => ['value' => $data['table_of_contents'] ?? 'required', 'unit' => null, 'enabled' => isset($data['enable_table_of_contents'])],
                'abstract' => ['value' => $data['abstract'] ?? 'required', 'unit' => null, 'enabled' => isset($data['enable_abstract'])],
                'bibliography' => ['value' => $data['bibliography'] ?? 'required', 'unit' => null, 'enabled' => isset($data['enable_bibliography'])]
            ]
        ];
        
        // Validate requirements
        foreach ($requirements as $type => $typeReqs) {
            foreach ($typeReqs as $key => $req) {
                // Validate numeric values
                if ($req['unit'] === 'inches' || $req['unit'] === 'pt' || $req['unit'] === 'lines') {
                    if (!is_numeric($req['value']) || floatval($req['value']) < 0) {
                        throw new Exception("Invalid value for {$type}.{$key}: must be a positive number");
                    }
                }
                
                // Validate font size
                if ($key === 'font_size') {
                    $fontSize = intval($req['value']);
                    if ($fontSize < 8 || $fontSize > 72) {
                        throw new Exception("Font size must be between 8 and 72 points");
                    }
                }
                
                // Validate margins
                if ($type === 'margins') {
                    $margin = floatval($req['value']);
                    if ($margin < 0.25 || $margin > 5.0) {
                        throw new Exception("Margins must be between 0.25 and 5.0 inches");
                    }
                }
            }
        }
        
        // Prepare SQL for INSERT ... ON DUPLICATE KEY UPDATE
        $sql = "INSERT INTO format_requirements (adviser_id, requirement_type, requirement_key, requirement_value, requirement_unit, is_enabled, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                requirement_value = VALUES(requirement_value), 
                requirement_unit = VALUES(requirement_unit), 
                is_enabled = VALUES(is_enabled), 
                updated_at = NOW()";
        
        $stmt = $pdo->prepare($sql);
        
        // Insert/update each requirement
        foreach ($requirements as $type => $typeReqs) {
            foreach ($typeReqs as $key => $req) {
                $result = $stmt->execute([
                    $adviserId,
                    $type,
                    $key,
                    $req['value'],
                    $req['unit'],
                    $req['enabled'] ? 1 : 0
                ]);
                
                if (!$result) {
                    throw new Exception("Failed to save requirement: {$type}.{$key}");
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Format requirements saved successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        throw $e;
    }
}

/**
 * Reset adviser's requirements to defaults
 */
function resetToDefaults($pdo, $adviserId) {
    $pdo->beginTransaction();
    
    try {
        // Delete current requirements
        $stmt = $pdo->prepare("DELETE FROM format_requirements WHERE adviser_id = ?");
        $stmt->execute([$adviserId]);
        
        // Insert default requirements
        $defaultRequirements = [
            ['margins', 'top', '1.0', 'inches', 1],
            ['margins', 'bottom', '1.0', 'inches', 1],
            ['margins', 'left', '1.0', 'inches', 1],
            ['margins', 'right', '1.0', 'inches', 1],
            ['typography', 'font_family', 'Times New Roman', null, 1],
            ['typography', 'font_size', '12', 'pt', 1],
            ['typography', 'font_style', 'normal', null, 1],
            ['spacing', 'line_spacing', '2.0', 'lines', 1],
            ['spacing', 'paragraph_spacing', '0', 'pt', 1],
            ['spacing', 'indentation', '0.5', 'inches', 0],
            ['page_setup', 'page_numbers', 'required', null, 1],
            ['page_setup', 'header_footer', 'optional', null, 0],
            ['page_setup', 'page_size', 'A4', null, 1],
            ['page_setup', 'orientation', 'portrait', null, 1],
            ['structure', 'title_page', 'required', null, 1],
            ['structure', 'table_of_contents', 'required', null, 1],
            ['structure', 'abstract', 'required', null, 1],
            ['structure', 'bibliography', 'required', null, 1]
        ];
        
        $sql = "INSERT INTO format_requirements (adviser_id, requirement_type, requirement_key, requirement_value, requirement_unit, is_enabled) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($defaultRequirements as $req) {
            $stmt->execute(array_merge([$adviserId], $req));
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Format requirements reset to defaults'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}
?> 