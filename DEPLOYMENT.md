# NeoGIGA Deployment Guide

## Prerequisites
- Docker & Docker Compose
- Git
- Domain name (optional)
- SSL certificate (optional, for production)

## Quick Start with Docker

### 1. Clone Repository
```bash
git clone <repository-url>
cd neogiga
```

### 2. Configure Environment
```bash
cp .env.example .env
# Edit .env with your database credentials, API keys, etc.
```

### 3. Start All Services
```bash
docker-compose up -d
```

This starts:
- **app** (PHP-FPM)
- **web** (Nginx)
- **db** (MySQL 8.0)
- **redis** (Cache & Queue)
- **queue** (Laravel Queue Worker)
- **scheduler** (Laravel Scheduler)

### 4. Install Dependencies & Setup
```bash
# Install PHP dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate --seed

# Create storage link
docker-compose exec app php artisan storage:link

# Optimize for production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### 5. Access Application
- Web: http://localhost
- Telescope (Dev): http://localhost/telescope

## Manual Installation (Without Docker)

### Requirements
- PHP 8.2+
- MySQL 8.0+ or PostgreSQL 13+
- Redis 6+
- Composer
- Node.js & NPM

### Steps

1. **Install PHP Extensions**
```bash
sudo apt-get install php8.2-mysql php8.2-gd php8.2-curl php8.2-mbstring php8.2-xml php8.2-zip php8.2-redis
```

2. **Clone & Install**
```bash
git clone <repository-url>
cd neogiga
composer install --no-interaction --optimize-autoloader
```

3. **Configure Environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Setup Database**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE neogiga CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate --seed
```

5. **Configure Redis**
```bash
# Update .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

6. **Setup Storage**
```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

7. **Configure Nginx**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/neogiga/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

8. **Start Queue Workers**
```bash
# Start queue worker
php artisan queue:work --tries=3 --timeout=90

# Start scheduler (add to crontab)
* * * * * cd /var/www/neogiga && php artisan schedule:work >> /dev/null 2>&1
```

## Production Checklist

### Security
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure HTTPS/SSL
- [ ] Set strong `APP_KEY`
- [ ] Review `.env` for exposed secrets
- [ ] Enable rate limiting
- [ ] Configure CORS properly

### Performance
- [ ] Enable OPcache
- [ ] Configure Redis cache
- [ ] Enable queue workers
- [ ] Optimize autoloader: `composer dump-autoload --classmap-authoritative`
- [ ] Cache config/routes/views

### Monitoring
- [ ] Setup Laravel Telescope (staging only)
- [ ] Configure error tracking (Sentry/Bugsnag)
- [ ] Setup log rotation
- [ ] Monitor queue health
- [ ] Database backup strategy

### Backup Strategy
```bash
# Daily database backup
0 2 * * * mysqldump -u neogiga -p neogiga | gzip > /backups/neogiga_$(date +\%Y\%m\%d).sql.gz

# Weekly full backup
0 3 * * 0 tar -czf /backups/neogiga_full_$(date +\%Y\%m\%d).tar.gz /var/www/neogiga
```

## Troubleshooting

### Permission Issues
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Queue Not Processing
```bash
# Check queue status
php artisan queue:monitor redis

# Restart queue worker
php artisan queue:restart
```

### Cache Issues
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Support

For issues and questions:
- GitHub Issues: [Create an issue]
- Documentation: `/docs` directory
- Email: support@neogiga.com
