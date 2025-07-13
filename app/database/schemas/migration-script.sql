-- Migration Script: Move from old schema to new unified schema
-- This script safely migrates data from the old two-table structure to the new unified structure

-- ========================================
-- IT Domain Migration
-- ========================================

USE it_domain_db;

-- Step 1: Create temporary backup tables
CREATE TABLE projects_backup AS SELECT * FROM projects;
CREATE TABLE dv_tasks_backup AS SELECT * FROM dv_tasks;

-- Step 2: Create new unified table structure
DROP TABLE IF EXISTS dv_tasks;
DROP TABLE IF EXISTS projects;
DROP VIEW IF EXISTS export_view;

-- Create the unified table (same as in it-domain-schema-new.sql)
CREATE TABLE it_domain_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_index VARCHAR(50) NOT NULL UNIQUE,
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
    dv_engineer VARCHAR(100),
    digital_designer VARCHAR(100),
    business_unit VARCHAR(10),
    analog_designer VARCHAR(100),
    inherit_from_ip VARCHAR(100),
    reuse_ip VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT chk_ip_subtype CHECK (ip_subtype IN ('default', 'gen2x1')),
    CONSTRAINT chk_business_unit CHECK (business_unit IN ('CN', 'PC', '') OR business_unit IS NULL),
    CONSTRAINT chk_reuse_ip CHECK (reuse_ip IN ('Y', 'N', '') OR reuse_ip IS NULL),
    CONSTRAINT chk_spip_url CHECK (spip_url = '' OR spip_url IS NULL OR spip_url LIKE 'http%'),
    CONSTRAINT chk_wiki_url CHECK (wiki_url = '' OR wiki_url IS NULL OR wiki_url LIKE 'http%')
);

-- Step 3: Migrate data from backup tables
INSERT INTO it_domain_projects (
    task_index, project_name, spip_ip, ip, ip_postfix, ip_subtype, alternative_name,
    spip_url, wiki_url, spec_version, spec_path,
    dv_engineer, digital_designer, business_unit, analog_designer, inherit_from_ip, reuse_ip,
    created_at, updated_at
)
SELECT 
    -- Generate task_index from existing data or create new one
    CASE 
        WHEN dt.task_index IS NOT NULL AND dt.task_index != '' THEN dt.task_index
        ELSE CONCAT('TASK', LPAD(ROW_NUMBER() OVER (ORDER BY p.id), 3, '0'))
    END as task_index,
    p.project_name,
    p.spip_ip,
    p.ip,
    p.ip_postfix,
    p.ip_subtype,
    p.alternative_name,
    p.spip_url,
    p.wiki_url,
    p.spec_version,
    p.spec_path,
    dt.dv_engineer,
    dt.digital_designer,
    dt.business_unit,
    dt.analog_designer,
    dt.inherit_from_ip,
    dt.reuse_ip,
    GREATEST(p.created_at, COALESCE(dt.created_at, p.created_at)) as created_at,
    GREATEST(p.updated_at, COALESCE(dt.updated_at, p.updated_at)) as updated_at
FROM projects_backup p
LEFT JOIN dv_tasks_backup dt ON p.id = dt.project_id;

-- Step 4: Create indexes
CREATE INDEX idx_project_name ON it_domain_projects(project_name);
CREATE INDEX idx_task_index ON it_domain_projects(task_index);
CREATE INDEX idx_dv_engineer ON it_domain_projects(dv_engineer);
CREATE INDEX idx_business_unit ON it_domain_projects(business_unit);

-- Step 5: Create trigger for auto-generating task_index for new records
DELIMITER //
CREATE TRIGGER generate_task_index 
BEFORE INSERT ON it_domain_projects
FOR EACH ROW
BEGIN
    DECLARE next_index INT;
    
    -- Only auto-generate if task_index is empty or NULL
    IF NEW.task_index IS NULL OR NEW.task_index = '' THEN
        -- Get the next sequence number
        SELECT COALESCE(MAX(CAST(SUBSTRING(task_index, 5) AS UNSIGNED)), 0) + 1 
        INTO next_index 
        FROM it_domain_projects 
        WHERE task_index REGEXP '^TASK[0-9]+$';
        
        -- Generate task_index in format TASK001, TASK002, etc.
        SET NEW.task_index = CONCAT('TASK', LPAD(next_index, 3, '0'));
    END IF;
END//
DELIMITER ;

-- Step 6: Create export view
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

