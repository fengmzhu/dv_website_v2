-- IT Domain Database Schema
CREATE DATABASE IF NOT EXISTS it_domain_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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