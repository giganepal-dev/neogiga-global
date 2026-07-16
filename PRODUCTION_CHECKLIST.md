# NeoGiga Production Readiness Checklist

## P0: CRITICAL - Protect Production (DO NOT SKIP)

### 1. Pre-Deployment Safety
- [ ] Database backup completed and verified
  ```bash
  pg_dump -U neogiga_user neogiga_production | gzip > /backups/neogiga-db-$(date +%Y%m%d-%H%M%S).sql.gz
  ```
- [ ] Environment files backed up
  ```bash
  cp /home/neogiga/laravel/current/.env /backups/env-backup-$(date +%Y%m%d-%H%M%S)
  ```
- [ ] All local modifications documented
  ```bash
  cd /home/neogiga/laravel/current && git status --porcelain > /backups/local-mods-$(date +%Y%m%d-%H%M%S).txt
  ```
- [ ] Current release path recorded
  ```bash
  readlink /home/neogiga/laravel/current > /backups/current-release-path.txt
  ```
- [ ] Rollback release created from current state

### 2. Server Security Audit
- [ ] SSH key-only authentication enabled
- [ ] Firewall configured (UFW/iptables)
- [ ] Fail2ban installed and configured
- [ ] SSL certificate valid and auto-renewal configured
- [ ] Database user has minimal required privileges
- [ ] Redis password set (if used)
- [ ] File permissions locked down (755 for dirs, 644 for files)

---

## P1: Infrastructure Synchronization

### 3. Repository & Deployment
- [ ] Production branch matches GitHub main
  ```bash
  cd /home/neogiga/neogiga-global/giga-nepal-backend
  git fetch origin
  git diff HEAD origin/main
  ```
- [ ] All 94 local modifications classified:
  - [ ] Source changes committed to repo
  - [ ] Environment config moved to .env
  - [ ] Cache/build files excluded
  - [ ] Runtime storage files in shared directory
  - [ ] Unnecessary changes discarded
- [ ] Deployment path documented:
  - Active path: `/home/neogiga/laravel/current`
  - Repository path: `/home/neogiga/neogiga-global/giga-nepal-backend`
  - Release directory: `/home/neogiga/laravel/releases`
- [ ] Deploy script tested on staging
- [ ] Rollback procedure documented and tested

### 4. Redis Integration (CRITICAL for 500k products)
- [ ] Redis installed: `apt-get install redis-server`
- [ ] Redis configuration optimized (see setup-redis.sh)
  - maxmemory: 2GB minimum
  - maxmemory-policy: allkeys-lru
  - Persistence: AOF enabled
- [ ] PHP Redis extension installed: `pecl install redis` or `apt-get install php-redis`
- [ ] .env updated:
  ```
  CACHE_DRIVER=redis
  SESSION_DRIVER=redis
  QUEUE_CONNECTION=redis
  REDIS_HOST=127.0.0.1
  REDIS_PORT=6379
  ```
- [ ] Redis connection tested: `redis-cli ping` → PONG
- [ ] Laravel cache test: `php artisan cache:clear`
- [ ] Queue test: `php artisan queue:listen --tries=1`

### 5. Queue Worker Configuration
- [ ] Systemd service installed (neogiga-queue-worker.service)
- [ ] Service enabled and started:
  ```bash
  systemctl daemon-reload
  systemctl enable neogiga-queue-worker
  systemctl start neogiga-queue-worker
  systemctl status neogiga-queue-worker
  ```
- [ ] Failed jobs cleared:
  ```bash
  php artisan queue:flush
  ```
- [ ] Search index jobs retried:
  ```bash
  php artisan queue:retry all
  ```
- [ ] Queue monitoring in place: `php artisan queue:monitor redis`

### 6. Database & Migrations
- [ ] All migrations run: `php artisan migrate --force`
- [ ] Migration rollback tested: `php artisan migrate:rollback --step=1`
- [ ] Database indexes verified for performance
- [ ] Query logging enabled for slow queries (>100ms)
- [ ] Connection pooling configured if needed

### 7. Cache Strategy
- [ ] Config cache: `php artisan config:cache`
- [ ] Route cache: `php artisan route:cache`
- [ ] View cache: `php artisan view:cache`
- [ ] Application cache warmed for critical pages
- [ ] Cache tags implemented for selective clearing

---

## P2: Catalog & Search Stabilization

### 8. Product Data Quality
- [ ] Approved products validated (sample audit of 100 products):
  - [ ] Valid manufacturer
  - [ ] Valid MPN
  - [ ] Unique manufacturer + MPN combination
  - [ ] Clean product title (<200 chars)
  - [ ] Correct category assigned
  - [ ] At least one specification
  - [ ] Datasheet URL present
  - [ ] Primary image exists
  - [ ] Package type defined
  - [ ] Lifecycle status set
  - [ ] RoHS status indicated
  - [ ] MOQ defined
  - [ ] Supplier stock > 0
  - [ ] Regional price calculated
