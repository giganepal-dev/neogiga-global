# NeoGiga Panel System Implementation - Next Phase Command

**Generated:** 2026-07-09  
**Phase:** Phase 1 Completion (P0-P2 Priority Items)  
**Duration:** 3-4 weeks  
**Priority:** Critical Path to Production MVP  

---

## Executive Summary

This command implements the complete panel system for NeoGiga, covering:
- **Admin Panel** - Global operations, oversight, and configuration
- **Seller Panel** - Vendor product/inventory/order management  
- **Distributor Panel** - Territory management, leads, customer relationships
- **Dashboard Analytics** - Real-time metrics and reporting across all panels

Aligns with `NEXT_PHASE_BACKLOG.md` items #1-9 (P0/P1 priorities).

---

## Pre-Implementation Audit Checklist

### 1. Current State Verification
```bash
cd /workspace/giga-nepal-backend

# Check existing panel controllers
ls -la app/Http/Controllers/Admin/
ls -la app/Http/Controllers/Api/Admin/
ls -la app/Http/Controllers/Api/Seller/
ls -la app/Http/Controllers/Api/Distributor/

# Verify existing models
ls -la app/Models/Marketplace/
ls -la app/Models/Distributor/

# Check existing routes
grep -n "admin\|seller\|distributor" routes/api.php | head -50
grep -n "admin\|seller\|distributor" routes/web.php | head -50

# Review existing dashboard controller
cat app/Http/Controllers/Admin/DashboardController.php
```

### 2. Database Schema Readiness
```bash
# Verify critical tables exist
php artisan migrate:status | grep -E "(vendors|sellers|distributors|orders|inventory|payments)"

# Check for empty-shell migrations mentioned in NEXT_PHASE_BACKLOG.md
ls -la database/migrations/ | grep -E "(ai_|pos_|lms_)"
```

### 3. Reference Material Review
Before implementation, review these documents:
- `/workspace/NEXT_PHASE_BACKLOG.md` - Priority backlog
- `/workspace/13_PHASE_STATUS.md` - Current phase status
- `/workspace/NEOGIGA_DASHBOARD_REFERENCE_MAP.md` - Dashboard patterns
- `/workspace/NEOGIGA_DASHBOARD_ADAPTATION_COMMAND.md` - Dashboard adaptation guide
- `/workspace/NEOGIGA_ERP_DASHBOARD_REFERENCE_MAP.md` - ERP dashboard patterns
- `/workspace/NEOGIGA_SMARTEND_BACKEND_DASHBOARD_REFERENCE.md` - Admin dashboard reference

---

## Implementation Phases

### PHASE 1: Foundation Hardening (Week 1)

#### Task 1.1: Schema Reconciliation (NEXT_PHASE_BACKLOG #1)
**Priority:** P0 Blocker  
**Estimated Effort:** 2-3 days

Fill empty-shell migrations for AI, POS, LMS, product extras, import/export:

```bash
# Create missing migration files based on existing models
php artisan make:migration fill_ai_session_schema --path=database/migrations
php artisan make:migration fill_pos_terminal_schema --path=database/migrations
php artisan make:migration fill_lms_course_schema --path=database/migrations
php artisan make:migration fill_import_export_schema --path=database/migrations
```

**Acceptance Criteria:**
- [ ] All models in `app/Models/` have corresponding migration files
- [ ] No drift between model fillable properties and migration columns
- [ ] `cart_items` AI columns properly defined
- [ ] `products.mpn` field exists and indexed

#### Task 1.2: Merge Orphaned Models (NEXT_PHASE_BACKLOG #2)
**Priority:** P0 Blocker  
**Estimated Effort:** 1-2 days

Merge `/workspace/app/Models/` tree into `giga-nepal-backend/app/Models/`:

