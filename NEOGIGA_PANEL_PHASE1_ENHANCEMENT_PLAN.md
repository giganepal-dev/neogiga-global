# NeoGiga Panel System - Phase 1 Enhancement Plan

**Date:** 2026-07-09  
**Status:** Foundation Complete → Enhancement Phase  
**Priority:** P0/P1 items from NEXT_PHASE_BACKLOG.md  

---

## Current State Assessment

### ✅ Already Implemented

#### Admin Panel
- `Admin/DashboardController.php` - Full dashboard with stats, categories, marketplaces, products, vendors, users
- `Admin/CommerceOpsController.php` - Commerce operations management
- `Admin/MarketingActionController.php` - Marketing campaign management
- `Api/Admin/*` - 15+ admin API controllers (Affiliate, B2B, BOM, Distributor, Finance, Inventory, LMS, Onboarding, Payment, Procurement, Product, Promotion, Quotation, Vendor)

#### Seller Panel
- `Api/Seller/SellerDashboardController.php` - Dashboard overview, sales, orders, products, inventory, payouts, alerts
- `Api/Seller/SellerProductController.php` - Product CRUD with variants, attributes, specs, warranty, documents
- `Api/Seller/SellerInventoryController.php` - Inventory management with adjustments
- `Api/Seller/SellerOrderController.php` - Order viewing and status updates
- `Api/Seller/SellerProfileController.php` - Profile and marketplace approval applications
- `Api/Seller/SellerPayoutController.php` - Payout history
- `Api/Seller/SellerSupportTicketController.php` - Support ticket management
- `Services/Seller/SellerDashboardService.php` - Comprehensive dashboard data aggregation
- `Services/Seller/SellerContextService.php` - Vendor context resolution
- `Services/Seller/SellerProductDetailService.php` - Product detail handling

#### Distributor Panel
- `Api/Distributor/DistributorDashboardController.php` - Basic dashboard overview
- `Api/Distributor/DistributorResourceController.php` - Leads, customers, orders, commissions, payouts, downlines
- `Api/Distributor/DistributorApplicationController.php` - Application management
- `Services/Distributor/*` - 11 service classes (Activity, Approval, Commission, Context, Dashboard, Hierarchy, Lead, Order, Payout, Registration, Territory, TerritoryStock)

#### Routes & Authentication
- Token-based auth with `api.token` middleware
- Role-based permissions: `permission:seller.access`, `permission:distributor.access`, `admin.token`
- Structured route groups for `/seller`, `/distributor`, `/admin`
- Application workflows for seller/distributor onboarding

#### Database Schema
- Complete migration files in organized subdirectories
- LMS tables: `2026_07_06_190200_complete_lms_learning_platform_tables.php`
- Inventory/POS tables: `2026_07_06_192000_complete_inventory_pos_tables.php`
- Distributor tables in `database/migrations/distributor/`
- Marketplace tables in `database/migrations/marketplace/`

---

## Phase 1 Enhancements (Week 1-2)

### Task 1.1: Admin Dashboard Analytics Enhancement
**Priority:** P1  
**Effort:** 2-3 days  

**Current Gap:** Admin dashboard shows basic counts but lacks real-time analytics, trend charts, and actionable insights.

**Implementation:**

```bash
cd /workspace/giga-nepal-backend
php artisan make:service DashboardAnalyticsService
php artisan make:controller Api/Admin/AnalyticsController --api
```

**Enhanced Dashboard Metrics:**
1. Revenue trends (7d/30d/90d with comparison)
2. Order volume charts with fulfillment rates
3. Geographic sales distribution (Nepal districts/map)
4. Top performing products/vendors/categories
5. Low stock alerts with auto-reorder suggestions
6. Pending application queue (seller/distributor)
7. Payment method breakdown
8. Customer acquisition trends
9. Average order value tracking
10. Real-time order status feed

