# DV Website Debug Summary

## Current Status
The DV Website is now partially running with the following setup:

### Running Containers
- **IT Domain**: `it-domain-web-test` on port 8080
- **NX Domain**: `nx-domain-web-test` on port 8081  
- **Databases**: MySQL containers for both domains (ports 3306, 3307)

### Website URLs
- **IT Domain**: http://localhost:8080
- **NX Domain**: http://localhost:8081
- **Test Pages**: 
  - http://localhost:8080/test.php
  - http://localhost:8081/test.php

## Current Issues
1. **Database Connection**: PHP apps show "Database connection failed" (expected - no network connection between web and DB containers)
2. **Network Access**: May have connectivity issues from host to containers
3. **PHP Applications**: The main index.php files are project management applications that require database connectivity

## What's Working
✅ Docker containers are built and running  
✅ Apache + PHP 8.1 servers are operational  
✅ PHP files are being executed (not just served as static files)  
✅ Playwright framework is installed and configured  
✅ Test infrastructure is in place  

## Next Steps to Fully Debug
1. Connect web containers to database containers in same network
2. Verify host-to-container network connectivity
3. Run Playwright tests with proper container networking
4. Test the full PHP applications with database connectivity

## Files Created for Debugging
- `playwright.config.js` - Playwright configuration
- `tests/website-debug.spec.js` - Debugging test suite
- `run-it-domain.sh` / `run-nx-domain.sh` - Container startup scripts
- `test-page.php` - Simple PHP test page
- `screenshots/` - Directory for test screenshots

## Playwright Commands
```bash
# Run tests (headless)
npx playwright test

# View test report
npx playwright show-report

# Run specific test
npx playwright test --grep "IT Domain"
```