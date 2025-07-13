-- IT Domain Database Schema - Unified Structure for TO Report Compliance
CREATE DATABASE IF NOT EXISTS it_domain_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_domain_db;

-- Drop existing tables if they exist (for migration)
DROP TABLE IF EXISTS dv_tasks;
DROP TABLE IF EXISTS projects;
DROP VIEW IF EXISTS export_view;

-- Unified IT Domain Projects table - Contains all 17 TO Report fields
CREATE TABLE it_domain_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Auto-generated fields
    task_index VARCHAR(50) NOT NULL UNIQUE,
    
    -- Basic project information (from original projects table)
    project_name VARCHAR(100) NOT NULL UNIQUE,
    spip_ip VARCHAR(100),
    ip VARCHAR(100),
    ip_postfix VARCHAR(50),
    ip_subtype VARCHAR(50) DEFAULT 'default',
    alternative_name VARCHAR(100),
    spip_url VARCHAR(500),
    wiki_url VARCHAR(500),
    spec_version VARCHAR(50),
    spec_path VARCHAR(500),
    
    -- Personnel and task information (from original dv_tasks table)
    dv_engineer VARCHAR(100),
    digital_designer VARCHAR(100),
    business_unit VARCHAR(10),
    analog_designer VARCHAR(100),
    inherit_from_ip VARCHAR(100),
    reuse_ip VARCHAR(100),
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints for data validation
    CONSTRAINT chk_ip_subtype CHECK (ip_subtype IN ('default', 'gen2x1')),
    CONSTRAINT chk_business_unit CHECK (business_unit IN ('CN', 'PC', '') OR business_unit IS NULL),
    CONSTRAINT chk_reuse_ip CHECK (reuse_ip IN ('Y', 'N', '') OR reuse_ip IS NULL),
    CONSTRAINT chk_spip_url CHECK (spip_url = '' OR spip_url IS NULL OR spip_url LIKE 'http%'),
    CONSTRAINT chk_wiki_url CHECK (wiki_url = '' OR wiki_url IS NULL OR wiki_url LIKE 'http%')
);

-- Create indexes for better performance
CREATE INDEX idx_project_name ON it_domain_projects(project_name);
CREATE INDEX idx_task_index ON it_domain_projects(task_index);
CREATE INDEX idx_dv_engineer ON it_domain_projects(dv_engineer);
CREATE INDEX idx_business_unit ON it_domain_projects(business_unit);

-- Create trigger for auto-generating task_index
DELIMITER //
CREATE TRIGGER generate_task_index 
BEFORE INSERT ON it_domain_projects
FOR EACH ROW
BEGIN
    DECLARE next_index INT;
    
    -- Get the next sequence number
    SELECT COALESCE(MAX(CAST(SUBSTRING(task_index, 5) AS UNSIGNED)), 0) + 1 
    INTO next_index 
    FROM it_domain_projects 
    WHERE task_index REGEXP '^TASK[0-9]+$';
    
    -- Generate task_index in format TASK001, TASK002, etc.
    SET NEW.task_index = CONCAT('TASK', LPAD(next_index, 3, '0'));
END//
DELIMITER ;

-- Create view for backward compatibility and easy data export
CREATE VIEW export_view AS
SELECT 
    task_index,
    project_name,
    spip_ip,
    ip,
    ip_postfix,
    ip_subtype,
    alternative_name,
    dv_engineer,
    digital_designer,
    business_unit,
    analog_designer,
    inherit_from_ip,
    reuse_ip,
    spip_url,
    wiki_url,
    spec_version,
    spec_path,
    created_at,
    updated_at
FROM it_domain_projects
ORDER BY task_index;

-- Create sequence management table for task_index generation
CREATE TABLE task_sequence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    last_sequence INT DEFAULT 0
);

-- Insert initial sequence value
INSERT INTO task_sequence (last_sequence) VALUES (0);

-- Sample data insertion (will auto-generate task_index)
INSERT INTO it_domain_projects (
    project_name, spip_ip, ip, ip_postfix, ip_subtype, alternative_name,
    dv_engineer, digital_designer, business_unit, analog_designer,
    inherit_from_ip, reuse_ip, spip_url, wiki_url, spec_version, spec_path
) VALUES 
(
    'RLE1339', 'XXXX_AFE', 'AFE', '', 'default', '',
    'LI', 'Jimmy', 'CN', 'John_AD',
    '', '', 'https://jira.rd.realtek.com/browse/SPIP-1234',
    'https://wiki.realtek.com/display/RDCDIGITAL/AFE+Specification',
    'v1.0', '/specs/afe_v1.0.pdf'
),
(
    'RL1234', 'XXXX_pcie', 'pcie', '', 'default', '',
    'CH', 'Jimmy', 'PC', 'Jane_AD',
    '', '', 'https://jira.rd.realtek.com/browse/SPIP-5678',
    'https://wiki.realtek.com/display/RDCDIGITAL/PCIe+Specification',
    'v2.1', '/specs/pcie_v2.1.pdf'
);