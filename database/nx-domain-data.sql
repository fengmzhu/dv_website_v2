-- Sample data for NX Domain based on JSON examples
USE nx_domain_db;

-- Insert sample coverage reports
INSERT INTO coverage_reports (project_name, line_coverage, fsm_coverage, interface_toggle_coverage, toggle_coverage, coverage_report_path, to_date, rtl_last_update, to_report_creation) VALUES
('RLE1339', 95.5, 88.2, 92.1, 87.5, '/project/coverage/RLE1339_coverage.html', '2025-06-23', '2025-06-20 14:30:00', '2025-06-23 09:32:12'),
('RL1234', 89.3, 91.7, 85.4, 90.2, '/project/coverage/RL1234_coverage.html', '2025-06-22', '2025-06-19 16:45:00', '2025-06-22 11:15:30');

-- Insert sample version control data
INSERT INTO version_control (project_name, sanity_svn, sanity_svn_ver, release_svn, release_svn_ver, git_path, git_version, golden_checklist, golden_checklist_version) VALUES
('RLE1339', 'http://dtdinfo/svn/RD/RLE1339/sanity', '12345', 'http://dtdinfo/svn/RD/RLE1339/release', '12340', 'ssh://git.xxx/RLE1339.git', 'abc123def', '/golden/RLE1339_checklist.xls', 'v2.0'),
('RL1234', 'http://dtdinfo/svn/RD/RL1234/sanity', '23456', 'http://dtdinfo/svn/RD/RL1234/release', '23450', 'ssh://git.xxx/RL1234.git', 'def456ghi', '/golden/RL1234_checklist.xls', 'v1.5');