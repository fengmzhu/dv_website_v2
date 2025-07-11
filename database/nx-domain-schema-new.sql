-- NX Domain Database Schema - Enhanced with Data Validation
CREATE DATABASE IF NOT EXISTS nx_domain_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nx_domain_db;

-- Drop existing tables if they exist (for migration)
DROP VIEW IF EXISTS to_summary_view;
DROP TABLE IF EXISTS imported_it_data;
DROP TABLE IF EXISTS version_control;
DROP TABLE IF EXISTS coverage_reports;

-- Coverage Reports table - DV regression results with validation
CREATE TABLE coverage_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(100) NOT NULL UNIQUE,
    line_coverage DECIMAL(5,2),
    fsm_coverage DECIMAL(5,2),
    interface_toggle_coverage DECIMAL(5,2),
    toggle_coverage DECIMAL(5,2),
    coverage_report_path VARCHAR(500),
    to_date DATE,
    rtl_last_update TIMESTAMP,
    to_report_creation TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Validation constraints for coverage percentages (0-100)
    CONSTRAINT chk_line_coverage CHECK (line_coverage IS NULL OR (line_coverage >= 0 AND line_coverage <= 100)),
    CONSTRAINT chk_fsm_coverage CHECK (fsm_coverage IS NULL OR (fsm_coverage >= 0 AND fsm_coverage <= 100)),
    CONSTRAINT chk_interface_toggle_coverage CHECK (interface_toggle_coverage IS NULL OR (interface_toggle_coverage >= 0 AND interface_toggle_coverage <= 100)),
    CONSTRAINT chk_toggle_coverage CHECK (toggle_coverage IS NULL OR (toggle_coverage >= 0 AND toggle_coverage <= 100)),
    CONSTRAINT chk_coverage_report_path CHECK (coverage_report_path IS NULL OR coverage_report_path = '' OR coverage_report_path LIKE '%.html' OR coverage_report_path LIKE '/project/%')
);

-- Version Control table - SVN/Git information with validation
CREATE TABLE version_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(100) NOT NULL UNIQUE,
    sanity_svn VARCHAR(500),
    sanity_svn_ver VARCHAR(100),
    release_svn VARCHAR(500),
    release_svn_ver VARCHAR(100),
    git_path VARCHAR(500),
    git_version VARCHAR(100),
    golden_checklist VARCHAR(500),
    golden_checklist_version VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Validation constraints
    CONSTRAINT chk_sanity_svn CHECK (sanity_svn IS NULL OR sanity_svn = '' OR sanity_svn LIKE 'http%'),
    CONSTRAINT chk_release_svn CHECK (release_svn IS NULL OR release_svn = '' OR release_svn LIKE 'http%'),
    CONSTRAINT chk_git_path CHECK (git_path IS NULL OR git_path = '' OR git_path LIKE 'ssh://git.%' OR git_path LIKE 'https://git%'),
    CONSTRAINT chk_git_version CHECK (git_version IS NULL OR git_version = '' OR CHAR_LENGTH(git_version) = 40 OR CHAR_LENGTH(git_version) <= 10)
);

-- Imported IT Data table - Data imported from IT domain with validation
CREATE TABLE imported_it_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(100) NOT NULL,
    task_index VARCHAR(50),
    spip_ip VARCHAR(100),
    ip VARCHAR(100),
    ip_postfix VARCHAR(50),
    ip_subtype VARCHAR(50),
    alternative_name VARCHAR(100),
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
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Validation constraints
    CONSTRAINT chk_it_business_unit CHECK (business_unit IS NULL OR business_unit = '' OR business_unit IN ('CN', 'PC')),
    CONSTRAINT chk_it_reuse_ip CHECK (reuse_ip IS NULL OR reuse_ip = '' OR reuse_ip IN ('Y', 'N')),
    CONSTRAINT chk_it_spip_url CHECK (spip_url IS NULL OR spip_url = '' OR spip_url LIKE 'http%'),
    CONSTRAINT chk_it_wiki_url CHECK (wiki_url IS NULL OR wiki_url = '' OR wiki_url LIKE 'http%'),
    CONSTRAINT chk_it_ip_subtype CHECK (ip_subtype IS NULL OR ip_subtype = '' OR ip_subtype IN ('default', 'gen2x1'))
);

