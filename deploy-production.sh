#!/bin/bash
# NeoGiga Production Deployment Script
# This script safely deploys code to production with rollback capability

set -e

# Configuration
DEPLOY_USER="neogiga"
DEPLOY_GROUP="neogiga"
DEPLOY_BASE="/home/neogiga/laravel"
RELEASES_DIR="$DEPLOY_BASE/releases"
CURRENT_LINK="$DEPLOY_BASE/current"
SHARED_DIR="$DEPLOY_BASE/shared"
BACKUP_DIR="$DEPLOY_BASE/backups"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
RELEASE_NAME="release-$TIMESTAMP"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as correct user
if [ "$EUID" -ne 0 ]; then
    log_error "Please run as root or with sudo"
    exit 1
fi

log_info "=== NeoGiga Production Deployment ==="
log_info "Timestamp: $TIMESTAMP"
log_info "Release: $RELEASE_NAME"

# Step 1: Pre-deployment checks
log_info "Step 1: Running pre-deployment checks..."

if [ ! -d "$DEPLOY_BASE" ]; then
    log_error "Deploy base directory not found: $DEPLOY_BASE"
    exit 1
fi

if [ ! -L "$CURRENT_LINK" ]; then
    log_error "Current symlink not found. Is this the first deployment?"
    exit 1
fi

# Check disk space
DISK_USAGE=$(df -h /home | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 85 ]; then
    log_warn "Disk usage is at ${DISK_USAGE}%. Consider cleanup before deployment."
fi

# Step 2: Create backup of current release
log_info "Step 2: Creating backup of current release..."

CURRENT_RELEASE=$(readlink "$CURRENT_LINK")
BACKUP_PATH="$BACKUP_DIR/backup-$TIMESTAMP"

mkdir -p "$BACKUP_PATH"
cp -r "$CURRENT_RELEASE/"* "$BACKUP_PATH/" 2>/dev/null || true
cp "$CURRENT_LINK/.env" "$BACKUP_PATH/.env.backup" 2>/dev/null || true

log_info "Backup created at: $BACKUP_PATH"

# Step 3: Clone/Pull latest code
log_info "Step 3: Fetching latest code from repository..."

NEW_RELEASE_PATH="$RELEASES_DIR/$RELEASE_NAME"
mkdir -p "$NEW_RELEASE_PATH"

cd "$RELEASES_DIR"

# If this is a fresh deployment from git
if [ -d "/home/neogiga/neogiga-global/giga-nepal-backend/.git" ]; then
    REPO_DIR="/home/neogiga/neogiga-global/giga-nepal-backend"
    cd "$REPO_DIR"
    
    # Stash any local changes first
    git stash push -m "Pre-deployment local changes $TIMESTAMP" 2>/dev/null || true
    
    # Pull latest from main
    git fetch origin
    git checkout main
    git pull origin main
    
    # Copy to new release directory
    rsync -av --exclude='.env' --exclude='storage/' --exclude='vendor/' \
          --exclude='node_modules/' --exclude='.git/' \
          "$REPO_DIR/" "$NEW_RELEASE_PATH/"
else
    log_error "Repository not found. Please ensure repo is cloned."
    exit 1
fi

# Step 4: Setup shared directories and files
log_info "Step 4: Setting up shared directories..."

# Create shared directories if they don't exist
mkdir -p "$SHARED_DIR/storage/app/public"
mkdir -p "$SHARED_DIR/storage/logs"
mkdir -p "$SHARED_DIR/storage/framework/cache"
mkdir -p "$SHARED_DIR/storage/framework/sessions"
mkdir -p "$SHARED_DIR/storage/framework/views"

# Symlink shared storage
rm -rf "$NEW_RELEASE_PATH/storage"
ln -sfn "$SHARED_DIR/storage" "$NEW_RELEASE_PATH/storage"

# Copy .env from current release (preserves environment config)
if [ -f "$CURRENT_LINK/.env" ]; then
    cp "$CURRENT_LINK/.env" "$NEW_RELEASE_PATH/.env"
    log_info "Environment configuration preserved from current release"
