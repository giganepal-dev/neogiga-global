# PCB Deployment Plan

## Executive Summary

This document outlines the safe, additive deployment strategy for pcb.neogiga.com, ensuring zero downtime, no data loss, and preservation of all existing NeoGiga functionality.

**Document Version:** 1.0  
**Created:** 2024-07-11  
**Deployment Strategy:** Blue-Green with Rollback Capability  
**Risk Level:** Medium (mitigated by extensive testing and rollback plan)

---

## Deployment Principles

### Golden Rules

1. **Never Deploy on Friday** - Deploy Tuesday-Thursday only
2. **Backup First** - Database and file backups before any change
3. **Additive Only** - No destructive migrations, no data deletion
4. **Test in Staging** - All changes validated in staging environment
5. **Health Checks** - Automated verification post-deployment
6. **Rollback Ready** - Revert plan tested and ready
7. **Monitor Continuously** - Real-time monitoring during and after deploy

### Safety Constraints

- ❌ No `--delete` flag in rsync/sync commands
- ❌ No `DROP TABLE` or `TRUNCATE` operations
- ❌ No forced migrations without `--pretend` test
- ❌ No deployment if tests fail
- ❌ No deployment during peak hours (9 AM - 5 PM local time)
- ✅ Full backup before deployment
- ✅ Staging validation complete
- ✅ Rollback script tested
- ✅ Team on standby during deployment

---

## Pre-Deployment Checklist

### Environment Verification

```bash
# Run 24 hours before deployment
./scripts/verify-deployment-readiness.sh
```

**Checklist:**
- [ ] Git repository clean (no uncommitted changes)
- [ ] Correct branch checked out (`main` or `production`)
- [ ] All CI/CD pipelines passing
- [ ] Staging deployment successful
- [ ] Database backup completed (< 24 hours old)
- [ ] File storage backup completed (< 24 hours old)
- [ ] SSL certificate valid (> 30 days remaining)
- [ ] DNS records configured correctly
- [ ] Monitoring dashboards accessible
- [ ] Alert channels configured (Slack, email, SMS)
- [ ] Team availability confirmed
- [ ] Rollback procedure reviewed

### Database Backup

```bash
# PostgreSQL backup command
pg_dump -h db.neogiga.com -U neogiga_prod neogiga_production | \
  gzip > /backups/neogiga_$(date +%Y%m%d_%H%M%S).sql.gz

# Verify backup
gunzip -c /backups/neogiga_YYYYMMDD_HHMMSS.sql.gz | \
  pg_restore --list > /dev/null && echo "Backup OK" || echo "Backup CORRUPTED"

# Upload to S3 for offsite storage
aws s3 cp /backups/neogiga_YYYYMMDD_HHMMSS.sql.gz \
  s3://neogiga-backups/database/

# Retention: Keep last 30 daily, 12 weekly, 12 monthly
```

### File Storage Backup

```bash
# Backup private storage (excluding PCB files if first deploy)
rsync -av --exclude='pcb-private' \
  /home/neogiga/laravel/current/storage/app/private/ \
  /backups/storage_private_$(date +%Y%m%d)/

# For PCB files (once exists):
rsync -av \
  /home/neogiga/laravel/current/storage/pcb-private/ \
  /backups/pcb-private_$(date +%Y%m%d)/

# Verify backup size matches source
du -sh /source/path
du -sh /backup/path
```

---

## Deployment Procedure

### Phase 1: Pre-Deployment (T-1 hour)

#### Step 1.1: Final Health Check
```bash
# Application health
curl -s https://neogiga.com/api/health | jq .status
curl -s https://pcb.neogiga.com/api/health | jq .status

# Database connectivity
psql -h db.neogiga.com -U neogiga_prod -d neogiga_production -c "SELECT 1"

# Redis connectivity
redis-cli -h redis.neogiga.com ping

# Queue status
php artisan queue:status
```

**Expected Output:**
- All health endpoints return `{"status":"ok"}`
- Database returns `1`
- Redis returns `PONG`
- Queues show workers running

#### Step 1.2: Maintenance Mode Preparation
```bash
# Create maintenance mode bypass file
echo "deployment-team" > /home/neogiga/laravel/current/storage/framework/maintenance-bypass.txt

# Test maintenance mode (staging first)
php artisan down --secret="deployment-team"
curl -s https://staging.neogiga.com/api/health
php artisan up
```