-- Step 7: Clean up backup tables (optional - comment out if you want to keep them)
-- DROP TABLE projects_backup;
-- DROP TABLE dv_tasks_backup;

-- ========================================
-- NX Domain Migration
-- ========================================

USE nx_domain_db;

-- Step 1: Create backup tables
CREATE TABLE coverage_reports_backup AS SELECT * FROM coverage_reports;
CREATE TABLE version_control_backup AS SELECT * FROM version_control;
CREATE TABLE imported_it_data_backup AS SELECT * FROM imported_it_data;

-- Step 2: Drop old tables and views
DROP VIEW IF EXISTS to_summary_view;
DROP TABLE IF EXISTS imported_it_data;
DROP TABLE IF EXISTS version_control;
DROP TABLE IF EXISTS coverage_reports;

-- Step 3: Create new tables with validation (same as in nx-domain-schema-new.sql)
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
    
    CONSTRAINT chk_line_coverage CHECK (line_coverage IS NULL OR (line_coverage >= 0 AND line_coverage <= 100)),
    CONSTRAINT chk_fsm_coverage CHECK (fsm_coverage IS NULL OR (fsm_coverage >= 0 AND fsm_coverage <= 100)),
    CONSTRAINT chk_interface_toggle_coverage CHECK (interface_toggle_coverage IS NULL OR (interface_toggle_coverage >= 0 AND interface_toggle_coverage <= 100)),
    CONSTRAINT chk_toggle_coverage CHECK (toggle_coverage IS NULL OR (toggle_coverage >= 0 AND toggle_coverage <= 100)),
    CONSTRAINT chk_coverage_report_path CHECK (coverage_report_path IS NULL OR coverage_report_path = '' OR coverage_report_path LIKE '%.html' OR coverage_report_path LIKE '/project/%')
);

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
    
    CONSTRAINT chk_sanity_svn CHECK (sanity_svn IS NULL OR sanity_svn = '' OR sanity_svn LIKE 'http%'),
    CONSTRAINT chk_release_svn CHECK (release_svn IS NULL OR release_svn = '' OR release_svn LIKE 'http%'),
    CONSTRAINT chk_git_path CHECK (git_path IS NULL OR git_path = '' OR git_path LIKE 'ssh://git.%' OR git_path LIKE 'https://git%'),
    CONSTRAINT chk_git_version CHECK (git_version IS NULL OR git_version = '' OR CHAR_LENGTH(git_version) = 40 OR CHAR_LENGTH(git_version) <= 10)
);

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
    
    CONSTRAINT chk_it_business_unit CHECK (business_unit IS NULL OR business_unit = '' OR business_unit IN ('CN', 'PC')),
    CONSTRAINT chk_it_reuse_ip CHECK (reuse_ip IS NULL OR reuse_ip = '' OR reuse_ip IN ('Y', 'N')),
    CONSTRAINT chk_it_spip_url CHECK (spip_url IS NULL OR spip_url = '' OR spip_url LIKE 'http%'),
    CONSTRAINT chk_it_wiki_url CHECK (wiki_url IS NULL OR wiki_url = '' OR wiki_url LIKE 'http%'),
    CONSTRAINT chk_it_ip_subtype CHECK (ip_subtype IS NULL OR ip_subtype = '' OR ip_subtype IN ('default', 'gen2x1'))
);

-- Step 4: Migrate data from backup tables
INSERT INTO coverage_reports SELECT * FROM coverage_reports_backup;
INSERT INTO version_control SELECT * FROM version_control_backup;
INSERT INTO imported_it_data SELECT * FROM imported_it_data_backup;

-- Step 5: Create indexes
CREATE INDEX idx_coverage_project ON coverage_reports(project_name);
CREATE INDEX idx_version_project ON version_control(project_name);
CREATE INDEX idx_imported_project ON imported_it_data(project_name);
CREATE INDEX idx_imported_task_index ON imported_it_data(task_index);
CREATE INDEX idx_coverage_to_date ON coverage_reports(to_date);
CREATE INDEX idx_coverage_line_coverage ON coverage_reports(line_coverage);

-- Step 6: Create enhanced TO Summary View with all 33 fields
CREATE VIEW to_summary_view AS
SELECT 
    it.task_index,
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

-- Step 7: Clean up backup tables (optional)
-- DROP TABLE coverage_reports_backup;
-- DROP TABLE version_control_backup;
-- DROP TABLE imported_it_data_backup;

-- Migration complete message
SELECT 'Migration completed successfully. Please verify your data and test the applications.' as message;