#!/bin/bash

###############################################################################
# Deployment Script for Manager HPR
# Author: Auto-generated
# Version: 1.0.0
# Description: Automated deployment script for Laravel application
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/manager"
BRANCH="59-edu"  # Change to your production branch
PHP_VERSION="8.2"

# Functions
print_header() {
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}$1${NC}"
    echo -e "${GREEN}========================================${NC}"
}

print_step() {
    echo -e "${YELLOW}➜ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Check if script is run as root or with sudo
if [ "$EUID" -ne 0 ]; then
    print_error "Please run this script as root or with sudo"
    exit 1
fi

print_header "Manager HPR - Deployment Script"

# Step 1: Enable Maintenance Mode
print_step "Enabling maintenance mode..."
cd $APP_DIR
sudo -u www-data php artisan down || true
print_success "Maintenance mode enabled"

# Step 2: Pull latest code
print_step "Pulling latest code from Git..."
sudo -u www-data git fetch origin
sudo -u www-data git checkout $BRANCH
sudo -u www-data git pull origin $BRANCH
print_success "Code updated"

# Step 3: Install/Update Composer dependencies
print_step "Installing Composer dependencies..."
sudo -u www-data composer install --optimize-autoloader --no-dev --no-interaction
print_success "Composer dependencies installed"

# Step 4: Install/Update NPM dependencies
print_step "Installing NPM dependencies..."
sudo -u www-data npm install --production=false
print_success "NPM dependencies installed"

# Step 5: Build frontend assets
print_step "Building frontend assets..."
sudo -u www-data npm run build
print_success "Assets built"

# Step 6: Run database migrations
print_step "Running database migrations..."
sudo -u www-data php artisan migrate --force
print_success "Migrations completed"

# Step 7: Clear all caches
print_step "Clearing application caches..."
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
print_success "Caches cleared"

# Step 8: Cache configuration, routes, and views
print_step "Caching configuration, routes, and views..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
print_success "Configuration cached"

# Step 9: Optimize autoloader
print_step "Optimizing Composer autoloader..."
sudo -u www-data composer dump-autoload --optimize
print_success "Autoloader optimized"

# Step 10: Restart queue workers
print_step "Restarting queue workers..."
supervisorctl restart manager-worker:*
print_success "Queue workers restarted"

# Step 11: Reload PHP-FPM
print_step "Reloading PHP-FPM..."
systemctl reload php${PHP_VERSION}-fpm
print_success "PHP-FPM reloaded"

# Step 12: Reload Nginx
print_step "Reloading Nginx..."
systemctl reload nginx
print_success "Nginx reloaded"

# Step 13: Fix permissions (just in case)
print_step "Fixing permissions..."
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache
print_success "Permissions fixed"

# Step 14: Disable Maintenance Mode
print_step "Disabling maintenance mode..."
sudo -u www-data php artisan up
print_success "Application is live!"

print_header "Deployment Completed Successfully!"

# Show current version/commit
echo ""
echo "Current commit:"
cd $APP_DIR
git log -1 --oneline

echo ""
echo -e "${GREEN}Deployment finished at: $(date)${NC}"
