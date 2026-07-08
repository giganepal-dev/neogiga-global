# NeoGiga Sprint 1: Seller/Vendor Onboarding Implementation Command

**Generated:** 2026-07-08  
**Sprint Duration:** 2 weeks  
**Priority:** P1 (Highest after P0 fixes)

---

## A. Audit Findings Reference

This command addresses findings from:
- `NEOGIGA_QWEN_CLAIMS_VS_CODE_VERIFICATION.md` - Seller panel partial, distributor missing
- `NEOGIGA_QWEN_ROUTE_API_AUDIT.md` - Routes exist but incomplete
- `NEOGIGA_QWEN_DATABASE_AUDIT.md` - Vendor tables exist, distributor tables missing
- `NEOGIGA_QWEN_MASTER_AUDIT_AND_STRATEGY_REPORT.md` - Sprint 1 priority

### Current State:
- ✅ Vendor registration API exists (`POST /api/v1/vendors/register`)
- ✅ Vendor marketplace application API exists
- ✅ Seller controllers exist (stub implementations)
- ✅ Admin vendor routes exist
- ⚠️ Admin controller is stub per pre-audit
- 🔴 Distributor tables missing
- 🔴 Seller dashboard UX incomplete
- 🔴 Approval workflow incomplete

---

## B. Implementation Scope

### Phase 1A: Complete Vendor/Seller Flow (Days 1-5)

#### 1. Create Missing Migrations
```bash
cd /workspace/giga-nepal-backend

# Extend vendor status enum if needed (additive migration)
php artisan make:migration extend_vendor_status_and_add_fields --path=database/migrations/marketplace

# Add seller-specific fields to users table
php artisan make:migration add_seller_fields_to_users_table --path=database/migrations/marketplace

# Create vendor reviews table (separate from ratings)
php artisan make:migration create_vendor_reviews_table --path=database/migrations/marketplace

# Create vendor branches table
php artisan make:migration create_vendor_branches_table --path=database/migrations/marketplace
```

#### 2. Create Models
```bash
# Vendor extensions
php artisan make:model Models/Marketplace/VendorReview
php artisan make:model Models/Marketplace/VendorBranch

# Update existing models with relationships
# Edit app/Models/Marketplace/Vendor.php
# Edit app/Models/User.php
```

#### 3. Create Form Requests
```bash
# Seller validation
php artisan make:request Seller/SellerRegistrationRequest
php artisan make:request Seller/SellerProfileUpdateRequest
php artisan make:request Seller/SellerDocumentUploadRequest

# Admin vendor approval
php artisan make:request Admin/Vendor/VendorApprovalRequest
php artisan make:request Admin/Vendor/VendorRejectionRequest
```

#### 4. Implement Services
```bash
# Ensure these services exist and are complete
mkdir -p app/Services/Vendor

# If not exists, create:
# app/Services/Vendor/VendorRegistrationService.php
# app/Services/Vendor/VendorApprovalService.php
# app/Services/Vendor/VendorDocumentService.php
# app/Services/Vendor/SellerDashboardService.php
```

#### 5. Update Controllers
```bash
# Implement Api/Vendor/VendorController methods
# - register(): Full registration with validation
# - applyMarketplace(): Application with document upload
# - show(): Public profile with reviews

# Implement Api/Seller/* controllers
# - SellerDashboardController::index()
# - SellerProfileController::update()
# - SellerProductController::store()/update()
# - SellerInventoryController::update()

# Implement Api/Admin/VendorAdminController
# - index(): List all vendors with filters
# - show(): Vendor detail with documents
# - approve(): Approve vendor
# - reject(): Reject with reason
# - suspend(): Suspend vendor
```

#### 6. Create Policies
```bash
php artisan make:policy VendorPolicy --model=Vendor
php artisan make:policy SellerProductPolicy
php artisan make:policy SellerOrderPolicy
php artisan make:policy SellerInventoryPolicy
```

