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
    
    // Use the same query structure as the main NX domain page
    $to_summary_sql = "
        SELECT 
            COALESCE(it.project_name, cr.project_name, vc.project_name) AS project,
            it.project_name,
            it.spip_ip,
            it.ip,
            it.ip_postfix,
            it.ip_subtype,
            it.alternative_name,
            it.task_index,
            cr.line_coverage,
            cr.fsm_coverage,
            cr.interface_toggle_coverage,
            cr.toggle_coverage,
            cr.coverage_report_path,
            it.dv_engineer,
            it.digital_designer,
            it.business_unit,
            it.analog_designer,
            it.inherit_from_ip,
            it.reuse_ip,
            vc.sanity_svn,
            vc.sanity_svn_ver,
            vc.release_svn,
            vc.release_svn_ver,
            vc.git_path,
            vc.git_version,
            vc.golden_checklist,
            vc.golden_checklist_version,
            cr.to_date,
            cr.rtl_last_update,
            cr.to_report_creation,
            it.spip_url,
            it.wiki_url,
            it.spec_version,
            it.spec_path
        FROM imported_it_data it
        LEFT JOIN coverage_reports cr ON it.project_name = cr.project_name
        LEFT JOIN version_control vc ON it.project_name = vc.project_name
        WHERE COALESCE(it.project_name, cr.project_name, vc.project_name) = ?
        LIMIT 1
    ";
    
    $stmt = $db->prepare($to_summary_sql);
    $stmt->execute([$identifier]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
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