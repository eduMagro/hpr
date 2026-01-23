#!/bin/bash

###############################################################################
# First Deployment Script for Manager HPR
# Author: Auto-generated
# Version: 1.0.0
# Description: Initial deployment script (use only once)
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/manager"
REPO_URL="https://github.com/eduMagro/hpr.git"  # Change to your repo
BRANCH="59-edu"  # Change to your production branch
DB_NAME="manager_production"
DB_USER="manager_user"

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

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Check if script is run as root or with sudo
if [ "$EUID" -ne 0 ]; then
    print_error "Please run this script as root or with sudo"
    exit 1
fi

print_header "Manager HPR - First Deployment Script"

# Warning
echo ""
print_info "This script will:"
print_info "  1. Clone the repository"
print_info "  2. Install dependencies"
print_info "  3. Set up the application"
print_info "  4. Run migrations"
print_info ""
read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_error "Deployment cancelled"
    exit 1
fi

# Step 1: Clone repository
print_step "Cloning repository..."
if [ -d "$APP_DIR" ]; then
    print_error "Directory $APP_DIR already exists!"
    read -p "Remove and continue? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf $APP_DIR
    else
        exit 1
    fi
fi

cd /var/www
git clone $REPO_URL manager
cd $APP_DIR
git checkout $BRANCH
print_success "Repository cloned"

# Step 2: Set ownership
print_step "Setting file ownership..."
chown -R www-data:www-data $APP_DIR
print_success "Ownership set"

# Step 3: Install Composer dependencies
print_step "Installing Composer dependencies..."
sudo -u www-data composer install --optimize-autoloader --no-dev --no-interaction
print_success "Composer dependencies installed"

# Step 4: Install NPM dependencies
print_step "Installing NPM dependencies..."
sudo -u www-data npm install
print_success "NPM dependencies installed"

# Step 5: Build frontend assets
print_step "Building frontend assets..."
sudo -u www-data npm run build
print_success "Assets built"

# Step 6: Copy .env file
print_step "Setting up environment file..."
if [ ! -f "$APP_DIR/.env" ]; then
    cp $APP_DIR/.env.example $APP_DIR/.env
    chown www-data:www-data $APP_DIR/.env
    print_success ".env file created"

    print_info "IMPORTANT: Edit .env file with your production settings!"
    print_info "Database: $DB_NAME"
    print_info "Database User: $DB_USER"
    echo ""
    read -p "Press enter when you've edited .env file..."
else
    print_success ".env file already exists"
fi

# Step 7: Generate application key
print_step "Generating application key..."
sudo -u www-data php artisan key:generate --force
print_success "Application key generated"

# Step 8: Set permissions
print_step "Setting permissions..."
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache
print_success "Permissions set"

# Step 9: Create storage symlink
print_step "Creating storage symlink..."
sudo -u www-data php artisan storage:link
print_success "Storage symlink created"

# Step 10: Run migrations
print_step "Running database migrations..."
echo ""
print_info "This will create all tables in the database."
read -p "Continue with migrations? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    sudo -u www-data php artisan migrate --force
    print_success "Migrations completed"
else
    print_info "Skipping migrations"
fi

# Step 11: Run seeders (optional)
print_step "Run database seeders?"
read -p "Do you want to run seeders? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    sudo -u www-data php artisan db:seed --force
    print_success "Seeders completed"
else
    print_info "Skipping seeders"
fi

# Step 12: Cache configuration
print_step "Caching configuration..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
print_success "Configuration cached"

# Step 13: Optimize autoloader
print_step "Optimizing autoloader..."
sudo -u www-data composer dump-autoload --optimize
print_success "Autoloader optimized"

# Step 14: Final permissions check
print_step "Final permissions check..."
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache
print_success "Permissions verified"

print_header "First Deployment Completed!"

echo ""
print_info "Next steps:"
print_info "  1. Configure Nginx (see docs/config/nginx-config.conf)"
print_info "  2. Configure Supervisor (see docs/config/supervisor-config.conf)"
print_info "  3. Set up SSL with Certbot"
print_info "  4. Configure cron for Laravel scheduler"
print_info ""
print_success "Application installed at: $APP_DIR"
print_success "Current commit: $(git log -1 --oneline)"
echo ""
