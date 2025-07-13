-- Create both databases and users
CREATE DATABASE IF NOT EXISTS it_domain_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS nx_domain_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create users
CREATE USER IF NOT EXISTS 'it_user'@'%' IDENTIFIED BY 'it_password';
CREATE USER IF NOT EXISTS 'nx_user'@'%' IDENTIFIED BY 'nx_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON it_domain_db.* TO 'it_user'@'%';
GRANT ALL PRIVILEGES ON nx_domain_db.* TO 'nx_user'@'%';
FLUSH PRIVILEGES;

-- Initialize IT Domain database
USE it_domain_db;

-- Projects table - Basic project information
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- DV Tasks table - Task assignments and personnel
CREATE TABLE dv_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    task_index VARCHAR(50),
    dv_engineer VARCHAR(100),
    digital_designer VARCHAR(100),
    business_unit VARCHAR(10),
    analog_designer VARCHAR(100),
    inherit_from_ip VARCHAR(100),
    reuse_ip VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_project_name ON projects(project_name);
CREATE INDEX idx_dv_tasks_project ON dv_tasks(project_id);

-- Create a view for easy data export
CREATE VIEW export_view AS
SELECT 
    p.project_name,
    p.spip_ip,
    p.ip,
    p.ip_postfix,
    p.ip_subtype,
    p.alternative_name,
    dt.task_index,
    dt.dv_engineer,
    dt.digital_designer,
    dt.business_unit,
    dt.analog_designer,
    dt.inherit_from_ip,
    dt.reuse_ip,
    p.spip_url,
    p.wiki_url,
    p.spec_version,
    p.spec_path
FROM projects p
LEFT JOIN dv_tasks dt ON p.id = dt.project_id;

-- Insert sample projects
INSERT INTO projects (project_name, spip_ip, ip, ip_postfix, ip_subtype, alternative_name, spip_url, wiki_url, spec_version, spec_path) VALUES
('RLE1339', 'XXXX_AFE', 'AFE', '', 'default', '', 'https://jira.rd.realtek.com/browse/SPIP-1234', 'https://wiki.realtek.com/display/RDCDIGITAL/AFE+Specification', 'v1.0', '/specs/afe_v1.0.pdf'),
('RL1234', 'XXXX_pcie', 'pcie', '', 'default', '', 'https://jira.rd.realtek.com/browse/SPIP-5678', 'https://wiki.realtek.com/display/RDCDIGITAL/PCIe+Specification', 'v2.1', '/specs/pcie_v2.1.pdf');

-- Insert sample DV tasks
INSERT INTO dv_tasks (project_id, task_index, dv_engineer, digital_designer, business_unit, analog_designer, inherit_from_ip, reuse_ip) VALUES
(1, 'TASK001', 'LI', 'Jimmy', 'CN', 'John_AD', '', ''),
(2, 'TASK002', 'CH', 'Jimmy', 'PC', 'Jane_AD', '', '');

-- Initialize NX Domain database
USE nx_domain_db;

-- Coverage Reports table - DV regression results
CREATE TABLE coverage_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(100) NOT NULL,
    line_coverage DECIMAL(5,2),
    fsm_coverage DECIMAL(5,2),
    interface_toggle_coverage DECIMAL(5,2),
    toggle_coverage DECIMAL(5,2),
    coverage_report_path VARCHAR(500),
    to_date DATE,
    rtl_last_update TIMESTAMP,
    to_report_creation TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Version Control table - SVN/Git information
CREATE TABLE version_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(100) NOT NULL,
    sanity_svn VARCHAR(500),
    sanity_svn_ver VARCHAR(100),
    release_svn VARCHAR(500),
    release_svn_ver VARCHAR(100),
    git_path VARCHAR(500),
    git_version VARCHAR(100),
    golden_checklist VARCHAR(500),
    golden_checklist_version VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Imported IT Data table - Data imported from IT domain
CREATE TABLE imported_it_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(100) NOT NULL,
    spip_ip VARCHAR(100),
    ip VARCHAR(100),
    ip_postfix VARCHAR(50),
    ip_subtype VARCHAR(50),
    alternative_name VARCHAR(100),
    task_index VARCHAR(50),
    dv_engineer VARCHAR(100),
    digital_designer VARCHAR(100),
    business_unit VARCHAR(10),
    analog_designer VARCHAR(100),
    inherit_from_ip VARCHAR(100),
    reuse_ip VARCHAR(100),
    spip_url VARCHAR(500),
    wiki_url VARCHAR(500),
    spec_version VARCHAR(50),
    spec_path VARCHAR(500),
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TO Summary View - Complete view combining all data (MySQL compatible)
CREATE VIEW to_summary_view AS
SELECT 
    it.id,
    it.project_name AS project,
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
LEFT JOIN version_control vc ON it.project_name = vc.project_name;

-- Create indexes for better performance
CREATE INDEX idx_coverage_project ON coverage_reports(project_name);
CREATE INDEX idx_version_project ON version_control(project_name);
CREATE INDEX idx_imported_project ON imported_it_data(project_name);

-- Insert sample coverage reports
INSERT INTO coverage_reports (project_name, line_coverage, fsm_coverage, interface_toggle_coverage, toggle_coverage, coverage_report_path, to_date, rtl_last_update, to_report_creation) VALUES
('RLE1339', 95.5, 88.2, 92.1, 87.5, '/project/coverage/RLE1339_coverage.html', '2025-06-23', '2025-06-20 14:30:00', '2025-06-23 09:32:12'),
('RL1234', 89.3, 91.7, 85.4, 90.2, '/project/coverage/RL1234_coverage.html', '2025-06-22', '2025-06-19 16:45:00', '2025-06-22 11:15:30');

-- Insert sample version control data
INSERT INTO version_control (project_name, sanity_svn, sanity_svn_ver, release_svn, release_svn_ver, git_path, git_version, golden_checklist, golden_checklist_version) VALUES
('RLE1339', 'http://dtdinfo/svn/RD/RLE1339/sanity', '12345', 'http://dtdinfo/svn/RD/RLE1339/release', '12340', 'ssh://git.xxx/RLE1339.git', 'abc123def', '/golden/RLE1339_checklist.xls', 'v2.0'),
('RL1234', 'http://dtdinfo/svn/RD/RL1234/sanity', '23456', 'http://dtdinfo/svn/RD/RL1234/release', '23450', 'ssh://git.xxx/RL1234.git', 'def456ghi', '/golden/RL1234_checklist.xls', 'v1.5');