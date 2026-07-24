# NeoGiga Queue Worker Fix

## Problem

The production server has **470 pending jobs** stuck in the `jobs` table since July 6, 2026. The root cause:

- **3 queue workers exist** for `transactional`, `webhooks`, `marketing`, `campaign-preparation`, `imports`, and `catalog-imports` queues
- **NO worker exists** for the `default` queue
- All scheduled jobs (abandoned carts, trending products, etc.) dispatch to the `default` queue
- Jobs accumulate indefinitely with no worker to process them

## Solution

### 1. Install the Default Queue Worker

```bash
# On the production server (precious)
cd /home/neogiga/laravel/current

# Copy the new service file
sudo cp deploy/systemd/neogiga-queue-default.service /etc/systemd/system/

# Reload systemd
sudo systemctl daemon-reload

# Enable and start the worker
sudo systemctl enable neogiga-queue-default
sudo systemctl start neogiga-queue-default

# Verify it's running
sudo systemctl status neogiga-queue-default
```

### 2. Process Stuck Jobs

```bash
# Check queue health
php artisan neogiga:queue-health

# Process pending jobs (dry run first)
php artisan neogiga:queue-process-pending --dry-run

# Process up to 100 jobs
php artisan neogiga:queue-process-pending --limit=100

# Process all pending jobs
php artisan neogiga:queue-process-pending --limit=1000
```

### 3. Setup All Workers (One-Time)

```bash
# Run the setup script
sudo bash deploy/setup-queue-workers.sh
```

## Queue Architecture

| Queue | Worker Service | Purpose |
|-------|---------------|---------|
| `default` | `neogiga-queue-default` | Scheduled jobs, core operations |
| `transactional,webhooks` | `neogiga-queue-transactional` | Email, webhooks |
| `campaign-preparation,marketing` | `neogiga-queue-marketing` | Marketing automation |
| `imports,catalog-imports` | `neogiga-queue-imports` | Product imports |

## Scheduled Jobs

| Job | Queue | Frequency |
|-----|-------|-----------|
| `DetectAbandonedCartsJob` | default | Every 15 minutes |
| `CalculateTrendingProductsJob` | default | Hourly |
| `CalculateTrendingCategoriesJob` | default | Hourly |
| `CalculateTopSearchTermsJob` | default | Hourly |
| `RefreshCustomerSegmentJob` | default | Daily |
| `GenerateRegionalSalesReportJob` | default | Daily |
| `PrepareScheduledEmailCampaignsJob` | campaign-preparation | Every minute |

## Monitoring Commands

```bash
# Health check
php artisan neogiga:queue-health

# View queue logs
tail -f storage/logs/default-queue-worker.log

# Check systemd status
systemctl status neogiga-queue-*

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## Files Modified

- `deploy/systemd/neogiga-queue-default.service` - New service file
- `deploy/setup-queue-workers.sh` - Setup script
- `app/Console/Commands/QueueHealthCheck.php` - Health check command
- `app/Console/Commands/QueueProcessPending.php` - Manual processor
- `QUEUE_WORKER_FIX.md` - This documentation
