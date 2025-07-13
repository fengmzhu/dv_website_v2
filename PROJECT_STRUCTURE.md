# DV Website v2 - Project Structure

## 📁 Directory Organization

```
dv_website_v2/
│
├── 🎯 Makefile                    # Main command center - run 'make help' for all commands
├── 📖 README.md                   # Quick start guide
├── 🚫 .gitignore                 # Git ignore rules
│
├── 🐳 docker/                    # Docker configurations
│   ├── docker-compose.yml        # Main compose file for production
│   ├── docker-compose-working.yml # Development compose file
│   ├── Dockerfile                # PHP 8.1 + Apache image
│   ├── Dockerfile-working        # Development Dockerfile
│   └── apache/                   # Apache configurations
│       ├── it-domain.conf        # IT domain virtual host
│       └── nx-domain.conf        # NX domain virtual host
│
├── 💻 src/                       # Source code
│   ├── it-domain/               # IT Domain application
│   │   ├── index.php           # Main application file
│   │   ├── index-old.php       # Legacy version
│   │   ├── ip-detail-modal.php # IP detail component
│   │   ├── api/                # API endpoints
│   │   ├── config/             # Configuration files
│   │   └── includes/           # Shared functions
│   │
│   ├── nx-domain/              # NX Domain application
│   │   ├── index.php          # Main application file
│   │   ├── index-new.php      # New version
│   │   ├── ip-detail-modal.php # IP detail component
│   │   ├── api/               # API endpoints
│   │   ├── config/            # Configuration files
│   │   └── includes/          # Shared functions
│   │
│   └── shared/                 # Shared components
│       └── ip-detail-modal.php # Common modal component
│
├── 🗄️ database/                  # Database files
│   ├── it-domain-schema.sql    # IT domain table definitions
│   ├── it-domain-data.sql      # IT domain sample data
│   ├── nx-domain-schema.sql    # NX domain table definitions
│   ├── nx-domain-data.sql      # NX domain sample data
│   └── migrations/             # Future migration scripts
│
├── 🔧 scripts/                   # Utility scripts
│   ├── analyze-buttons.sh      # Button functionality analyzer
│   ├── test-button-interactions.sh # Button interaction tester
│   ├── backup-*.sh             # Various backup scripts
│   ├── deploy*.sh              # Deployment scripts
│   └── *.py                    # Python utility scripts
│
├── 🧪 tests/                     # Test suites
│   └── playwright/             # Playwright tests
│       ├── button-functionality.spec.js
│       └── website-debug.spec.js
│
├── 📚 docs/                      # Documentation
│   ├── BUTTON_FUNCTIONALITY_REPORT.md
│   ├── DEPLOYMENT_REPORT.md
│   ├── DOCKER_DEPLOYMENT_PLAN.md
│   ├── MAINTENANCE_GUIDE.md
│   ├── TROUBLESHOOTING.md
│   └── *.md                    # Other documentation
│
├── 📸 screenshots/               # Test screenshots
├── 💾 backups/                   # Database backups (created by make db-backup)
├── 📦 node_modules/              # Node.js dependencies
└── 🔍 test-results/              # Test execution results
```

## 🚀 Quick Commands

### Start Everything
```bash
make init     # One command to rule them all!
```

### Daily Development
```bash
make up       # Start containers
make status   # Check what's running
make logs     # View logs
make down     # Stop everything
```

### Database Operations
```bash
make db-backup    # Backup databases
make db-restore   # Restore from backup
make mysql-it     # Access IT domain MySQL
make mysql-nx     # Access NX domain MySQL
```

### Testing
```bash
make test         # Run all tests
make test-buttons # Test UI buttons
make health       # Health check
```

### Troubleshooting
```bash
make logs         # View all logs
make shell-it     # Debug IT domain
make shell-nx     # Debug NX domain
make clean        # Clean restart
```

## 🎯 Key Benefits of This Structure

1. **Clean Separation**: Each component has its own directory
2. **Easy Navigation**: Logical grouping of related files
3. **Make-driven**: All operations through simple make commands
4. **Docker-first**: Everything runs in containers
5. **Test-ready**: Organized test structure with Playwright
6. **Documentation**: All docs in one place
7. **Version Control**: Proper .gitignore for clean commits

## 💡 Tips

- Run `make help` anytime to see all available commands
- Use `make init` for first-time setup
- Use `make dev` for development mode
- Check `docs/` for detailed documentation
- All sensitive data is gitignored