#### 7. Update Routes
```php
// In routes/api.php, ensure these routes exist:

// Enhanced vendor registration
Route::post('/vendors/register', [VendorController::class, 'register'])
    ->middleware('throttle:writes');

// Seller panel (protected)
Route::prefix('seller')->middleware(['api.token', 'permission:seller.access'])->group(function () {
    Route::get('/dashboard', [SellerDashboardController::class, 'index']);
    Route::get('/profile', [SellerProfileController::class, 'show']);
    Route::put('/profile', [SellerProfileController::class, 'update']);
    Route::post('/documents', [SellerDocumentController::class, 'upload']);
    
    // Products
    Route::apiResource('products', SellerProductController::class);
    
    // Inventory
    Route::get('/inventory', [SellerInventoryController::class, 'index']);
    Route::put('/inventory/{id}', [SellerInventoryController::class, 'update']);
    
    // Orders
    Route::get('/orders', [SellerOrderController::class, 'index']);
    Route::get('/orders/{id}', [SellerOrderController::class, 'show']);
    
    // Payouts
    Route::get('/payouts', [SellerPayoutController::class, 'index']);
});

// Admin vendor management
Route::prefix('admin/vendors')->middleware(['admin.token'])->group(function () {
    Route::get('/', [VendorAdminController::class, 'index']);
    Route::get('/{id}', [VendorAdminController::class, 'show']);
    Route::post('/{id}/approve', [VendorAdminController::class, 'approve']);
    Route::post('/{id}/reject', [VendorAdminController::class, 'reject']);
    Route::post('/{id}/suspend', [VendorAdminController::class, 'suspend']);
    Route::get('/{id}/products', [VendorAdminController::class, 'products']);
    Route::put('/products/{productId}/status', [VendorAdminController::class, 'updateProductStatus']);
});
```

### Phase 1B: Distributor Foundation (Days 6-10)

#### 1. Create Distributor Migrations
```bash
cd /workspace/giga-nepal-backend

# Main distributor tables
php artisan make:migration create_distributors_table --path=database/migrations/distributor
php artisan make:migration create_distributor_profiles_table --path=database/migrations/distributor
php artisan make:migration create_distributor_territories_table --path=database/migrations/distributor
php artisan make:migration create_distributor_staff_table --path=database/migrations/distributor
php artisan make:migration create_distributor_leads_table --path=database/migrations/distributor
php artisan make:migration create_distributor_customers_table --path=database/migrations/distributor
php artisan make:migration create_distributor_orders_table --path=database/migrations/distributor
php artisan make:migration create_distributor_commission_rules_table --path=database/migrations/distributor
php artisan make:migration create_distributor_commissions_table --path=database/migrations/distributor
php artisan make:migration create_distributor_payouts_table --path=database/migrations/distributor
php artisan make:migration create_distributor_activity_logs_table --path=database/migrations/distributor
```

#### 2. Create Distributor Models
```bash
mkdir -p app/Models/Distributor

php artisan make:model Models/Distributor/Distributor
php artisan make:model Models/Distributor/DistributorProfile
php artisan make:model Models/Distributor/DistributorTerritory
php artisan make:model Models/Distributor/DistributorStaff
php artisan make:model Models/Distributor/DistributorLead
php artisan make:model Models/Distributor/DistributorCustomer
php artisan make:model Models/Distributor/DistributorOrder
php artisan make:model Models/Distributor/DistributorCommissionRule
php artisan make:model Models/Distributor/DistributorCommission
php artisan make:model Models/Distributor/DistributorPayout
php artisan make:model Models/Distributor/DistributorActivityLog
```

#### 3. Create Distributor Controllers
```bash
mkdir -p app/Http/Controllers/Api/Distributor
mkdir -p app/Http/Controllers/Api/Admin

php artisan make:controller Api/Distributor/DistributorApplicationController
php artisan make:controller Api/Distributor/DistributorDashboardController
php artisan make:controller Api/Distributor/DistributorLeadController
php artisan make:controller Api/Distributor/DistributorCustomerController
php artisan make:controller Api/Distributor/DistributorOrderController
php artisan make:controller Api/Distributor/DistributorCommissionController
php artisan make:controller Api/Admin/DistributorAdminController
```

