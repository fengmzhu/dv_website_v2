<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_project'])) {
        try {
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
            $message = showAlert('Project added successfully!', 'success');
        } catch (PDOException $e) {
            $message = showAlert('Error adding project: ' . $e->getMessage(), 'danger');
        }
    }
    
    if (isset($_POST['add_task'])) {
        try {
            $stmt = $db->prepare("INSERT INTO dv_tasks (project_id, task_index, dv_engineer, digital_designer, business_unit, analog_designer, inherit_from_ip, reuse_ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                (int)$_POST['project_id'],
                sanitizeInput($_POST['task_index']),
                sanitizeInput($_POST['dv_engineer']),
                sanitizeInput($_POST['digital_designer']),
                sanitizeInput($_POST['business_unit']),
                sanitizeInput($_POST['analog_designer']),
                sanitizeInput($_POST['inherit_from_ip']),
                sanitizeInput($_POST['reuse_ip'])
            ]);
            $message = showAlert('DV Task added successfully!', 'success');
        } catch (PDOException $e) {
            $message = showAlert('Error adding task: ' . $e->getMessage(), 'danger');
        }
    }
    
    if (isset($_POST['export_data'])) {
        try {
            $stmt = $db->query("SELECT * FROM export_view ORDER BY project_name");
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

$projects = $db->query("SELECT * FROM projects ORDER BY project_name")->fetchAll();
$tasks = $db->query("SELECT dt.*, p.project_name FROM dv_tasks dt JOIN projects p ON dt.project_id = p.id ORDER BY p.project_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Domain - DV Project Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">IT Domain - DV Management</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="?action=list">View Data</a>
                <a class="nav-link" href="?action=add_project">Add Project</a>
                <a class="nav-link" href="?action=add_task">Add Task</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php echo $message; ?>
        
        <?php if ($action === 'list'): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Projects and Tasks Overview</h2>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="export_data" class="btn btn-success">Export to CSV</button>
                        </form>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Projects</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Project Name</th>
                                            <th>SPIP IP</th>
                                            <th>IP</th>
                                            <th>IP Subtype</th>
                                            <th>SPIP URL</th>
                                            <th>Spec Version</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                            <td><?php echo htmlspecialchars($project['spip_ip']); ?></td>
                                            <td><?php echo htmlspecialchars($project['ip']); ?></td>
                                            <td><?php echo htmlspecialchars($project['ip_subtype']); ?></td>
                                            <td>
                                                <?php if ($project['spip_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($project['spip_url']); ?>" target="_blank">SPIP Link</a>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($project['spec_version']); ?></td>
                                            <td>
                                                <a href="index-new.php?action=edit&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>DV Tasks</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Task Index</th>
                                            <th>DV Engineer</th>
                                            <th>Digital Designer</th>
                                            <th>Business Unit</th>
                                            <th>Analog Designer</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                            <td><?php echo htmlspecialchars($task['task_index']); ?></td>
                                            <td><?php echo htmlspecialchars($task['dv_engineer']); ?></td>
                                            <td><?php echo htmlspecialchars($task['digital_designer']); ?></td>
                                            <td><?php echo htmlspecialchars($task['business_unit']); ?></td>
                                            <td><?php echo htmlspecialchars($task['analog_designer']); ?></td>
                                            <td>
                                                <a href="index-new.php?action=edit&id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
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
            
        <?php elseif ($action === 'add_project'): ?>
            <h2>Add New Project</h2>
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_name" class="form-label">Project Name *</label>
                                    <input type="text" class="form-control" id="project_name" name="project_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="spip_ip" class="form-label">SPIP IP</label>
                                    <input type="text" class="form-control" id="spip_ip" name="spip_ip">
                                </div>
                                <div class="mb-3">
                                    <label for="ip" class="form-label">IP</label>
                                    <input type="text" class="form-control" id="ip" name="ip">
                                </div>
                                <div class="mb-3">
                                    <label for="ip_postfix" class="form-label">IP Postfix</label>
                                    <input type="text" class="form-control" id="ip_postfix" name="ip_postfix">
                                </div>
                                <div class="mb-3">
                                    <label for="ip_subtype" class="form-label">IP Subtype</label>
                                    <select class="form-control" id="ip_subtype" name="ip_subtype">
                                        <option value="default">default</option>
                                        <option value="gen2x1">gen2x1</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="alternative_name" class="form-label">Alternative Name</label>
                                    <input type="text" class="form-control" id="alternative_name" name="alternative_name">
                                </div>
                                <div class="mb-3">
                                    <label for="spip_url" class="form-label">SPIP URL</label>
                                    <input type="url" class="form-control" id="spip_url" name="spip_url">
                                </div>
                                <div class="mb-3">
                                    <label for="wiki_url" class="form-label">Wiki URL</label>
                                    <input type="url" class="form-control" id="wiki_url" name="wiki_url">
                                </div>
                                <div class="mb-3">
                                    <label for="spec_version" class="form-label">Spec Version</label>
                                    <input type="text" class="form-control" id="spec_version" name="spec_version">
                                </div>
                                <div class="mb-3">
                                    <label for="spec_path" class="form-label">Spec Path</label>
                                    <input type="text" class="form-control" id="spec_path" name="spec_path">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="add_project" class="btn btn-primary">Add Project</button>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php elseif ($action === 'add_task'): ?>
            <h2>Add New DV Task</h2>
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_id" class="form-label">Project *</label>
                                    <select class="form-control" id="project_id" name="project_id" required>
                                        <option value="">Select Project</option>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="task_index" class="form-label">Task Index</label>
                                    <input type="text" class="form-control" id="task_index" name="task_index">
                                </div>
                                <div class="mb-3">
                                    <label for="dv_engineer" class="form-label">DV Engineer</label>
                                    <input type="text" class="form-control" id="dv_engineer" name="dv_engineer">
                                </div>
                                <div class="mb-3">
                                    <label for="digital_designer" class="form-label">Digital Designer</label>
                                    <input type="text" class="form-control" id="digital_designer" name="digital_designer">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_unit" class="form-label">Business Unit</label>
                                    <select class="form-control" id="business_unit" name="business_unit">
                                        <option value="">Select BU</option>
                                        <option value="CN">CN</option>
                                        <option value="PC">PC</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="analog_designer" class="form-label">Analog Designer</label>
                                    <input type="text" class="form-control" id="analog_designer" name="analog_designer">
                                </div>
                                <div class="mb-3">
                                    <label for="inherit_from_ip" class="form-label">Inherit from IP</label>
                                    <input type="text" class="form-control" id="inherit_from_ip" name="inherit_from_ip">
                                </div>
                                <div class="mb-3">
                                    <label for="reuse_ip" class="form-label">Re-use IP</label>
                                    <input type="text" class="form-control" id="reuse_ip" name="reuse_ip">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="add_task" class="btn btn-primary">Add Task</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>