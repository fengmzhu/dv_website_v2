<?php
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function parseCSV($file_path) {
    $data = [];
    if (($handle = fopen($file_path, "r")) !== FALSE) {
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== FALSE) {
            $data[] = array_combine($headers, $row);
        }
        fclose($handle);
    }
    return $data;
}

function importITData($db, $csv_data) {
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO imported_it_data 
            (project_name, task_index, spip_ip, ip, ip_postfix, ip_subtype, alternative_name, 
             dv_engineer, digital_designer, business_unit, analog_designer, inherit_from_ip, 
             reuse_ip, spip_url, wiki_url, spec_version, spec_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            task_index = VALUES(task_index),
            spip_ip = VALUES(spip_ip),
            ip = VALUES(ip),
            ip_postfix = VALUES(ip_postfix),
            ip_subtype = VALUES(ip_subtype),
            alternative_name = VALUES(alternative_name),
            dv_engineer = VALUES(dv_engineer),
            digital_designer = VALUES(digital_designer),
            business_unit = VALUES(business_unit),
            analog_designer = VALUES(analog_designer),
            inherit_from_ip = VALUES(inherit_from_ip),
            reuse_ip = VALUES(reuse_ip),
            spip_url = VALUES(spip_url),
            wiki_url = VALUES(wiki_url),
            spec_version = VALUES(spec_version),
            spec_path = VALUES(spec_path)
        ");
        
        foreach ($csv_data as $row) {
            try {
                // Validate required fields
                if (empty($row['project_name'])) {
                    $error_count++;
                    $errors[] = "Missing project name in row";
                    continue;
                }
                
                // Validate business unit
                $business_unit = $row['business_unit'] ?? '';
                if (!empty($business_unit) && !in_array($business_unit, ['CN', 'PC'])) {
                    $error_count++;
                    $errors[] = "Invalid business unit: " . $business_unit;
                    continue;
                }
                
                // Validate reuse IP
                $reuse_ip = $row['reuse_ip'] ?? '';
                if (!empty($reuse_ip) && !in_array($reuse_ip, ['Y', 'N'])) {
                    $error_count++;
                    $errors[] = "Invalid reuse IP value: " . $reuse_ip;
                    continue;
                }
                
                // Validate URLs
                $spip_url = $row['spip_url'] ?? '';
                if (!empty($spip_url) && !filter_var($spip_url, FILTER_VALIDATE_URL)) {
                    $error_count++;
                    $errors[] = "Invalid SPIP URL: " . $spip_url;
                    continue;
                }
                
                $wiki_url = $row['wiki_url'] ?? '';
                if (!empty($wiki_url) && !filter_var($wiki_url, FILTER_VALIDATE_URL)) {
                    $error_count++;
                    $errors[] = "Invalid Wiki URL: " . $wiki_url;
                    continue;
                }
                
                $stmt->execute([
                    $row['project_name'],
                    $row['task_index'] ?? '',
                    $row['spip_ip'] ?? '',
                    $row['ip'] ?? '',
                    $row['ip_postfix'] ?? '',
                    $row['ip_subtype'] ?? 'default',
                    $row['alternative_name'] ?? '',
                    $row['dv_engineer'] ?? '',
                    $row['digital_designer'] ?? '',
                    $business_unit,
                    $row['analog_designer'] ?? '',
                    $row['inherit_from_ip'] ?? '',
                    $reuse_ip,
                    $spip_url,
                    $wiki_url,
                    $row['spec_version'] ?? '',
                    $row['spec_path'] ?? ''
                ]);
                $success_count++;
            } catch (PDOException $e) {
                $error_count++;
                $errors[] = "Database error for project " . ($row['project_name'] ?? 'unknown') . ": " . $e->getMessage();
            }
        }
        
        $db->commit();
        
    } catch (Exception $e) {
        $db->rollback();
        $errors[] = "Transaction error: " . $e->getMessage();
    }
    
    return [
        'success_count' => $success_count,
        'error_count' => $error_count,
        'errors' => $errors
    ];
}

function showAlert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">' 
           . $message . 
           '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

function formatPercentage($value) {
    if ($value === null || $value === '') {
        return 'N/A';
    }
    return number_format($value, 1) . '%';
}