#### 4. Create Distributor Routes
```php
// In routes/api.php

// Public distributor application
Route::prefix('distributor')->group(function () {
    Route::post('/apply', [DistributorApplicationController::class, 'apply'])
        ->middleware('throttle:writes');
    Route::get('/application/status', [DistributorApplicationController::class, 'status'])
        ->middleware('api.token');
});

// Protected distributor panel
Route::prefix('distributor')->middleware(['api.token', 'permission:distributor.access'])->group(function () {
    Route::get('/dashboard', [DistributorDashboardController::class, 'index']);
    Route::get('/leads', [DistributorLeadController::class, 'index']);
    Route::post('/leads/{id}/convert', [DistributorLeadController::class, 'convert']);
    Route::get('/customers', [DistributorCustomerController::class, 'index']);
    Route::get('/orders', [DistributorOrderController::class, 'index']);
    Route::get('/commission', [DistributorCommissionController::class, 'index']);
    Route::get('/payouts', [DistributorPayoutController::class, 'index']);
});

// Admin distributor management
Route::prefix('admin/distributors')->middleware(['admin.token'])->group(function () {
    Route::get('/', [DistributorAdminController::class, 'index']);
    Route::get('/{id}', [DistributorAdminController::class, 'show']);
    Route::post('/{id}/approve', [DistributorAdminController::class, 'approve']);
    Route::post('/{id}/reject', [DistributorAdminController::class, 'reject']);
    Route::put('/{id}/territory', [DistributorAdminController::class, 'assignTerritory']);
});
```

---

## C. Verification Steps

After implementation, verify:

### 1. Migration Verification
```bash
php artisan migrate:status
# Should show all new migrations as pending
```

### 2. Model Verification
```bash
# Test model instantiation
php artisan tinker
>>> $vendor = new App\Models\Marketplace\Vendor();
>>> $distributor = new App\Models\Distributor\Distributor();
```

### 3. Route Verification
```bash
php artisan route:list --path=api/v1/seller
php artisan route:list --path=api/v1/distributor
php artisan route:list --path=api/v1/admin/vendors
php artisan route:list --path=api/v1/admin/distributors
```

### 4. API Testing
```bash
# Test vendor registration
curl -X POST https://backend.neogiga.com/api/v1/vendors/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Vendor","email":"vendor@test.com",...}'

# Test seller dashboard (with token)
curl -X GET https://backend.neogiga.com/api/v1/seller/dashboard \
  -H "Authorization: Bearer {token}"

# Test admin vendor list (with admin token)
curl -X GET https://backend.neogiga.com/api/v1/admin/vendors \
  -H "Authorization: Bearer {admin_token}"
```

### 5. Policy Verification
```bash
# Test that policies prevent unauthorized access
# Seller cannot access another seller's products
# Non-admin cannot access admin routes
```

---

## D. Implementation Report Template

After completing Sprint 1, create:

```markdown
# NeoGiga Sprint 1 Implementation Report

**Completed:** YYYY-MM-DD
**Developer:** [Name]

## Migrations Created
[List all migrations]

## Models Created
[List all models]

## Controllers Implemented
[List all controllers with methods]

## Routes Added
[Count of new routes]

## Tests Written
[List feature tests]

## Known Issues
[Any remaining gaps]

## Next Steps
[What needs to be done in Sprint 2]
```

---

## E. Preservation Requirements

⚠️ **CRITICAL:** During this sprint, you MUST:

1. **Preserve IoT modules** - Do not modify device-related tables
2. **Preserve Nepal geography** - Do not modify provinces/districts/municipalities/wards
3. **Preserve existing vendor schema** - Only add, never remove or rename
4. **Use additive migrations only** - No destructive commands
5. **Do not run migrate:fresh/reset** - Use incremental migrations
6. **Backup before any DB changes** - Always backup production DB

---

*Command generated by Qwen Code Audit System - 2026-07-08*
