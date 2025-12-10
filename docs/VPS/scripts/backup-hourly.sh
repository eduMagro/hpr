#!/bin/bash

###############################################################################
# Backup Horario de Base de Datos - Manager HPR
# Description: Crea backups de la BD cada hora con retención de 24h
###############################################################################

set -e

# Configuration
APP_DIR="/var/www/manager"
BACKUP_DIR="/var/backups/manager/hourly"
DB_NAME="manager_production"
DB_USER="manager_user"
DB_PASSWORD=""

# Read DB password from .env if not set
if [ -z "$DB_PASSWORD" ]; then
    if [ -f "$APP_DIR/.env" ]; then
        DB_PASSWORD=$(grep DB_PASSWORD $APP_DIR/.env | cut -d '=' -f2)
    fi
fi

# Timestamp format: YYYYMMDD_HH
TIMESTAMP=$(date +%Y%m%d_%H)

# Retention in hours
RETENTION_HOURS=24

# Create backup directory
mkdir -p $BACKUP_DIR

# Create backup
FILENAME="${DB_NAME}_hourly_${TIMESTAMP}.sql.gz"
FILEPATH="${BACKUP_DIR}/${FILENAME}"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting hourly backup..."

if [ -z "$DB_PASSWORD" ]; then
    mysqldump -u $DB_USER $DB_NAME 2>/dev/null | gzip > $FILEPATH
else
    mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME 2>/dev/null | gzip > $FILEPATH
fi

# Verify backup was created
if [ ! -f "$FILEPATH" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Backup file not created!"
    exit 1
fi

SIZE=$(du -h $FILEPATH | cut -f1)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backup completed: $FILENAME ($SIZE)"

# Cleanup old hourly backups (older than RETENTION_HOURS)
find $BACKUP_DIR -name "${DB_NAME}_hourly_*.sql.gz" -mmin +$((RETENTION_HOURS * 60)) -delete 2>/dev/null || true

REMAINING=$(ls -1 $BACKUP_DIR/${DB_NAME}_hourly_*.sql.gz 2>/dev/null | wc -l)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Total hourly backups: $REMAINING"
