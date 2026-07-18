# NeoGiga Deployment Runbook

## Production Servers

| Server | IP | Port | User | Purpose |
|--------|-----|------|------|---------|
| neogiga | 217.216.78.56 | 22 | root | Primary: neogiga.com, admin.neogiga.com |
| precious | 217.217.249.72 | 2222 | root | giganepal.com (WordPress + backend) |

## Application Path

```
/home/neogiga/neogiga-global/giga-nepal-backend/
```

## Pre-Deployment Checklist

1. [ ] All tests pass locally: `php artisan test`
2. [ ] Config cache cleared locally: `php artisan config:clear`
3. [ ] No destructive migrations: `php artisan migrate:status`
4. [ ] Backup exists: `ls -la /root/backups/`
5. [ ] Production health check passes: `curl https://neogiga.com/health`

## Deploy Code

```bash
# From local repo
cd giga-nepal-backend

# Create deploy package of changed files
git diff --name-only <base-commit>..HEAD | tar -czf deploy.tgz -T -

# Transfer to production
scp deploy.tgz neogiga:/tmp/

# Backup current code
ssh neogiga "cp -r /home/neogiga/neogiga-global/giga-nepal-backend/app /tmp/backup-app-$(date +%Y%m%d)"

# Extract
ssh neogiga "cd /home/neogiga/neogiga-global/giga-nepal-backend && tar -xzf /tmp/deploy.tgz"

# Rebuild caches
ssh neogiga "cd /home/neogiga/neogiga-global/giga-nepal-backend && \
  php artisan config:cache && \
  php artisan view:clear && \
  php artisan route:clear"

# Restart PHP-FPM (OPcache has validate_timestamps=0)
ssh neogiga "systemctl reload php8.4-fpm"

# Verify
curl -sL -o /dev/null -w "%{http_code}\n" --resolve "neogiga.com:443:217.216.78.56" "https://neogiga.com/en"
curl -sL -o /dev/null -w "%{http_code}\n" --resolve "neogiga.com:443:217.216.78.56" "https://neogiga.com/seller/login"
curl https://neogiga.com/health
```

## Run Migrations

```bash
ssh neogiga "cd /home/neogiga/neogiga-global/giga-nepal-backend && php artisan migrate --force"
```

## Post-Deploy Validation

| Check | Command | Expected |
|-------|---------|----------|
| Homepage | `curl /en` | 200, SSR content |
| Category | `curl /en/categories/raspberry-pi` | 200 |
| Product | `curl /en/products/*` | 200 |
| Seller | `curl /seller/login` | 200 |
| Health | `curl /health` | `{"status":"ok"}` |
| Redis | `redis-cli -n 1 DBSIZE` | > 0 keys |
| Cache | Check `x-page-cache: HIT` on repeat request | HIT |
| Queue | `supervisorctl status` | All RUNNING |
| Backup | `ls -la /root/backups/` | Today's dump |

## Key Services

| Service | Restart Command |
|---------|----------------|
| PHP-FPM | `systemctl reload php8.4-fpm` |
| nginx | `systemctl reload nginx` |
| Redis | `systemctl restart redis-server` |
| PostgreSQL | `systemctl restart postgresql` |
| Supervisor | `supervisorctl restart all` |

## Caching Notes

- **Config cache**: Always rebuilt after code deploy (`php artisan config:cache`)
- **View cache**: Clear after Blade changes (`php artisan view:clear`)
- **Route cache**: Keep OFF (`php artisan route:clear`) — incompatible with seller portal middleware
- **OPcache**: Requires PHP-FPM reload/restart (validate_timestamps=0 in production)
- **Page cache**: Auto-expires after 5 minutes. Clear specific keys: `redis-cli -n 1 --scan --pattern 'page:*' | xargs redis-cli -n 1 DEL`

## Emergency Contacts

- Server: neogiga-prod (217.216.78.56)
- Backup server: precious (217.217.249.72:2222)
- GitHub: giganepal-dev/neogiga-global
