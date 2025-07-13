#!/usr/bin/env python3
"""
Database Schema Validation Script
This script validates the database schemas for the DV Website deployment
without requiring Docker or MySQL to be installed.
"""

import os
import re
import sys
from datetime import datetime

def print_status(message):
    print(f"\033[0;32m[INFO]\033[0m {message}")

def print_warning(message):
    print(f"\033[1;33m[WARNING]\033[0m {message}")

def print_error(message):
    print(f"\033[0;31m[ERROR]\033[0m {message}")

def print_step(message):
    print(f"\033[0;34m[STEP]\033[0m {message}")

def validate_sql_file(filepath):
    """Validate SQL file syntax and structure"""
    if not os.path.exists(filepath):
        return False, f"File not found: {filepath}"
    
    try:
        with open(filepath, 'r') as f:
            content = f.read()
        
        # Check for basic SQL syntax
        if not content.strip():
            return False, "Empty file"
        
        # Check for common SQL keywords
        keywords = ['CREATE', 'INSERT', 'SELECT', 'DROP', 'ALTER']
        if not any(keyword in content.upper() for keyword in keywords):
            return False, "No SQL keywords found"
        
        # Check for balanced parentheses
        if content.count('(') != content.count(')'):
            return False, "Unbalanced parentheses"
        
        return True, "SQL file syntax appears valid"
    
    except Exception as e:
        return False, f"Error reading file: {str(e)}"

def parse_create_table_statements(content):
    """Parse CREATE TABLE statements from SQL content"""
    tables = {}
    
    # Find all CREATE TABLE statements
    create_table_pattern = r'CREATE TABLE\s+(\w+)\s*\((.*?)\);'
    matches = re.findall(create_table_pattern, content, re.DOTALL | re.IGNORECASE)
    
    for table_name, table_def in matches:
        # Parse column definitions
        columns = []
        for line in table_def.split('\n'):
            line = line.strip()
            if line and not line.startswith('--') and not line.startswith('CONSTRAINT'):
                # Extract column name and type
                if line.endswith(','):
                    line = line[:-1]
                if ' ' in line:
                    col_name = line.split()[0]
                    columns.append(col_name)
        
        tables[table_name] = columns
    
    return tables

def validate_it_domain_schema():
    """Validate IT Domain schema files"""
    print_step("Validating IT Domain Schema Files")
    
    old_schema_file = "/workspace/dv_website/database/it-domain-schema.sql"
    new_schema_file = "/workspace/dv_website/database/it-domain-schema-new.sql"
    
    results = {}
    
    # Validate old schema
    valid, message = validate_sql_file(old_schema_file)
    results['old_schema'] = {'valid': valid, 'message': message}
    
    if valid:
        print_status(f"Old IT schema file: {message}")
    else:
        print_error(f"Old IT schema file: {message}")
    
    # Validate new schema
    valid, message = validate_sql_file(new_schema_file)
    results['new_schema'] = {'valid': valid, 'message': message}
    
    if valid:
        print_status(f"New IT schema file: {message}")
    else:
        print_error(f"New IT schema file: {message}")
    
    # Compare schemas
    if results['old_schema']['valid'] and results['new_schema']['valid']:
        try:
            with open(old_schema_file, 'r') as f:
                old_content = f.read()
            with open(new_schema_file, 'r') as f:
                new_content = f.read()
            
            old_tables = parse_create_table_statements(old_content)
            new_tables = parse_create_table_statements(new_content)
            
            print_status(f"Old schema tables: {list(old_tables.keys())}")
            print_status(f"New schema tables: {list(new_tables.keys())}")
            
            # Check for unified table structure
            if 'it_domain_projects' in new_tables:
                print_status("✅ New unified table 'it_domain_projects' found")
                columns = new_tables['it_domain_projects']
                print_status(f"Unified table has {len(columns)} columns")
                
                # Check for key fields
                expected_fields = ['task_index', 'project_name', 'dv_engineer', 'digital_designer']
                missing_fields = [field for field in expected_fields if field not in columns]
                
                if not missing_fields:
                    print_status("✅ All expected fields found in unified table")
                else:
                    print_warning(f"Missing expected fields: {missing_fields}")
            else:
                print_error("❌ New unified table 'it_domain_projects' not found")
        
        except Exception as e:
            print_error(f"Error comparing schemas: {str(e)}")
    
    return results