**Acceptance Criteria:**
- [ ] Analytics endpoint returns structured data for chart rendering
- [ ] Date range filtering (custom + presets: today, week, month, quarter, year)
- [ ] Export to CSV/PDF functionality
- [ ] Cache layer with 5-minute TTL for heavy queries
- [ ] Unit tests for all metric calculations

---

### Task 1.2: Seller Panel Product Management Wizard
**Priority:** P1  
**Effort:** 3-4 days  

**Current Gap:** Product controller exists but lacks guided multi-step workflow for complex product creation.

**Implementation Steps:**

1. **Create Product Wizard Service:**
```bash
php artisan make:service Seller/ProductWizardService
php artisan make:request Seller/StoreProductRequest
php artisan make:request Seller/UpdateProductRequest
```

2. **Multi-Step Workflow:**
   - Step 1: Basic Info (name, category, brand, SKU, MPN)
   - Step 2: Pricing & Tax (cost, price, VAT rules, bulk pricing)
   - Step 3: Inventory (warehouses, stock levels, reorder points)
   - Step 4: Media (images, videos, 360° views)
   - Step 5: Specifications (attribute key-values, technical specs)
   - Step 6: Variants (color, size, model combinations)
   - Step 7: SEO (meta tags, slug, descriptions)
   - Step 8: Review & Submit (validation summary, submission for approval)

3. **Enhanced Features:**
   - Draft auto-save every 30 seconds
   - Bulk import via CSV/XLSX
   - Duplicate product function
   - AI-powered description generation
   - Competitor price comparison (if marketplace data available)
   - Compliance checklist (CE, RoHS, FCC for electronics)

**Acceptance Criteria:**
- [ ] All 8 steps validated with progressive disclosure
- [ ] Draft persistence between sessions
- [ ] Bulk upload supports 1000+ products with progress tracking
- [ ] Image optimization (WebP conversion, max 2MB per image)
- [ ] Variant matrix supports 3 dimensions × 10 values each
- [ ] Submission triggers admin notification workflow

---

### Task 1.3: Distributor Territory & Lead Management CRM
**Priority:** P1  
**Effort:** 3-4 days  

**Current Gap:** Basic lead/customer tables exist but lack CRM workflow, territory visualization, and conversion tracking.

**Implementation:**

```bash
php artisan make:service Distributor/LeadPipelineService
php artisan make:service Distributor/TerritoryVisualizationService
php artisan make:controller Api/Distributor/DistributorLeadController --api
php artisan make:controller Api/Distributor/DistributorCustomerController --api
```

**CRM Features:**
1. **Lead Pipeline Stages:**
   - New → Contacted → Qualified → Proposal Sent → Negotiation → Won/Lost
   - Drag-and-drop Kanban board
   - Stage conversion rate analytics

2. **Territory Management:**
   - Interactive Nepal map with district/municipality boundaries
   - Heat map of leads/customers/orders by region
   - Territory assignment and transfer workflows
   - Exclusive vs. non-exclusive territory flags

3. **Customer Relationship Tracking:**
   - Contact history timeline (calls, emails, meetings, quotes)
   - Customer segmentation (A/B/C tiers by revenue potential)
   - Automated follow-up reminders
   - Quote-to-order conversion tracking

4. **Commission Calculator:**
   - Real-time commission estimation based on pipeline value
   - Tiered commission structure support
   - Downline commission rollup (if multi-level)

**Acceptance Criteria:**
- [ ] Lead stages configurable per distributor
- [ ] Map integration with Leaflet.js or Google Maps
- [ ] Activity logging for all customer interactions
- [ ] Email/SMS templates for common communications
- [ ] Commission projections accurate to ±5%

---

### Task 1.4: Inventory Reservation & Cart Hardening
**Priority:** P0 Blocker  
**Effort:** 2-3 days  

**Current Gap:** Cart exists but needs server-side session persistence, 15-minute soft reservation, and NP VAT 13% calculation.

**Implementation:**

