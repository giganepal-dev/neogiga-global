# NeoGiga Rollback Runbook

## Rollback Triggers

Initiate rollback if any of these occur after deployment:

- Homepage returns non-200 for > 60 seconds
- `/health` returns `{"status":"error"}` or non-200
- PostgreSQL connection failures in logs
- Redis DBSIZE drops to 0
- Queue workers stop processing (supervisorctl status shows STOPPED)
- Seller portal returns 404
- Customer checkout failures reported

## Pre-Flight

Before rolling back, capture current state:

```bash
ssh neogiga "uptime; redis-cli -n 1 DBSIZE; curl -s localhost/health"
```

## Rollback Methods

### Method 1: Code Rollback (fastest, most common)

Restore from pre-deploy backup:

```bash
ssh neogiga "
  cd /home/neogiga/neogiga-global/giga-nepal-backend
  # Restore app directory from backup
  cp -r /tmp/backup-app-*/* app/
  # Rebuild caches
  php artisan config:cache
  php artisan view:clear
  php artisan route:clear
  # Restart
  systemctl restart php8.4-fpm
"
```

### Method 2: Git Revert

If production had a .git repo (currently doesn't):

```bash
ssh neogiga "
  cd /home/neogiga/neogiga-global/giga-nepal-backend
  git log --oneline -5        # identify bad commit
  git revert <bad-commit>     # revert it
  php artisan config:cache
  systemctl reload php8.4-fpm
"
```

### Method 3: Database Rollback

Only for migration-related issues:

```bash
ssh neogiga "
  cd /home/neogiga/neogiga-global/giga-nepal-backend
  php artisan migrate:rollback --step=1
  php artisan config:cache
"
```

**Warning:** `migrate:rollback` drops columns/tables added by the migration. Verify no data loss before running.

### Method 4: Full Restore from Backup

Last resort — restore PostgreSQL from daily backup:

```bash
ssh neogiga "
  # Stop the application
  systemctl stop php8.4-fpm
  
  # Drop and recreate database
  sudo -u postgres psql -c 'DROP DATABASE IF EXISTS neogiga;'
  sudo -u postgres psql -c 'CREATE DATABASE neogiga;'
  
  # Restore from latest backup
  gunzip -c /root/backups/neogiga_*.sql.gz | sudo -u postgres psql -d neogiga
  
  # Rebuild caches
  cd /home/neogiga/neogiga-global/giga-nepal-backend
  php artisan config:cache
  
  # Start application
  systemctl start php8.4-fpm
"
```

**Warning:** All data since last backup will be lost. Last resort only.

## Quick Fixes for Common Issues

### Page Cache Serving Stale Content

```bash
ssh neogiga "redis-cli -n 1 --scan --pattern 'page:*' | xargs redis-cli -n 1 DEL"
```

### PostgreSQL Connection Pool Exhausted

```bash
ssh neogiga "
  sudo -u postgres psql -d neogiga -c \"SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE pid != pg_backend_pid();\"
  systemctl restart php8.4-fpm
"
```

### Redis Memory Full

```bash
ssh neogiga "redis-cli CONFIG SET maxmemory 512mb && redis-cli CONFIG REWRITE"
```

### OPcache Not Picking Up Changes

```bash
ssh neogiga "systemctl restart php8.4-fpm"
```
(Reload is insufficient when validate_timestamps=0)

### Queue Workers Stuck

```bash
ssh neogiga "supervisorctl restart all"
```

## Post-Rollback Validation

Same checks as deployment validation:

```bash
curl https://neogiga.com/health
curl -sL -o /dev/null -w "%{http_code}\n" --resolve "neogiga.com:443:217.216.78.56" "https://neogiga.com/en"
curl -sL -o /dev/null -w "%{http_code}\n" --resolve "neogiga.com:443:217.216.78.56" "https://neogiga.com/seller/login"
ssh neogiga "supervisorctl status"
```

## Backup Locations

| Type | Location | Retention |
|------|----------|-----------|
| PostgreSQL | /root/backups/neogiga_*.sql.gz | 7 days |
| Code (pre-deploy) | /tmp/backup-app-*/ | Manual |
| .env (pre-Redis switch) | /home/.../giga-nepal-backend/.env.backup-20260718 | Manual |