def validate_nx_domain_schema():
    """Validate NX Domain schema files"""
    print_step("Validating NX Domain Schema Files")
    
    old_schema_file = "/workspace/dv_website/database/nx-domain-schema.sql"
    new_schema_file = "/workspace/dv_website/database/nx-domain-schema-new.sql"
    
    results = {}
    
    # Validate old schema
    valid, message = validate_sql_file(old_schema_file)
    results['old_schema'] = {'valid': valid, 'message': message}
    
    if valid:
        print_status(f"Old NX schema file: {message}")
    else:
        print_error(f"Old NX schema file: {message}")
    
    # Validate new schema
    valid, message = validate_sql_file(new_schema_file)
    results['new_schema'] = {'valid': valid, 'message': message}
    
    if valid:
        print_status(f"New NX schema file: {message}")
    else:
        print_error(f"New NX schema file: {message}")
    
    # Check for TO summary view
    if results['new_schema']['valid']:
        try:
            with open(new_schema_file, 'r') as f:
                new_content = f.read()
            
            if 'to_summary_view' in new_content:
                print_status("✅ TO Summary view found in new schema")
                
                # Count fields in the view
                view_pattern = r'CREATE VIEW\s+to_summary_view\s+AS\s+SELECT\s+(.*?)\s+FROM'
                match = re.search(view_pattern, new_content, re.DOTALL | re.IGNORECASE)
                if match:
                    select_clause = match.group(1)
                    # Count fields (rough estimate)
                    fields = [field.strip() for field in select_clause.split(',') if field.strip()]
                    print_status(f"TO Summary view contains approximately {len(fields)} fields")
                    
                    if len(fields) >= 30:
                        print_status("✅ TO Summary view appears to have all expected fields")
                    else:
                        print_warning(f"TO Summary view may be missing fields (found {len(fields)}, expected 33)")
            else:
                print_error("❌ TO Summary view not found in new schema")
        
        except Exception as e:
            print_error(f"Error analyzing NX schema: {str(e)}")
    
    return results

def validate_migration_script():
    """Validate the migration script"""
    print_step("Validating Migration Script")
    
    migration_file = "/workspace/dv_website/database/migration-script.sql"
    
    valid, message = validate_sql_file(migration_file)
    
    if valid:
        print_status(f"Migration script: {message}")
        
        try:
            with open(migration_file, 'r') as f:
                content = f.read()
            
            # Check for backup creation
            if 'backup' in content.lower():
                print_status("✅ Migration script includes backup creation")
            else:
                print_warning("Migration script may not include backup creation")
            
            # Check for both domains
            if 'it_domain_db' in content and 'nx_domain_db' in content:
                print_status("✅ Migration script handles both domains")
            else:
                print_warning("Migration script may not handle both domains")
            
            # Check for constraint validation
            if 'CONSTRAINT' in content:
                print_status("✅ Migration script includes constraint validation")
            else:
                print_warning("Migration script may not include constraint validation")
                
        except Exception as e:
            print_error(f"Error analyzing migration script: {str(e)}")
    
    else:
        print_error(f"Migration script: {message}")
    
    return valid

def validate_deployment_script():
    """Validate the deployment script"""
    print_step("Validating Deployment Script")
    
    deploy_script = "/workspace/dv_website/deploy-fixes.sh"
    
    if not os.path.exists(deploy_script):
        print_error("Deployment script not found")
        return False
    
    try:
        with open(deploy_script, 'r') as f:
            content = f.read()
        
        # Check for essential deployment steps
        checks = [
            ('backup', 'Database backup'),
            ('docker', 'Docker container management'),
            ('mysql', 'Database operations'),
            ('validation', 'Validation checks'),
            ('restart', 'Service restart')
        ]
        
        for keyword, description in checks:
            if keyword in content.lower():
                print_status(f"✅ {description} found in deployment script")
            else:
                print_warning(f"❌ {description} not found in deployment script")
        
        return True
        
    except Exception as e:
        print_error(f"Error analyzing deployment script: {str(e)}")
        return False

def main():
    """Main validation function"""
    print("=" * 50)
    print("DV Website Database Schema Validation")
    print("=" * 50)
    print()
    
    print(f"Validation started at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()
    
    # Validate IT Domain schemas
    it_results = validate_it_domain_schema()
    print()
    
    # Validate NX Domain schemas
    nx_results = validate_nx_domain_schema()
    print()
    
    # Validate migration script
    migration_valid = validate_migration_script()
    print()
    
    # Validate deployment script
    deployment_valid = validate_deployment_script()
    print()
    
    # Summary
    print_step("Validation Summary")
    print()
    
    if (it_results['old_schema']['valid'] and it_results['new_schema']['valid'] and
        nx_results['old_schema']['valid'] and nx_results['new_schema']['valid'] and
        migration_valid and deployment_valid):
        print_status("✅ All validation checks passed!")
        print_status("Schema files are ready for deployment")
    else:
        print_warning("⚠️  Some validation checks failed")
        print_warning("Please review the issues above before deployment")
    
    print()
    print("=" * 50)
    print("Note: This validation only checks file syntax and structure.")
    print("Actual deployment requires Docker and MySQL to be running.")
    print("=" * 50)

if __name__ == "__main__":
    main()