```bash
php artisan make:migration add_reserved_until_to_inventory_stocks
php artisan make:service Cart/InventoryReservationService
php artisan make:job ReleaseUnpaidInventoryReservation
```

**Key Features:**
1. **15-Minute Soft Reserve:**
   - When item added to cart, decrement `quantity_available`, increment `quantity_reserved`
   - Set `reserved_until` timestamp (now + 15 minutes)
   - Scheduled job runs every minute to release expired reservations

2. **Server-Side Cart:**
   - Cart stored in database (not localStorage)
   - Linked to user session or guest cookie token
   - Merge guest cart into user cart on login

3. **NP VAT 13% Calculation:**
   - Configurable tax rules per product category
   - Line-item tax breakdown (subtotal, discount, taxable amount, VAT 13%, total)
   - Tax invoice generation compliant with Nepal IRD requirements

4. **Concurrency Handling:**
   - Optimistic locking on inventory updates
   - Queue-based reservation processing
   - Race condition prevention for flash sales

**Acceptance Criteria:**
- [ ] Reservation expires exactly at 15:00 minutes
- [ ] Expired reservations released automatically via scheduler
- [ ] Cart persists across devices for logged-in users
- [ ] Tax calculation matches Nepal IRD guidelines
- [ ] Load test passes 100 concurrent checkouts without overselling

---

### Task 1.5: Payment Gateway Integration Framework
**Priority:** P0 Blocker  
**Effort:** 3-4 days  

**Current Gap:** Payment abstraction tables exist but no gateway adapters implemented.

**Implementation:**

```bash
php artisan make:interface PaymentGatewayInterface
php artisan make:class Services/Payment/Gateways/EsewaGateway
php artisan make:class Services/Payment/Gateways/KhaltiGateway
php artisan make:class Services/Payment/Gateways/StripeGateway
php artisan make:class Services/Payment/PaymentProcessorService
```

**Supported Gateways (Phase 1):**
1. **eSewa** (Nepal - most popular)
   - Sandbox + Production modes
   - Webhook signature verification
   - Refund support

2. **Khalti** (Nepal - growing adoption)
   - Mobile wallet integration
   - QR payment support
   - Webhook handling

3. **Stripe** (International cards)
   - Credit/debit card processing
   - 3D Secure 2.0 compliance
   - Subscription support (for future)

4. **Cash on Delivery** (Fallback)
   - COD fee configuration
   - Order amount limits for COD eligibility

**Payment Flow:**
1. Checkout creates pending order with 15-minute payment window
2. User selects payment method → redirected to gateway or shown QR/embedded form
3. Gateway callback → webhook verifies signature → updates order status
4. Success → trigger inventory commitment, send confirmation email
5. Failure → release inventory reservation, allow retry

**Acceptance Criteria:**
- [ ] All 4 payment methods functional in sandbox/test mode
- [ ] Webhook endpoints secured with HMAC signature verification
- [ ] Idempotency handling for duplicate webhook calls
- [ ] Payment audit log captures all transactions
- [ ] Refund workflow tested end-to-end

---

## Phase 2 Enhancements (Week 3-4)

### Task 2.1: Admin Console Reporting Module
**Priority:** P2  
**Effort:** 3-4 days  

**Reports to Implement:**
1. Sales Report (by date, vendor, category, region)
2. Inventory Valuation Report
3. Vendor Performance Scorecard
4. Distributor Commission Statement
5. Tax Liability Report (VAT 13% breakdown)
6. Customer Lifetime Value Analysis
7. Product Return Rate Analysis
8. Payment Reconciliation Report
9. Low Stock Forecast Report
10. Order Fulfillment SLA Report

**Features:**
- Filter by date range, marketplace, vendor, category
- Export to CSV, XLSX, PDF
- Schedule automated email delivery (daily/weekly/monthly)
- Custom report builder (drag-and-drop fields)

---

### Task 2.2: Seller Analytics Dashboard
**Priority:** P2  
**Effort:** 2-3 days  

