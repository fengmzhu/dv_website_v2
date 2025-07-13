# DV Website v2

A dual-domain LAMP stack system for IC Design Verification project management and reporting.

## 🚀 Quick Start

```bash
# Initialize and start everything
make init

# Or step by step:
make build      # Build Docker images
make up         # Start containers
make status     # Check status
```

Access the websites:
- **IT Domain**: http://localhost:8080 (Project Management)
- **NX Domain**: http://localhost:8081 (Reports & TO Summary)

## 📁 Project Structure

```
dv_website_v2/
├── Makefile                # Main build and management commands
├── README.md              # This file
├── docker/                # Docker configurations
│   ├── docker-compose.yml # Main Docker Compose file
│   ├── Dockerfile         # PHP/Apache image definition
│   └── apache/           # Apache configurations
├── src/                   # Source code
│   ├── it-domain/        # IT Domain PHP application
│   ├── nx-domain/        # NX Domain PHP application
│   └── shared/           # Shared components
├── database/             # Database files
│   ├── it-domain-schema.sql
│   ├── it-domain-data.sql
│   ├── nx-domain-schema.sql
│   └── nx-domain-data.sql
├── scripts/              # Utility scripts
├── tests/                # Test files
│   └── playwright/       # Playwright tests
├── docs/                 # Documentation
└── backups/              # Database backups (created by make db-backup)
```

## 🛠️ Available Commands

### Basic Operations
```bash
make help         # Show all available commands
make build        # Build Docker images
make up           # Start all containers
make down         # Stop all containers
make restart      # Restart all containers
make status       # Show container status
make logs         # Show container logs
```

### Development
```bash
make dev          # Start in development mode
make shell-it     # Open shell in IT domain container
make shell-nx     # Open shell in NX domain container
make mysql-it     # Open MySQL shell for IT domain
make mysql-nx     # Open MySQL shell for NX domain
```

### Database Management
```bash
make db-init      # Initialize databases with schemas
make db-backup    # Backup all databases
make db-restore   # Restore databases from backup
```

### Testing
```bash
make test         # Run all tests
make test-buttons # Test button functionality
make playwright   # Run Playwright tests
```

### Maintenance
```bash
make clean        # Clean up containers and volumes
make clean-all    # Remove everything including images
make lint         # Run code linting
make health       # Check health status
```

## 🔧 Configuration

### Ports
- IT Domain Web: 8080
- NX Domain Web: 8081  
- IT Domain MySQL: 3306
- NX Domain MySQL: 3307

### Database Credentials
- **IT Domain DB**: 
  - Database: `it_domain_db`
  - User: `it_user`
  - Password: `it_password`
- **NX Domain DB**:
  - Database: `nx_domain_db`
  - User: `nx_user`
  - Password: `nx_password`
- **Root Password**: `root_password`

## 📊 Features

### IT Domain (Project Management)
- Project creation and management
- Task assignment tracking
- Engineer and designer assignments
- Export data to CSV
- Modal-based project details

### NX Domain (Reports & TO Summary)
- Data visualization and reporting
- TO summary generation
- Import CSV from IT domain
- Project metrics display

## 🧪 Testing

The project includes comprehensive testing:

```bash
# Run all tests
make test

# Test button functionality specifically
make test-buttons

# Run Playwright tests
make playwright
```

## 🚨 Troubleshooting

### Check if ports are available
```bash
make check-ports
```

### View logs
```bash
make logs         # All containers
make logs-it      # IT domain only
make logs-nx      # NX domain only
make tail-logs    # Follow logs in real-time
```

### Health check
```bash
make health
```

### Clean start
```bash
make clean-all    # Remove everything
make init         # Fresh initialization
```

## 📚 Documentation

Additional documentation is available in the `docs/` directory:
- `DEPLOYMENT_REPORT.md` - Deployment details
- `MAINTENANCE_GUIDE.md` - Maintenance procedures
- `TROUBLESHOOTING.md` - Common issues and solutions
- `BUTTON_FUNCTIONALITY_REPORT.md` - UI testing results

## 🤝 Contributing

1. Make changes in the appropriate `src/` directory
2. Test locally with `make dev`
3. Run tests with `make test`
4. Commit changes with descriptive messages

## 📄 License

This project is proprietary and confidential.