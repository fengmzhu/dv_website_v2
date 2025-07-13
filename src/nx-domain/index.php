<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die('Database connection failed. Please check if the database service is running.');
}

$message = '';
$action = $_GET['action'] ?? 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_it_data']) && isset($_FILES['csv_file'])) {
        $upload_dir = 'imports/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = basename($_FILES['csv_file']['name']);
        $target_file = $upload_dir . time() . '_' . $file_name;
        
        if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $target_file)) {
            $csv_data = parseCSV($target_file);
            
            if (!empty($csv_data)) {
                $result = importITData($db, $csv_data);
                
                if ($result['success_count'] > 0) {
                    $message = showAlert(
                        "Import completed! {$result['success_count']} records imported successfully. " .
                        ($result['error_count'] > 0 ? "{$result['error_count']} errors occurred." : ""),
                        $result['error_count'] > 0 ? 'warning' : 'success'
                    );
                } else {
                    $message = showAlert('Import failed: ' . implode(', ', $result['errors']), 'danger');
                }
            } else {
                $message = showAlert('CSV file is empty or invalid format', 'danger');
            }
            
            unlink($target_file);
        } else {
            $message = showAlert('Failed to upload file', 'danger');
        }
    }
}

$coverage_reports = $db->query("SELECT * FROM coverage_reports ORDER BY project_name")->fetchAll();
$version_control = $db->query("SELECT * FROM version_control ORDER BY project_name")->fetchAll();
$imported_data = $db->query("SELECT DISTINCT project_name, import_date FROM imported_it_data ORDER BY project_name")->fetchAll();

$to_summary_sql = "
    SELECT 
        COALESCE(it.project_name, cr.project_name, vc.project_name) AS project,
        it.spip_ip,
        it.ip,
        it.ip_postfix,
        it.ip_subtype,
        it.alternative_name,
        cr.line_coverage,
        cr.fsm_coverage,
        cr.interface_toggle_coverage,
        cr.toggle_coverage,
        cr.coverage_report_path,
        it.dv_engineer,
        it.digital_designer,
        it.business_unit,
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
        it.spec_path,
        it.analog_designer,
        it.inherit_from_ip,
        it.reuse_ip
    FROM imported_it_data it
    LEFT JOIN coverage_reports cr ON it.project_name = cr.project_name
    LEFT JOIN version_control vc ON it.project_name = vc.project_name
    
    UNION ALL
    
    SELECT 
        cr.project_name AS project,
        NULL as spip_ip,
        NULL as ip,
        NULL as ip_postfix,
        NULL as ip_subtype,
        NULL as alternative_name,
        cr.line_coverage,
        cr.fsm_coverage,
        cr.interface_toggle_coverage,
        cr.toggle_coverage,
        cr.coverage_report_path,
        NULL as dv_engineer,
        NULL as digital_designer,
        NULL as business_unit,
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
        NULL as spip_url,
        NULL as wiki_url,
        NULL as spec_version,
        NULL as spec_path,
        NULL as analog_designer,
        NULL as inherit_from_ip,
        NULL as reuse_ip
    FROM coverage_reports cr
    LEFT JOIN version_control vc ON cr.project_name = vc.project_name
    WHERE NOT EXISTS (SELECT 1 FROM imported_it_data it WHERE it.project_name = cr.project_name)
    
    ORDER BY project
";

