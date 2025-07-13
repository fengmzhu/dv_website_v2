# DV Website v2 - Advanced Reorganization Plan

## 🎯 Current Issues

The root directory is still cluttered with:
- Loose config files (`nginx-http-only.conf`)
- Test files scattered around (`test-*.php`, `test-*.js`)
- Mixed purposes (data generation, testing, configs)
- Node.js artifacts in root
- Multiple temporary/build directories

## 🏗️ Proposed Clean Structure

```
dv_website_v2/
│
├── 📋 Makefile                    # Central command center
├── 📖 README.md                   # Quick start only
├── 🚫 .gitignore                 
│
├── 🎛️ config/                    # All configuration files
│   ├── nginx-http-only.conf      # Nginx config
│   ├── playwright.config.js      # Test config
│   ├── package.json              # Node dependencies
│   └── .env.example              # Environment template
│
├── 🐳 docker/                    # Docker ecosystem
│   ├── compose/                  # Compose files by environment
│   │   ├── production.yml        # Production setup
│   │   ├── development.yml       # Dev environment
│   │   └── testing.yml           # Test environment
│   ├── images/                   # Dockerfile definitions
│   │   ├── web/                  # Web server images
│   │   └── database/             # DB initialization
│   └── configs/                  # Container configs
│       └── apache/               # Apache virtual hosts
│
├── 🏗️ app/                       # Main application code
│   ├── domains/                  # Domain applications
│   │   ├── it-domain/           # IT Domain app
│   │   ├── nx-domain/           # NX Domain app
│   │   └── shared/              # Shared components
│   └── database/                # Database layer
│       ├── schemas/             # SQL schemas
│       ├── seeds/               # Sample data
│       ├── migrations/          # Migration scripts
│       └── backup/              # Backup utilities
│
├── 🔧 tools/                     # Development and maintenance tools
│   ├── data-generation/         # Move gen_to_report_from_dv_data here
│   ├── deployment/              # Deploy scripts
│   ├── backup/                  # Backup scripts
│   ├── maintenance/             # Health checks, monitoring
│   └── testing/                 # Test utilities and helpers
│
├── 📁 storage/                   # Runtime data and outputs
│   ├── logs/                    # Application logs
│   ├── backups/                 # Database backups
│   ├── uploads/                 # File uploads
│   └── cache/                   # Temporary cache files
│
├── 🧪 tests/                     # All testing
│   ├── unit/                    # Unit tests
│   ├── integration/             # Integration tests
│   ├── e2e/                     # End-to-end tests (Playwright)
│   │   ├── specs/               # Test specifications
│   │   ├── fixtures/            # Test data
│   │   └── reports/             # Test outputs
│   └── utils/                   # Test utilities
│
├── 📚 docs/                      # Documentation only
│   ├── api/                     # API documentation
│   ├── deployment/              # Deployment guides
│   ├── architecture/            # System design docs
│   └── user/                    # User guides
│
└── 🚮 .build/                    # Build artifacts (gitignored)
    ├── node_modules/            # Node dependencies
    ├── vendor/                  # PHP dependencies
    ├── dist/                    # Built assets
    └── reports/                 # Generated reports
```

## 🎯 Clean Root Directory

After reorganization, root will only contain:
```
dv_website_v2/
├── Makefile              # Command center
├── README.md             # Quick start
├── .gitignore           # Git ignore
├── config/              # Configurations
├── docker/              # Container setup
├── app/                 # Main application
├── tools/               # Development tools
├── storage/             # Runtime data
├── tests/               # All tests
└── docs/                # Documentation
```

## 📦 File Movement Plan

### Phase 1: Configuration Consolidation
```bash
mkdir -p config/
mv package.json config/
mv package-lock.json config/
mv playwright.config.js config/
mv nginx-http-only.conf config/
```

### Phase 2: Application Structure
```bash
mkdir -p app/{domains,database}/{schemas,seeds,migrations}
mv src/* app/domains/
mv database/* app/database/schemas/
```

### Phase 3: Docker Reorganization
```bash
mkdir -p docker/{compose,images/web,configs}
mv docker/docker-compose*.yml docker/compose/
mv docker/Dockerfile* docker/images/web/
mv docker/apache docker/configs/
```

### Phase 4: Tools Consolidation
```bash
mkdir -p tools/{data-generation,deployment,backup,maintenance,testing}
mv gen_to_report_from_dv_data/* tools/data-generation/
mv scripts/deploy* tools/deployment/
mv scripts/backup* tools/backup/
mv scripts/test* tools/testing/
mv scripts/healthcheck* tools/maintenance/
```

### Phase 5: Testing Organization
```bash
mkdir -p tests/{e2e/specs,e2e/reports,utils}
mv tests/playwright/* tests/e2e/specs/
mv test-*.php tests/utils/
mv test-*.js tests/utils/
mv screenshots tests/e2e/reports/
mv test-results tests/e2e/reports/
mv playwright-report tests/e2e/reports/
```

### Phase 6: Storage and Build
```bash
mkdir -p storage/{logs,backups,uploads,cache}
mkdir -p .build
mv node_modules .build/
```

## 🔧 Updated Makefile Structure

The Makefile will need path updates:
```makefile
# New paths
DOCKER_COMPOSE = docker compose -f docker/compose/production.yml
CONFIG_DIR = config
APP_DIR = app
TOOLS_DIR = tools
```

## 🎯 Benefits of This Structure

1. **Crystal Clear Root**: Only 8 top-level directories
2. **Purpose-Driven**: Each directory has a single, clear purpose
3. **Scalable**: Easy to add new components
4. **Environment Separation**: Different Docker configs for different environments
5. **Tool Organization**: All utilities grouped by function
6. **Build Isolation**: All build artifacts in .build (gitignored)
7. **Storage Management**: All runtime data in storage/
8. **Test Organization**: All testing in one place with clear categories

## 🚀 Implementation Commands

```bash
# Create the new structure
make reorganize-create-structure

# Move files to new locations
make reorganize-move-files

# Update configurations
make reorganize-update-configs

# Test the new structure
make reorganize-test

# Commit the reorganization
make reorganize-commit
```

## 📋 Validation Checklist

- [ ] Root directory has ≤ 8 items
- [ ] All configs in config/
- [ ] All Docker files in docker/
- [ ] All application code in app/
- [ ] All tools in tools/
- [ ] All tests in tests/
- [ ] All docs in docs/
- [ ] All runtime data in storage/
- [ ] Build artifacts in .build (gitignored)
- [ ] Makefile commands work with new paths
- [ ] Docker containers start successfully
- [ ] Tests run from new locations

This structure follows enterprise-grade organization patterns and will make the project much more maintainable and professional.