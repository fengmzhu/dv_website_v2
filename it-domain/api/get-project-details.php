<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid project ID provided');
    }
    
    $projectId = (int)$_GET['id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Get project details from existing schema
    $stmt = $db->prepare("SELECT p.*, dt.task_index, dt.dv_engineer, dt.digital_designer, dt.business_unit, dt.analog_designer, dt.inherit_from_ip, dt.reuse_ip 
                         FROM projects p 
                         LEFT JOIN dv_tasks dt ON p.id = dt.project_id 
                         WHERE p.id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        throw new Exception('Project not found');
    }
    
    // Add formatted timestamps
    if ($project['created_at']) {
        $project['created_at_formatted'] = date('M j, Y g:i A', strtotime($project['created_at']));
    }
    if ($project['updated_at']) {
        $project['updated_at_formatted'] = date('M j, Y g:i A', strtotime($project['updated_at']));
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