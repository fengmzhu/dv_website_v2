#!/bin/bash

# Configurable backup script with retention settings
# Usage: ./backup-configurable.sh [retention_days]
# Default: 7 days if not specified

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/dv-website/backups"
RETENTION_DAYS=${1:-7}  # Use provided value or default to 7

# Load environment variables
source /opt/dv-website/.env

# Create backup directory
mkdir -p $BACKUP_DIR

echo "Starting backup with $RETENTION_DAYS days retention..."

# Backup databases
docker-compose -f /opt/dv-website/docker-compose.yml exec -T mysql \
    mysqldump -u root -p${MYSQL_ROOT_PASSWORD} it_domain_db > $BACKUP_DIR/it_domain_backup_$DATE.sql

docker-compose -f /opt/dv-website/docker-compose.yml exec -T mysql \
    mysqldump -u root -p${MYSQL_ROOT_PASSWORD} nx_domain_db > $BACKUP_DIR/nx_domain_backup_$DATE.sql

# Compress backups
tar -czf $BACKUP_DIR/dv_backup_$DATE.tar.gz $BACKUP_DIR/*_$DATE.sql

# Remove individual SQL files
rm $BACKUP_DIR/*_$DATE.sql

# Clean up old backups based on retention policy
find $BACKUP_DIR -name "dv_backup_*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: dv_backup_$DATE.tar.gz"
echo "Keeping backups for $RETENTION_DAYS days"
echo "Current backups:"
ls -lh $BACKUP_DIR/dv_backup_*.tar.gz | tail -n 10