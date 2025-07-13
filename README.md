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

## 📁 Clean Project Structure

```
dv_website_v2/
├── Makefile              # 🎯 Central command center
├── README.md             # 📖 This file
├── .gitignore           # 🚫 Git ignore rules
│
├── config/              # ⚙️ All configuration files
│   ├── package.json     # Node.js dependencies
│   ├── playwright.config.js # Test configuration
│   └── nginx-http-only.conf # Nginx configuration
│
├── app/                 # 🏗️ Main application code
│   ├── domains/         # Domain applications
│   │   ├── it-domain/   # IT Domain (Project Management)
│   │   ├── nx-domain/   # NX Domain (Reports & TO Summary)
│   │   └── shared/      # Shared components
│   └── database/        # Database layer
│       ├── schemas/     # SQL schemas and data
│       ├── migrations/  # Migration scripts
│       └── backup/      # Backup utilities
│
├── docker/              # 🐳 Docker ecosystem
│   ├── compose/         # Environment-specific compose files
│   ├── images/web/      # Dockerfile definitions
│   └── configs/apache/  # Apache configurations
│
├── tools/               # 🔧 Development and maintenance tools
│   ├── data-generation/ # Data generation utilities
│   ├── deployment/      # Deployment scripts
│   ├── backup/          # Backup scripts
│   ├── maintenance/     # Health checks and monitoring
│   └── testing/         # Testing utilities
│
├── tests/               # 🧪 All testing
│   ├── e2e/specs/       # End-to-end test specifications
│   ├── e2e/reports/     # Test outputs and screenshots
│   └── utils/           # Test utilities
│
├── storage/             # 💾 Runtime data and outputs
│   ├── backups/         # Database backups
│   ├── logs/            # Application logs
│   ├── uploads/         # File uploads
│   └── cache/           # Temporary cache
│
├── docs/                # 📚 Documentation
└── .build/              # 🚮 Build artifacts (gitignored)
```

## 🛠️ Available Commands

### Quick Commands
```bash
make init         # 🚀 One-command setup and start
make structure    # 📁 Show project structure  
make help         # 📋 Show all commands
```

### Basic Operations
```bash
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
make install      # Install dependencies
```

### Database Management
```bash
make db-init      # Initialize databases with schemas
make db-backup    # Backup all databases
make db-restore   # Restore databases from backup
make mysql-it     # Open MySQL shell for IT domain
make mysql-nx     # Open MySQL shell for NX domain
```

### Testing
```bash
make test         # Run all tests
make test-buttons # Test button functionality
make playwright   # Run Playwright tests
make health       # Check health status
```

### Maintenance
```bash
make clean        # Clean up containers and volumes
make clean-all    # Remove everything including images
make lint         # Run code linting
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
  - User: `it_user` / Password: `it_password`
- **NX Domain DB**:
  - Database: `nx_domain_db`
  - User: `nx_user` / Password: `nx_password`
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

## 🎯 Benefits of This Structure

✅ **Crystal Clear Root**: Only 7 main directories  
✅ **Purpose-Driven**: Each directory has a single, clear purpose  
✅ **Scalable**: Easy to add new components  
✅ **Environment Separation**: Different Docker configs  
✅ **Tool Organization**: All utilities grouped by function  
✅ **Build Isolation**: All build artifacts in .build (gitignored)  
✅ **Storage Management**: All runtime data in storage/  
✅ **Test Organization**: Comprehensive testing structure  

## 🚨 Troubleshooting

### Quick Diagnostics
```bash
make check-ports  # Check if ports are available
make health       # Full health check
make structure    # Show project layout
```

### View Logs
```bash
make logs         # All containers
make logs-it      # IT domain only
make logs-nx      # NX domain only
make tail-logs    # Follow logs in real-time
```

### Clean Start
```bash
make clean-all    # Remove everything
make init         # Fresh initialization
```

## 📚 Documentation

Additional documentation is available in the `docs/` directory:
- Button functionality testing results
- Deployment guides  
- Maintenance procedures
- API documentation
- Architecture overview

## 🤝 Contributing

1. Make changes in the appropriate directory (`app/`, `config/`, etc.)
2. Test locally with `make dev`
3. Run tests with `make test`
4. Use `make structure` to verify organization
5. Commit changes with descriptive messages

---

**This project follows enterprise-grade organization patterns for maximum maintainability and professional development workflows.**