function formatCoverageBadge($value) {
    if ($value === null || $value === '') {
        return '<span class="badge bg-secondary coverage-badge">N/A</span>';
    }
    
    $percentage = (float)$value;
    $color = 'danger';
    
    if ($percentage >= 90) {
        $color = 'success';
    } elseif ($percentage >= 80) {
        $color = 'warning';
    } elseif ($percentage >= 70) {
        $color = 'info';
    }
    
    return '<span class="badge bg-' . $color . ' coverage-badge">' . number_format($percentage, 1) . '%</span>';
}

function formatDate($date) {
    if (!$date || $date === '0000-00-00') {
        return '<span class="text-muted">N/A</span>';
    }
    return date('Y-m-d', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return '<span class="text-muted">N/A</span>';
    }
    return date('Y-m-d H:i', strtotime($datetime));
}

function formatTimestamp($timestamp) {
    if (!$timestamp || $timestamp === '0000-00-00 00:00:00') {
        return '<span class="text-muted">N/A</span>';
    }
    return '<span title="' . date('Y-m-d H:i:s', strtotime($timestamp)) . '">' . date('M j, Y', strtotime($timestamp)) . '</span>';
}

function formatGitHash($hash) {
    if (!$hash || $hash === '') {
        return '<span class="text-muted">N/A</span>';
    }
    
    $length = strlen($hash);
    if ($length === 40) {
        // Full Git hash - show first 8 characters
        return '<code class="git-hash" title="' . htmlspecialchars($hash) . '">' . substr($hash, 0, 8) . '</code>';
    } elseif ($length <= 10) {
        // Short hash or version
        return '<code class="git-hash">' . htmlspecialchars($hash) . '</code>';
    } else {
        // Medium length hash
        return '<code class="git-hash" title="' . htmlspecialchars($hash) . '">' . substr($hash, 0, 10) . '...</code>';
    }
}

function formatUrl($url, $text = null) {
    if (!$url || $url === '') {
        return '<span class="text-muted">N/A</span>';
    }
    
    $display_text = $text ?? (strlen($url) > 30 ? substr($url, 0, 30) . '...' : $url);
    return '<a href="' . htmlspecialchars($url) . '" target="_blank" title="' . htmlspecialchars($url) . '">' . htmlspecialchars($display_text) . '</a>';
}

function formatPath($path) {
    if (!$path || $path === '') {
        return '<span class="text-muted">N/A</span>';
    }
    
    $display_path = strlen($path) > 40 ? '...' . substr($path, -37) : $path;
    return '<span title="' . htmlspecialchars($path) . '">' . htmlspecialchars($display_path) . '</span>';
}

function validateCoveragePercentage($value) {
    if ($value === null || $value === '') {
        return true; // Allow null values
    }
    
    $percentage = (float)$value;
    return $percentage >= 0 && $percentage <= 100;
}

function validateGitHash($hash) {
    if ($hash === null || $hash === '') {
        return true; // Allow null values
    }
    
    $length = strlen($hash);
    // Valid git hashes are typically 40 characters (full) or 7-10 characters (short)
    return ($length === 40 && ctype_xdigit($hash)) || ($length >= 7 && $length <= 10);
}

function validateUrl($url) {
    if ($url === null || $url === '') {
        return true; // Allow null values
    }
    
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function generateTOSummaryReport($db, $format = 'json') {
    $stmt = $db->query("SELECT * FROM to_summary_view ORDER BY project");
    $data = $stmt->fetchAll();
    
    switch ($format) {
        case 'csv':
            return generateCSVReport($data);
        case 'json':
            return json_encode($data, JSON_PRETTY_PRINT);
        case 'xml':
            return generateXMLReport($data);
        default:
            return $data;
    }
}

function generateCSVReport($data) {
    if (empty($data)) {
        return '';
    }
    
    $output = fopen('php://temp', 'w');
    
    // Write headers
    fputcsv($output, array_keys($data[0]));
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    rewind($output);
    $csv_content = stream_get_contents($output);
    fclose($output);
    
    return $csv_content;
}

function generateXMLReport($data) {
    $xml = new SimpleXMLElement('<to_summary_report/>');
    
    foreach ($data as $row) {
        $project = $xml->addChild('project');
        foreach ($row as $key => $value) {
            $project->addChild($key, htmlspecialchars($value));
        }
    }
    
    return $xml->asXML();
}

function logError($message, $context = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context
    ];
    
    error_log(json_encode($log_entry));
}

function logImportActivity($action, $project_name, $details = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'project' => $project_name,
        'details' => $details
    ];
    
    error_log("IMPORT_ACTIVITY: " . json_encode($log_entry));
}
?>