```bash
# Review orphaned models
ls -la /workspace/app/Models/

# Merge with proper namespacing
# Models/Ai/ → app/Models/Ai/
# Models/AiCommerce/ → app/Models/CommerceAi/
# Models/Cart/ → app/Models/Marketplace/
# Models/Inventory/ → app/Models/Inventory/
# Models/Lms/ → app/Models/Lms/
# Models/Marketplace/ → app/Models/Marketplace/
# Models/Order/ → app/Models/Marketplace/
# Models/Pos/ → app/Models/POS/
# Models/Product/ → app/Models/Product/
# Models/Seo/ → app/Models/SEO/
# Models/Vendor/ → app/Models/Vendor/
```

**Acceptance Criteria:**
- [ ] All models under unified `app/Models/` namespace
- [ ] Import statements updated throughout codebase
- [ ] Root `/workspace/app/Models/` retired
- [ ] No duplicate model definitions

#### Task 1.3: Blueprint Migration Template (NEXT_PHASE_BACKLOG #3)
**Priority:** P0 Blocker  
**Estimated Effort:** 1 day

Adopt blueprint-compliant migration template:

```php
// Standard migration template per Blueprint §10
Schema::create('table_name', function (Blueprint $table) {
    $table->uuidv7('id')->primary(); // UUIDv7 primary keys
    $table->timestampsTz();
    $table->softDeletesTz();
    $table->uuid('row_version')->unique(); // Optimistic locking
    $table->foreignUuid('created_by')->nullable()->constrained('users');
    $table->foreignUuid('updated_by')->nullable()->constrained('users');
    
    // Audit trigger hook
    $table->index(['created_at', 'deleted_at']);
});

// Add audit trigger after table creation
DB::statement("CREATE TRIGGER audit_table_name AFTER INSERT OR UPDATE OR DELETE ON table_name FOR EACH ROW EXECUTE FUNCTION audit_trigger_func()");
```

**Acceptance Criteria:**
- [ ] All new migrations use UUIDv7 PKs
- [ ] Soft deletes enabled on business entities
- [ ] `row_version` column for optimistic locking
- [ ] `created_by`/`updated_by` foreign keys
- [ ] Audit trigger wired to `audit_logs` table

#### Task 1.4: Laravel Sanctum Auth + RBAC (NEXT_PHASE_BACKLOG #4)
**Priority:** P0 Blocker  
**Estimated Effort:** 2-3 days

Replace `admin.token` middleware with real authentication:

```bash
# Install Sanctum
composer require laravel/sanctum

# Publish config
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Update User model to use HasApiTokens
# Create roles: customer, vendor_admin, regional_ops, catalog_ops, finance, global_admin
# Create policies for each resource
```

**Implementation Files:**
```
app/Models/Role.php (enhance existing)
app/Models/Permission.php (new)
app/Policies/VendorPolicy.php (new)
app/Policies/ProductPolicy.php (new)
app/Policies/OrderPolicy.php (new)
app/Policies/InventoryPolicy.php (new)
app/Http/Middleware/CheckRole.php (new)
app/Http/Middleware/CheckPermission.php (enhance existing)
config/sanctum.php (new)
```

**Acceptance Criteria:**
- [ ] Sanctum token authentication working
- [ ] Role-based access control enforced
- [ ] Policies guard all CRUD operations
- [ ] IDOR vulnerabilities eliminated (SEC-01/03/10)
- [ ] `admin.token` middleware replaced

---

### PHASE 2: Admin Panel Enhancement (Week 2)

#### Task 2.1: Admin Dashboard Analytics
**Priority:** P1  
**Estimated Effort:** 2-3 days

Enhance `DashboardController` with real-time metrics:

