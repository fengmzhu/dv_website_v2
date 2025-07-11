# DV Website Maintenance Guide

This guide is designed for part-time engineers with basic website knowledge to maintain the DV (Design Verification) website system.

## System Overview

The DV website consists of two isolated LAMP stack websites:

- **IT Domain Website** (Port 8080) - Data entry and management
- **NX Domain Website** (Port 8081) - DV reports display and TO summary

## Quick Start

### Starting the System
```bash
cd /workspace/dv_website
./start.sh
```

### Stopping the System
```bash
cd /workspace/dv_website
docker-compose down
```

### Checking System Status
```bash
cd /workspace/dv_website
docker-compose ps
```

## Daily Operations

### 1. IT Domain Website (http://localhost:8080)

**Purpose**: Manual data entry for project information

**Common Tasks**:
- Add new projects: Go to "Add Project" in navigation
- Add DV tasks: Go to "Add Task" in navigation  
- Export data: Click "Export to CSV" button on main page
- Edit existing data: Click "Edit" button next to any record

**Required Fields**:
- Project Name (must be unique)
- Other fields are optional but recommended for complete TO summary

### 2. NX Domain Website (http://localhost:8081)

**Purpose**: Display DV regression results and combined TO summary

**Common Tasks**:
- View coverage reports: Go to "Coverage Reports"
- Import IT domain data: Go to "Import IT Data", upload CSV file
- View TO summary: Go to "TO Summary" for complete project overview

## Data Flow Process

```
1. IT Domain: Enter project data → Export as CSV
2. Manual Transfer: Download CSV from IT domain  
3. NX Domain: Import CSV → View combined TO summary
```

## Troubleshooting

### Website Won't Load
```bash
# Check if containers are running
docker-compose ps

# If containers are stopped, restart them
docker-compose up -d

# Check logs for errors
docker-compose logs -f
```

### Database Connection Errors
```bash
# Restart database containers
docker-compose restart it-domain-db nx-domain-db

# Wait 30 seconds for databases to initialize
sleep 30
```

### Import/Export Issues

**CSV Export Problems**:
- Check if projects exist in IT domain database
- Verify export button is clicked from the main page
- Look for browser download notifications

**CSV Import Problems**:
- Ensure CSV file has proper headers (exported from IT domain)
- Check file size (should be reasonable, not empty)
- Verify file format is CSV, not Excel

### Common Error Messages

| Error | Solution |
|-------|----------|
| "Connection error" | Restart database containers |
| "Import failed" | Check CSV file format and headers |
| "Project name already exists" | Use unique project names or edit existing |
| "File upload failed" | Check file permissions and disk space |

## Database Management

### Accessing Databases Directly

**IT Domain Database**:
```bash
docker exec -it it-domain-mysql mysql -u it_user -pit_password it_domain_db
```

**NX Domain Database**:
```bash
docker exec -it nx-domain-mysql mysql -u nx_user -pnx_password nx_domain_db
```

### Backup Databases
```bash
# Backup IT Domain
docker exec it-domain-mysql mysqldump -u it_user -pit_password it_domain_db > it_domain_backup.sql

# Backup NX Domain  
docker exec nx-domain-mysql mysqldump -u nx_user -pnx_password nx_domain_db > nx_domain_backup.sql
```

### Restore Databases
```bash
# Restore IT Domain
docker exec -i it-domain-mysql mysql -u it_user -pit_password it_domain_db < it_domain_backup.sql

# Restore NX Domain
docker exec -i nx-domain-mysql mysql -u nx_user -pnx_password nx_domain_db < nx_domain_backup.sql
```

## File Structure

```
/workspace/dv_website/
├── docker-compose.yml          # Container configuration
├── Dockerfile                  # PHP container setup  
├── start.sh                   # Startup script
├── database/                  # Database schemas and sample data
├── it-domain/                 # IT Domain website files
│   ├── index.php             # Main application
│   ├── config/database.php   # Database connection
│   └── includes/functions.php # Utility functions
├── nx-domain/                 # NX Domain website files  
│   ├── index.php             # Main application
│   ├── config/database.php   # Database connection
│   ├── includes/functions.php # Utility functions
│   └── imports/              # CSV import directory
└── docker/apache/            # Apache configuration
```

## Regular Maintenance Tasks

### Weekly
- Check disk space: `df -h`
- Backup databases (see commands above)
- Verify both websites are accessible

### Monthly  
- Update sample data if needed
- Review import/export logs
- Clean up old CSV files in imports directory

### When Adding New Fields

If new fields need to be added to the TO summary:

1. **Update Database Schema**:
   - Add columns to appropriate tables in database/ files
   - Restart containers to apply changes

2. **Update Website Forms**:
   - Modify HTML forms in index.php files
   - Add new form fields with proper validation

3. **Update Display Pages**:
   - Add new columns to tables
   - Update CSV export/import functions

## Security Considerations

- Default passwords are set in docker-compose.yml
- For production: Change database passwords and use environment files
- Both websites are isolated from each other (security by design)
- File uploads are limited to CSV format only

## Performance Tips

- Keep CSV files small (< 1000 records per import)
- Regular database backups prevent data loss
- Monitor disk space in Docker volumes
- Restart containers weekly to clear any memory issues

## Support Contacts

For technical issues beyond this guide:
- Check Docker logs: `docker-compose logs`
- Review database connectivity
- Verify file permissions in mounted volumes

## Useful Commands Quick Reference

```bash
# Start system
./start.sh

# Stop system  
docker-compose down

# View logs
docker-compose logs -f [service-name]

# Restart single service
docker-compose restart [service-name]

# Check container status
docker-compose ps

# Access database
docker exec -it [db-container] mysql -u [user] -p[password] [database]
```