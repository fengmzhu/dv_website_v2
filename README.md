# DV Website System

A dual-domain LAMP stack system for IC Design Verification project management and reporting.

## Overview

This system provides two isolated websites:

1. **IT Domain Website** - Manual data entry for project management
2. **NX Domain Website** - DV regression reports and TO summary display

## Quick Start

```bash
# Start the system
./start.sh

# Access websites
# IT Domain: http://localhost:8080
# NX Domain: http://localhost:8081
```

## Architecture

- **Frontend**: PHP with Bootstrap UI
- **Backend**: Apache web server
- **Database**: MySQL 8.0 (separate instances for each domain)
- **Deployment**: Docker Compose

## Features

### IT Domain Website
- ✅ Project data entry and management
- ✅ DV task assignment tracking  
- ✅ Data export to CSV format
- ✅ Personnel and specification management

### NX Domain Website  
- ✅ IT domain data import via CSV
- ✅ DV regression report display
- ✅ Coverage metrics visualization
- ✅ Combined TO summary view with 33 standardized fields

## Data Flow

```
IT Domain → Export CSV → Manual Transfer → NX Domain Import → TO Summary
```

## Database Schema

### IT Domain Tables
- `projects` - Basic project information
- `dv_tasks` - Task assignments and personnel
- `export_view` - Combined data for CSV export

### NX Domain Tables  
- `coverage_reports` - DV regression results
- `version_control` - SVN/Git information
- `imported_it_data` - Data imported from IT domain
- `to_summary_view` - Combined view of all project data

## Development

### Prerequisites
- Docker & Docker Compose
- PHP 8.1+ (for local development)
- MySQL 8.0+ (for local development)

### Local Development Setup
```bash
# Clone and start
git clone <repository>
cd dv_website
./start.sh
```

### File Structure
```
├── docker-compose.yml     # Container orchestration
├── Dockerfile            # PHP container configuration
├── database/             # MySQL schemas and sample data
├── it-domain/           # IT Domain website
├── nx-domain/          # NX Domain website  
└── docker/apache/      # Apache virtual host configs
```

## Maintenance

See [MAINTENANCE_GUIDE.md](MAINTENANCE_GUIDE.md) for detailed maintenance instructions.

## TO Summary Fields

The system supports all 33 TO summary fields from the original Python integration:

**Basic Info**: Index, Project, SPIP_IP, IP, IP Postfix, IP Subtype, Alternative Name  
**Coverage**: Line, FSM, Interface Toggle, Toggle Coverage + Report Path  
**Personnel**: DV Engineer, Digital Designer, Business Unit, Analog Designer  
**Version Control**: SVN paths/versions, Git path/version, Golden checklist  
**Timestamps**: TO Date, RTL update, TO report creation  
**Links**: SPIP URL, Wiki URL  
**Specifications**: Version, Path  
**Design**: Inherit from IP, Re-use IP  

## Security

- Database credentials configurable via environment variables
- Input validation and sanitization  
- SQL injection prevention with prepared statements
- File upload restricted to CSV format only
- Isolated network architecture between domains

## License

Internal use for IC Design Verification workflows.