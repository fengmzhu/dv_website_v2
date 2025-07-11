-- Sample data for IT Domain based on JSON examples
USE it_domain_db;

-- Insert sample projects
INSERT INTO projects (project_name, spip_ip, ip, ip_postfix, ip_subtype, alternative_name, spip_url, wiki_url, spec_version, spec_path) VALUES
('RLE1339', 'XXXX_AFE', 'AFE', '', 'default', '', 'https://jira.rd.realtek.com/browse/SPIP-1234', 'https://wiki.realtek.com/display/RDCDIGITAL/AFE+Specification', 'v1.0', '/specs/afe_v1.0.pdf'),
('RL1234', 'XXXX_pcie', 'pcie', '', 'default', '', 'https://jira.rd.realtek.com/browse/SPIP-5678', 'https://wiki.realtek.com/display/RDCDIGITAL/PCIe+Specification', 'v2.1', '/specs/pcie_v2.1.pdf');

-- Insert sample DV tasks
INSERT INTO dv_tasks (project_id, task_index, dv_engineer, digital_designer, business_unit, analog_designer, inherit_from_ip, reuse_ip) VALUES
(1, 'TASK001', 'LI', 'Jimmy', 'CN', 'John_AD', '', ''),
(2, 'TASK002', 'CH', 'Jimmy', 'PC', 'Jane_AD', '', '');