#!/bin/bash
# NeoGiga Queue Worker Setup Script
# Run this on the production server to install and start all queue workers
#
# Usage: sudo bash deploy/setup-queue-workers.sh

set -euo pipefail

APP_DIR="/home/neogiga/laravel/current"
SERVICE_DIR="/etc/systemd/system"
DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "=== NeoGiga Queue Worker Setup ==="
echo "App directory: $APP_DIR"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "ERROR: Please run as root (sudo bash deploy/setup-queue-workers.sh)"
    exit 1
fi

# Check if the app directory exists
if [ ! -d "$APP_DIR" ]; then
    echo "ERROR: Application directory not found: $APP_DIR"
    exit 1
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP not found in PATH"
    exit 1
fi

echo "PHP version: $(php -v | head -n1)"
echo ""

# Copy service files
echo "Installing systemd service files..."
cp "$DEPLOY_DIR/systemd/neogiga-queue-default.service" "$SERVICE_DIR/"
cp "$DEPLOY_DIR/systemd/neogiga-queue-transactional.service" "$SERVICE_DIR/"
cp "$DEPLOY_DIR/systemd/neogiga-queue-marketing.service" "$SERVICE_DIR/"
cp "$DEPLOY_DIR/systemd/neogiga-queue-imports.service" "$SERVICE_DIR/"

# Reload systemd
echo "Reloading systemd daemon..."
systemctl daemon-reload

# Enable and start services
echo ""
echo "Enabling and starting queue workers..."

for service in \
    neogiga-queue-default \
    neogiga-queue-transactional \
    neogiga-queue-marketing \
    neogiga-queue-imports; do

    echo "  - $service..."
    systemctl enable "$service"
    systemctl restart "$service"
    sleep 1

    if systemctl is-active --quiet "$service"; then
        echo "    ✓ Running"
    else
        echo "    ✗ FAILED to start"
        echo "    Check: journalctl -u $service -n 50"
    fi
done

echo ""
echo "=== Queue Worker Status ==="
echo ""

# Show status of all queue workers
for service in \
    neogiga-queue-default \
    neogiga-queue-transactional \
    neogiga-queue-marketing \
    neogiga-queue-imports; do

    status=$(systemctl is-active "$service" 2>/dev/null || echo "inactive")
    printf "  %-40s %s\n" "$service" "$status"
done

echo ""
echo "=== Pending Jobs Summary ==="
echo ""

# Check pending jobs in database
cd "$APP_DIR"
php artisan tinker --execute="
\$jobs = DB::table('jobs')->count();
\$failed = DB::table('failed_jobs')->count();
\$queues = DB::table('jobs')->select('queue', DB::raw('count(*) as pending'))->groupBy('queue')->get();
echo \"Total pending jobs: \" . \$jobs . PHP_EOL;
echo \"Total failed jobs: \" . \$failed . PHP_EOL;
if (\$queues->isNotEmpty()) {
    echo PHP_EOL . \"Jobs by queue:\" . PHP_EOL;
    foreach (\$queues as \$q) {
        echo \"  - \" . \$q->queue . \": \" . \$q->pending . PHP_EOL;
    }
}
" 2>/dev/null || echo "Could not query job counts"

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Useful commands:"
echo "  systemctl status neogiga-queue-*          # Check worker status"
echo "  journalctl -u neogiga-queue-default -f    # Tail default queue logs"
echo "  php artisan queue:work database --once    # Process one job manually"
echo "  php artisan queue:failed                  # List failed jobs"
echo "  php artisan queue:retry all               # Retry all failed jobs"
echo ""