```php
// app/Services/DashboardMetricsService.php
class DashboardMetricsService {
    public function getOverview(DateRangeDTO $range): array {
        return [
            'totalRevenue' => $this->calculateRevenue($range),
            'totalOrders' => $this->countOrders($range),
            'averageOrderValue' => $this->calculateAOV($range),
            'newCustomers' => $this->countNewCustomers($range),
            'topProducts' => $this->getTopProducts($range, 10),
            'lowStockAlerts' => $this->getLowStockItems(),
            'pendingVendorApplications' => $this->countPendingVendorApps(),
            'pendingDistributorApplications' => $this->countPendingDistributorApps(),
        ];
    }
    
    public function getSalesChart(DateRangeDTO $range, string $groupBy = 'day'): array {
        // Group sales by day/week/month
    }
    
    public function getInventoryHealth(): array {
        return [
            'totalSKUs' => Product::count(),
            'inStock' => InventoryStock::where('quantity', '>', 0)->count(),
            'outOfStock' => InventoryStock::where('quantity', 0)->count(),
            'lowStock' => $this->getLowStockCount(),
            'overstocked' => $this->getOverstockedCount(),
        ];
    }
}
```

**Dashboard Widgets to Implement:**
- Revenue trend chart (7d/30d/90d)
- Order volume chart
- Top selling products
- Low stock alerts
- Pending applications (vendor/distributor)
- Recent orders table
- Geographic sales distribution (Nepal map)
- Payment method breakdown

**Acceptance Criteria:**
- [ ] Dashboard loads in < 2 seconds
- [ ] All metrics cached with appropriate TTL
- [ ] Date range filters working
- [ ] Export to CSV functionality
- [ ] Real-time updates via polling/WebSocket (optional)

#### Task 2.2: Admin Console Features
**Priority:** P1  
**Estimated Effort:** 2 days

Enhance `AdminConsoleController` with full operational control:

```php
// app/Http/Controllers/Api/Admin/AdminConsoleController.php
class AdminConsoleController extends Controller {
    // Marketplace management
    public function createMarketplace(Request $request)
    public function updateMarketplace($id, Request $request)
    public function toggleMarketplace($id)
    
    // Category management
    public function createCategory(Request $request)
    public function updateCategory($id, Request $request)
    public function reorderCategories(Request $request)
    
    // Vendor approval workflow
    public function approveVendor(SellerApplication $application)
    public function rejectVendor(SellerApplication $application, Request $request)
    public function suspendVendor(Vendor $vendor)
    
    // Distributor approval workflow
    public function approveDistributor(DistributorApplication $application)
    public function assignTerritory(DistributorApplication $application, Request $request)
    
    // Product moderation
    public function approveProduct(Product $product)
    public function rejectProduct(Product $product, Request $request)
    public function featureProduct(Product $product)
    
    // Order management
    public function viewOrder(Order $order)
    public function escalateOrderIssue(Order $order, Request $request)
    public function refundOrder(Order $order, Request $request)
    
    // System configuration
    public function updateTaxRules(Request $request)
    public function updateShippingRates(Request $request)
    public function manageBanners(Request $request)
}
```

**Acceptance Criteria:**
- [ ] Full CRUD for marketplaces, categories, vendors
- [ ] Approval workflows with notifications
- [ ] Product moderation queue
- [ ] Order dispute resolution
- [ ] System configuration UI
- [ ] Audit log for all admin actions

#### Task 2.3: Reporting Module
**Priority:** P1  
**Estimated Effort:** 2 days

Implement comprehensive reporting:

```php
// app/Http/Controllers/Api/Admin/ReportController.php
class ReportController extends Controller {
    // Sales reports
    public function salesReport(Request $request)
    public function productPerformanceReport(Request $request)
    public function categoryPerformanceReport(Request $request)
    public function vendorPerformanceReport(Request $request)
    
    // Customer reports
    public function customerAcquisitionReport(Request $request)
    public function customerRetentionReport(Request $request)
    public function customerLifetimeValueReport(Request $request)
    
    // Inventory reports
    public function stockLevelReport(Request $request)
    public function inventoryMovementReport(Request $request)
    public function lowStockReport(Request $request)
    
    // Financial reports
    public function revenueReport(Request $request)
    public function payoutReport(Request $request)
    public function taxReport(Request $request)
    
    // Export functions
    public function exportToCsv($reportType, Request $request)
    public function exportToPdf($reportType, Request $request)
}
```

