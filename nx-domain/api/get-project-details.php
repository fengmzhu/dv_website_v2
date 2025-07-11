<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Project identifier is required');
    }
    
    $identifier = $_GET['id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Try to find project by ID first, then by project name or task_index
    $project = null;
    
    // Try by numeric ID first
    if (is_numeric($identifier)) {
        $stmt = $db->prepare("SELECT * FROM to_summary_view WHERE id = ? LIMIT 1");
        $stmt->execute([$identifier]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // If not found by ID, try by project name or task_index
    if (!$project) {
        $stmt = $db->prepare("SELECT * FROM to_summary_view WHERE project = ? OR task_index = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$project) {
        throw new Exception('Project not found');
    }
    
    // Add formatted timestamps
    if ($project['to_date']) {
        $project['to_date_formatted'] = date('M j, Y', strtotime($project['to_date']));
    }
    if ($project['rtl_last_update']) {
        $project['rtl_last_update_formatted'] = date('M j, Y g:i A', strtotime($project['rtl_last_update']));
    }
    if ($project['to_report_creation']) {
        $project['to_report_creation_formatted'] = date('M j, Y g:i A', strtotime($project['to_report_creation']));
    }
    
    echo json_encode([
        'success' => true,
        'project' => $project
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>