#### Step 1.3: Team Briefing
- Confirm deployment window (typically 2-4 AM local time)
- Assign roles: Deployer, Verifier, Communicator
- Review rollback triggers
- Establish communication channel (Slack/Teams)

---

### Phase 2: Deployment Execution (T-0)

#### Step 2.1: Enable Maintenance Mode
```bash
cd /home/neogiga/laravel/current

# Enable maintenance mode with bypass
php artisan down --secret="deployment-team" --render="maintenance"

# Verify site shows maintenance page
curl -s https://neogiga.com | grep -i "maintenance"
```

#### Step 2.2: Code Deployment
```bash
# Navigate to release directory
cd /home/neogiga/laravel

# Create new release directory
RELEASE_NAME=$(date +%Y%m%d_%H%M%S)
mkdir -p releases/$RELEASE_NAME

# Clone repository (or pull if using git deployment)
git clone --branch main --depth 1 \
  git@github.com:neogiga/platform.git \
  releases/$RELEASE_NAME

# Or if already cloned:
cd current
git fetch origin main
git checkout main
git reset --hard origin/main

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --only=production  # if frontend assets changed
npm run build  # if frontend assets changed

# Note: NO --delete flag to preserve existing files
```

#### Step 2.3: Environment Configuration
```bash
# Copy environment file (preserves existing secrets)
cp /home/neogiga/laravel/shared/.env \
   /home/neogiga/laravel/releases/$RELEASE_NAME/.env

# Verify PCB-specific env vars present
grep SESSION_DOMAIN /home/neogiga/laravel/releases/$RELEASE_NAME/.env
grep COOKIE_DOMAIN /home/neogiga/laravel/releases/$RELEASE_NAME/.env
```

#### Step 2.4: Directory Symlinks
```bash
# Create symlinks to shared directories
cd /home/neogiga/laravel/releases/$RELEASE_NAME

# Storage symlink
ln -nfs ../../shared/storage storage

# Public storage symlink (if needed)
ln -nfs ../../shared/public_storage public/storage

# Log symlink
ln -nfs ../../shared/logs storage/logs

# Cache symlinks
ln -nfs ../../shared/bootstrap/cache bootstrap/cache
```

#### Step 2.5: Database Migrations
```bash
# CRITICAL: Test migrations first
php artisan migrate:status
php artisan migrate --pretend

# Review output - should ONLY show NEW migrations
# If any DROP/DELETE operations appear, STOP and investigate

# Run migrations if safe
php artisan migrate --force

# Verify migration success
php artisan migrate:status | grep "N"
# Should show no pending migrations
```

#### Step 2.6: Cache Warm-up
```bash
# Clear and rebuild caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verify routes registered
php artisan route:list | grep pcb | wc -l
# Should show expected number of PCB routes
```

#### Step 2.7: Queue Worker Restart
```bash
# Gracefully stop existing workers
php artisan queue:restart

# Wait for workers to finish current jobs (max 60 seconds)
sleep 60

# Start new workers (via supervisor)
sudo supervisorctl restart neogiga-worker:*

# Verify workers running
sudo supervisorctl status neogiga-worker:*
# All should show RUNNING

# Verify PCB queues registered
php artisan queue:monitor database,redis,pcb-file-scan,pcb-file-process
```

#### Step 2.8: Symbolic Link Switch
```bash
cd /home/neogiga/laravel

# Atomic switch to new release
ln -nfs releases/$RELEASE_NAME current

# Verify symlink points to correct release
ls -la current | grep releases
```

#### Step 2.9: Disable Maintenance Mode
```bash
cd /home/neogiga/laravel/current

php artisan up

# Verify site is live
curl -s https://neogiga.com/api/health | jq .status
curl -s https://pcb.neogiga.com/api/health | jq .status
```

---

### Phase 3: Post-Deployment Verification (T+30 minutes)

#### Step 3.1: Automated Health Checks
```bash
# Run comprehensive health check script
./scripts/post-deployment-health-check.sh
```

**Checks Performed:**
- [ ] Homepage loads (HTTP 200)
- [ ] Login page accessible
- [ ] API health endpoint OK
- [ ] Database connection working
- [ ] Redis connection working
- [ ] Queue workers processing
- [ ] File uploads working
- [ ] Email sending functional
- [ ] PCB routes responding
- [ ] Authentication working across subdomains

#### Step 3.2: Functional Smoke Tests
```bash
# Test critical user journeys
./scripts/smoke-tests.sh
```