**Acceptance Criteria:**
- [ ] 15+ pre-built report templates
- [ ] Custom date range selection
- [ ] Filter by marketplace/region/category/vendor
- [ ] CSV/PDF export functionality
- [ ] Scheduled report generation (cron)
- [ ] Email delivery of reports

---

### PHASE 3: Seller Panel Implementation (Week 3)

#### Task 3.1: Seller Dashboard
**Priority:** P1  
**Estimated Effort:** 2 days

Enhance existing seller dashboard controllers:

```php
// app/Http/Controllers/Api/Seller/SellerDashboardController.php
class SellerDashboardController extends Controller {
    public function overview() {
        return [
            'todayRevenue' => $this->getTodayRevenue(),
            'monthRevenue' => $this->getMonthRevenue(),
            'pendingOrders' => $this->getPendingOrderCount(),
            'lowStockProducts' => $this->getLowStockCount(),
            'recentOrders' => $this->getRecentOrders(10),
            'performanceScore' => $this->calculatePerformanceScore(),
        ];
    }
    
    public function salesSummary(Request $request) {
        // Sales chart data by date range
    }
    
    public function orderSummary(Request $request) {
        // Order status breakdown
    }
    
    public function productSummary() {
        return [
            'totalProducts' => $this->getProductCount(),
            'activeProducts' => $this->getActiveProductCount(),
            'pendingApproval' => $this->getPendingApprovalCount(),
            'rejectedProducts' => $this->getRejectedCount(),
        ];
    }
    
    public function inventorySummary() {
        return [
            'totalSKUs' => $this->getSKUCount(),
            'inStockValue' => $this->getInStockValue(),
            'outOfStockCount' => $this->getOutOfStockCount(),
            'reservedQuantity' => $this->getReservedQuantity(),
        ];
    }
    
    public function payoutSummary() {
        return [
            'pendingPayout' => $this->getPendingPayout(),
            'lastPayout' => $this->getLastPayout(),
            'nextPayoutDate' => $this->getNextPayoutDate(),
            'lifetimeEarnings' => $this->getLifetimeEarnings(),
        ];
    }
}
```

**Acceptance Criteria:**
- [ ] Real-time dashboard metrics
- [ ] Sales trend visualization
- [ ] Order status tracking
- [ ] Inventory health indicators
- [ ] Payout status and history
- [ ] Performance score calculation

#### Task 3.2: Seller Product Management
**Priority:** P1  
**Estimated Effort:** 2 days

Complete `SellerProductController`:

```php
// app/Http/Controllers/Api/Seller/SellerProductController.php
class SellerProductController extends Controller {
    public function index(Request $request) {
        // List products with filters (status, category, etc.)
    }
    
    public function create() {
        // Return form data: categories, brands, spec templates
    }
    
    public function store(Request $request) {
        // Validate and create product with variants, specs, images
    }
    
    public function edit(Product $product) {
        // Return product data for editing
    }
    
    public function update(Product $product, Request $request) {
        // Update product (with approval workflow if needed)
    }
    
    public function uploadImages(Product $product, Request $request) {
        // Handle image upload with validation
    }
    
    public function uploadDatasheet(Product $product, Request $request) {
        // Handle PDF datasheet upload
    }
    
    public function manageVariants(Product $product, Request $request) {
        // CRUD for product variants
    }
    
    public function manageSpecs(Product $product, Request $request) {
        // CRUD for product specifications
    }
    
    public function submitForReview(Product $product) {
        // Submit product for admin approval
    }
}
```

**Acceptance Criteria:**
- [ ] Multi-step product creation wizard
- [ ] Variant management (size, color, etc.)
- [ ] Specification template auto-fill by category
- [ ] Image upload with optimization
- [ ] Datasheet/manual upload
- [ ] Approval status tracking
- [ ] Bulk product import (CSV)

