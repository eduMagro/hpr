#!/bin/bash

###############################################################################
# Backup Script for Manager HPR
# Author: Auto-generated
# Version: 1.0.0
# Description: Creates backups of database and storage files
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/manager"
BACKUP_DIR="/var/backups/manager"
DB_NAME="manager_production"
DB_USER="manager_user"
DB_PASSWORD=""  # Leave empty to prompt or set in .my.cnf

# Read DB password from .env if not set
if [ -z "$DB_PASSWORD" ]; then
    if [ -f "$APP_DIR/.env" ]; then
        DB_PASSWORD=$(grep DB_PASSWORD $APP_DIR/.env | cut -d '=' -f2)
    fi
fi

# Date format for backup files
DATE=$(date +%Y%m%d_%H%M%S)
DATE_DAILY=$(date +%Y%m%d)

# Retention (days)
RETENTION_DAYS=7
RETENTION_WEEKLY=30
RETENTION_MONTHLY=90

# Functions
print_step() {
    echo -e "${YELLOW}➜ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Create backup directories
print_step "Creating backup directories..."
mkdir -p $BACKUP_DIR/database
mkdir -p $BACKUP_DIR/storage
mkdir -p $BACKUP_DIR/weekly
mkdir -p $BACKUP_DIR/monthly
print_success "Directories created"

# Backup Database
print_step "Backing up database: $DB_NAME..."
if [ -z "$DB_PASSWORD" ]; then
    # Use .my.cnf or prompt
    mysqldump -u $DB_USER $DB_NAME | gzip > $BACKUP_DIR/database/${DB_NAME}_${DATE}.sql.gz
else
    # Use password from .env
    mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME | gzip > $BACKUP_DIR/database/${DB_NAME}_${DATE}.sql.gz
fi
print_success "Database backed up: ${DB_NAME}_${DATE}.sql.gz"

# Backup Storage directory
print_step "Backing up storage directory..."
tar -czf $BACKUP_DIR/storage/storage_${DATE}.tar.gz -C $APP_DIR storage
print_success "Storage backed up: storage_${DATE}.tar.gz"

# Backup .env file
print_step "Backing up .env file..."
cp $APP_DIR/.env $BACKUP_DIR/.env_${DATE}
print_success ".env backed up"

# Weekly backup (Sundays)
if [ $(date +%u) -eq 7 ]; then
    print_step "Creating weekly backup..."
    cp $BACKUP_DIR/database/${DB_NAME}_${DATE}.sql.gz $BACKUP_DIR/weekly/${DB_NAME}_weekly_${DATE}.sql.gz
    cp $BACKUP_DIR/storage/storage_${DATE}.tar.gz $BACKUP_DIR/weekly/storage_weekly_${DATE}.tar.gz
    print_success "Weekly backup created"
fi

# Monthly backup (First day of month)
if [ $(date +%d) -eq 01 ]; then
    print_step "Creating monthly backup..."
    cp $BACKUP_DIR/database/${DB_NAME}_${DATE}.sql.gz $BACKUP_DIR/monthly/${DB_NAME}_monthly_${DATE}.sql.gz
    cp $BACKUP_DIR/storage/storage_${DATE}.tar.gz $BACKUP_DIR/monthly/storage_monthly_${DATE}.tar.gz
    print_success "Monthly backup created"
fi

# Cleanup old backups
print_step "Cleaning up old backups..."

# Daily backups older than RETENTION_DAYS
find $BACKUP_DIR/database -name "${DB_NAME}_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR/storage -name "storage_*.tar.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name ".env_*" -mtime +$RETENTION_DAYS -delete

# Weekly backups older than RETENTION_WEEKLY
find $BACKUP_DIR/weekly -name "*_weekly_*.sql.gz" -mtime +$RETENTION_WEEKLY -delete
find $BACKUP_DIR/weekly -name "storage_weekly_*.tar.gz" -mtime +$RETENTION_WEEKLY -delete

# Monthly backups older than RETENTION_MONTHLY
find $BACKUP_DIR/monthly -name "*_monthly_*.sql.gz" -mtime +$RETENTION_MONTHLY -delete
find $BACKUP_DIR/monthly -name "storage_monthly_*.tar.gz" -mtime +$RETENTION_MONTHLY -delete

print_success "Old backups cleaned up"

# Show backup sizes
print_step "Backup summary:"
echo "Database backup size: $(du -h $BACKUP_DIR/database/${DB_NAME}_${DATE}.sql.gz | cut -f1)"
echo "Storage backup size: $(du -h $BACKUP_DIR/storage/storage_${DATE}.tar.gz | cut -f1)"
echo "Total backup size: $(du -sh $BACKUP_DIR | cut -f1)"

print_success "Backup completed successfully!"

# Optional: Send notification (uncomment if using email notifications)
# echo "Backup completed at $(date)" | mail -s "Manager HPR Backup Success" admin@hierrospacoreyes.es
