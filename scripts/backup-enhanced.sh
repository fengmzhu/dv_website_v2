#!/bin/bash

# Enhanced backup script with cloud storage option
# Usage: ./backup-enhanced.sh [--upload-s3]

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/dv-website/backups"
S3_BUCKET="your-backup-bucket"  # Change this to your S3 bucket name

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[BACKUP]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Load environment variables
source /opt/dv-website/.env

# Create backup directory
mkdir -p $BACKUP_DIR

print_status "Starting backup at $(date)"

# Backup databases
print_status "Backing up IT Domain database..."
docker-compose -f /opt/dv-website/docker-compose.yml exec -T mysql \
    mysqldump -u root -p${MYSQL_ROOT_PASSWORD} \
    --single-transaction --routines --triggers \
    it_domain_db > $BACKUP_DIR/it_domain_backup_$DATE.sql

print_status "Backing up NX Domain database..."
docker-compose -f /opt/dv-website/docker-compose.yml exec -T mysql \
    mysqldump -u root -p${MYSQL_ROOT_PASSWORD} \
    --single-transaction --routines --triggers \
    nx_domain_db > $BACKUP_DIR/nx_domain_backup_$DATE.sql

# Backup application files (configuration changes)
print_status "Backing up configuration files..."
tar -czf $BACKUP_DIR/config_backup_$DATE.tar.gz \
    /opt/dv-website/docker-compose.yml \
    /opt/dv-website/.env \
    /opt/dv-website/nginx/conf.d/ \
    2>/dev/null

# Create combined backup
print_status "Creating compressed backup archive..."
tar -czf $BACKUP_DIR/dv_complete_backup_$DATE.tar.gz \
    $BACKUP_DIR/*_$DATE.sql \
    $BACKUP_DIR/config_backup_$DATE.tar.gz

# Calculate backup size
BACKUP_SIZE=$(du -h $BACKUP_DIR/dv_complete_backup_$DATE.tar.gz | cut -f1)
print_status "Backup created: dv_complete_backup_$DATE.tar.gz (${BACKUP_SIZE})"

# Remove individual files
rm $BACKUP_DIR/*_$DATE.sql $BACKUP_DIR/config_backup_$DATE.tar.gz

# Upload to S3 if requested
if [[ "$1" == "--upload-s3" ]]; then
    if command -v aws &> /dev/null; then
        print_status "Uploading to S3 bucket: $S3_BUCKET"
        aws s3 cp $BACKUP_DIR/dv_complete_backup_$DATE.tar.gz s3://$S3_BUCKET/dv-backups/
        if [ $? -eq 0 ]; then
            print_status "S3 upload successful"
        else
            print_warning "S3 upload failed"
        fi
    else
        print_warning "AWS CLI not installed, skipping S3 upload"
    fi
fi

# Keep only last 7 days of local backups
print_status "Cleaning up old backups..."
find $BACKUP_DIR -name "dv_complete_backup_*.tar.gz" -mtime +7 -delete

# Show backup statistics
print_status "Backup statistics:"
echo "  - Local backups: $(ls -1 $BACKUP_DIR/dv_complete_backup_*.tar.gz | wc -l) files"
echo "  - Total local size: $(du -sh $BACKUP_DIR | cut -f1)"
echo "  - Latest backup: $BACKUP_SIZE"

print_status "Backup completed at $(date)"