$to_summary = $db->query($to_summary_sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NX Domain - DV Reports & TO Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">NX Domain - DV Reports</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="?action=dashboard">Dashboard</a>
                <a class="nav-link" href="?action=coverage">Coverage Reports</a>
                <a class="nav-link" href="?action=import">Import IT Data</a>
                <a class="nav-link" href="?action=to_summary">TO Summary</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php echo $message; ?>
        
        <?php if ($action === 'dashboard'): ?>
            <h2>NX Domain Dashboard</h2>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-header bg-info text-white">
                            <h5>Coverage Reports</h5>
                        </div>
                        <div class="card-body">
                            <h3><?php echo count($coverage_reports); ?></h3>
                            <p>Projects with coverage data</p>
                            <a href="?action=coverage" class="btn btn-info">View Reports</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-header bg-warning text-white">
                            <h5>Imported IT Data</h5>
                        </div>
                        <div class="card-body">
                            <h3><?php echo count($imported_data); ?></h3>
                            <p>Projects from IT domain</p>
                            <a href="?action=import" class="btn btn-warning">Import Data</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-header bg-primary text-white">
                            <h5>TO Summary</h5>
                        </div>
                        <div class="card-body">
                            <h3><?php echo count($to_summary); ?></h3>
                            <p>Combined project records</p>
                            <a href="?action=to_summary" class="btn btn-primary">View Summary</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Coverage Data</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Line Coverage</th>
                                            <th>FSM Coverage</th>
                                            <th>Toggle Coverage</th>
                                            <th>TO Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($coverage_reports, 0, 5) as $report): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($report['project_name']); ?></td>
                                            <td><?php echo formatPercentage($report['line_coverage']); ?></td>
                                            <td><?php echo formatPercentage($report['fsm_coverage']); ?></td>
                                            <td><?php echo formatPercentage($report['toggle_coverage']); ?></td>
                                            <td><?php echo formatDate($report['to_date']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($action === 'coverage'): ?>
            <h2>Coverage Reports</h2>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Line Coverage</th>
                                    <th>FSM Coverage</th>
                                    <th>Interface Toggle</th>
                                    <th>Toggle Coverage</th>
                                    <th>Coverage Report</th>
                                    <th>TO Date</th>
                                    <th>RTL Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coverage_reports as $report): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($report['project_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $report['line_coverage'] >= 90 ? 'success' : ($report['line_coverage'] >= 80 ? 'warning' : 'danger'); ?>">
                                            <?php echo formatPercentage($report['line_coverage']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $report['fsm_coverage'] >= 90 ? 'success' : ($report['fsm_coverage'] >= 80 ? 'warning' : 'danger'); ?>">
                                            <?php echo formatPercentage($report['fsm_coverage']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $report['interface_toggle_coverage'] >= 90 ? 'success' : ($report['interface_toggle_coverage'] >= 80 ? 'warning' : 'danger'); ?>">
                                            <?php echo formatPercentage($report['interface_toggle_coverage']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $report['toggle_coverage'] >= 90 ? 'success' : ($report['toggle_coverage'] >= 80 ? 'warning' : 'danger'); ?>">
                                            <?php echo formatPercentage($report['toggle_coverage']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($report['coverage_report_path']): ?>
                                            <a href="<?php echo htmlspecialchars($report['coverage_report_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info">View Report</a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($report['to_date']); ?></td>
                                    <td><?php echo formatDateTime($report['rtl_last_update']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        <?php elseif ($action === 'import'): ?>
            <h2>Import IT Domain Data</h2>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Upload CSV File from IT Domain</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">Select CSV File</label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                    <div class="form-text">Upload the CSV file exported from IT Domain website</div>
                                </div>
                                <button type="submit" name="import_it_data" class="btn btn-primary">Import Data</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Import History</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($imported_data)): ?>
                                <div class="list-group">
                                    <?php foreach ($imported_data as $import): ?>
                                    <div class="list-group-item">
                                        <strong><?php echo htmlspecialchars($import['project_name']); ?></strong><br>
                                        <small class="text-muted">Imported: <?php echo formatDateTime($import['import_date']); ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No data imported yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($action === 'to_summary'): ?>
            <h2>TO Summary - Combined Project Data</h2>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Project</th>
                                    <th>SPIP IP</th>
                                    <th>IP</th>
                                    <th>DV Engineer</th>
                                    <th>DD</th>
                                    <th>BU</th>
                                    <th>Line Cov</th>
                                    <th>FSM Cov</th>
                                    <th>Toggle Cov</th>
                                    <th>TO Date</th>
                                    <th>Git Version</th>
                                    <th>Links</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($to_summary as $summary): ?>
                                <tr>
                                    <td>
                                        <a href="javascript:void(0)" onclick="showIPDetails('<?php echo htmlspecialchars($summary['project']); ?>', 'nx')" class="text-decoration-none">
                                            <strong><?php echo htmlspecialchars($summary['project']); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($summary['spip_ip'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="javascript:void(0)" onclick="showIPDetails('<?php echo htmlspecialchars($summary['project']); ?>', 'nx')" class="text-decoration-none fw-bold">
                                            <?php echo htmlspecialchars($summary['ip'] ?? 'N/A'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($summary['dv_engineer'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($summary['digital_designer'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($summary['business_unit'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($summary['line_coverage']): ?>
                                            <span class="badge bg-<?php echo $summary['line_coverage'] >= 90 ? 'success' : ($summary['line_coverage'] >= 80 ? 'warning' : 'danger'); ?>">
                                                <?php echo formatPercentage($summary['line_coverage']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($summary['fsm_coverage']): ?>
                                            <span class="badge bg-<?php echo $summary['fsm_coverage'] >= 90 ? 'success' : ($summary['fsm_coverage'] >= 80 ? 'warning' : 'danger'); ?>">
                                                <?php echo formatPercentage($summary['fsm_coverage']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($summary['toggle_coverage']): ?>
                                            <span class="badge bg-<?php echo $summary['toggle_coverage'] >= 90 ? 'success' : ($summary['toggle_coverage'] >= 80 ? 'warning' : 'danger'); ?>">
                                                <?php echo formatPercentage($summary['toggle_coverage']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($summary['to_date']); ?></td>
                                    <td>
                                        <?php if ($summary['git_version']): ?>
                                            <code class="small"><?php echo substr(htmlspecialchars($summary['git_version']), 0, 8); ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($summary['spip_url']): ?>
                                                <a href="<?php echo htmlspecialchars($summary['spip_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="SPIP">S</a>
                                            <?php endif; ?>
                                            <?php if ($summary['wiki_url']): ?>
                                                <a href="<?php echo htmlspecialchars($summary['wiki_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Wiki">W</a>
                                            <?php endif; ?>
                                            <?php if ($summary['coverage_report_path']): ?>
                                                <a href="<?php echo htmlspecialchars($summary['coverage_report_path']); ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Coverage">C</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($to_summary)): ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No TO summary data available. Import IT domain data and ensure coverage reports are available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'ip-detail-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>