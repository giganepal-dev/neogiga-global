# NeoGiga Production Deployment Guide

## Quick Start

### First-Time Setup

```bash
# 1. Install Redis (required for production)
sudo bash setup-redis.sh

# 2. Copy environment configuration
cp .env.production.example .env

# 3. Generate application key
php artisan key:generate

# 4. Update .env with your production values
# Edit database credentials, Redis settings, S3 keys, etc.

# 5. Run the deployment script
sudo bash deploy-production.sh
```

### Routine Deployment

```bash
# Ensure you're on main branch with latest changes
git checkout main
git pull origin main

# Run deployment (creates new release, runs migrations, restarts services)
sudo bash deploy-production.sh
```

### Emergency Rollback

```bash
# Immediately rollback to previous release
sudo bash rollback-production.sh
```

---

## Architecture Overview

### Directory Structure

```
/home/neogiga/laravel/
├── current/           → Symlink to active release
├── releases/          → All releases (kept: last 5)
│   ├── release-YYYYMMDD-HHMMSS/
│   └── ...
├── shared/            → Persistent across releases
│   └── storage/       → Logs, cache, sessions, uploads
└── backups/           → Deployment backups
```

### Services

| Service | Purpose | Systemd Unit |
|---------|---------|--------------|
| PHP-FPM | Application runtime | `php8.3-fpm.service` |
| Apache | Web server | `apache2.service` |
| PostgreSQL | Database | `postgresql.service` |
| Redis | Cache/Queue/Sessions | `redis-server.service` |
| Queue Worker | Background jobs | `neogiga-queue-worker.service` |
| Search Worker | Index rebuilding | `neogiga-search-worker.service` |
| Health Monitor | Continuous monitoring | `neogiga-health-monitor.service` |

---

## Configuration Reference

### Environment Variables (.env)

#### Critical Settings

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://neogiga.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=neogiga_production
DB_USERNAME=neogiga_user
DB_PASSWORD=<strong-password>

# Redis (REQUIRED for production)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# File Storage (S3 recommended)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<key>
AWS_SECRET_ACCESS_KEY=<secret>
AWS_DEFAULT_REGION=ap-south-1
AWS_BUCKET=neogiga-assets-prod
```

#### Feature Flags

```ini
NEO_ENABLE_REDIS_CACHE=true
NEO_ENABLE_QUEUE_WORKERS=true
NEO_ENABLE_AUTOCOMPLETE_SEARCH=true
NEO_ENABLE_GERBER_ANALYSIS=true
NEO_ENABLE_BOM_SOURCING=true
NEO_MAX_IMPORT_BATCH_SIZE=1000
NEO_SEARCH_INDEX_CHUNK_SIZE=5000
```

---

## Maintenance Commands

### Database Operations

```bash
# Run migrations
php artisan migrate --force

# Rollback last migration batch
php artisan migrate:rollback --step=1

# Seed database (careful in production!)
php artisan db:seed --class=SpecificSeeder

# Backup database
pg_dump -U neogiga_user neogiga_production | gzip > backup-$(date +%Y%m%d).sql.gz
```

### Cache Management

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Warm critical caches
php artisan neogiga:warm-caches
```

### Queue Management

```bash
# Monitor queue status
php artisan queue:monitor redis

# Retry failed jobs
php artisan queue:retry <job-id>
php artisan queue:retry all

# Flush failed jobs table
php artisan queue:flush

# Check queue size
php artisan tinker
>>> Queue::size('default')
>>> Queue::size('search-index')
```

### Search Index

```bash
# Rebuild entire search index
php artisan scout:import "App\\Models\\Product"

# Remove from index
php artisan scout:flush "App\\Models\\Product"

# Import specific chunk (for resume support)
php artisan neogiga:rebuild-search-index --chunk=5000 --offset=0
```

### Product Catalog

```bash
# Validate product data quality
php artisan neogiga:validate-products

# Fix common product issues
php artisan neogiga:fix-product-data

# Regenerate product slugs
php artisan neogiga:regenerate-slugs

# Update product counts
php artisan neogiga:update-counts
```

---

## Monitoring

### Health Checks

```bash
# Manual health check
php artisan neogiga:health-check

# View health endpoint (from server)
curl http://localhost/health

# Continuous monitoring (every 5 min via systemd)
systemctl status neogiga-health-monitor
journalctl -u neogiga-health-monitor -f
```

### Log Files

| Log | Location |
|-----|----------|
| Application | `/home/neogiga/laravel/shared/storage/logs/laravel.log` |
| PHP-FPM | `/var/log/php8.3-fpm.log` |
| Apache | `/var/log/apache2/error.log` |
| PostgreSQL | `/var/log/postgresql/postgresql-16-main.log` |
| Redis | `/var/log/redis/redis-server.log` |
| Queue Worker | `journalctl -u neogiga-queue-worker -f` |

### Real-time Monitoring

```bash
# Watch application logs
tail -f /home/neogiga/laravel/shared/storage/logs/laravel.log

# Watch for errors only
tail -f /home/neogiga/laravel/shared/storage/logs/laravel.log | grep -i error

# Monitor queue processing
watch -n 5 'php artisan queue:monitor redis'

# Database connections
watch -n 5 'psql -U neogiga_user -d neogiga_production -c "SELECT count(*) FROM pg_stat_activity WHERE datname = current_database()"'

# Redis stats
watch -n 5 'redis-cli INFO stats | grep -E "connected_clients|used_memory|ops_per_sec"'
```