#### Task 3.3: Seller Inventory Management
**Priority:** P1  
**Estimated Effort:** 1-2 days

Enhance `SellerInventoryController`:

```php
// app/Http/Controllers/Api/Seller/SellerInventoryController.php
class SellerInventoryController extends Controller {
    public function index(Request $request) {
        // List inventory with warehouse filters
    }
    
    public function adjust(Request $request) {
        // Stock adjustment with reason tracking
    }
    
    public function bulkAdjust(Request $request) {
        // Bulk stock update via CSV
    }
    
    public function transferStock(Request $request) {
        // Transfer between warehouses
    }
    
    public function getStockMovements(Product $product, Request $request) {
        // History of stock changes
    }
    
    public function setLowStockThreshold(Request $request) {
        // Configure alert thresholds
    }
}
```

**Acceptance Criteria:**
- [ ] Real-time stock levels per warehouse
- [ ] Stock adjustment with audit trail
- [ ] Bulk import/export
- [ ] Inter-warehouse transfers
- [ ] Movement history
- [ ] Low stock alerts

#### Task 3.4: Seller Order Management
**Priority:** P1  
**Estimated Effort:** 1-2 days

Enhance `SellerOrderController`:

```php
// app/Http/Controllers/Api/Seller/SellerOrderController.php
class SellerOrderController extends Controller {
    public function index(Request $request) {
        // List orders with filters (status, date, etc.)
    }
    
    public function show(Order $order) {
        // Order details with items, shipping, payment
    }
    
    public function updateStatus(Order $order, Request $request) {
        // Update order status with validation
    }
    
    public function printInvoice(Order $order) {
        // Generate PDF invoice
    }
    
    public function printPackingSlip(Order $order) {
        // Generate packing slip
    }
    
    public function markAsShipped(Order $order, Request $request) {
        // Update with tracking info
    }
    
    public function acceptReturn(ReturnRequest $return) {
        // Process return request
    }
}
```

**Acceptance Criteria:**
- [ ] Order list with advanced filters
- [ ] Order detail view
- [ ] Status update workflow
- [ ] Invoice/packing slip generation
- [ ] Shipping label integration (optional)
- [ ] Return request handling

---

### PHASE 4: Distributor Panel Implementation (Week 4)

#### Task 4.1: Distributor Dashboard
**Priority:** P1  
**Estimated Effort:** 2 days

Enhance `DistributorDashboardController`:

```php
// app/Http/Controllers/Api/Distributor/DistributorDashboardController.php
class DistributorDashboardController extends Controller {
    public function overview() {
        return [
            'territoryCoverage' => $this->getTerritoryStats(),
            'totalLeads' => $this->getLeadCount(),
            'convertedLeads' => $this->getConvertedLeads(),
            'activeCustomers' => $this->getCustomerCount(),
            'monthRevenue' => $this->getMonthRevenue(),
            'commissionEarned' => $this->getCommissionEarned(),
            'targetProgress' => $this->getTargetProgress(),
        ];
    }
    
    public function territoryStock() {
        // Products available in distributor's territory
    }
    
    public function leadsSummary() {
        // Lead pipeline breakdown
    }
    
    public function customerSummary() {
        // Customer acquisition and retention metrics
    }
}
```

**Acceptance Criteria:**
- [ ] Territory overview metrics
- [ ] Lead pipeline visualization
- [ ] Customer summary
- [ ] Commission tracking
- [ ] Target vs actual progress

#### Task 4.2: Distributor Lead Management
**Priority:** P1  
**Estimated Effort:** 2 days

Implement lead CRM functionality:

