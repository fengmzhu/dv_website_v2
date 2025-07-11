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
            (project_name, spip_ip, ip, ip_postfix, ip_subtype, alternative_name, task_index, 
             dv_engineer, digital_designer, business_unit, analog_designer, inherit_from_ip, 
             reuse_ip, spip_url, wiki_url, spec_version, spec_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            spip_ip = VALUES(spip_ip),
            ip = VALUES(ip),
            ip_postfix = VALUES(ip_postfix),
            ip_subtype = VALUES(ip_subtype),
            alternative_name = VALUES(alternative_name),
            task_index = VALUES(task_index),
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
                $stmt->execute([
                    $row['project_name'] ?? '',
                    $row['spip_ip'] ?? '',
                    $row['ip'] ?? '',
                    $row['ip_postfix'] ?? '',
                    $row['ip_subtype'] ?? 'default',
                    $row['alternative_name'] ?? '',
                    $row['task_index'] ?? '',
                    $row['dv_engineer'] ?? '',
                    $row['digital_designer'] ?? '',
                    $row['business_unit'] ?? '',
                    $row['analog_designer'] ?? '',
                    $row['inherit_from_ip'] ?? '',
                    $row['reuse_ip'] ?? '',
                    $row['spip_url'] ?? '',
                    $row['wiki_url'] ?? '',
                    $row['spec_version'] ?? '',
                    $row['spec_path'] ?? ''
                ]);
                $success_count++;
            } catch (PDOException $e) {
                $error_count++;
                $errors[] = "Row error: " . $e->getMessage();
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
    return $value ? number_format($value, 1) . '%' : 'N/A';
}

function formatDate($date) {
    return $date ? date('Y-m-d', strtotime($date)) : 'N/A';
}

function formatDateTime($datetime) {
    return $datetime ? date('Y-m-d H:i', strtotime($datetime)) : 'N/A';
}
?>