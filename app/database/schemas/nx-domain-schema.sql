-- NX Domain Database Schema
CREATE DATABASE IF NOT EXISTS nx_domain_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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