**Enhancements to Existing Seller Dashboard:**
- Revenue trend chart with YoY comparison
- Product performance matrix (views → carts → orders conversion)
- Customer review sentiment analysis
- Inventory turnover ratio
- Recommended actions (restock alerts, price optimization suggestions)
- Benchmarking against category averages (anonymized aggregate data)

---

### Task 2.3: Distributor Mobile-Optimized Views
**Priority:** P2  
**Effort:** 2-3 days  

**Mobile-First Enhancements:**
- Responsive design for field sales reps
- Offline mode for lead capture (sync when online)
- GPS check-in for customer visits
- QR code scanner for product lookup
- Voice-to-text for activity logging
- Push notifications for lead status changes

---

## Testing Strategy

### Unit Tests
```bash
php artisan make:test DashboardAnalyticsServiceTest
php artisan make:test InventoryReservationServiceTest
php artisan make:test PaymentProcessorServiceTest
php artisan make:test ProductWizardServiceTest
```

### Feature Tests
- Seller product creation workflow
- Distributor lead conversion flow
- Admin approval/rejection workflows
- Cart checkout with all payment methods
- Inventory reservation expiration

### Load Testing
- 100 concurrent checkouts
- 500 concurrent product searches
- 1000 concurrent dashboard loads
- Inventory race condition scenarios

---

## Security Considerations

1. **RBAC Enforcement:**
   - All panel endpoints require authentication
   - Permission checks on every action
   - Sellers cannot access other sellers' data
   - Distributors limited to assigned territories

2. **Data Validation:**
   - Request validation classes for all inputs
   - SQL injection prevention via Eloquent
   - XSS protection on user-generated content
   - File upload restrictions (type, size, virus scan)

3. **Audit Logging:**
   - Log all admin/seller/distributor actions
   - Capture IP, user agent, timestamp
   - Retain logs for 7 years (compliance)

4. **Sensitive Data:**
   - Encrypt payment tokens
   - Hash API keys
   - Mask customer PII in logs

---

## Deployment Checklist

- [ ] Run migrations: `php artisan migrate`
- [ ] Seed initial data: `php artisan db:seed`
- [ ] Configure scheduled jobs: `php artisan schedule:work`
- [ ] Start queue workers: `php artisan queue:work --daemon`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Test payment gateways in sandbox mode
- [ ] Verify SSL certificates
- [ ] Enable monitoring (Sentry/LogRocket)
- [ ] Set up error alerting (email/Slack)

---

## Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Admin dashboard load time | < 2s | Chrome DevTools |
| Seller product creation time | < 5 min | User testing |
| Cart checkout success rate | > 95% | Analytics |
| Payment failure rate | < 3% | Payment logs |
| Inventory oversell incidents | 0 | Audit logs |
| Panel uptime | 99.9% | Monitoring |
| User satisfaction (NPS) | > 50 | Surveys |

---

## Next Actions

1. **Immediate (This Week):**
   - [ ] Review and approve this enhancement plan
   - [ ] Prioritize P0 items (inventory reservation, payment gateway)
   - [ ] Assign developers to tasks
   - [ ] Set up project board (GitHub Projects/Jira)

2. **Week 1:**
   - [ ] Implement Task 1.4 (Inventory Reservation)
   - [ ] Implement Task 1.5 (Payment Gateway)
   - [ ] Begin Task 1.1 (Admin Analytics)

3. **Week 2:**
   - [ ] Complete Task 1.1 (Admin Analytics)
   - [ ] Implement Task 1.2 (Seller Product Wizard)
   - [ ] Begin Task 1.3 (Distributor CRM)

4. **Week 3-4:**
   - [ ] Complete Task 1.3 (Distributor CRM)
   - [ ] Implement Phase 2 reporting features
   - [ ] End-to-end testing
   - [ ] UAT with sample users
   - [ ] Production deployment

---

**Approval Required:** Please confirm priority order and resource allocation to proceed with implementation.