```php
// app/Http/Controllers/Api/Distributor/DistributorLeadController.php
class DistributorLeadController extends Controller {
    public function index(Request $request) {
        // List leads with filters (status, source, etc.)
    }
    
    public function create() {
        // Return lead creation form
    }
    
    public function store(Request $request) {
        // Create new lead
    }
    
    public function show(DistributorLead $lead) {
        // Lead details with interaction history
    }
    
    public function update(DistributorLead $lead, Request $request) {
        // Update lead info
    }
    
    public function updateStatus(DistributorLead $lead, Request $request) {
        // Move through pipeline (new → contacted → qualified → converted)
    }
    
    public function addInteraction(DistributorLead $lead, Request $request) {
        // Log call/email/meeting
    }
    
    public function convertToCustomer(DistributorLead $lead) {
        // Convert lead to customer
    }
}
```

**Acceptance Criteria:**
- [ ] Lead capture forms
- [ ] Pipeline stage management
- [ ] Interaction history logging
- [ ] Follow-up reminders
- [ ] Lead conversion workflow
- [ ] Lead assignment (if team-based)

#### Task 4.3: Distributor Customer Management
**Priority:** P1  
**Estimated Effort:** 1-2 days

```php
// app/Http/Controllers/Api/Distributor/DistributorCustomerController.php
class DistributorCustomerController extends Controller {
    public function index(Request $request) {
        // List customers in territory
    }
    
    public function show(DistributorCustomer $customer) {
        // Customer profile with order history
    }
    
    public function addNote(DistributorCustomer $customer, Request $request) {
        // Add relationship notes
    }
    
    public function viewOrderHistory(DistributorCustomer $customer) {
        // Customer's order history
    }
    
    public function getRecommendations(DistributorCustomer $customer) {
        // AI-powered product recommendations
    }
}
```

**Acceptance Criteria:**
- [ ] Customer directory
- [ ] Customer profiles
- [ ] Order history visibility
- [ ] Relationship notes
- [ ] Product recommendations

#### Task 4.4: Distributor Territory Management
**Priority:** P1  
**Estimated Effort:** 1 day

```php
// app/Http/Controllers/Api/Distributor/DistributorTerritoryController.php
class DistributorTerritoryController extends Controller {
    public function getTerritories() {
        // Assigned territories
    }
    
    public function getTerritoryProducts(Territory $territory) {
        // Products allocated to territory
    }
    
    public function getTerritoryVendors(Territory $territory) {
        // Vendors operating in territory
    }
    
    public function checkStockAvailability(Request $request) {
        // Check product availability in territory
    }
}
```

**Acceptance Criteria:**
- [ ] Territory boundary visualization
- [ ] Product allocation view
- [ ] Vendor directory by territory
- [ ] Stock availability checker

---

### PHASE 5: Cart/Checkout/Orders (Parallel Track)

#### Task 5.1: Cart Service (NEXT_PHASE_BACKLOG #5)
**Priority:** P1  
**Estimated Effort:** 2-3 days

```php
// app/Services/CartService.php
class CartService {
    public function addItem(Cart $cart, Product $product, int $quantity): CartItem {
        // Validate stock availability
        // Calculate server-side price (no client price trust)
        // Apply bulk pricing tiers
        // Handle variant selection
    }
    
    public function updateItem(CartItem $item, int $quantity): CartItem {
        // Validate quantity against stock
        // Recalculate totals
    }
    
    public function removeItem(CartItem $item): void {
        // Remove from cart
    }
    
    public function applyCoupon(Cart $cart, string $code): void {
        // Validate coupon
        // Apply discount
    }
    
    public function calculateTotals(Cart $cart): array {
        return [
            'subtotal' => $this->getSubtotal($cart),
            'discount' => $this->getDiscount($cart),
            'tax' => $this->calculateTax($cart), // NP VAT 13%
            'shipping' => $this->calculateShipping($cart),
            'total' => $this->getTotal($cart),
        ];
    }
}
```

**Acceptance Criteria:**
- [ ] Server-side price calculation only
- [ ] Stock validation before add
- [ ] Bulk pricing tier support
- [ ] Coupon/discount application
- [ ] NP VAT 13% tax rule
- [ ] Cart persistence (DB-backed)