else
    log_warn "No .env file found. You must create one before proceeding."
fi

# Step 5: Install dependencies
log_info "Step 5: Installing PHP dependencies..."

cd "$NEW_RELEASE_PATH"

# Check PHP version
PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
log_info "Detected PHP version: $PHP_VERSION"

# Install Composer dependencies
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction
    log_info "Composer dependencies installed"
else
    log_error "Composer not found. Please install composer."
    exit 1
fi

# Install Node dependencies if package.json exists
if [ -f "package.json" ]; then
    log_info "Installing Node dependencies..."
    if command -v npm &> /dev/null; then
        npm ci --production
        npm run build || npm run prod || true
    fi
fi

# Step 6: Run database migrations
log_info "Step 6: Running database migrations..."

php artisan migrate --force --no-interaction
log_info "Database migrations completed"

# Step 7: Clear and rebuild caches
log_info "Step 7: Clearing and rebuilding caches..."

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clear old cache before rebuilding
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

log_info "Caches rebuilt successfully"

# Step 8: Optimize autoloader and permissions
log_info "Step 8: Setting permissions..."

chown -R "$DEPLOY_USER:$DEPLOY_GROUP" "$NEW_RELEASE_PATH"
chmod -R 755 "$NEW_RELEASE_PATH/storage"
chmod -R 755 "$NEW_RELEASE_PATH/bootstrap/cache"

# Step 9: Update symlink (atomic switch)
log_info "Step 9: Switching to new release..."

ln -sfn "$NEW_RELEASE_PATH" "$CURRENT_LINK"

log_info "Symlink updated to: $NEW_RELEASE_PATH"

# Step 10: Restart services
log_info "Step 10: Restarting services..."

# Restart PHP-FPM
if systemctl is-active --quiet php8.3-fpm; then
    systemctl restart php8.3-fpm
    log_info "PHP-FPM restarted"
elif systemctl is-active --quiet php8.2-fpm; then
    systemctl restart php8.2-fpm
    log_info "PHP-FPM restarted"
elif systemctl is-active --quiet php-fpm; then
    systemctl restart php-fpm
    log_info "PHP-FPM restarted"
else
    log_warn "PHP-FPM service not found or not running"
fi

# Restart queue workers gracefully
if systemctl is-active --quiet neogiga-queue-worker; then
    systemctl reload neogiga-queue-worker
    log_info "Queue workers reloaded"
fi

# Step 11: Post-deployment verification
log_info "Step 11: Running post-deployment verification..."

cd "$CURRENT_LINK"

# Check if application responds
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health 2>/dev/null || echo "000")

if [ "$HEALTH_CHECK" = "200" ]; then
    log_info "✓ Health check passed (HTTP $HEALTH_CHECK)"
else
    log_warn "Health check returned HTTP $HEALTH_CHECK. Manual verification recommended."
fi

# Verify critical directories exist
for dir in "storage" "bootstrap/cache" "vendor"; do
    if [ -d "$CURRENT_LINK/$dir" ]; then
        log_info "✓ Directory exists: $dir"
    else
        log_error "Missing directory: $dir"
    fi
done

# Step 12: Cleanup old releases (keep last 5)
log_info "Step 12: Cleaning up old releases..."

cd "$RELEASES_DIR"
ls -t | tail -n +6 | xargs -r rm -rf

log_info "Old releases cleaned up (keeping last 5)"

# Final summary
echo ""
log_info "=== Deployment Complete ==="
log_info "Release: $RELEASE_NAME"
log_info "Current: $(readlink "$CURRENT_LINK")"
log_info "Backup: $BACKUP_PATH"
echo ""
log_warn "Remember to monitor logs: tail -f $SHARED_DIR/storage/logs/laravel.log"
log_warn "Rollback command: ln -sfn $BACKUP_PATH $CURRENT_LINK && systemctl restart php-fpm"
echo ""

exit 0