- [ ] Draft products excluded from search engines
  - [ ] robots.txt configured
  - [ ] Meta noindex on draft product pages
  - [ ] Sitemap excludes drafts
- [ ] Image validation:
  - [ ] No broken images (check storage logs)
  - [ ] Fallback image configured
  - [ ] Image CDN configured (if used)
- [ ] Datasheet validation:
  - [ ] PDF links accessible
  - [ ] No 404 errors on datasheets

### 9. Search Index Rebuild
- [ ] Failed jobs investigated and resolved
- [ ] Resume functionality tested for large rebuilds
- [ ] Full search index rebuild completed:
  ```bash
  php artisan scout:import "App\\Models\\Product"
  ```
- [ ] Search results verified (test 20 common searches)
- [ ] Autocomplete search tested
- [ ] Search indexing job scheduled (if applicable)
- [ ] Duplicate index entries checked and removed

### 10. SEO Verification
- [ ] Homepage meta title and description
- [ ] Regional title patterns working (Nepal, Global, etc.)
- [ ] Canonical URLs correct on all pages
- [ ] hreflang tags present for regional variants
- [ ] Product schema.org markup validated (Google Rich Results Test)
- [ ] Offer schema present with correct pricing
- [ ] Breadcrumb schema implemented
- [ ] Manufacturer schema present
- [ ] XML sitemap accessible and valid
- [ ] Robots.txt allows approved products, blocks drafts
- [ ] No 404 errors on brand pages
- [ ] Brand logos uploadable and displaying

---

## P3: Commerce Workflows Completion

### 11. Customer Account & Authentication
- [ ] Customer registration working
- [ ] Email verification sent and working
- [ ] OTP login (if implemented) tested
- [ ] Password reset flow working
- [ ] Login session persistence working
- [ ] Address book CRUD operations working
- [ ] Country-aware address forms working

### 12. Regional Inventory & Pricing
- [ ] Product shows correct stock per marketplace
- [ ] China warehouse stock visible in China marketplace
- [ ] Nepal warehouse stock visible in Nepal marketplace
- [ ] Price calculation verified end-to-end:
  - Base supplier price
  - Currency conversion
  - Import cost
  - Freight
  - Duty/tax
  - Warehouse handling
  - Marketplace margin
  - Distributor margin (if applicable)
  - Final selling price
- [ ] Price consistent across:
  - Product card
  - Product detail page
  - Cart
  - Checkout
  - API responses
- [ ] B2B quantity breaks working
- [ ] Promotional prices applying correctly

### 13. Cart & Checkout
- [ ] Add to cart working
- [ ] Cart persists across sessions
- [ ] Cart quantity updates working
- [ ] Cart item removal working
- [ ] Stock reservation on cart add (or checkout start)
- [ ] Shipping options calculated correctly
- [ ] Tax calculation accurate per region
- [ ] Checkout totals match expectation:
  - Subtotal
  - Shipping
  - Tax
  - Discounts
  - Grand total
- [ ] Guest checkout (if enabled) working
- [ ] Registered user checkout working

### 14. Payment Integration
- [ ] Payment gateway configured (Stripe/PayPal/Local)
- [ ] Test payment successful
- [ ] Payment callback verification working
- [ ] Idempotency checks prevent duplicate charges
- [ ] Failed payment recovery flow working
- [ ] Payment status updates order correctly
- [ ] Invoice generated post-payment
- [ ] Refund processing tested (partial and full)
- [ ] Bank transfer confirmation workflow (if applicable)
- [ ] Credit order workflow (if applicable)

### 15. Order Management
- [ ] Order placed successfully
- [ ] Order confirmation email sent
- [ ] Order visible in customer account
- [ ] Order status workflow working:
  - Pending → Processing → Shipped → Delivered
- [ ] Order cancellation working (before shipment)
- [ ] Return request workflow working
- [ ] Tracking number entry (admin)
- [ ] Delivery confirmation workflow

---

## P4: Marketplace Operations

### 16. Seller Onboarding
- [ ] Seller registration form working
- [ ] KYC document upload working
- [ ] Seller approval workflow (admin)
- [ ] Seller marketplace assignment working
- [ ] Seller dashboard accessible
- [ ] Seller product listing CRUD working
- [ ] Seller inventory management working
- [ ] Seller pricing controls working
- [ ] Seller order notification working
- [ ] Commission calculation accurate
- [ ] Seller statement generation working
- [ ] Payout history visible to seller