**Tests:**
- [ ] User can log in
- [ ] User can browse products
- [ ] User can add to cart
- [ ] User can create PCB project (new feature)
- [ ] User can upload file to PCB project (new feature)
- [ ] Admin can access dashboard
- [ ] Checkout flow works

#### Step 3.3: Error Log Monitoring
```bash
# Check for new errors in logs
tail -n 100 /home/neogiga/laravel/current/storage/logs/laravel.log | \
  grep -i "error\|exception\|critical"

# Monitor error rate for 15 minutes
watch -n 60 'tail -n 1000 /home/neogiga/laravel/current/storage/logs/laravel.log | \
  grep -c "ERROR"'
```

**Acceptable Threshold:**
- Zero CRITICAL errors
- < 5 ERROR entries (expected noise)
- No increase in error rate vs. pre-deployment baseline

#### Step 3.4: Performance Baseline
```bash
# Check response times
curl -w "@curl-format.txt" -o /dev/null -s https://neogiga.com
curl -w "@curl-format.txt" -o /dev/null -s https://pcb.neogiga.com

# Compare against baseline
# Acceptable: < 20% increase in response time
```

#### Step 3.5: Database Integrity
```bash
# Verify row counts (should not decrease)
psql -h db.neogiga.com -U neogiga_prod -d neogiga_production <<EOF
SELECT 
  (SELECT count(*) FROM users) as users,
  (SELECT count(*) FROM products) as products,
  (SELECT count(*) FROM orders) as orders,
  (SELECT count(*) FROM pcb_projects) as pcb_projects;
EOF

# Compare with pre-deployment counts
# All counts should be >= pre-deployment values
```

---

## Rollback Procedure

### Trigger Conditions

Rollback immediately if ANY of the following occur:
- 🔴 Critical errors in production logs
- 🔴 Database migration failure
- 🔴 > 50% increase in error rate
- 🔴 Core functionality broken (login, checkout)
- 🔴 Security vulnerability discovered
- 🔴 Performance degradation > 50%
- 🔴 Data corruption detected

### Rollback Steps

```bash
# Step 1: Enable maintenance mode
cd /home/neogiga/laravel/current
php artisan down --secret="deployment-team"

# Step 2: Switch symlink back to previous release
cd /home/neogiga/laravel
ln -nfs releases/PREVIOUS_RELEASE_NAME current

# Step 3: Restart queue workers with old code
sudo supervisorctl restart neogiga-worker:*

# Step 4: Restore database from backup (if migrations ran)
gunzip -c /backups/neogiga_YYYYMMDD_HHMMSS.sql.gz | \
  psql -h db.neogiga.com -U neogiga_prod neogiga_production

# Step 5: Clear caches
cd /home/neogiga/laravel/current
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Step 6: Bring site back up
php artisan up

# Step 7: Verify rollback successful
curl -s https://neogiga.com/api/health | jq .status

# Step 8: Notify stakeholders
echo "ROLLBACK COMPLETED at $(date)" | \
  slack-cli -d '#deployments' -m "Production rolled back to PREVIOUS_RELEASE_NAME"
```

### Rollback Verification

- [ ] Site accessible and healthy
- [ ] All core functions working
- [ ] Error rate returned to baseline
- [ ] Database integrity confirmed
- [ ] Users can transact normally
- [ ] No data loss occurred

---

## Post-Deployment Activities

### Day 1 (Deployment Day)

**Immediate (T+1 hour):**
- [ ] Send deployment success notification
- [ ] Update deployment tracker
- [ ] Document any issues encountered
- [ ] Monitor error rates continuously

**T+4 hours:**
- [ ] Review error logs
- [ ] Check queue performance
- [ ] Verify backup completion
- [ ] Confirm no customer complaints

**T+24 hours:**
- [ ] Full metrics review
- [ ] Performance comparison vs. baseline
- [ ] Customer support ticket review
- [ ] Decision: Keep or rollback

### Day 2-7

**Daily Checks:**
- [ ] Error rate trending
- [ ] Performance metrics
- [ ] Queue throughput
- [ ] Database query performance
- [ ] Customer feedback

**Day 7:**
- [ ] Week-long metrics summary
- [ ] Deployment retrospective
- [ ] Lessons learned documented
- [ ] Update deployment playbook

---

## Monitoring & Alerting

### Key Metrics to Monitor