#### Task 5.2: Checkout Flow (NEXT_PHASE_BACKLOG #5)
**Priority:** P1  
**Estimated Effort:** 3-4 days

```php
// app/Services/CheckoutService.php
class CheckoutService {
    public function initiateCheckout(Cart $cart, User $user): Order {
        // Validate cart items still available
        // Create order with status 'pending_payment'
        // Soft-reserve inventory (15 min TTL)
        // Calculate final totals
    }
    
    public function processPayment(Order $order, PaymentMethod $method, array $details): Payment {
        // Route to payment adapter (eSewa/Khalti/FonePay/COD)
        // Handle payment state machine
        // Update order status on success
    }
    
    public function confirmOrder(Order $order): void {
        // Convert soft-reserve to permanent
        // Send confirmation emails
        // Trigger fulfillment workflow
    }
    
    public function cancelOrder(Order $order, string $reason): void {
        // Release inventory reservation
        // Process refund if needed
        // Update order status
    }
}
```

**Acceptance Criteria:**
- [ ] Multi-step checkout flow
- [ ] Address validation (Nepal provinces/districts)
- [ ] Shipping method selection
- [ ] Payment gateway integration (eSewa/Khalti/FonePay)
- [ ] COD option
- [ ] Order confirmation emails
- [ ] Invoice generation

#### Task 5.3: Inventory Soft-Reserve (NEXT_PHASE_BACKLOG #6)
**Priority:** P1  
**Estimated Effort:** 1-2 days

```php
// app/Jobs/ReleaseExpiredReservations.php
class ReleaseExpiredReservations implements ShouldQueue {
    public function handle() {
        // Find reservations older than 15 minutes
        // Release stock back to available
        // Notify user if cart expired
    }
}

// Schedule in app/Console/Kernel.php
protected function schedule(Schedule $schedule) {
    $schedule->job(new ReleaseExpiredReservations)->everyMinute();
}
```

**Acceptance Criteria:**
- [ ] 15-minute reservation TTL
- [ ] Automated release job running every minute
- [ ] Oversell prevention via conditional update
- [ ] User notification on expiration

#### Task 5.4: Payment Adapter Interface (NEXT_PHASE_BACKLOG #7)
**Priority:** P1  
**Estimated Effort:** 2-3 days

```php
// app/Services/Payments/PaymentAdapterInterface.php
interface PaymentAdapterInterface {
    public function initiate(array $data): PaymentResponse;
    public function verify(string $transactionId): PaymentResponse;
    public function refund(string $transactionId, float $amount): PaymentResponse;
    public function getStatus(string $transactionId): string;
}

// app/Services/Payments/ESewaAdapter.php
class ESewaAdapter implements PaymentAdapterInterface {
    // eSewa sandbox integration
}

// app/Services/Payments/KhaltiAdapter.php
class KhaltiAdapter implements PaymentAdapterInterface {
    // Khalti integration
}

// app/Services/Payments/FonePayAdapter.php
class FonePayAdapter implements PaymentAdapterInterface {
    // FonePay integration
}

// app/Services/Payments/CodAdapter.php
class CodAdapter implements PaymentAdapterInterface {
    // Cash on Delivery (no API calls)
}
```

**Acceptance Criteria:**
- [ ] Adapter pattern for payment gateways
- [ ] eSewa sandbox integration
- [ ] Khalti integration
- [ ] FonePay integration
- [ ] COD handling
- [ ] Payment state machine (`initiated → authorized → captured → refunded`)
- [ ] Event-driven state transitions

---

## Testing Strategy

### Unit Tests
```bash
# Run existing tests
php artisan test

# Create new tests for panel features
php artisan make:test AdminDashboardTest
php artisan make:test SellerPanelTest
php artisan make:test DistributorPanelTest
php artisan make:test CheckoutFlowTest
php artisan make:test PaymentAdapterTest
```

