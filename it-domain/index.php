<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die('Database connection failed. Please check if the database service is running.');
}

$message = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_project'])) {
        try {
            $db->beginTransaction();
            
            // Insert into projects table
            $stmt = $db->prepare("INSERT INTO projects (project_name, spip_ip, ip, ip_postfix, ip_subtype, alternative_name, spip_url, wiki_url, spec_version, spec_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                sanitizeInput($_POST['project_name']),
                sanitizeInput($_POST['spip_ip']),
                sanitizeInput($_POST['ip']),
                sanitizeInput($_POST['ip_postfix']),
                sanitizeInput($_POST['ip_subtype']),
                sanitizeInput($_POST['alternative_name']),
                sanitizeInput($_POST['spip_url']),
                sanitizeInput($_POST['wiki_url']),
                sanitizeInput($_POST['spec_version']),
                sanitizeInput($_POST['spec_path'])
            ]);
            
            $projectId = $db->lastInsertId();
            
            // Generate task index
            $stmt = $db->prepare("SELECT COUNT(*) FROM dv_tasks");
            $stmt->execute();
            $taskCount = $stmt->fetchColumn();
            $taskIndex = 'TASK' . str_pad($taskCount + 1, 3, '0', STR_PAD_LEFT);
            
            // Insert into dv_tasks table
            $stmt = $db->prepare("INSERT INTO dv_tasks (project_id, task_index, dv_engineer, digital_designer, business_unit, analog_designer, inherit_from_ip, reuse_ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $projectId,
                $taskIndex,
                sanitizeInput($_POST['dv_engineer']),
                sanitizeInput($_POST['digital_designer']),
                sanitizeInput($_POST['business_unit']),
                sanitizeInput($_POST['analog_designer']),
                sanitizeInput($_POST['inherit_from_ip']),
                sanitizeInput($_POST['reuse_ip'])
            ]);
            
            $db->commit();
            $message = showAlert('Project added successfully! Task index: ' . $taskIndex, 'success');
        } catch (PDOException $e) {
            $db->rollBack();
            $message = showAlert('Error adding project: ' . $e->getMessage(), 'danger');
        }
    }
    
    if (isset($_POST['update_project'])) {
        try {
            $db->beginTransaction();
            
            // Update projects table
            $stmt = $db->prepare("UPDATE projects SET 
                project_name = ?, spip_ip = ?, ip = ?, ip_postfix = ?, ip_subtype = ?, alternative_name = ?,
                spip_url = ?, wiki_url = ?, spec_version = ?, spec_path = ?
                WHERE id = ?");
            
            $stmt->execute([
                sanitizeInput($_POST['project_name']),
                sanitizeInput($_POST['spip_ip']),
                sanitizeInput($_POST['ip']),
                sanitizeInput($_POST['ip_postfix']),
                sanitizeInput($_POST['ip_subtype']),
                sanitizeInput($_POST['alternative_name']),
                sanitizeInput($_POST['spip_url']),
                sanitizeInput($_POST['wiki_url']),
                sanitizeInput($_POST['spec_version']),
                sanitizeInput($_POST['spec_path']),
                (int)$_POST['id']
            ]);
            
            // Update or insert dv_tasks table
            $stmt = $db->prepare("SELECT id FROM dv_tasks WHERE project_id = ?");
            $stmt->execute([(int)$_POST['id']]);
            $taskExists = $stmt->fetch();
            
            if ($taskExists) {
                // Update existing task
                $stmt = $db->prepare("UPDATE dv_tasks SET 
                    dv_engineer = ?, digital_designer = ?, business_unit = ?, analog_designer = ?, 
                    inherit_from_ip = ?, reuse_ip = ?
                    WHERE project_id = ?");
                $stmt->execute([
                    sanitizeInput($_POST['dv_engineer']),
                    sanitizeInput($_POST['digital_designer']),
                    sanitizeInput($_POST['business_unit']),
                    sanitizeInput($_POST['analog_designer']),
                    sanitizeInput($_POST['inherit_from_ip']),
                    sanitizeInput($_POST['reuse_ip']),
                    (int)$_POST['id']
                ]);
            } else {
                // Create new task if it doesn't exist
                $stmt = $db->prepare("SELECT COUNT(*) FROM dv_tasks");
                $stmt->execute();
                $taskCount = $stmt->fetchColumn();
                $taskIndex = 'TASK' . str_pad($taskCount + 1, 3, '0', STR_PAD_LEFT);
                
                $stmt = $db->prepare("INSERT INTO dv_tasks (project_id, task_index, dv_engineer, digital_designer, business_unit, analog_designer, inherit_from_ip, reuse_ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    (int)$_POST['id'],
                    $taskIndex,
                    sanitizeInput($_POST['dv_engineer']),
                    sanitizeInput($_POST['digital_designer']),
                    sanitizeInput($_POST['business_unit']),
                    sanitizeInput($_POST['analog_designer']),
                    sanitizeInput($_POST['inherit_from_ip']),
                    sanitizeInput($_POST['reuse_ip'])
                ]);
            }
            
            $db->commit();
            $message = showAlert('Project updated successfully!', 'success');
        } catch (PDOException $e) {
            $db->rollBack();
            $message = showAlert('Error updating project: ' . $e->getMessage(), 'danger');
        }
    }
    
    if (isset($_POST['export_data'])) {
        try {
            $stmt = $db->query("SELECT * FROM export_view ORDER BY task_index");
            $data = $stmt->fetchAll();
            
            if (!empty($data)) {
                generateCSV($data, 'it_domain_export_' . date('Y-m-d_H-i-s') . '.csv');
            } else {
                $message = showAlert('No data to export', 'warning');
            }
        } catch (PDOException $e) {
            $message = showAlert('Error exporting data: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get current project data from the existing schema
$projects = $db->query("SELECT p.*, dt.task_index, dt.dv_engineer, dt.digital_designer, dt.business_unit, dt.analog_designer, dt.inherit_from_ip, dt.reuse_ip 
                       FROM projects p 
                       LEFT JOIN dv_tasks dt ON p.id = dt.project_id 
                       ORDER BY p.project_name")->fetchAll();

// Get project for editing from existing schema
$edit_project = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT p.*, dt.task_index, dt.dv_engineer, dt.digital_designer, dt.business_unit, dt.analog_designer, dt.inherit_from_ip, dt.reuse_ip 
                         FROM projects p 
                         LEFT JOIN dv_tasks dt ON p.id = dt.project_id 
                         WHERE p.id = ?");
    $stmt->execute([$id]);
    $edit_project = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Domain - DV Project Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table-responsive {
            font-size: 0.875rem;
        }
        .field-group {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .field-group-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .auto-generated {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">IT Domain - DV Management</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="?action=list">View Projects</a>
                <a class="nav-link" href="?action=add">Add Project</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php echo $message; ?>
        
        <?php if ($action === 'list'): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>All Projects (<?php echo count($projects); ?>)</h2>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="export_data" class="btn btn-success">Export All Data to CSV</button>
                        </form>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Complete Project Overview - All 17 TO Report Fields</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Task Index</th>
                                            <th>Project</th>
                                            <th>SPIP IP</th>
                                            <th>IP</th>
                                            <th>IP Postfix</th>
                                            <th>IP Subtype</th>
                                            <th>Alternative Name</th>
                                            <th>DV Engineer</th>
                                            <th>Digital Designer</th>
                                            <th>Business Unit</th>
                                            <th>Analog Designer</th>
                                            <th>Inherit from IP</th>
                                            <th>Re-use IP</th>
                                            <th>SPIP URL</th>
                                            <th>Wiki URL</th>
                                            <th>Spec Version</th>
                                            <th>Spec Path</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($project['task_index'] ?? 'N/A'); ?></code></td>
                                            <td>
                                                <a href="javascript:void(0)" onclick="showIPDetails(<?php echo $project['id']; ?>, 'it')" class="text-decoration-none">
                                                    <strong><?php echo htmlspecialchars($project['project_name']); ?></strong>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($project['spip_ip'] ?? ''); ?></td>
                                            <td>
                                                <a href="javascript:void(0)" onclick="showIPDetails(<?php echo $project['id']; ?>, 'it')" class="text-decoration-none fw-bold">
                                                    <?php echo htmlspecialchars($project['ip'] ?? ''); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($project['ip_postfix'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $project['ip_subtype'] === 'default' ? 'secondary' : 'primary'; ?>">
                                                    <?php echo htmlspecialchars($project['ip_subtype'] ?? 'default'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($project['alternative_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($project['dv_engineer'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($project['digital_designer'] ?? ''); ?></td>
                                            <td>
                                                <?php if ($project['business_unit']): ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($project['business_unit']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($project['analog_designer'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($project['inherit_from_ip'] ?? ''); ?></td>
                                            <td>
                                                <?php if ($project['reuse_ip'] === 'Y'): ?>
                                                    <span class="badge bg-success">Yes</span>
                                                <?php elseif ($project['reuse_ip'] === 'N'): ?>
                                                    <span class="badge bg-danger">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($project['spip_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($project['spip_url'] ?? ''); ?>" target="_blank" class="btn btn-sm btn-outline-primary">SPIP</a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($project['wiki_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($project['wiki_url'] ?? ''); ?>" target="_blank" class="btn btn-sm btn-outline-info">Wiki</a>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($project['spec_version'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($project['spec_path'] ?? ''); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" onclick="showIPDetails(<?php echo $project['id']; ?>, 'it')" class="btn btn-sm btn-outline-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="?action=edit&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (empty($projects)): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">No projects found. <a href="?action=add">Add your first project</a></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <h2><?php echo $action === 'add' ? 'Add New Project' : 'Edit Project'; ?></h2>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="projectForm">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_project['id']; ?>">
                        <?php endif; ?>
                        
                        <!-- Auto-generated Field -->
                        <div class="field-group">
                            <div class="field-group-title">Auto-Generated Fields</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Task Index</label>
                                        <div class="auto-generated">
                                            <?php echo $action === 'edit' ? htmlspecialchars($edit_project['task_index']) : 'Will be auto-generated (e.g., TASK001)'; ?>
                                        </div>
                                        <small class="form-text text-muted">Task index is automatically generated upon project creation</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Basic Project Information -->
                        <div class="field-group">
                            <div class="field-group-title">Basic Project Information</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="project_name" class="form-label">Project Name *</label>
                                        <input type="text" class="form-control" id="project_name" name="project_name" 
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['project_name'] ?? '') : ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="spip_ip" class="form-label">SPIP IP</label>
                                        <input type="text" class="form-control" id="spip_ip" name="spip_ip"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['spip_ip'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="ip" class="form-label">IP</label>
                                        <input type="text" class="form-control" id="ip" name="ip"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['ip'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="ip_postfix" class="form-label">IP Postfix</label>
                                        <input type="text" class="form-control" id="ip_postfix" name="ip_postfix"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['ip_postfix'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ip_subtype" class="form-label">IP Subtype</label>
                                        <select class="form-control" id="ip_subtype" name="ip_subtype">
                                            <option value="default" <?php echo ($edit_project && ($edit_project['ip_subtype'] ?? 'default') === 'default') ? 'selected' : ''; ?>>default</option>
                                            <option value="gen2x1" <?php echo ($edit_project && ($edit_project['ip_subtype'] ?? '') === 'gen2x1') ? 'selected' : ''; ?>>gen2x1</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="alternative_name" class="form-label">Alternative Name</label>
                                        <input type="text" class="form-control" id="alternative_name" name="alternative_name"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['alternative_name'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="spip_url" class="form-label">SPIP URL</label>
                                        <input type="url" class="form-control" id="spip_url" name="spip_url"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['spip_url'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="wiki_url" class="form-label">Wiki URL</label>
                                        <input type="url" class="form-control" id="wiki_url" name="wiki_url"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['wiki_url'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Personnel Information -->
                        <div class="field-group">
                            <div class="field-group-title">Personnel Information</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="dv_engineer" class="form-label">DV Engineer</label>
                                        <input type="text" class="form-control" id="dv_engineer" name="dv_engineer"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['dv_engineer'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="digital_designer" class="form-label">Digital Designer</label>
                                        <input type="text" class="form-control" id="digital_designer" name="digital_designer"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['digital_designer'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="business_unit" class="form-label">Business Unit</label>
                                        <select class="form-control" id="business_unit" name="business_unit">
                                            <option value="">Select BU</option>
                                            <option value="CN" <?php echo ($edit_project && ($edit_project['business_unit'] ?? '') === 'CN') ? 'selected' : ''; ?>>CN</option>
                                            <option value="PC" <?php echo ($edit_project && ($edit_project['business_unit'] ?? '') === 'PC') ? 'selected' : ''; ?>>PC</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="analog_designer" class="form-label">Analog Designer</label>
                                        <input type="text" class="form-control" id="analog_designer" name="analog_designer"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['analog_designer'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Design Information -->
                        <div class="field-group">
                            <div class="field-group-title">Design Information</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="inherit_from_ip" class="form-label">Inherit from IP</label>
                                        <input type="text" class="form-control" id="inherit_from_ip" name="inherit_from_ip"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['inherit_from_ip'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="reuse_ip" class="form-label">Re-use IP</label>
                                        <select class="form-control" id="reuse_ip" name="reuse_ip">
                                            <option value="">Select Option</option>
                                            <option value="Y" <?php echo ($edit_project && ($edit_project['reuse_ip'] ?? '') === 'Y') ? 'selected' : ''; ?>>Yes</option>
                                            <option value="N" <?php echo ($edit_project && ($edit_project['reuse_ip'] ?? '') === 'N') ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="spec_version" class="form-label">Spec Version</label>
                                        <input type="text" class="form-control" id="spec_version" name="spec_version"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['spec_version'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="spec_path" class="form-label">Spec Path</label>
                                        <input type="text" class="form-control" id="spec_path" name="spec_path"
                                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['spec_path'] ?? '') : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="<?php echo $action === 'add' ? 'add_project' : 'update_project'; ?>" class="btn btn-primary">
                                <?php echo $action === 'add' ? 'Add Project' : 'Update Project'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'ip-detail-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('projectForm')?.addEventListener('submit', function(e) {
            const projectName = document.getElementById('project_name').value.trim();
            if (!projectName) {
                e.preventDefault();
                alert('Project name is required');
                return false;
            }
            
            // URL validation
            const spipUrl = document.getElementById('spip_url').value.trim();
            const wikiUrl = document.getElementById('wiki_url').value.trim();
            
            if (spipUrl && !spipUrl.startsWith('http')) {
                e.preventDefault();
                alert('SPIP URL must start with http:// or https://');
                return false;
            }
            
            if (wikiUrl && !wikiUrl.startsWith('http')) {
                e.preventDefault();
                alert('Wiki URL must start with http:// or https://');
                return false;
            }
        });
    </script>
</body>
</html>