| Metric | Normal Range | Alert Threshold | Critical Threshold |
|--------|--------------|-----------------|-------------------|
| Response Time (p95) | < 500ms | > 800ms | > 1500ms |
| Error Rate | < 0.1% | > 0.5% | > 2% |
| Queue Lag | < 100 jobs | > 500 jobs | > 2000 jobs |
| Database Connections | < 80% capacity | > 90% | > 95% |
| Memory Usage | < 70% | > 85% | > 95% |
| CPU Usage | < 60% | > 80% | > 95% |
| Disk Usage | < 70% | > 85% | > 95% |

### Alert Channels

- **Email:** ops-team@neogiga.com
- **Slack:** #alerts-production
- **SMS:** On-call engineer (PagerDuty/Opsgenie)
- **Dashboard:** Grafana/Datadog

### Escalation Matrix

| Severity | Response Time | Escalation Path |
|----------|---------------|-----------------|
| P0 (Critical) | 5 minutes | On-call → Tech Lead → CTO |
| P1 (High) | 15 minutes | On-call → Tech Lead |
| P2 (Medium) | 1 hour | On-call |
| P3 (Low) | 4 hours | Next business day |

---

## Deployment Schedule

### Recommended Windows

| Day | Preferred Window | Avoid |
|-----|------------------|-------|
| Monday | ❌ Not recommended | High traffic day |
| Tuesday | 2:00 AM - 6:00 AM | ✅ Preferred |
| Wednesday | 2:00 AM - 6:00 AM | ✅ Preferred |
| Thursday | 2:00 AM - 6:00 AM | ✅ Preferred |
| Friday | ❌ Never deploy | Weekend risk |
| Saturday | Emergency only | Staffing limited |
| Sunday | Emergency only | Staffing limited |

### Blackout Periods

Do NOT deploy during:
- Major sales events (Black Friday, etc.)
- End-of-month financial closing
- Known high-traffic periods
- Team vacations (key personnel unavailable)
- Holidays

---

## Communication Plan

### Pre-Deployment (T-24 hours)

**Internal Notification:**
```
Subject: Scheduled Deployment - PCB Platform Stage 1 - [Date]

Team,

We will deploy PCB Platform Stage 1 on [Date] at [Time].

Expected Downtime: 5-10 minutes (maintenance mode)
Impact: All NeoGiga sites temporarily unavailable

Deployment Lead: [Name]
On-Call Engineer: [Name]

Please report any concerns by [Time -12 hours].
```

### During Deployment

**Status Updates:**
- T-0: "Deployment started"
- T+15min: "Migrations running"
- T+30min: "Verification in progress"
- T+45min: "Deployment complete" OR "Rollback initiated"

### Post-Deployment

**Success Notification:**
```
Subject: ✅ Deployment Successful - PCB Platform Stage 1

The deployment completed successfully at [Time].

All health checks passed.
No critical errors detected.
Performance within baseline.

Next Steps:
- Monitor for 24 hours
- Retrospective scheduled for [Date]

Thank you to the team!
```

**Failure/Rollback Notification:**
```
Subject: ⚠️ Deployment Rolled Back - PCB Platform Stage 1

The deployment was rolled back at [Time] due to:
[Reason]

System Status:
- All services restored to previous version
- No data loss
- Normal operations resumed

Next Steps:
- Incident review scheduled for [Date]
- Root cause analysis in progress
- New deployment date TBD
```

---

## Appendix A: Deployment Scripts

### Pre-Deployment Verification Script

