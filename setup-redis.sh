#!/bin/bash
# NeoGiga Redis Installation and Configuration Script
# Installs Redis 7.x and configures it for Laravel queue, cache, and sessions

set -e

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

log_info "=== NeoGiga Redis Installation ==="

# Step 1: Check if Redis is already installed
if command -v redis-server &> /dev/null; then
    REDIS_VERSION=$(redis-server --version | awk -F'=' '{print $2}' | awk '{print $1}')
    log_info "Redis is already installed (version $REDIS_VERSION)"
    read -p "Do you want to reinstall? (y/n): " REINSTALL
    if [ "$REINSTALL" != "y" ]; then
        log_info "Skipping installation, proceeding to configuration..."
        goto_config=true
    fi
fi

if [ "$goto_config" != "true" ]; then
    # Step 2: Install Redis
    log_info "Step 1: Installing Redis..."
    
    apt-get update
    apt-get install -y redis-server redis-tools
    
    log_info "Redis installed successfully"
fi

# Step 3: Configure Redis for production
log_info "Step 2: Configuring Redis for production..."

REDIS_CONF="/etc/redis/redis.conf"
BACKUP_CONF="/etc/redis/redis.conf.backup.$(date +%Y%m%d-%H%M%S)"

# Backup existing config
if [ -f "$REDIS_CONF" ]; then
    cp "$REDIS_CONF" "$BACKUP_CONF"
    log_info "Backup created: $BACKUP_CONF"
fi

# Create production Redis configuration
cat > "$REDIS_CONF" << 'EOF'
# NeoGiga Production Redis Configuration
# Generated automatically - do not edit manually

# Network
bind 127.0.0.1
port 6379
protected-mode yes
tcp-backlog 511
timeout 0
tcp-keepalive 300

# General
daemonize yes
supervised systemd
pidfile /var/run/redis/redis-server.pid
loglevel notice
logfile /var/log/redis/redis-server.log
databases 16

# Snapshotting (Persistence)
save 900 1
save 300 10
save 60 10000
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir /var/lib/redis

# Replication (disable for single instance)
replica-serve-stale-data yes
replica-read-only yes
repl-diskless-sync no

# Security (IMPORTANT: Set a strong password in production)
# requirepass YOUR_STRONG_PASSWORD_HERE

# Memory Management (Critical for 500k+ products)
maxmemory 2gb
maxmemory-policy allkeys-lru

# Lazy Freeing
lazyfree-lazy-eviction no
lazyfree-lazy-expire no
lazyfree-lazy-server-del no
replica-lazy-flush no

# Append Only Mode (AOF Persistence)
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec
no-appendfsync-on-rewrite no
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb
aof-load-truncated yes
aof-use-rdb-preamble yes

# Lua scripting
lua-time-limit 5000

# Cluster (disabled for single instance)
cluster-enabled no

# Slow Log
slowlog-log-slower-than 10000
slowlog-max-len 128

# Latency Monitor
latency-monitor-threshold 100

# Event Notification
notify-keyspace-events ""

# Advanced Config
hash-max-ziplist-entries 512
hash-max-ziplist-value 64
list-max-ziplist-size -2
list-compress-depth 0
set-max-intset-entries 512
zset-max-ziplist-entries 128
zset-max-ziplist-value 64
hll-sparse-max-bytes 3000
stream-node-max-bytes 4096
stream-node-max-entries 100
activerehashing yes
client-output-buffer-limit normal 0 0 0
client-output-buffer-limit replica 256mb 64mb 60
client-output-buffer-limit pubsub 32mb 8mb 60
hz 10
dynamic-hz yes
aof-rewrite-incremental-fsync yes
rdb-save-incremental-fsync yes
EOF

log_info "Redis configuration written"

# Step 4: Set proper permissions
log_info "Step 3: Setting permissions..."

chown redis:redis "$REDIS_CONF"
chmod 640 "$REDIS_CONF"

# Ensure directories exist with correct permissions
mkdir -p /var/lib/redis
mkdir -p /var/log/redis
chown -R redis:redis /var/lib/redis
chown -R redis:redis /var/log/redis
chmod 750 /var/lib/redis
chmod 750 /var/log/redis

# Step 5: Enable and start Redis service
log_info "Step 4: Starting Redis service..."

systemctl daemon-reload
systemctl enable redis-server
systemctl restart redis-server

# Wait for Redis to start
sleep 2

# Step 6: Verify Redis is running
log_info "Step 5: Verifying Redis installation..."

if systemctl is-active --quiet redis-server; then
    log_info "✓ Redis service is active"
else
    log_error "Redis service failed to start. Check logs: /var/log/redis/redis-server.log"
    exit 1
fi

# Test Redis connection
if redis-cli ping | grep -q "PONG"; then
    log_info "✓ Redis is responding to PING"
else
    log_error "Redis is not responding. Check configuration."
    exit 1
fi

# Get Redis info
REDIS_INFO=$(redis-cli INFO server | grep redis_version)
log_info "Redis version: $REDIS_INFO"

MEMORY_INFO=$(redis-cli INFO memory | grep used_memory_human | head -1)
log_info "Current memory usage: $MEMORY_INFO"

# Step 7: Install PHP Redis extension
log_info "Step 6: Installing PHP Redis extension..."

# Detect PHP version
PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
log_info "Detected PHP version: $PHP_VERSION"

# Install appropriate PHP Redis extension
if [[ "$PHP_VERSION" == "8.3" ]]; then
    apt-get install -y php8.3-redis || pecl install redis
elif [[ "$PHP_VERSION" == "8.2" ]]; then
    apt-get install -y php8.2-redis || pecl install redis
elif [[ "$PHP_VERSION" == "8.1" ]]; then
    apt-get install -y php8.1-redis || pecl install redis
else
    # Generic fallback
    apt-get install -y php-redis || pecl install redis
fi

# Enable the extension if needed
phpenmod redis 2>/dev/null || true

# Restart PHP-FPM
log_info "Step 7: Restarting PHP-FPM..."

if systemctl is-active --quiet php8.3-fpm; then
    systemctl restart php8.3-fpm
elif systemctl is-active --quiet php8.2-fpm; then
    systemctl restart php8.2-fpm
elif systemctl is-active --quiet php8.1-fpm; then
    systemctl restart php8.1-fpm
elif systemctl is-active --quiet php-fpm; then
    systemctl restart php-fpm
fi

log_info "PHP-FPM restarted"

# Step 8: Verify PHP Redis extension
log_info "Step 8: Verifying PHP Redis extension..."

if php -m | grep -i redis; then
    log_info "✓ PHP Redis extension is loaded"
else
    log_warn "PHP Redis extension may not be loaded. Please verify manually."
fi

# Final summary
echo ""
log_info "=== Redis Installation Complete ==="
log_info "Configuration file: $REDIS_CONF"
log_info "Log file: /var/log/redis/redis-server.log"
log_info "Data directory: /var/lib/redis"
echo ""
log_info "Next steps:"
log_info "1. Update your .env file with REDIS settings"
log_info "2. Set a strong password in redis.conf (requirepass directive)"
log_info "3. Test Laravel integration: php artisan cache:clear"
log_info "4. Monitor Redis: redis-cli monitor"
log_info "5. View stats: redis-cli INFO"
echo ""
log_warn "IMPORTANT: For production, set a strong password in $REDIS_CONF"
log_warn "Uncomment and set: requirepass YOUR_STRONG_PASSWORD_HERE"
echo ""

exit 0
