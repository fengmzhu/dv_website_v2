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

// Get data for dashboard
$coverage_reports = $db->query("SELECT * FROM coverage_reports ORDER BY project_name")->fetchAll();
$version_control = $db->query("SELECT * FROM version_control ORDER BY project_name")->fetchAll();
$imported_data = $db->query("SELECT DISTINCT project_name, import_date FROM imported_it_data ORDER BY project_name")->fetchAll();

// Get complete TO summary data with all 33 fields
$to_summary = $db->query("SELECT * FROM to_summary_view ORDER BY project")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NX Domain - DV Reports & TO Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-responsive {
            font-size: 0.75rem;
        }
        .to-summary-table {
            white-space: nowrap;
        }
        .to-summary-table th {
            background-color: #212529;
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .field-group {
            border-left: 3px solid #007bff;
            padding-left: 10px;
        }
        .coverage-badge {
            min-width: 50px;
            text-align: center;
        }
        .url-link {
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .git-hash {
            font-family: monospace;
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .field-section {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
        }
        .section-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        .expandable-table {
            max-height: 600px;
            overflow-y: auto;
        }
        .btn-toggle {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">NX Domain - DV Reports</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="?action=dashboard">Dashboard</a>
                <a class="nav-link" href="?action=coverage">Coverage Reports</a>
                <a class="nav-link" href="?action=import">Import IT Data</a>
                <a class="nav-link" href="?action=to_summary">Complete TO Summary</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php echo $message; ?>
        
        <?php if ($action === 'dashboard'): ?>
            <h2>NX Domain Dashboard</h2>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-header bg-info text-white">
                            <h6>Coverage Reports</h6>
                        </div>
                        <div class="card-body">
                            <h4><?php echo count($coverage_reports); ?></h4>
                            <p>Projects with coverage data</p>
                            <a href="?action=coverage" class="btn btn-info btn-sm">View Reports</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-header bg-warning text-white">
                            <h6>Imported IT Data</h6>
                        </div>
                        <div class="card-body">
                            <h4><?php echo count($imported_data); ?></h4>
                            <p>Projects from IT domain</p>
                            <a href="?action=import" class="btn btn-warning btn-sm">Import Data</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-header bg-primary text-white">
                            <h6>TO Summary</h6>
                        </div>
                        <div class="card-body">
                            <h4><?php echo count($to_summary); ?></h4>
                            <p>Combined project records</p>
                            <a href="?action=to_summary" class="btn btn-primary btn-sm">View Summary</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-header bg-secondary text-white">
                            <h6>Version Control</h6>
                        </div>
                        <div class="card-body">
                            <h4><?php echo count($version_control); ?></h4>
                            <p>Projects with VCS data</p>
                            <a href="?action=coverage" class="btn btn-secondary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Coverage Data Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Line Coverage</th>
                                            <th>FSM Coverage</th>
                                            <th>Interface Toggle</th>
                                            <th>Toggle Coverage</th>
                                            <th>TO Date</th>
                                            <th>Report</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($coverage_reports, 0, 10) as $report): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($report['project_name']); ?></strong></td>
                                            <td><?php echo formatCoverageBadge($report['line_coverage']); ?></td>
                                            <td><?php echo formatCoverageBadge($report['fsm_coverage']); ?></td>
                                            <td><?php echo formatCoverageBadge($report['interface_toggle_coverage']); ?></td>
                                            <td><?php echo formatCoverageBadge($report['toggle_coverage']); ?></td>
                                            <td><?php echo formatDate($report['to_date']); ?></td>
                                            <td>
                                                <?php if ($report['coverage_report_path']): ?>
                                                    <a href="<?php echo htmlspecialchars($report['coverage_report_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info">View</a>
                                                <?php endif; ?>
                                            </td>
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
                                    <td><strong><?php echo htmlspecialchars($report['project_name']); ?></strong></td>
                                    <td><?php echo formatCoverageBadge($report['line_coverage']); ?></td>
                                    <td><?php echo formatCoverageBadge($report['fsm_coverage']); ?></td>
                                    <td><?php echo formatCoverageBadge($report['interface_toggle_coverage']); ?></td>
                                    <td><?php echo formatCoverageBadge($report['toggle_coverage']); ?></td>
                                    <td>
                                        <?php if ($report['coverage_report_path']): ?>
                                            <a href="<?php echo htmlspecialchars($report['coverage_report_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info">View Report</a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Complete TO Summary - All 33 Fields</h2>
                <div>
                    <button class="btn btn-outline-secondary btn-sm" onclick="toggleFieldGroups()">Toggle Field Groups</button>
                    <button class="btn btn-success btn-sm" onclick="exportToCSV()">Export to CSV</button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive expandable-table">
                        <table class="table table-striped table-hover to-summary-table" id="toSummaryTable">
                            <thead>
                                <tr>
                                    <!-- IT Domain Fields (17 fields) -->
                                    <th rowspan="2" class="field-group">Project</th>
                                    <th colspan="6" class="text-center bg-primary text-white">Basic Project Info</th>
                                    <th colspan="4" class="text-center bg-info text-white">Personnel</th>
                                    <th colspan="6" class="text-center bg-warning text-dark">Documentation</th>
                                    
                                    <!-- NX Domain Fields (16 fields) -->
                                    <th colspan="5" class="text-center bg-success text-white">Coverage Metrics</th>
                                    <th colspan="8" class="text-center bg-danger text-white">Version Control</th>
                                    <th colspan="3" class="text-center bg-secondary text-white">Timestamps</th>
                                </tr>
                                <tr>
                                    <!-- IT Domain Fields -->
                                    <th class="bg-primary text-white">Task Index</th>
                                    <th class="bg-primary text-white">SPIP IP</th>
                                    <th class="bg-primary text-white">IP</th>
                                    <th class="bg-primary text-white">IP Postfix</th>
                                    <th class="bg-primary text-white">IP Subtype</th>
                                    <th class="bg-primary text-white">Alt Name</th>
                                    
                                    <th class="bg-info text-white">DV Engineer</th>
                                    <th class="bg-info text-white">Digital Designer</th>
                                    <th class="bg-info text-white">Business Unit</th>
                                    <th class="bg-info text-white">Analog Designer</th>
                                    
                                    <th class="bg-warning text-dark">SPIP URL</th>
                                    <th class="bg-warning text-dark">Wiki URL</th>
                                    <th class="bg-warning text-dark">Spec Ver</th>
                                    <th class="bg-warning text-dark">Spec Path</th>
                                    <th class="bg-warning text-dark">Inherit IP</th>
                                    <th class="bg-warning text-dark">Reuse IP</th>
                                    
                                    <!-- NX Domain Fields -->
                                    <th class="bg-success text-white">Line Cov</th>
                                    <th class="bg-success text-white">FSM Cov</th>
                                    <th class="bg-success text-white">Interface Toggle</th>
                                    <th class="bg-success text-white">Toggle Cov</th>
                                    <th class="bg-success text-white">Report Path</th>
                                    
                                    <th class="bg-danger text-white">Sanity SVN</th>
                                    <th class="bg-danger text-white">Sanity Ver</th>
                                    <th class="bg-danger text-white">Release SVN</th>
                                    <th class="bg-danger text-white">Release Ver</th>
                                    <th class="bg-danger text-white">Git Path</th>
                                    <th class="bg-danger text-white">Git Version</th>
                                    <th class="bg-danger text-white">Golden Checklist</th>
                                    <th class="bg-danger text-white">Checklist Ver</th>
                                    
                                    <th class="bg-secondary text-white">TO Date</th>
                                    <th class="bg-secondary text-white">RTL Update</th>
                                    <th class="bg-secondary text-white">Report Creation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($to_summary as $summary): ?>
                                <tr>
                                    <!-- Project Name -->
                                    <td><strong><?php echo htmlspecialchars($summary['project'] ?? ''); ?></strong></td>
                                    
                                    <!-- Basic Project Info -->
                                    <td><code><?php echo htmlspecialchars($summary['task_index'] ?? ''); ?></code></td>
                                    <td><?php echo htmlspecialchars($summary['spip_ip'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($summary['ip'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($summary['ip_postfix'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($summary['ip_subtype']): ?>
                                            <span class="badge bg-<?php echo $summary['ip_subtype'] === 'default' ? 'secondary' : 'primary'; ?>">
                                                <?php echo htmlspecialchars($summary['ip_subtype']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($summary['alternative_name'] ?? ''); ?></td>
                                    
                                    <!-- Personnel -->
                                    <td><?php echo htmlspecialchars($summary['dv_engineer'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($summary['digital_designer'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($summary['business_unit']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($summary['business_unit']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($summary['analog_designer'] ?? ''); ?></td>
                                    
                                    <!-- Documentation -->
                                    <td>
                                        <?php if ($summary['spip_url']): ?>
                                            <a href="<?php echo htmlspecialchars($summary['spip_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">SPIP</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($summary['wiki_url']): ?>
                                            <a href="<?php echo htmlspecialchars($summary['wiki_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info">Wiki</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($summary['spec_version'] ?? ''); ?></td>
                                    <td class="url-link"><?php echo htmlspecialchars($summary['spec_path'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($summary['inherit_from_ip'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($summary['reuse_ip'] === 'Y'): ?>
                                            <span class="badge bg-success">Yes</span>
                                        <?php elseif ($summary['reuse_ip'] === 'N'): ?>
                                            <span class="badge bg-danger">No</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Coverage Metrics -->
                                    <td><?php echo formatCoverageBadge($summary['line_coverage']); ?></td>
                                    <td><?php echo formatCoverageBadge($summary['fsm_coverage']); ?></td>
                                    <td><?php echo formatCoverageBadge($summary['interface_toggle_coverage']); ?></td>
                                    <td><?php echo formatCoverageBadge($summary['toggle_coverage']); ?></td>
                                    <td>
                                        <?php if ($summary['coverage_report_path']): ?>
                                            <a href="<?php echo htmlspecialchars($summary['coverage_report_path']); ?>" target="_blank" class="btn btn-sm btn-outline-success">View</a>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Version Control -->
                                    <td class="url-link"><?php echo htmlspecialchars($summary['sanity_svn'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($summary['sanity_svn_ver'] ?? ''); ?></td>
                                    <td class="url-link"><?php echo htmlspecialchars($summary['release_svn'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($summary['release_svn_ver'] ?? ''); ?></td>
                                    <td class="url-link"><?php echo htmlspecialchars($summary['git_path'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($summary['git_version']): ?>
                                            <code class="git-hash"><?php echo htmlspecialchars($summary['git_version']); ?></code>
                                        <?php endif; ?>
                                    </td>
                                    <td class="url-link"><?php echo htmlspecialchars($summary['golden_checklist'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($summary['golden_checklist_version'] ?? ''); ?></td>
                                    
                                    <!-- Timestamps -->
                                    <td><?php echo formatDate($summary['to_date']); ?></td>
                                    <td><?php echo formatDateTime($summary['rtl_last_update']); ?></td>
                                    <td><?php echo formatDateTime($summary['to_report_creation']); ?></td>
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
            
            <div class="mt-3">
                <small class="text-muted">
                    <strong>Field Count:</strong> Displaying all 33 TO Report fields across both domains. 
                    <strong>Color coding:</strong> 
                    <span class="badge bg-primary">Basic Info</span>
                    <span class="badge bg-info">Personnel</span>
                    <span class="badge bg-warning text-dark">Documentation</span>
                    <span class="badge bg-success">Coverage</span>
                    <span class="badge bg-danger">Version Control</span>
                    <span class="badge bg-secondary">Timestamps</span>
                </small>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleFieldGroups() {
            const table = document.getElementById('toSummaryTable');
            const headers = table.querySelectorAll('th[colspan]');
            headers.forEach(header => {
                header.style.display = header.style.display === 'none' ? '' : 'none';
            });
        }
        
        function exportToCSV() {
            const table = document.getElementById('toSummaryTable');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            let csvContent = '';
            
            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('td, th'));
                const rowData = cells.map(cell => {
                    let text = cell.textContent.trim();
                    if (text.includes(',')) {
                        text = '"' + text + '"';
                    }
                    return text;
                }).join(',');
                csvContent += rowData + '\n';
            });
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'to_summary_complete_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>