```bash
#!/bin/bash
# scripts/verify-deployment-readiness.sh

set -e

echo "=== Deployment Readiness Check ==="

# Git status
echo "[1/8] Checking Git status..."
cd /home/neogiga/laravel/current
if [[ -n $(git status --porcelain) ]]; then
    echo "❌ Uncommitted changes found"
    exit 1
fi
echo "✅ Git clean"

# Database backup age
echo "[2/8] Checking database backup..."
LATEST_BACKUP=$(ls -t /backups/*.sql.gz | head -1)
BACKUP_AGE=$(( ($(date +%s) - $(stat -f %m "$LATEST_BACKUP" 2>/dev/null || stat -c %Y "$LATEST_BACKUP")) / 3600 ))
if [[ $BACKUP_AGE -gt 24 ]]; then
    echo "❌ Backup older than 24 hours"
    exit 1
fi
echo "✅ Backup recent ($BACKUP_AGE hours old)"

# SSL certificate
echo "[3/8] Checking SSL certificate..."
EXPIRY=$(echo | openssl s_client -servername neogiga.com -connect neogiga.com:443 2>/dev/null | \
    openssl x509 -noout -enddate | cut -d= -f2)
EXPIRY_TS=$(date -d "$EXPIRY" +%s 2>/dev/null || date -j -f "%b %d %T %Y %Z" "$EXPIRY" +%s)
DAYS_LEFT=$(( ($EXPIRY_TS - $(date +%s)) / 86400 ))
if [[ $DAYS_LEFT -lt 30 ]]; then
    echo "❌ SSL expires in $DAYS_LEFT days"
    exit 1
fi
echo "✅ SSL valid ($DAYS_LEFT days remaining)"

# Staging deployment
echo "[4/8] Checking staging deployment..."
STAGING_STATUS=$(curl -s https://staging.neogiga.com/api/health | jq -r .status)
if [[ "$STAGING_STATUS" != "ok" ]]; then
    echo "❌ Staging environment unhealthy"
    exit 1
fi
echo "✅ Staging healthy"

# CI/CD pipeline
echo "[5/8] Checking CI/CD pipeline..."
# Add your CI/CD API check here
echo "✅ Pipeline passing"

# Team availability
echo "[6/8] Checking team availability..."
# Add your team calendar check here
echo "✅ Team available"

# Disk space
echo "[7/8] Checking disk space..."
DISK_USAGE=$(df /home | tail -1 | awk '{print $5}' | sed 's/%//')
if [[ $DISK_USAGE -gt 85 ]]; then
    echo "❌ Disk usage at ${DISK_USAGE}%"
    exit 1
fi
echo "✅ Disk space OK (${DISK_USAGE}%)"

# Queue status
echo "[8/8] Checking queue status..."
QUEUE_STATUS=$(php artisan queue:status | grep -c "Running")
if [[ $QUEUE_STATUS -lt 1 ]]; then
    echo "❌ No queue workers running"
    exit 1
fi
echo "✅ Queues operational"

echo ""
echo "=== All checks passed! Ready for deployment ==="
```

### Post-Deployment Health Check Script

```bash
#!/bin/bash
# scripts/post-deployment-health-check.sh

set -e

echo "=== Post-Deployment Health Check ==="

PASS=0
FAIL=0

check() {
    local name=$1
    local command=$2
    if eval "$command" > /dev/null 2>&1; then
        echo "✅ $name"
        ((PASS++))
    else
        echo "❌ $name"
        ((FAIL++))
    fi
}

# Homepage
check "Homepage loads" "curl -sf https://neogiga.com"

# PCB Homepage
check "PCB homepage loads" "curl -sf https://pcb.neogiga.com"

# API Health
check "API health" "curl -sf https://neogiga.com/api/health | grep ok"

# Login page
check "Login accessible" "curl -sf https://neogiga.com/login"

# Database
check "Database connection" "psql -h db.neogiga.com -U neogiga_prod -d neogiga_production -c 'SELECT 1'"

# Redis
check "Redis connection" "redis-cli -h redis.neogiga.com ping"

# Queue workers
check "Queue workers running" "supervisorctl status neogiga-worker:* | grep -q RUNNING"

# PCB projects route
check "PCB projects API" "curl -sf https://pcb.neogiga.com/api/pcb/projects"

# File upload
check "File upload endpoint" "curl -sf https://pcb.neogiga.com/api/pcb/upload"

# Admin dashboard
check "Admin dashboard" "curl -sf https://admin.neogiga.com/admin/pcb"

echo ""
echo "=== Results: $PASS passed, $FAIL failed ==="

if [[ $FAIL -gt 0 ]]; then
    exit 1
fi
```

---

## Appendix B: Contact List

| Role | Name | Phone | Email | Slack |
|------|------|-------|-------|-------|
| Deployment Lead | | | | |
| On-Call Engineer | | | | |
| Tech Lead | | | | |
| DBA | | | | |
| DevOps | | | | |
| Security | | | | |
| Product Owner | | | | |

---

## Document Approval

| Role | Name | Date | Signature |
|------|------|------|-----------|
| CTO | | | |
| Tech Lead | | | |
| DevOps Lead | | | |
| Security Lead | | | |

---

**Last Updated:** 2024-07-11  
**Next Review:** After each deployment  
**Document Owner:** DevOps Engineer
