#!/bin/bash
# NeoGiga Production Rollback Script
# Safely rolls back to previous release in case of deployment failure

set -e

DEPLOY_BASE="/home/neogiga/laravel"
CURRENT_LINK="$DEPLOY_BASE/current"
RELEASES_DIR="$DEPLOY_BASE/releases"
BACKUP_DIR="$DEPLOY_BASE/backups"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

if [ "$EUID" -ne 0 ]; then
    log_error "Please run as root or with sudo"
    exit 1
fi

log_info "=== NeoGiga Production Rollback ==="

# Check if current symlink exists
if [ ! -L "$CURRENT_LINK" ]; then
    log_error "Current symlink not found. Cannot determine current release."
    exit 1
fi

# Get current release path
CURRENT_RELEASE=$(readlink "$CURRENT_LINK")
log_info "Current release: $CURRENT_RELEASE"

# Find previous release
PREVIOUS_RELEASE=$(ls -t "$RELEASES_DIR" | grep -v "$(basename "$CURRENT_RELEASE")" | head -1)

if [ -z "$PREVIOUS_RELEASE" ]; then
    log_error "No previous release found. Check backup directory."
    
    # Try backup directory
    if [ -d "$BACKUP_DIR" ]; then
        LATEST_BACKUP=$(ls -t "$BACKUP_DIR" | head -1)
        if [ -n "$LATEST_BACKUP" ]; then
            log_info "Found backup: $LATEST_BACKUP"
            read -p "Rollback to backup? (y/n): " CONFIRM
            if [ "$CONFIRM" = "y" ]; then
                TARGET_PATH="$BACKUP_DIR/$LATEST_BACKUP"
            else
                exit 1
            fi
        else
            log_error "No backups found. Manual intervention required."
            exit 1
        fi
    else
        log_error "No backup directory found. Manual intervention required."
        exit 1
    fi
else
    TARGET_PATH="$RELEASES_DIR/$PREVIOUS_RELEASE"
    log_info "Previous release: $TARGET_PATH"
fi

# Confirm rollback
log_warn "About to rollback from $(basename "$CURRENT_RELEASE") to $(basename "$TARGET_PATH")"
read -p "Are you sure? This will affect live site. (y/n): " CONFIRM

if [ "$CONFIRM" != "y" ]; then
    log_info "Rollback cancelled."
    exit 0
fi

# Perform rollback
log_info "Switching symlink to previous release..."

ln -sfn "$TARGET_PATH" "$CURRENT_LINK"

log_info "Symlink updated"

# Verify new target exists
if [ ! -d "$TARGET_PATH" ]; then
    log_error "Target release directory does not exist!"
    exit 1
fi

# Restart services
log_info "Restarting services..."

if systemctl is-active --quiet php8.3-fpm; then
    systemctl restart php8.3-fpm
elif systemctl is-active --quiet php8.2-fpm; then
    systemctl restart php8.2-fpm
elif systemctl is-active --quiet php-fpm; then
    systemctl restart php-fpm
fi

if systemctl is-active --quiet neogiga-queue-worker; then
    systemctl reload neogiga-queue-worker
fi

# Health check
log_info "Running health check..."

sleep 2
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health 2>/dev/null || echo "000")

if [ "$HEALTH_CHECK" = "200" ]; then
    log_info "✓ Health check passed (HTTP $HEALTH_CHECK)"
else
    log_warn "Health check returned HTTP $HEALTH_CHECK. Manual verification required!"
fi

# Summary
echo ""
log_info "=== Rollback Complete ==="
log_info "Previous (failed): $CURRENT_RELEASE"
log_info "Current (active): $(readlink "$CURRENT_LINK")"
echo ""
log_warn "Investigate the failed deployment before attempting redeployment."
log_warn "Check logs: tail -f $DEPLOY_BASE/shared/storage/logs/laravel.log"
echo ""

exit 0