### 17. Distributor System
- [ ] Distributor registration working
- [ ] Manufacturer authorization upload
- [ ] Regional distributor assignment
- [ ] Distributor pricing levels working
- [ ] Distributor warehouse assignment
- [ ] Stock synchronization (if automated)
- [ ] Distributor RFQ response workflow
- [ ] Quote comparison tools working
- [ ] Credit limit enforcement
- [ ] Territory restriction enforcement

### 18. RFQ & BOM Workflow
- [ ] RFQ submission form working
- [ ] CSV/XLSX BOM upload working
- [ ] Column mapping interface working
- [ ] MPN normalization working
- [ ] Duplicate line grouping working
- [ ] Manufacturer matching suggestions
- [ ] Alternate part suggestions
- [ ] Lifecycle status displayed
- [ ] RoHS/REACH status displayed
- [ ] Stock by marketplace shown
- [ ] Price breaks visible
- [ ] Distributor bidding (if implemented)
- [ ] Buyer quote comparison
- [ ] Internal approval workflow
- [ ] Quote expiry logic working
- [ ] Quote-to-cart conversion
- [ ] Quote-to-purchase-order conversion
- [ ] Communication history logged

### 19. Settlement & Ledger
- [ ] Seller settlement calculation
- [ ] Distributor settlement calculation
- [ ] Commission ledger entries
- [ ] Refund ledger entries
- [ ] Tax/VAT invoice generation
- [ ] Reconciliation reports available
- [ ] Payout processing workflow

---

## P5: Warehouse & Fulfillment

### 20. Warehouse Operations
- [ ] Warehouse-level stock tracking
- [ ] Storage locations/bins configured
- [ ] Receiving workflow
- [ ] Put-away workflow
- [ ] Pick list generation
- [ ] Packing workflow
- [ ] Shipment label generation
- [ ] Serial/lot tracking (if applicable)
- [ ] Stock transfer between warehouses
- [ ] Damaged inventory workflow
- [ ] Cycle count workflow
- [ ] Inventory adjustment approval

### 21. Shipping & Delivery
- [ ] Cross-border shipping rules configured
- [ ] Consolidated shipping logic
- [ ] Carrier integration (if applicable)
- [ ] Tracking number capture
- [ ] Delivery confirmation workflow
- [ ] Failed delivery handling

---

## P6: Monitoring & Maintenance

### 22. Logging & Monitoring
- [ ] Application logs rotating (daily)
- [ ] Error notifications configured (email/Slack/Sentry)
- [ ] Performance monitoring enabled
- [ ] Uptime monitoring configured
- [ ] Database query logging for slow queries
- [ ] Queue job failure alerts
- [ ] Disk space monitoring
- [ ] Memory usage monitoring

### 23. Backup & Recovery
- [ ] Daily database backups automated
- [ ] Backup retention policy (30 days minimum)
- [ ] Backup restoration tested
- [ ] File backup strategy (uploads, images)
- [ ] Disaster recovery plan documented

### 24. Security Hardening
- [ ] Laravel security headers configured
- [ ] CORS properly configured
- [ ] Rate limiting enabled
- [ ] SQL injection protection verified
- [ ] XSS protection verified
- [ ] CSRF protection enabled
- [ ] Admin access restricted (IP whitelist if possible)
- [ ] Two-factor authentication for admins (recommended)
- [ ] Security audit log enabled

### 25. Performance Optimization
- [ ] OPcache enabled and tuned
- [ ] Database query optimization (no N+1 queries)
- [ ] Eager loading implemented where needed
- [ ] Indexes on frequently queried columns
- [ ] CDN configured for static assets
- [ ] Image optimization (WebP, compression)
- [ ] HTTP/2 enabled
- [ ] Gzip/Brotli compression enabled
- [ ] Browser caching headers configured

---

## Final Verification: End-to-End Transaction Test

### Complete One Full Transaction
- [ ] Search for a product
- [ ] View product detail page
- [ ] Verify datasheet accessible
- [ ] Add to cart
- [ ] Proceed to checkout
- [ ] Enter shipping address
- [ ] Select shipping method
- [ ] Complete payment (test mode)
- [ ] Receive order confirmation email
- [ ] Verify order in customer account
- [ ] Admin receives order notification
- [ ] Inventory deducted correctly
- [ ] Order appears in admin panel
- [ ] Order status can be updated
- [ ] Invoice downloadable
- [ ] (If applicable) Seller notified
- [ ] (If applicable) Commission recorded

---

## Sign-Off

| Area | Responsible | Date | Status |
|------|-------------|------|--------|
| Infrastructure | | | ☐ Complete |
| Catalog & Search | | | ☐ Complete |
| Commerce Flows | | | ☐ Complete |
| Marketplace Ops | | | ☐ Complete |
| Security | | | ☐ Complete |
| Monitoring | | | ☐ Complete |

**Production Go-Live Approval:** ___________________ **Date:** ___________
