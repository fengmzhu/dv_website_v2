#!/bin/bash

echo "====================================="
echo "DV Website Rollback Script"
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

# Check if backup directory exists
if [ ! -d "backups" ]; then
    print_error "No backups directory found. Cannot rollback."
    exit 1
fi

# Find the most recent backup
LATEST_BACKUP=$(ls -t backups/ | head -n 1)
if [ -z "$LATEST_BACKUP" ]; then
    print_error "No backup files found. Cannot rollback."
    exit 1
fi

BACKUP_DIR="backups/$LATEST_BACKUP"
print_status "Using backup from: $BACKUP_DIR"

# Confirm rollback
echo ""
print_warning "This will rollback all changes and restore the system to the backup state."
print_warning "Backup directory: $BACKUP_DIR"
echo ""
read -p "Are you sure you want to proceed with rollback? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_status "Rollback cancelled."
    exit 0
fi

print_step "Step 1: Stopping web containers"
print_status "Stopping web containers..."
docker-compose stop it-domain-web nx-domain-web

print_step "Step 2: Restoring database schemas"
print_status "Restoring IT domain database..."
docker exec -i it-domain-mysql mysql -u it_user -pit_password it_domain_db < "$BACKUP_DIR/it_domain_backup.sql"

print_status "Restoring NX domain database..."
docker exec -i nx-domain-mysql mysql -u nx_user -pnx_password nx_domain_db < "$BACKUP_DIR/nx_domain_backup.sql"

print_step "Step 3: Restoring web application files"
print_status "Restoring IT domain files..."
rm -rf it-domain/
cp -r "$BACKUP_DIR/it-domain-backup" it-domain/

print_status "Restoring NX domain files..."
rm -rf nx-domain/
cp -r "$BACKUP_DIR/nx-domain-backup" nx-domain/

print_step "Step 4: Restarting containers"
print_status "Restarting all containers..."
docker-compose restart

print_status "Waiting for containers to stabilize..."
sleep 20

print_step "Step 5: Verifying rollback"
print_status "Verifying system state..."

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

# Test websites
IT_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080)
NX_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8081)

if [ "$IT_RESPONSE" = "200" ] && [ "$NX_RESPONSE" = "200" ]; then
    print_status "Websites are accessible"
else
    print_warning "Website accessibility issues detected"
    print_warning "IT domain: HTTP $IT_RESPONSE"
    print_warning "NX domain: HTTP $NX_RESPONSE"
fi

echo ""
echo "====================================="
echo "ROLLBACK COMPLETED"
echo "====================================="
echo ""
print_status "System has been restored to backup state from: $BACKUP_DIR"
print_status "All changes have been reverted"
print_status "Websites are accessible at:"
echo "  IT Domain:  http://localhost:8080"
echo "  NX Domain:  http://localhost:8081"
echo ""
print_status "If you need to investigate issues, backup files are preserved in: $BACKUP_DIR"
echo ""