-- Enhanced TO Summary View - Complete view combining all data with all 33 fields
CREATE VIEW to_summary_view AS
SELECT 
    -- Field 1: Index (task_index)
    it.task_index,
    -- Field 2: Project
    COALESCE(it.project_name, cr.project_name, vc.project_name) AS project,
    -- Field 3: SPIP_IP
    it.spip_ip,
    -- Field 4: IP
    it.ip,
    -- Field 5: IP Postfix
    it.ip_postfix,
    -- Field 6: IP Subtype
    it.ip_subtype,
    -- Field 7: Alternative Name
    it.alternative_name,
    -- Field 8: Line Coverage
    cr.line_coverage,
    -- Field 9: FSM Coverage
    cr.fsm_coverage,
    -- Field 10: Interface Toggle Coverage
    cr.interface_toggle_coverage,
    -- Field 11: Toggle Coverage
    cr.toggle_coverage,
    -- Field 12: Coverage Report Path
    cr.coverage_report_path,
    -- Field 13: DV
    it.dv_engineer,
    -- Field 14: DD
    it.digital_designer,
    -- Field 15: BU
    it.business_unit,
    -- Field 16: sanity SVN
    vc.sanity_svn,
    -- Field 17: sanity SVN ver
    vc.sanity_svn_ver,
    -- Field 18: release SVN
    vc.release_svn,
    -- Field 19: release SVN ver
    vc.release_svn_ver,
    -- Field 20: git path
    vc.git_path,
    -- Field 21: git version
    vc.git_version,
    -- Field 22: golden checklist
    vc.golden_checklist,
    -- Field 23: golden checklist version
    vc.golden_checklist_version,
    -- Field 24: TO Date
    cr.to_date,
    -- Field 25: RTL last update timestamp
    cr.rtl_last_update,
    -- Field 26: TO report creation timestamp
    cr.to_report_creation,
    -- Field 27: SPIP url
    it.spip_url,
    -- Field 28: Wiki url
    it.wiki_url,
    -- Field 29: spec version
    it.spec_version,
    -- Field 30: spec path
    it.spec_path,
    -- Field 31: AD
    it.analog_designer,
    -- Field 32: Inherit from IP
    it.inherit_from_ip,
    -- Field 33: re-use IP
    it.reuse_ip
FROM imported_it_data it
LEFT JOIN coverage_reports cr ON it.project_name = cr.project_name
LEFT JOIN version_control vc ON it.project_name = vc.project_name

UNION ALL

-- Include NX domain data that doesn't have corresponding IT domain data
SELECT 
    NULL as task_index,
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

ORDER BY project;

-- Create indexes for better performance
CREATE INDEX idx_coverage_project ON coverage_reports(project_name);
CREATE INDEX idx_version_project ON version_control(project_name);
CREATE INDEX idx_imported_project ON imported_it_data(project_name);
CREATE INDEX idx_imported_task_index ON imported_it_data(task_index);
CREATE INDEX idx_coverage_to_date ON coverage_reports(to_date);
CREATE INDEX idx_coverage_line_coverage ON coverage_reports(line_coverage);

-- Sample data insertion with validation
INSERT INTO coverage_reports (
    project_name, line_coverage, fsm_coverage, interface_toggle_coverage, toggle_coverage,
    coverage_report_path, to_date, rtl_last_update, to_report_creation
) VALUES 
(
    'RLE1339', 95.5, 88.2, 92.1, 87.5,
    '/project/coverage/RLE1339_coverage.html',
    '2025-06-23', '2025-06-20 14:30:00', '2025-06-23 09:32:12'
),
(
    'RL1234', 89.3, 91.7, 85.4, 90.2,
    '/project/coverage/RL1234_coverage.html',
    '2025-06-22', '2025-06-19 16:45:00', '2025-06-22 11:15:30'
);

-- Sample version control data with validation
INSERT INTO version_control (
    project_name, sanity_svn, sanity_svn_ver, release_svn, release_svn_ver,
    git_path, git_version, golden_checklist, golden_checklist_version
) VALUES 
(
    'RLE1339', 'http://dtdinfo/svn/RD/RLE1339/sanity', '12345',
    'http://dtdinfo/svn/RD/RLE1339/release', '12340',
    'ssh://git.xxx/RLE1339.git', 'abc123def456789012345678901234567890abcd',
    '/golden/RLE1339_checklist.xls', 'v2.0'
),
(
    'RL1234', 'http://dtdinfo/svn/RD/RL1234/sanity', '23456',
    'http://dtdinfo/svn/RD/RL1234/release', '23450',
    'ssh://git.xxx/RL1234.git', 'def456ghi789012345678901234567890123456',
    '/golden/RL1234_checklist.xls', 'v1.5'
);