---

## Troubleshooting

### Common Issues

#### 1. Site Returns 500 Error

```bash
# Check logs
tail -100 /home/neogiga/laravel/shared/storage/logs/laravel.log

# Verify permissions
ls -la /home/neogiga/laravel/current/storage/

# Check PHP-FPM status
systemctl status php8.3-fpm

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

#### 2. Queue Jobs Not Processing

```bash
# Check worker status
systemctl status neogiga-queue-worker

# Restart workers
sudo systemctl restart neogiga-queue-worker

# Check Redis connection
redis-cli ping

# View failed jobs
php artisan queue:failed

# Retry all failed
php artisan queue:retry all
```

#### 3. High Memory Usage

```bash
# Check process memory
ps aux | grep php | sort -k4 -rn | head -10

# Clear opcode cache
sudo systemctl restart php8.3-fpm

# Check Redis memory
redis-cli INFO memory

# If Redis > 90%, consider increasing maxmemory
```

#### 4. Database Connection Errors

```bash
# Check PostgreSQL status
systemctl status postgresql

# Check connection count
psql -U neogiga_user -d neogiga_production -c "SELECT count(*) FROM pg_stat_activity"

# Check for locks
psql -U neogiga_user -d neogiga_production -c "SELECT * FROM pg_locks WHERE granted = false"

# Restart PostgreSQL if needed
sudo systemctl restart postgresql
```

#### 5. Search Not Working

```bash
# Check index coverage
php artisan tinker
>>> App\Models\Product::count()
>>> App\Models\SearchIndex::count()

# Rebuild index
php artisan scout:import "App\\Models\\Product"

# Check for failed index jobs
php artisan queue:failed | grep -i search
```

---

## Security Checklist

### Before Going Live

- [ ] Change all default passwords
- [ ] Set strong Redis password
- [ ] Configure firewall (UFW)
- [ ] Enable SSL (Let's Encrypt auto-renewal)
- [ ] Set up fail2ban
- [ ] Restrict admin access by IP
- [ ] Enable rate limiting
- [ ] Configure CORS properly
- [ ] Set secure cookie flags
- [ ] Disable debug mode (`APP_DEBUG=false`)
- [ ] Remove test/seeder data
- [ ] Review file permissions
- [ ] Set up backup automation
- [ ] Configure error reporting (Sentry/Bugsnag)

### Ongoing Security

- [ ] Weekly security updates
- [ ] Monthly dependency audits (`composer audit`)
- [ ] Quarterly password rotation
- [ ] Annual penetration testing
- [ ] Monitor failed login attempts
- [ ] Review access logs regularly

---

## Performance Optimization

### Database Indexes

Ensure these indexes exist:

```sql
-- Products
CREATE INDEX CONCURRENTLY idx_products_status ON products(status);
CREATE INDEX CONCURRENTLY idx_products_mpns ON products(manufacturer, mpn);
CREATE INDEX CONCURRENTLY idx_products_category ON products(category_id);
CREATE INDEX CONCURRENTLY idx_products_created ON products(created_at);

-- Search
CREATE INDEX CONCURRENTLY idx_search_product_id ON search_index(product_id);
CREATE INDEX CONCURRENTLY idx_search_vector ON search_index USING gin(search_vector);

-- Orders
CREATE INDEX CONCURRENTLY idx_orders_customer ON orders(customer_id);
CREATE INDEX CONCURRENTLY idx_orders_status ON orders(status);
CREATE INDEX CONCURRENTLY idx_orders_created ON orders(created_at);
```

### Caching Strategy

```php
// Cache expensive queries
$products = Cache::remember('featured-products', 3600, function () {
    return Product::approved()->featured()->limit(20)->get();
});

// Use cache tags for selective invalidation
Cache::tags(['products', 'category-' . $categoryId])
    ->remember('category-products-' . $categoryId, 3600, function () use ($categoryId) {
        return Product::approved()->where('category_id', $categoryId)->get();
    });

// Invalidate when data changes
Cache::tags(['products'])->flush();
```

### CDN Configuration

Configure CloudFront or similar for:

- Static assets (`/css`, `/js`, `/images`)
- Product images (S3 + CloudFront)
- Datasheets (S3 signed URLs)

---

## Scaling Considerations

### Horizontal Scaling

When to scale:

- CPU consistently > 70%
- Memory consistently > 80%
- Response time > 500ms
- Queue backlog growing

Options:

1. **Add more queue workers**
   ```bash
   # Deploy additional worker instances
   sudo systemctl enable neogiga-queue-worker@{1..5}
   ```

2. **Separate database server**
   - Move PostgreSQL to dedicated instance
   - Configure read replicas for search queries

3. **Redis Cluster**
   - For > 10GB cache requirements
   - Distribute across multiple nodes

4. **Load Balancer**
   - Add HAProxy or AWS ALB
   - Multiple application servers behind LB

### Vertical Scaling

Minimum production specs:

- CPU: 4 cores
- RAM: 8GB
- Storage: 100GB SSD
- Network: 1Gbps

Recommended for 500K+ products:

- CPU: 8 cores
- RAM: 16-32GB
- Storage: 500GB NVMe SSD
- Network: 1Gbps+

---

## Contact & Support

For production issues:

1. Check health monitor logs
2. Review application logs
3. Attempt rollback if recent deployment
4. Contact infrastructure team

Emergency contacts should be documented separately.
