#!/bin/bash

# Archive backup script - keeps daily, weekly, and monthly backups
# Runs daily but creates different retention policies

DATE=$(date +%Y%m%d_%H%M%S)
DAY_OF_WEEK=$(date +%u)  # 1-7 (Monday-Sunday)
DAY_OF_MONTH=$(date +%d) # 01-31
BACKUP_DIR="/opt/dv-website/backups"

# Subdirectories for different retention periods
DAILY_DIR="$BACKUP_DIR/daily"
WEEKLY_DIR="$BACKUP_DIR/weekly"
MONTHLY_DIR="$BACKUP_DIR/monthly"

# Create directories
mkdir -p $DAILY_DIR $WEEKLY_DIR $MONTHLY_DIR

# Load environment variables
source /opt/dv-website/.env

# Create daily backup
echo "Creating daily backup..."
docker-compose -f /opt/dv-website/docker-compose.yml exec -T mysql \
    mysqldump -u root -p${MYSQL_ROOT_PASSWORD} --all-databases > $DAILY_DIR/backup_$DATE.sql

tar -czf $DAILY_DIR/dv_backup_$DATE.tar.gz $DAILY_DIR/backup_$DATE.sql
rm $DAILY_DIR/backup_$DATE.sql

# Copy to weekly on Sundays
if [ "$DAY_OF_WEEK" -eq 7 ]; then
    echo "Creating weekly backup..."
    cp $DAILY_DIR/dv_backup_$DATE.tar.gz $WEEKLY_DIR/
fi

# Copy to monthly on the 1st
if [ "$DAY_OF_MONTH" -eq 01 ]; then
    echo "Creating monthly backup..."
    cp $DAILY_DIR/dv_backup_$DATE.tar.gz $MONTHLY_DIR/
fi

# Cleanup policies
echo "Applying retention policies..."
# Keep daily backups for 7 days
find $DAILY_DIR -name "dv_backup_*.tar.gz" -mtime +7 -delete

# Keep weekly backups for 4 weeks
find $WEEKLY_DIR -name "dv_backup_*.tar.gz" -mtime +28 -delete

# Keep monthly backups for 12 months
find $MONTHLY_DIR -name "dv_backup_*.tar.gz" -mtime +365 -delete

# Show backup summary
echo "Backup summary:"
echo "  Daily backups: $(ls -1 $DAILY_DIR/*.tar.gz 2>/dev/null | wc -l)"
echo "  Weekly backups: $(ls -1 $WEEKLY_DIR/*.tar.gz 2>/dev/null | wc -l)"
echo "  Monthly backups: $(ls -1 $MONTHLY_DIR/*.tar.gz 2>/dev/null | wc -l)"
echo "  Total size: $(du -sh $BACKUP_DIR | cut -f1)"