### Feature Tests
- Admin authentication and authorization
- Seller product CRUD operations
- Distributor lead management
- Cart add/update/remove
- Checkout flow end-to-end
- Payment gateway mock responses
- Inventory reservation/release

### Integration Tests
- API endpoint contract tests
- Database transaction tests
- Queue job tests
- Email notification tests

---

## Security Considerations

### Authentication & Authorization
- [ ] Sanctum token authentication for all API routes
- [ ] Role-based access control (RBAC)
- [ ] Policy enforcement on every resource
- [ ] IDOR prevention (verify resource ownership)
- [ ] Rate limiting per user role

### Data Validation
- [ ] Input validation on all endpoints
- [ ] File upload validation (type, size, AV scan)
- [ ] SQL injection prevention (use Eloquent)
- [ ] XSS prevention (sanitize outputs)

### Audit Logging
- [ ] Log all admin actions
- [ ] Log all state-changing operations
- [ ] Log failed authentication attempts
- [ ] Retain logs per compliance requirements

---

## Deployment Checklist

### Pre-Deployment
- [ ] Run all migrations in staging
- [ ] Seed test data
- [ ] Execute full test suite
- [ ] Performance benchmark critical paths
- [ ] Security penetration testing
- [ ] Backup production database

### Deployment
- [ ] Deploy code to staging
- [ ] Run migrations
- [ ] Clear caches
- [ ] Verify health endpoints
- [ ] Smoke test critical flows
- [ ] Deploy to production (blue-green or rolling)
- [ ] Monitor error rates

### Post-Deployment
- [ ] Verify all panels accessible
- [ ] Test authentication flows
- [ ] Monitor queue workers
- [ ] Check scheduled jobs running
- [ ] Review error logs
- [ ] Gather user feedback

---

## Documentation Updates

### API Documentation
- [ ] Update OpenAPI 3.1 spec (`/api/v1`)
- [ ] Document all new endpoints
- [ ] Provide example requests/responses
- [ ] Document error codes

### User Guides
- [ ] Admin panel user guide
- [ ] Seller onboarding guide
- [ ] Distributor handbook
- [ ] Video tutorials (optional)

### Technical Docs
- [ ] Architecture decision records (ADRs)
- [ ] Database schema documentation
- [ ] Deployment runbook
- [ ] Troubleshooting guide

---

## Success Metrics

### Functional Completeness
- [ ] All P0/P1 items from NEXT_PHASE_BACKLOG.md completed
- [ ] Admin panel fully operational
- [ ] Seller panel supports complete product lifecycle
- [ ] Distributor panel manages leads/customers
- [ ] Cart/checkout/orders working end-to-end

### Performance
- [ ] Dashboard loads in < 2 seconds
- [ ] API response time < 200ms (p95)
- [ ] Checkout completes in < 5 seconds
- [ ] No N+1 query issues

### Quality
- [ ] Test coverage > 70% for new code
- [ ] Zero critical security vulnerabilities
- [ ] Zero P0 bugs in production
- [ ] Code review approval from team

---

## Rollback Plan

If deployment fails:
1. Revert to previous Git tag
2. Rollback database migrations
3. Restore database from backup
4. Clear all caches
5. Restart queue workers
6. Verify health endpoints
7. Communicate status to stakeholders

---

## Next Steps After This Phase

Once this phase completes:
1. **Phase 2 Prep**: AI orchestrator, RAG/vector DB, LMS course delivery
2. **CI/CD**: GitHub Actions pipeline
3. **Observability**: Structured logging, metrics, alerting
4. **SEO**: SSR pages, JSON-LD, sitemaps
5. **Hardening**: Penetration testing, load testing

---

*Command generated for NeoGiga Phase 1 Panel Implementation*  
*Last Updated: 2026-07-09*  
*Priority: CRITICAL PATH TO PRODUCTION*
