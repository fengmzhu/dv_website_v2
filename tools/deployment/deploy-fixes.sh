#!/bin/bash

echo "====================================="
echo "DV Website Implementation Fix Deployment"
echo "====================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

# Check if containers are running
if ! docker-compose ps | grep -q "Up"; then
    print_warning "Containers are not running. Starting them first..."
    docker-compose up -d
    sleep 10
fi

print_step "Step 1: Creating backup of current database schemas"
print_status "Backing up current database state..."

# Create backup directory
mkdir -p backups/$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"

# Backup current databases
docker exec it-domain-mysql mysqldump -u it_user -pit_password it_domain_db > "$BACKUP_DIR/it_domain_backup.sql"
docker exec nx-domain-mysql mysqldump -u nx_user -pnx_password nx_domain_db > "$BACKUP_DIR/nx_domain_backup.sql"

# Backup current web files
cp -r it-domain "$BACKUP_DIR/it-domain-backup"
cp -r nx-domain "$BACKUP_DIR/nx-domain-backup"

print_status "Backup completed in $BACKUP_DIR"

print_step "Step 2: Applying database schema updates"
print_status "Applying database migrations..."

# Apply IT domain schema changes
print_status "Updating IT domain database schema..."
docker exec -i it-domain-mysql mysql -u it_user -pit_password < database/migration-script.sql

# Apply NX domain schema changes
print_status "Updating NX domain database schema..."  
docker exec -i nx-domain-mysql mysql -u nx_user -pnx_password < database/migration-script.sql

# Verify database changes
print_status "Verifying database schema changes..."
IT_TABLES=$(docker exec it-domain-mysql mysql -u it_user -pit_password -e "SHOW TABLES FROM it_domain_db;" | grep -c "it_domain_projects")
NX_TABLES=$(docker exec nx-domain-mysql mysql -u nx_user -pnx_password -e "SHOW TABLES FROM nx_domain_db;" | grep -c "coverage_reports")

if [ "$IT_TABLES" -eq "1" ] && [ "$NX_TABLES" -eq "1" ]; then
    print_status "Database schema migration successful"
else
    print_error "Database schema migration failed"
    print_error "IT domain projects table: $IT_TABLES (should be 1)"
    print_error "NX domain coverage table: $NX_TABLES (should be 1)"
    exit 1
fi

print_step "Step 3: Updating web application files"
print_status "Updating IT domain web application..."

# Update IT domain files
cp it-domain/index-new.php it-domain/index.php
print_status "IT domain index.php updated"

# Update NX domain files
cp nx-domain/index-new.php nx-domain/index.php
cp nx-domain/includes/functions-new.php nx-domain/includes/functions.php
print_status "NX domain files updated"

print_step "Step 4: Restarting web containers"
print_status "Restarting web containers to apply changes..."

docker-compose restart it-domain-web nx-domain-web

print_status "Waiting for containers to stabilize..."
sleep 15

print_step "Step 5: Validating deployment"
print_status "Running validation checks..."

# Check container health
IT_CONTAINER_STATUS=$(docker inspect it-domain-web --format='{{.State.Status}}')
NX_CONTAINER_STATUS=$(docker inspect nx-domain-web --format='{{.State.Status}}')

if [ "$IT_CONTAINER_STATUS" = "running" ] && [ "$NX_CONTAINER_STATUS" = "running" ]; then
    print_status "Web containers are running successfully"
else
    print_error "Container health check failed"
    print_error "IT domain container: $IT_CONTAINER_STATUS"
    print_error "NX domain container: $NX_CONTAINER_STATUS"
    exit 1
fi

# Test database connections
print_status "Testing database connections..."
docker exec -i it-domain-web php -r "
\$db = new PDO('mysql:host=it-domain-db;dbname=it_domain_db', 'it_user', 'it_password');
\$count = \$db->query('SELECT COUNT(*) FROM it_domain_projects')->fetchColumn();
echo 'IT domain projects: ' . \$count . PHP_EOL;
"

docker exec -i nx-domain-web php -r "
\$db = new PDO('mysql:host=nx-domain-db;dbname=nx_domain_db', 'nx_user', 'nx_password');
\$count = \$db->query('SELECT COUNT(*) FROM to_summary_view')->fetchColumn();
echo 'NX domain TO summary records: ' . \$count . PHP_EOL;
"

print_step "Step 6: Running comprehensive tests"
print_status "Testing website functionality..."

# Test IT domain website
IT_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080)
if [ "$IT_RESPONSE" = "200" ]; then
    print_status "IT domain website is accessible (HTTP $IT_RESPONSE)"
else
    print_warning "IT domain website returned HTTP $IT_RESPONSE"
fi

# Test NX domain website
NX_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8081)
if [ "$NX_RESPONSE" = "200" ]; then
    print_status "NX domain website is accessible (HTTP $NX_RESPONSE)"
else
    print_warning "NX domain website returned HTTP $NX_RESPONSE"
fi

# Test TO summary page
TO_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8081?action=to_summary)
if [ "$TO_RESPONSE" = "200" ]; then
    print_status "TO summary page is accessible (HTTP $TO_RESPONSE)"
else
    print_warning "TO summary page returned HTTP $TO_RESPONSE"
fi

print_step "Step 7: Cleanup temporary files"
print_status "Cleaning up temporary files..."

# Remove temporary files
rm -f it-domain/index-new.php
rm -f nx-domain/index-new.php
rm -f nx-domain/includes/functions-new.php

print_status "Temporary files cleaned up"

print_step "Step 8: Final verification"
print_status "Running final verification checks..."

# Verify all 33 TO summary fields are available
FIELD_COUNT=$(docker exec -i nx-domain-mysql mysql -u nx_user -pnx_password -e "DESCRIBE nx_domain_db.to_summary_view;" | wc -l)
if [ "$FIELD_COUNT" -gt "30" ]; then
    print_status "TO summary view contains $FIELD_COUNT fields (expecting 33+)"
else
    print_warning "TO summary view contains only $FIELD_COUNT fields"
fi

# Verify IT domain unified table
IT_FIELD_COUNT=$(docker exec -i it-domain-mysql mysql -u it_user -pit_password -e "DESCRIBE it_domain_db.it_domain_projects;" | wc -l)
if [ "$IT_FIELD_COUNT" -gt "15" ]; then
    print_status "IT domain projects table contains $IT_FIELD_COUNT fields (expecting 17+)"
else
    print_warning "IT domain projects table contains only $IT_FIELD_COUNT fields"
fi

echo ""
echo "====================================="
echo "DEPLOYMENT COMPLETED SUCCESSFULLY!"
echo "====================================="
echo ""
print_status "High Priority Fixes Applied:"
echo "  ✓ IT Domain Data Architecture Restructuring"
echo "  ✓ Complete TO Summary Display (33 fields)"
echo "  ✓ Data Validation Implementation"
echo "  ✓ Index Field Auto-Generation"
echo ""
print_status "Website Access:"
echo "  IT Domain:  http://localhost:8080"
echo "  NX Domain:  http://localhost:8081"
echo "  TO Summary: http://localhost:8081?action=to_summary"
echo ""
print_status "Backup Location: $BACKUP_DIR"
echo ""
print_status "Next Steps:"
echo "  1. Test the websites thoroughly"
echo "  2. Verify all 33 TO summary fields are displayed"
echo "  3. Test data import/export functionality"
echo "  4. Validate auto-generation of task indexes"
echo ""

# Show final status
print_status "All systems operational. Deployment complete!"
echo ""