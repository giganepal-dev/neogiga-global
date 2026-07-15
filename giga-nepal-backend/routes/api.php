<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Auth\PublicAuthController;
use App\Http\Controllers\Api\Auth\SellerAuthController;
use App\Http\Controllers\Api\Auth\DistributorAuthController;
use App\Http\Controllers\Api\Marketplace\MarketplaceController;
use App\Http\Controllers\Api\Product\CategoryController;
use App\Http\Controllers\Api\Product\BrandController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\Product\ProductCommerceController;
use App\Http\Controllers\Api\Vendor\VendorController;
use App\Http\Controllers\Api\Inventory\InventoryController;
use App\Http\Controllers\Api\Cart\CartController;
use App\Http\Controllers\Api\Order\OrderController;
use App\Http\Controllers\Api\AI\AiCommerceController;
use App\Http\Controllers\Api\POS\PosController;
use App\Http\Controllers\Api\LMS\LmsController;
use App\Http\Controllers\Api\Admin\AdminConsoleController;
use App\Http\Controllers\Api\Admin\MarketplaceAdminController;
use App\Http\Controllers\Api\Admin\ProductAdminController;
use App\Http\Controllers\Api\Admin\VendorAdminController;
use App\Http\Controllers\Api\Admin\DistributorAdminController;
use App\Http\Controllers\Api\Admin\B2BAdminController;
use App\Http\Controllers\Api\Admin\BomAdminController;
use App\Http\Controllers\Api\Admin\OnboardingAdminController;
use App\Http\Controllers\Api\Admin\InventoryAdminController;
use App\Http\Controllers\Api\Admin\LmsAdminController;
use App\Http\Controllers\Api\Admin\ImportExportController;
use App\Http\Controllers\Api\Seller\SellerDashboardController;
use App\Http\Controllers\Api\Seller\SellerInventoryController;
use App\Http\Controllers\Api\Seller\SellerOrderController;
use App\Http\Controllers\Api\Seller\SellerPayoutController;
use App\Http\Controllers\Api\Seller\SellerPerformanceController;
use App\Http\Controllers\Api\Seller\SellerProductController;
use App\Http\Controllers\Api\Seller\SellerProfileController;
use App\Http\Controllers\Api\Seller\SellerSupportTicketController;
use App\Http\Controllers\Api\Distributor\DistributorApplicationController;
use App\Http\Controllers\Api\Distributor\DistributorDashboardController;
use App\Http\Controllers\Api\Distributor\DistributorResourceController;
use App\Http\Controllers\Api\B2B\B2BAccountController;
use App\Http\Controllers\Api\B2B\B2BRfqController;
use App\Http\Controllers\Api\B2B\B2BQuotationController;
use App\Http\Controllers\Api\Bom\BomImportController;
use App\Http\Controllers\Api\Bom\BomProjectController;
use App\Http\Controllers\Api\CommerceAi\CommerceAiDemoController;
use App\Http\Controllers\Api\Onboarding\SellerApplicationController;
use App\Http\Controllers\Api\Onboarding\DistributorApplicationController as PublicDistributorApplicationController;

/*
|--------------------------------------------------------------------------
| API Routes for NeoGiga Marketplace — v1
|--------------------------------------------------------------------------
| Versioned per Blueprint §8. All routes inherit throttle:api (60/min);
| anonymous writes get the stricter `writes` limiter; /admin/* requires
| the admin token gate until Sanctum + RBAC land in Phase 1.
*/

Route::prefix('v1')->group(function () {
    Route::post('/seller-applications', [SellerApplicationController::class, 'store'])->middleware('throttle:writes');
    Route::post('/distributor-applications', [PublicDistributorApplicationController::class, 'store'])->middleware('throttle:writes');

    Route::prefix('commerce-ai')->group(function () {
        Route::get('/examples', [CommerceAiDemoController::class, 'examples']);
        Route::post('/session', [CommerceAiDemoController::class, 'session'])->middleware('throttle:writes');
        Route::post('/message', [CommerceAiDemoController::class, 'message'])->middleware('throttle:writes');
        Route::post('/build-bom', [CommerceAiDemoController::class, 'buildBom'])->middleware('throttle:writes');
    });

    // Auth (Phase 1 foundation): first-party bearer token auth.
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:writes');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:writes');
        Route::middleware('api.token')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::prefix('seller')->group(function () {
        Route::post('/register', [SellerAuthController::class, 'register'])->middleware('throttle:writes');
        Route::post('/login', [SellerAuthController::class, 'login'])->middleware('throttle:writes');
        Route::middleware(['api.token', 'permission:seller.access'])->group(function () {
            Route::get('/me', [SellerAuthController::class, 'me']);
            Route::post('/logout', [SellerAuthController::class, 'logout']);
        });
    });

    Route::prefix('distributor')->group(function () {
        Route::post('/register', [DistributorAuthController::class, 'register'])->middleware('throttle:writes');
        Route::post('/login', [DistributorAuthController::class, 'login'])->middleware('throttle:writes');
        Route::middleware(['api.token', 'permission:distributor.access'])->group(function () {
            Route::get('/me', [DistributorAuthController::class, 'me']);
            Route::post('/logout', [DistributorAuthController::class, 'logout']);
        });
    });

    // Marketplace resolution (public, read-only)
    Route::prefix('marketplaces')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index']);
        Route::get('/active', [MarketplaceController::class, 'active']);
        Route::get('/current', [MarketplaceController::class, 'current']);
        Route::get('/by-domain', [MarketplaceController::class, 'byDomain']);
    });
    // Public alias used by the storefront (codex §8: GET /api/marketplace/current)
    Route::get('/marketplace/current', [MarketplaceController::class, 'current']);

    // Catalog (public, read-only)
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/tree', [CategoryController::class, 'tree']);
        Route::get('/{slug}', [CategoryController::class, 'show']);
    });

    Route::prefix('brands')->group(function () {
        Route::get('/', [BrandController::class, 'index']);
        Route::get('/{slug}', [BrandController::class, 'show']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        // Static segments MUST precede the {slug} catch-all.
        Route::get('/search', [ProductController::class, 'search']);
        Route::get('/category/{slug}', [ProductController::class, 'byCategory']);
        Route::get('/brand/{slug}', [ProductController::class, 'byBrand']);
        Route::get('/{product}/attributes', [ProductCommerceController::class, 'attributes']);
        Route::get('/{product}/specs', [ProductCommerceController::class, 'specs']);
        Route::get('/{product}/variants', [ProductCommerceController::class, 'variants']);
        Route::get('/{product}/datasheets', [ProductCommerceController::class, 'datasheets']);
        Route::get('/{product}/warranty', [ProductCommerceController::class, 'warranty']);
        Route::get('/{product}/generic-suggestions', [ProductCommerceController::class, 'genericSuggestions']);
        Route::get('/{product}/compatible', [ProductCommerceController::class, 'compatible']);
        Route::get('/{product}/related', [ProductCommerceController::class, 'related']);
        Route::get('/{product}/accessories', [ProductCommerceController::class, 'accessories']);
        Route::get('/{product}/stock', [ProductCommerceController::class, 'stock']);
        Route::get('/{product}/stock/marketplace/{marketplace}', [ProductCommerceController::class, 'stockMarketplace'])->whereNumber('marketplace');
        Route::get('/{product}/stock/region/{region}', [ProductCommerceController::class, 'stockRegion'])->whereNumber('region');
        Route::get('/{slug}', [ProductController::class, 'show']);
    });

    // Vendors
    Route::prefix('vendors')->group(function () {
        Route::get('/', [VendorController::class, 'index']);
        Route::post('/register', [VendorController::class, 'register'])->middleware('throttle:writes');
        Route::get('/{slug}', [VendorController::class, 'show']);
        Route::get('/{vendor}/marketplace-approvals', [VendorController::class, 'marketplaceApprovals'])->whereNumber('vendor');
        Route::post('/{vendor}/apply-marketplace', [VendorController::class, 'applyMarketplace'])
            ->whereNumber('vendor')
            ->middleware('throttle:writes');
    });

    Route::prefix('seller')->middleware(['api.token', 'permission:seller.access'])->group(function () {
        Route::get('/dashboard', [SellerDashboardController::class, 'dashboard']);
        Route::get('/dashboard/overview', [SellerDashboardController::class, 'overview']);
        Route::get('/dashboard/sales-summary', [SellerDashboardController::class, 'salesSummary']);
        Route::get('/dashboard/order-summary', [SellerDashboardController::class, 'orderSummary']);
        Route::get('/dashboard/product-summary', [SellerDashboardController::class, 'productSummary']);
        Route::get('/dashboard/inventory-summary', [SellerDashboardController::class, 'inventorySummary']);
        Route::get('/dashboard/payout-summary', [SellerDashboardController::class, 'payoutSummary']);
        Route::get('/dashboard/alerts', [SellerDashboardController::class, 'alerts']);

        Route::get('/profile', [SellerProfileController::class, 'profile']);
        Route::patch('/profile', [SellerProfileController::class, 'update'])->middleware(['permission:seller.profile.manage', 'throttle:writes']);
        Route::get('/marketplace-approvals', [SellerProfileController::class, 'marketplaceApprovals']);
        Route::post('/marketplace-approvals', [SellerProfileController::class, 'applyMarketplace'])->middleware(['permission:seller.profile.manage', 'throttle:writes']);

        Route::get('/products', [SellerProductController::class, 'index'])->middleware('permission:seller.products.manage');
        Route::post('/products', [SellerProductController::class, 'store'])->middleware(['permission:seller.products.manage', 'throttle:writes']);
        Route::get('/products/{product}', [SellerProductController::class, 'show'])->whereNumber('product')->middleware('permission:seller.products.manage');
        Route::patch('/products/{product}', [SellerProductController::class, 'update'])->whereNumber('product')->middleware(['permission:seller.products.manage', 'throttle:writes']);
        Route::post('/products/{product}/submit-review', [SellerProductController::class, 'submitReview'])->whereNumber('product')->middleware(['permission:seller.products.manage', 'throttle:writes']);
        Route::post('/products/{product}/documents', [SellerProductController::class, 'storeDocument'])->whereNumber('product')->middleware(['permission:seller.products.manage', 'throttle:writes']);
        Route::post('/products/{product}/datasheets', [SellerProductController::class, 'storeDocument'])->whereNumber('product')->middleware(['permission:seller.products.manage', 'throttle:writes']);
        Route::post('/products/{product}/variants', [SellerProductController::class, 'storeVariant'])->whereNumber('product')->middleware(['permission:seller.products.manage', 'throttle:writes']);
        Route::post('/products/{product}/attributes', [SellerProductController::class, 'storeAttributes'])->whereNumber('product')->middleware(['permission:seller.products.manage', 'throttle:writes']);
        Route::post('/products/{product}/specs', [SellerProductController::class, 'storeSpec'])->whereNumber('product')->middleware(['permission:seller.products.manage', 'throttle:writes']);
        Route::post('/products/{product}/warranty', [SellerProductController::class, 'storeWarranty'])->whereNumber('product')->middleware(['permission:seller.products.manage', 'throttle:writes']);

        Route::get('/inventory', [SellerInventoryController::class, 'index'])->middleware('permission:seller.inventory.manage');
        Route::post('/inventory/adjust', [SellerInventoryController::class, 'adjust'])->middleware(['permission:seller.inventory.manage', 'throttle:writes']);

        Route::get('/orders', [SellerOrderController::class, 'index'])->middleware('permission:seller.orders.view');
        Route::get('/orders/{order}', [SellerOrderController::class, 'show'])->whereNumber('order')->middleware('permission:seller.orders.view');
        Route::patch('/orders/{order}/status', [SellerOrderController::class, 'updateStatus'])->whereNumber('order')->middleware(['permission:seller.orders.manage', 'throttle:writes']);

        Route::get('/payouts', [SellerPayoutController::class, 'index'])->middleware('permission:seller.payouts.view');
        Route::get('/performance', [SellerPerformanceController::class, 'show']);
        Route::get('/support-tickets', [SellerSupportTicketController::class, 'index'])->middleware('permission:seller.support.manage');
        Route::post('/support-tickets', [SellerSupportTicketController::class, 'store'])->middleware(['permission:seller.support.manage', 'throttle:writes']);
    });

    Route::post('/distributors/apply', [DistributorApplicationController::class, 'apply'])->middleware('throttle:writes');

    Route::prefix('distributor')->middleware(['api.token', 'permission:distributor.access'])->group(function () {
        Route::get('/dashboard', [DistributorDashboardController::class, 'dashboard']);
        Route::get('/dashboard/overview', [DistributorDashboardController::class, 'overview']);
        Route::get('/dashboard/territory-stock', [DistributorDashboardController::class, 'territoryStock']);
        Route::get('/dashboard/leads-summary', [DistributorDashboardController::class, 'leadsSummary']);
        Route::get('/dashboard/customer-summary', [DistributorDashboardController::class, 'customerSummary']);
        Route::get('/profile', [DistributorResourceController::class, 'profile']);
        Route::get('/territories', [DistributorResourceController::class, 'territories']);
        Route::get('/products/territory', [DistributorResourceController::class, 'territoryProducts']);
        Route::get('/vendors/territory', [DistributorResourceController::class, 'territoryVendors']);
        Route::get('/leads', [DistributorResourceController::class, 'leads']);
        Route::post('/leads', [DistributorResourceController::class, 'storeLead'])->middleware(['permission:distributor.leads.manage', 'throttle:writes']);
        Route::get('/customers', [DistributorResourceController::class, 'table'])->defaults('table', 'distributor_customers');
        Route::get('/orders', [DistributorResourceController::class, 'table'])->defaults('table', 'distributor_orders');
        Route::get('/commissions', [DistributorResourceController::class, 'table'])->defaults('table', 'distributor_commissions');
        Route::get('/payouts', [DistributorResourceController::class, 'table'])->defaults('table', 'distributor_payouts');
        Route::get('/downlines', [DistributorResourceController::class, 'table'])->defaults('table', 'distributor_downlines');
    });

    Route::post('/b2b/apply', [B2BAccountController::class, 'apply'])->middleware('throttle:writes');
    Route::prefix('b2b')->middleware(['api.token', 'permission:b2b.access'])->group(function () {
        Route::get('/account', [B2BAccountController::class, 'show']);
        Route::patch('/account', [B2BAccountController::class, 'update'])->middleware('throttle:writes');
        Route::post('/rfq', [B2BRfqController::class, 'store'])->middleware('throttle:writes');
        Route::get('/rfq', [B2BRfqController::class, 'index']);
        Route::get('/quotations', [B2BQuotationController::class, 'index']);
        Route::post('/quotations/{quotation}/accept', [B2BQuotationController::class, 'accept'])->whereNumber('quotation')->middleware('throttle:writes');
    });

    Route::prefix('bom')->group(function () {
        Route::get('/projects', [BomProjectController::class, 'index']);
        Route::get('/projects/{slug}', [BomProjectController::class, 'show']);
        Route::get('/projects/{slug}/items', [BomProjectController::class, 'items']);
        Route::post('/projects/{slug}/price', [BomProjectController::class, 'price'])->middleware('throttle:writes');
        Route::middleware('api.token')->group(function () {
            Route::post('/projects/{slug}/add-to-cart', [BomProjectController::class, 'addToCart'])->middleware('throttle:writes');
            Route::post('/build-custom', [BomProjectController::class, 'buildCustom'])->middleware('throttle:writes');
            Route::post('/user-builds', [BomProjectController::class, 'storeUserBuild'])->middleware('throttle:writes');
            Route::get('/user-builds/{build}', [BomProjectController::class, 'showUserBuild'])->whereNumber('build');

            // BOM procurement import: upload parts list -> match catalog -> RFQ.
            Route::get('/imports', [BomImportController::class, 'index']);
            Route::post('/imports', [BomImportController::class, 'store'])->middleware('throttle:writes');
            Route::get('/imports/{import}', [BomImportController::class, 'show'])->whereNumber('import');
            Route::post('/imports/{import}/rematch', [BomImportController::class, 'rematch'])->whereNumber('import')->middleware('throttle:writes');
            Route::patch('/imports/{import}/lines/{line}', [BomImportController::class, 'updateLine'])->whereNumber('import')->whereNumber('line')->middleware('throttle:writes');
            Route::post('/imports/{import}/convert-to-rfq', [BomImportController::class, 'convertToRfq'])->whereNumber('import')->middleware('throttle:writes');
        });
    });

    // Inventory (availability reads public; reservations require API token)
    Route::prefix('inventory')->group(function () {
        Route::get('/product/{product}', [InventoryController::class, 'byProduct'])->whereNumber('product');
        Route::get('/marketplace/{marketplace}', [InventoryController::class, 'byMarketplace'])->whereNumber('marketplace');
        Route::get('/warehouse/{warehouse}', [InventoryController::class, 'byWarehouse'])->whereNumber('warehouse');
        Route::post('/reserve', [InventoryController::class, 'reserve'])->middleware('api.token');
        Route::post('/release-reservation', [InventoryController::class, 'releaseReservation'])->middleware('api.token');
    });

    // Commerce (cart and manual checkout APIs require auth/permissions)
    Route::prefix('cart')->middleware(['api.token', 'permission:cart.manage'])->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::post('/add-bom', [CartController::class, 'addBom']);
        Route::patch('/items/{item}', [CartController::class, 'updateItem'])->whereNumber('item');
        Route::delete('/items/{item}', [CartController::class, 'removeItem'])->whereNumber('item');
    });

    Route::post('/checkout', [OrderController::class, 'checkout'])->middleware(['api.token', 'permission:checkout.create']);

    Route::prefix('orders')->middleware(['api.token', 'permission:orders.view'])->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{order}', [OrderController::class, 'show'])->whereNumber('order');
        Route::get('/{order}/invoice', [OrderController::class, 'invoice'])->whereNumber('order');
    });

    // AI Commerce (contract stable; protected while Phase 2 orchestrator is incomplete)
    Route::prefix('ai')->middleware('api.token')->group(function () {
        Route::post('/session', [AiCommerceController::class, 'createSession']);
        Route::post('/message', [AiCommerceController::class, 'sendMessage']);
        Route::post('/build-bom', [AiCommerceController::class, 'buildBom']);
        Route::post('/add-bom-to-cart', [AiCommerceController::class, 'addBomToCart']);
        Route::post('/create-pos-invoice', [AiCommerceController::class, 'createPosInvoice']);
    });

    // POS (product search public; terminal/session/sale mutations require API token)
    Route::prefix('pos')->group(function () {
        Route::get('/products/search', [PosController::class, 'searchProducts']);
        Route::middleware('api.token')->group(function () {
            Route::post('/sessions/open', [PosController::class, 'openSession']);
            Route::post('/sessions/close', [PosController::class, 'closeSession']);
            Route::post('/sales', [PosController::class, 'createSale']);
            Route::get('/sales/{sale}', [PosController::class, 'showSale'])->whereNumber('sale');
            Route::post('/sales/{sale}/payment', [PosController::class, 'processPayment'])->whereNumber('sale');
            Route::post('/sales/{sale}/refund', [PosController::class, 'processRefund'])->whereNumber('sale');
        });
    });

    // LMS (public catalog/project reads; learner actions require API token)
    Route::prefix('lms')->group(function () {
        Route::get('/courses', [LmsController::class, 'courses']);
        Route::get('/courses/{course}/modules', [LmsController::class, 'courseModules'])->whereNumber('course');
        Route::get('/projects', [LmsController::class, 'projects']);
        Route::get('/projects/{slug}', [LmsController::class, 'showProject']);
        Route::get('/projects/{slug}/components', [LmsController::class, 'projectComponents']);
        Route::get('/projects/{slug}/code-samples', [LmsController::class, 'projectCodeSamples']);
        Route::middleware('api.token')->group(function () {
            Route::post('/enrollments', [LmsController::class, 'enroll'])->middleware('throttle:writes');
            Route::get('/my-enrollments', [LmsController::class, 'myEnrollments']);
            Route::post('/progress', [LmsController::class, 'progress'])->middleware('throttle:writes');
        });
    });

    // Admin (fail-closed token gate; replace with Sanctum + RBAC in Phase 1)
    Route::middleware('admin.token')->group(function () {
        // Marketplace domain/SEO/status management (codex §8). Fine-grained
        // permission:marketplaces.* gating is a follow-up; admin.token is the
        // fail-closed gate for now.
        Route::prefix('admin/marketplaces')->group(function () {
            Route::get('/', [MarketplaceAdminController::class, 'index']);
            Route::get('/{id}', [MarketplaceAdminController::class, 'show'])->whereNumber('id');
            Route::patch('/{id}/status', [MarketplaceAdminController::class, 'updateStatus'])->whereNumber('id');
            Route::post('/{id}/generate-domain', [MarketplaceAdminController::class, 'generateDomain'])->whereNumber('id');
            Route::post('/{id}/verify-domain', [MarketplaceAdminController::class, 'verifyDomain'])->whereNumber('id');
            Route::post('/{id}/generate-seo', [MarketplaceAdminController::class, 'generateSeo'])->whereNumber('id');
            Route::post('/{id}/validate-launch', [MarketplaceAdminController::class, 'validateLaunch'])->whereNumber('id');
            Route::post('/{id}/clear-cache', [MarketplaceAdminController::class, 'clearCache'])->whereNumber('id');
            Route::get('/{id}/audit-history', [MarketplaceAdminController::class, 'auditHistory'])->whereNumber('id');
        });

        Route::prefix('admin/imports')->group(function () {
            Route::post('/dry-run', [ImportExportController::class, 'dryRun']);
            Route::post('/execute', [ImportExportController::class, 'execute']);
            Route::get('/{import}', [ImportExportController::class, 'show'])->whereNumber('import');
            Route::get('/{import}/errors', [ImportExportController::class, 'errors'])->whereNumber('import');
        });

        Route::prefix('admin/exports')->group(function () {
            Route::post('/create', [ImportExportController::class, 'createExport']);
        });

        Route::prefix('admin/console')->group(function () {
            Route::get('/overview', [AdminConsoleController::class, 'overview']);
            Route::get('/navigation', [AdminConsoleController::class, 'navigation']);
            Route::get('/settings', [AdminConsoleController::class, 'settings']);
            Route::post('/settings', [AdminConsoleController::class, 'storeSetting'])->middleware(['admin.permission:settings.manage', 'throttle:writes']);
            Route::get('/media', [AdminConsoleController::class, 'media']);
            Route::post('/media', [AdminConsoleController::class, 'storeMedia'])->middleware('throttle:writes');
            Route::get('/seo', [AdminConsoleController::class, 'seo']);
            Route::post('/seo/pages', [AdminConsoleController::class, 'storeSeoPage'])->middleware('throttle:writes');
            Route::post('/seo/redirects', [AdminConsoleController::class, 'storeRedirect'])->middleware('throttle:writes');
            Route::get('/permissions', [AdminConsoleController::class, 'permissions']);
            Route::get('/approvals', [AdminConsoleController::class, 'approvals']);
        });

        Route::prefix('admin/inventory')->group(function () {
            Route::get('/overview', [InventoryAdminController::class, 'overview']);
            Route::get('/stocks', [InventoryAdminController::class, 'stocks']);
            Route::get('/movements', [InventoryAdminController::class, 'movements']);
            Route::get('/low-stock', [InventoryAdminController::class, 'lowStock']);
            Route::post('/adjust', [InventoryAdminController::class, 'adjust'])->middleware('throttle:writes');
            Route::post('/transfer', [InventoryAdminController::class, 'transfer'])->middleware('throttle:writes');
            Route::post('/receive', [InventoryAdminController::class, 'receive'])->middleware('throttle:writes');
        });

        Route::prefix('admin/lms')->group(function () {
            Route::get('/overview', [LmsAdminController::class, 'overview']);
            Route::get('/courses', [LmsAdminController::class, 'courses']);
            Route::post('/courses', [LmsAdminController::class, 'storeCourse'])->middleware('throttle:writes');
            Route::get('/projects', [LmsAdminController::class, 'projects']);
            Route::post('/projects', [LmsAdminController::class, 'storeProject'])->middleware('throttle:writes');
            Route::post('/lessons', [LmsAdminController::class, 'storeLesson'])->middleware('throttle:writes');
            Route::get('/enrollments', [LmsAdminController::class, 'enrollments']);
            Route::get('/certificates', [LmsAdminController::class, 'certificates']);
        });

        Route::prefix('admin')->group(function () {
            Route::get('/seller-applications', [OnboardingAdminController::class, 'sellerApplications']);
            Route::get('/seller-applications/{application}', [OnboardingAdminController::class, 'sellerApplication'])->whereNumber('application');
            Route::patch('/seller-applications/{application}/status', [OnboardingAdminController::class, 'updateSellerStatus'])->whereNumber('application')->middleware('throttle:writes');
            Route::post('/seller-applications/{application}/convert-to-vendor', [OnboardingAdminController::class, 'convertSellerToVendor'])->whereNumber('application')->middleware('throttle:writes');
            Route::get('/distributor-applications', [OnboardingAdminController::class, 'distributorApplications']);
            Route::get('/distributor-applications/{application}', [OnboardingAdminController::class, 'distributorApplication'])->whereNumber('application');
            Route::patch('/distributor-applications/{application}/status', [OnboardingAdminController::class, 'updateDistributorStatus'])->whereNumber('application')->middleware('throttle:writes');
            Route::post('/distributor-applications/{application}/convert-to-distributor', [OnboardingAdminController::class, 'convertDistributor'])->whereNumber('application')->middleware('throttle:writes');
            Route::get('/dashboard/seller-onboarding-summary', [OnboardingAdminController::class, 'sellerOnboardingSummary']);
            Route::get('/dashboard/ai-commerce-summary', [OnboardingAdminController::class, 'aiCommerceSummary']);

            Route::get('/vendors', [VendorAdminController::class, 'index']);
            Route::get('/vendors/{vendor}', [VendorAdminController::class, 'show'])->whereNumber('vendor');
            Route::post('/vendors/{vendor}/approve', [VendorAdminController::class, 'approve'])->whereNumber('vendor')->middleware('throttle:writes');
            Route::post('/vendors/{vendor}/reject', [VendorAdminController::class, 'reject'])->whereNumber('vendor')->middleware('throttle:writes');
            Route::post('/vendors/{vendor}/suspend', [VendorAdminController::class, 'suspend'])->whereNumber('vendor')->middleware('throttle:writes');
            Route::get('/vendor-approvals', [VendorAdminController::class, 'approvals']);
            Route::post('/vendor-approvals/{approval}/approve', [VendorAdminController::class, 'approveMarketplace'])->whereNumber('approval')->middleware('throttle:writes');
            Route::post('/vendor-approvals/{approval}/reject', [VendorAdminController::class, 'rejectMarketplace'])->whereNumber('approval')->middleware('throttle:writes');
            Route::get('/vendor-products/pending', [VendorAdminController::class, 'pendingProducts']);
            Route::post('/vendor-products/{product}/approve', [VendorAdminController::class, 'approveProduct'])->whereNumber('product')->middleware('throttle:writes');
            Route::post('/vendor-products/{product}/reject', [VendorAdminController::class, 'rejectProduct'])->whereNumber('product')->middleware('throttle:writes');
            Route::get('/products', [ProductAdminController::class, 'index']);
            Route::get('/products/pending', [ProductAdminController::class, 'pending']);
            Route::get('/products/{product}', [ProductAdminController::class, 'show'])->whereNumber('product');
            Route::post('/products/{product}/approve', [ProductAdminController::class, 'approve'])->whereNumber('product')->middleware('throttle:writes');
            Route::post('/products/{product}/reject', [ProductAdminController::class, 'reject'])->whereNumber('product')->middleware('throttle:writes');
            Route::get('/product-generic-groups', [ProductAdminController::class, 'genericGroups']);
            Route::post('/product-generic-groups', [ProductAdminController::class, 'storeGenericGroup'])->middleware('throttle:writes');
            Route::post('/products/{product}/generic-suggestions', [ProductAdminController::class, 'storeGenericSuggestion'])->whereNumber('product')->middleware('throttle:writes');
            Route::patch('/product-generic-suggestions/{suggestion}', [ProductAdminController::class, 'updateGenericSuggestion'])->whereNumber('suggestion')->middleware('throttle:writes');
            Route::delete('/product-generic-suggestions/{suggestion}', [ProductAdminController::class, 'deleteGenericSuggestion'])->whereNumber('suggestion')->middleware('throttle:writes');
            Route::get('/vendor-payouts', [VendorAdminController::class, 'payouts']);
            Route::post('/vendor-payouts/{payout}/mark-paid', [VendorAdminController::class, 'markPayoutPaid'])->whereNumber('payout')->middleware('throttle:writes');

            Route::get('/distributors', [DistributorAdminController::class, 'index']);
            Route::get('/distributors/{distributor}', [DistributorAdminController::class, 'show'])->whereNumber('distributor');
            Route::post('/distributors/{distributor}/approve', [DistributorAdminController::class, 'approve'])->whereNumber('distributor')->middleware('throttle:writes');
            Route::post('/distributors/{distributor}/reject', [DistributorAdminController::class, 'reject'])->whereNumber('distributor')->middleware('throttle:writes');
            Route::post('/distributors/{distributor}/suspend', [DistributorAdminController::class, 'suspend'])->whereNumber('distributor')->middleware('throttle:writes');
            Route::post('/distributors/{distributor}/assign-territory', [DistributorAdminController::class, 'assignTerritory'])->whereNumber('distributor')->middleware('throttle:writes');
            Route::get('/distributor-commissions', [DistributorAdminController::class, 'commissions']);
            Route::post('/distributor-commissions/{commission}/approve', [DistributorAdminController::class, 'approveCommission'])->whereNumber('commission')->middleware('throttle:writes');
            Route::get('/distributor-payouts', [DistributorAdminController::class, 'payouts']);
            Route::post('/distributor-payouts/{payout}/mark-paid', [DistributorAdminController::class, 'markPayoutPaid'])->whereNumber('payout')->middleware('throttle:writes');

            Route::get('/b2b/accounts', [B2BAdminController::class, 'accounts']);
            Route::get('/b2b/accounts/{account}', [B2BAdminController::class, 'account'])->whereNumber('account');
            Route::post('/b2b/accounts/{account}/approve', [B2BAdminController::class, 'approve'])->whereNumber('account')->middleware('throttle:writes');
            Route::post('/b2b/accounts/{account}/reject', [B2BAdminController::class, 'reject'])->whereNumber('account')->middleware('throttle:writes');
            Route::get('/b2b/rfq', [B2BAdminController::class, 'rfqs']);
            Route::get('/b2b/rfq/{rfq}', [B2BAdminController::class, 'rfq'])->whereNumber('rfq');
            Route::post('/b2b/rfq/{rfq}/create-quotation', [B2BAdminController::class, 'createQuotation'])->whereNumber('rfq')->middleware('throttle:writes');
            Route::get('/b2b/quotations', [B2BAdminController::class, 'quotations']);
            Route::post('/b2b/quotations', [B2BAdminController::class, 'createQuotation'])->middleware('throttle:writes');
            Route::get('/b2b/purchase-orders', [B2BAdminController::class, 'purchaseOrders']);
            Route::get('/b2b/price-lists', [B2BAdminController::class, 'priceLists']);

            Route::get('/bom/projects', [BomAdminController::class, 'projects']);
            Route::post('/bom/projects', [BomAdminController::class, 'storeProject'])->middleware('throttle:writes');
            Route::patch('/bom/projects/{project}', [BomAdminController::class, 'updateProject'])->whereNumber('project')->middleware('throttle:writes');
            Route::post('/bom/projects/{project}/items', [BomAdminController::class, 'storeItem'])->whereNumber('project')->middleware('throttle:writes');
            Route::patch('/bom/projects/{project}/items/{item}', [BomAdminController::class, 'updateItem'])->whereNumber('project')->whereNumber('item')->middleware('throttle:writes');
            Route::delete('/bom/projects/{project}/items/{item}', [BomAdminController::class, 'deleteItem'])->whereNumber('project')->whereNumber('item')->middleware('throttle:writes');
        });
    });
});


/*
|--------------------------------------------------------------------------
| Marketing Phase 2 Foundation
|--------------------------------------------------------------------------
| Additive CRM/newsletter/email/WhatsApp/analytics endpoints. Public writes
| are throttled; admin routes keep the existing fail-closed admin.token gate.
*/
use App\Http\Controllers\Api\Marketing\CustomerProfileController as MarketingCustomerProfileController;
use App\Http\Controllers\Api\Marketing\NewsletterController as MarketingNewsletterController;
use App\Http\Controllers\Api\Marketing\AuthEmailOtpController as MarketingAuthEmailOtpController;
use App\Http\Controllers\Api\Marketing\WhatsappOptInController as MarketingWhatsappOptInController;
use App\Http\Controllers\Api\Marketing\AnalyticsController as MarketingAnalyticsController;
use App\Http\Controllers\Api\Admin\Marketing\CrmController as MarketingCrmController;
use App\Http\Controllers\Api\Admin\Marketing\NewsletterAdminController as MarketingNewsletterAdminController;
use App\Http\Controllers\Api\Admin\Marketing\EmailAdminController as MarketingEmailAdminController;
use App\Http\Controllers\Api\Admin\Marketing\WhatsappAdminController as MarketingWhatsappAdminController;
use App\Http\Controllers\Api\Admin\Marketing\AbandonedCartAdminController as MarketingAbandonedCartAdminController;
use App\Http\Controllers\Api\Admin\Marketing\CampaignAdminController as MarketingCampaignAdminController;
use App\Http\Controllers\Api\Admin\Marketing\AnalyticsAdminController as MarketingAnalyticsAdminController;
use App\Http\Controllers\Api\Admin\Marketing\DashboardAdminController as MarketingDashboardAdminController;
use App\Http\Controllers\Api\Admin\Marketing\SettingsAdminController as MarketingSettingsAdminController;
use App\Http\Controllers\Api\Admin\Marketing\CustomerImportController as MarketingCustomerImportController;
use App\Http\Controllers\Api\Marketing\EmailWebhookController;

$marketingPublic = function () {
    Route::post('/email/webhooks/{provider}', EmailWebhookController::class)
        ->where('provider', '[a-z0-9_-]+')->middleware('throttle:120,1');
    Route::post('/newsletter/subscribe', [MarketingNewsletterController::class, 'subscribe'])->middleware('throttle:writes');
    Route::post('/newsletter/confirm', [MarketingNewsletterController::class, 'confirm'])->middleware('throttle:writes');
    Route::post('/newsletter/unsubscribe', [MarketingNewsletterController::class, 'unsubscribe'])->middleware('throttle:writes');
    Route::get('/newsletter/preferences', [MarketingNewsletterController::class, 'preferences']);
    Route::patch('/newsletter/preferences', [MarketingNewsletterController::class, 'updatePreferences'])->middleware('throttle:writes');
    Route::post('/unsubscribe', [MarketingCustomerProfileController::class, 'unsubscribe'])->middleware('throttle:writes');
    Route::post('/auth/email-otp/request', [MarketingAuthEmailOtpController::class, 'request'])->middleware('throttle:writes');
    Route::post('/auth/email-otp/verify', [MarketingAuthEmailOtpController::class, 'verify'])->middleware('throttle:writes');
    Route::post('/whatsapp/opt-in', [MarketingWhatsappOptInController::class, 'optIn'])->middleware('throttle:writes');
    Route::post('/whatsapp/opt-out', [MarketingWhatsappOptInController::class, 'optOut'])->middleware('throttle:writes');
    Route::post('/analytics/event', [MarketingAnalyticsController::class, 'event'])->middleware('throttle:writes');
    Route::post('/analytics/product-view', [MarketingAnalyticsController::class, 'productView'])->middleware('throttle:writes');
    Route::post('/analytics/search', [MarketingAnalyticsController::class, 'search'])->middleware('throttle:writes');
    Route::middleware('api.token')->group(function () {
        Route::get('/customer/profile', [MarketingCustomerProfileController::class, 'profile']);
        Route::patch('/customer/profile', [MarketingCustomerProfileController::class, 'update'])->middleware('throttle:writes');
        Route::patch('/customer/preferences', [MarketingCustomerProfileController::class, 'preferences'])->middleware('throttle:writes');
        Route::post('/customer/marketing-consent', [MarketingCustomerProfileController::class, 'consent'])->middleware('throttle:writes');
    });
};

$marketingAdmin = function () {
    Route::post('/customer-imports/preview', [MarketingCustomerImportController::class, 'preview'])->middleware('admin.permission:customers.import');
    Route::post('/customer-imports', [MarketingCustomerImportController::class, 'execute'])->middleware('admin.permission:customers.import');
    Route::get('/customer-imports/{import}', [MarketingCustomerImportController::class, 'status'])->whereNumber('import')->middleware('admin.permission:customers.view');
    Route::get('/customers', [MarketingCrmController::class, 'customers'])->middleware('admin.permission:customers.view');
    Route::get('/customers/export', [MarketingCrmController::class, 'export'])->middleware('admin.permission:customers.export');
    Route::get('/customers/country-summary', [MarketingCrmController::class, 'countrySummary'])->middleware('admin.permission:customers.view');
    Route::get('/customers/{customer}', [MarketingCrmController::class, 'customer'])->whereNumber('customer')->middleware('admin.permission:customers.view');
    Route::get('/customer-accounts', [MarketingCrmController::class, 'accounts'])->middleware('admin.permission:customers.view');
    Route::get('/customer-contacts', [MarketingCrmController::class, 'contacts'])->middleware('admin.permission:customers.view');
    Route::get('/customer-consents', [MarketingCrmController::class, 'consents'])->middleware('admin.permission:customers.consent.manage');
    Route::get('/customer-suppressions', [MarketingCrmController::class, 'suppressions'])->middleware('admin.permission:customers.suppression.manage');
    Route::get('/customer-segments', [MarketingCrmController::class, 'segments'])->middleware('admin.permission:customers.view');
    Route::post('/customer-segments', [MarketingCrmController::class, 'storeSegment'])->middleware('admin.permission:campaigns.create');
    Route::post('/customer-segments/{segment}/refresh', [MarketingCrmController::class, 'refreshSegment'])->whereNumber('segment')->middleware('admin.permission:campaigns.create');
    Route::get('/contact-lists', [MarketingCrmController::class, 'contactLists'])->middleware('admin.permission:customers.view');
    Route::post('/contact-lists', [MarketingCrmController::class, 'storeContactList'])->middleware('admin.permission:campaigns.create');
    Route::post('/contact-lists/{list}/members', [MarketingCrmController::class, 'addMembers'])->whereNumber('list')->middleware('admin.permission:campaigns.create');
    Route::get('/newsletter/subscribers', [MarketingNewsletterAdminController::class, 'subscribers'])->middleware('admin.permission:customers.view');
    Route::get('/newsletter/templates', [MarketingNewsletterAdminController::class, 'templates'])->middleware('admin.permission:campaigns.view');
    Route::post('/newsletter/templates', [MarketingNewsletterAdminController::class, 'storeTemplate'])->middleware('admin.permission:email.templates.manage');
    Route::patch('/newsletter/templates/{template}', [MarketingNewsletterAdminController::class, 'updateTemplate'])->whereNumber('template')->middleware('admin.permission:email.templates.manage');
    Route::get('/newsletter/campaigns', [MarketingNewsletterAdminController::class, 'campaigns'])->middleware('admin.permission:campaigns.view');
    Route::post('/newsletter/campaigns', [MarketingNewsletterAdminController::class, 'storeCampaign'])->middleware('admin.permission:campaigns.create');
    Route::post('/newsletter/campaigns/{campaign}/preview', [MarketingNewsletterAdminController::class, 'preview'])->whereNumber('campaign')->middleware('admin.permission:campaigns.view');
    Route::post('/newsletter/campaigns/{campaign}/approve', [MarketingNewsletterAdminController::class, 'approve'])->whereNumber('campaign')->middleware('admin.permission:campaigns.approve');
    Route::post('/newsletter/campaigns/{campaign}/schedule', [MarketingNewsletterAdminController::class, 'schedule'])->whereNumber('campaign')->middleware('admin.permission:campaigns.schedule');
    Route::post('/newsletter/campaigns/{campaign}/send-test', [MarketingNewsletterAdminController::class, 'sendTest'])->whereNumber('campaign')->middleware('admin.permission:campaigns.test');
    Route::post('/newsletter/campaigns/{campaign}/send-now', [MarketingNewsletterAdminController::class, 'sendNow'])->whereNumber('campaign')->middleware('admin.permission:campaigns.send');
    Route::post('/newsletter/campaigns/{campaign}/pause', [MarketingNewsletterAdminController::class, 'pause'])->whereNumber('campaign')->middleware('admin.permission:campaigns.send');
    Route::post('/newsletter/campaigns/{campaign}/resume', [MarketingNewsletterAdminController::class, 'resume'])->whereNumber('campaign')->middleware('admin.permission:campaigns.send');
    Route::post('/newsletter/campaigns/{campaign}/cancel', [MarketingNewsletterAdminController::class, 'cancel'])->whereNumber('campaign')->middleware('admin.permission:campaigns.send');
    Route::get('/email/templates', [MarketingEmailAdminController::class, 'templates'])->middleware('admin.permission:campaigns.view');
    Route::post('/email/templates', [MarketingEmailAdminController::class, 'storeTemplate'])->middleware('admin.permission:campaigns.create');
    Route::patch('/email/templates/{template}', [MarketingEmailAdminController::class, 'updateTemplate'])->whereNumber('template')->middleware('admin.permission:campaigns.create');
    Route::get('/email/campaigns', [MarketingEmailAdminController::class, 'campaigns'])->middleware('admin.permission:campaigns.view');
    Route::post('/email/campaigns', [MarketingEmailAdminController::class, 'storeCampaign'])->middleware('admin.permission:campaigns.create');
    Route::post('/email/campaigns/{campaign}/preview', [MarketingEmailAdminController::class, 'preview'])->whereNumber('campaign')->middleware('admin.permission:campaigns.view');
    Route::post('/email/campaigns/{campaign}/approve', [MarketingEmailAdminController::class, 'approve'])->whereNumber('campaign')->middleware('admin.permission:campaigns.approve');
    Route::post('/email/campaigns/{campaign}/schedule', [MarketingEmailAdminController::class, 'schedule'])->whereNumber('campaign')->middleware('admin.permission:campaigns.schedule');
    Route::post('/email/campaigns/{campaign}/send-test', [MarketingEmailAdminController::class, 'sendTest'])->whereNumber('campaign')->middleware('admin.permission:campaigns.test');
    Route::post('/email/campaigns/{campaign}/send-now', [MarketingEmailAdminController::class, 'sendNow'])->whereNumber('campaign')->middleware('admin.permission:campaigns.send');
    Route::post('/email/campaigns/{campaign}/pause', [MarketingEmailAdminController::class, 'pause'])->whereNumber('campaign')->middleware('admin.permission:campaigns.send');
    Route::post('/email/campaigns/{campaign}/resume', [MarketingEmailAdminController::class, 'resume'])->whereNumber('campaign')->middleware('admin.permission:campaigns.send');
    Route::post('/email/campaigns/{campaign}/cancel', [MarketingEmailAdminController::class, 'cancel'])->whereNumber('campaign')->middleware('admin.permission:campaigns.send');
    Route::post('/email/provider/test', [MarketingEmailAdminController::class, 'providerTest'])->middleware('admin.permission:email.providers.manage');
    Route::post('/email/transactional/test', [MarketingEmailAdminController::class, 'transactionalProviderTest'])->middleware('admin.permission:email.providers.manage');
    Route::get('/email/events', [MarketingEmailAdminController::class, 'events'])->middleware('admin.permission:email.events.view');
    Route::get('/email/automation-rules', [MarketingEmailAdminController::class, 'automationRules'])->middleware('admin.permission:campaigns.view');
    Route::post('/email/automation-rules', [MarketingEmailAdminController::class, 'storeAutomationRule'])->middleware('admin.permission:campaigns.create');
    Route::patch('/email/automation-rules/{rule}', [MarketingEmailAdminController::class, 'updateAutomationRule'])->whereNumber('rule')->middleware('admin.permission:campaigns.create');
    Route::get('/abandoned-carts', [MarketingAbandonedCartAdminController::class, 'index'])->middleware('admin.permission:campaigns.view');
    Route::get('/abandoned-carts/recovery-report', [MarketingAbandonedCartAdminController::class, 'report'])->middleware('admin.permission:campaigns.view');
    Route::get('/abandoned-carts/{cart}', [MarketingAbandonedCartAdminController::class, 'show'])->whereNumber('cart')->middleware('admin.permission:campaigns.view');
    Route::post('/abandoned-carts/{cart}/send-reminder', [MarketingAbandonedCartAdminController::class, 'sendReminder'])->whereNumber('cart')->middleware('admin.permission:campaigns.send');
    Route::get('/whatsapp/templates', [MarketingWhatsappAdminController::class, 'templates'])->middleware('admin.permission:campaigns.view');
    Route::post('/whatsapp/templates', [MarketingWhatsappAdminController::class, 'storeTemplate'])->middleware('admin.permission:email.templates.manage');
    Route::get('/whatsapp/campaigns', [MarketingWhatsappAdminController::class, 'campaigns'])->middleware('admin.permission:campaigns.view');
    Route::post('/whatsapp/campaigns', [MarketingWhatsappAdminController::class, 'storeCampaign'])->middleware('admin.permission:campaigns.create');
    Route::post('/whatsapp/campaigns/{campaign}/preview', [MarketingWhatsappAdminController::class, 'preview'])->whereNumber('campaign')->middleware('admin.permission:campaigns.view');
    Route::post('/whatsapp/campaigns/{campaign}/schedule', [MarketingWhatsappAdminController::class, 'schedule'])->whereNumber('campaign')->middleware('admin.permission:campaigns.schedule');
    Route::post('/whatsapp/campaigns/{campaign}/send-test', [MarketingWhatsappAdminController::class, 'sendTest'])->whereNumber('campaign')->middleware('admin.permission:campaigns.test');
    Route::post('/whatsapp/campaigns/{campaign}/send-now', [MarketingWhatsappAdminController::class, 'sendNow'])->whereNumber('campaign')->middleware('admin.permission:campaigns.send');
    Route::get('/whatsapp/events', [MarketingWhatsappAdminController::class, 'events'])->middleware('admin.permission:email.events.view');
    Route::post('/whatsapp/export-recipients', [MarketingWhatsappAdminController::class, 'exportRecipients'])->middleware('admin.permission:customers.export');
    Route::post('/campaigns/audience-preview', [MarketingCampaignAdminController::class, 'audiencePreview'])->middleware('admin.permission:campaigns.view');
    Route::get('/campaigns/audience-count', [MarketingCampaignAdminController::class, 'audienceCount'])->middleware('admin.permission:campaigns.view');
    Route::post('/campaigns/create-multi-channel', [MarketingCampaignAdminController::class, 'createMultiChannel'])->middleware('admin.permission:campaigns.create');
    Route::get('/analytics/dashboard', [MarketingAnalyticsAdminController::class, 'dashboard'])->middleware('admin.permission:campaigns.view');
    Route::get('/analytics/trending-products', [MarketingAnalyticsAdminController::class, 'trendingProducts'])->middleware('admin.permission:campaigns.view');
    Route::get('/analytics/trending-categories', [MarketingAnalyticsAdminController::class, 'trendingCategories'])->middleware('admin.permission:campaigns.view');
    Route::get('/analytics/top-searches', [MarketingAnalyticsAdminController::class, 'topSearches'])->middleware('admin.permission:campaigns.view');
    Route::get('/analytics/regional-orders', [MarketingAnalyticsAdminController::class, 'regionalOrders'])->middleware('admin.permission:campaigns.view');
    Route::get('/analytics/country-sales', [MarketingAnalyticsAdminController::class, 'countrySales'])->middleware('admin.permission:campaigns.view');
    Route::get('/analytics/campaign-performance', [MarketingAnalyticsAdminController::class, 'campaignPerformance'])->middleware('admin.permission:campaigns.view');
    Route::get('/analytics/newsletter-performance', [MarketingAnalyticsAdminController::class, 'newsletterPerformance'])->middleware('admin.permission:campaigns.view');
    Route::get('/dashboard/overview', [MarketingDashboardAdminController::class, 'overview'])->middleware('admin.permission:campaigns.view');
    Route::get('/dashboard/{type}', [MarketingDashboardAdminController::class, 'proxy'])->middleware('admin.permission:campaigns.view');
    Route::get('/settings/marketing', [MarketingSettingsAdminController::class, 'marketing'])->middleware('admin.permission:email.providers.manage');
    Route::patch('/settings/marketing', [MarketingSettingsAdminController::class, 'updateMarketing'])->middleware('admin.permission:email.providers.manage');
    Route::get('/settings/analytics', [MarketingSettingsAdminController::class, 'analytics'])->middleware('admin.permission:email.providers.manage');
    Route::patch('/settings/analytics', [MarketingSettingsAdminController::class, 'updateAnalytics'])->middleware('admin.permission:email.providers.manage');
};

$marketingPublic();
Route::middleware('admin.token')->prefix('admin')->group($marketingAdmin);
Route::prefix('v1')->group(function () use ($marketingPublic, $marketingAdmin) { $marketingPublic(); Route::middleware('admin.token')->prefix('admin')->group($marketingAdmin); });

/*
|--------------------------------------------------------------------------
| Affiliate / referral (2026-07-07 adaptation — additive, self-contained)
|--------------------------------------------------------------------------
| Public track is rate-limited; apply/dashboard require api.token; all admin
| endpoints require admin.token. No monetary field is client-trusted.
*/
Route::prefix('v1/affiliate')->group(function () {
    Route::post('/track', [\App\Http\Controllers\Api\Affiliate\AffiliateController::class, 'track'])->middleware('throttle:writes');
    Route::middleware('api.token')->group(function () {
        Route::post('/apply', [\App\Http\Controllers\Api\Affiliate\AffiliateController::class, 'apply'])->middleware('throttle:writes');
        Route::get('/dashboard', [\App\Http\Controllers\Api\Affiliate\AffiliateController::class, 'dashboard']);
    });
});

$affiliateAdmin = function () {
    Route::get('/affiliates', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'index']);
    Route::get('/affiliates/{affiliate}', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'show'])->whereNumber('affiliate');
    Route::post('/affiliates/{affiliate}/approve', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'approve'])->whereNumber('affiliate');
    Route::post('/affiliates/{affiliate}/suspend', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'suspend'])->whereNumber('affiliate');
    Route::get('/affiliate-commissions', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'commissions']);
    Route::post('/affiliate-commissions/{entry}/approve', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'approveCommission'])->whereNumber('entry');
    Route::post('/affiliate-commissions/{entry}/reverse', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'reverseCommission'])->whereNumber('entry');
    Route::get('/affiliate-payouts', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'payouts']);
    Route::post('/affiliate-payouts/{payout}/mark-paid', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'markPayoutPaid'])->whereNumber('payout');
    Route::get('/commission-rules', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'rules']);
    Route::post('/commission-rules', [\App\Http\Controllers\Api\Admin\AffiliateAdminController::class, 'storeRule']);
};
Route::middleware('admin.token')->prefix('admin')->group($affiliateAdmin);
Route::middleware('admin.token')->prefix('v1/admin')->group($affiliateAdmin);

/*
|--------------------------------------------------------------------------
| ERP procurement (2026-07-07 adaptation — additive, admin-only)
|--------------------------------------------------------------------------
| Suppliers + Purchase Orders. All PO totals server-computed. admin.token gated.
*/
$procurementAdmin = function () {
    Route::get('/suppliers', [\App\Http\Controllers\Api\Admin\ProcurementAdminController::class, 'suppliers']);
    Route::post('/suppliers', [\App\Http\Controllers\Api\Admin\ProcurementAdminController::class, 'storeSupplier']);
    Route::patch('/suppliers/{supplier}', [\App\Http\Controllers\Api\Admin\ProcurementAdminController::class, 'updateSupplier'])->whereNumber('supplier');
    Route::get('/purchase-orders', [\App\Http\Controllers\Api\Admin\ProcurementAdminController::class, 'purchaseOrders']);
    Route::post('/purchase-orders', [\App\Http\Controllers\Api\Admin\ProcurementAdminController::class, 'storePurchaseOrder']);
    Route::get('/purchase-orders/{order}', [\App\Http\Controllers\Api\Admin\ProcurementAdminController::class, 'showPurchaseOrder'])->whereNumber('order');
    Route::post('/purchase-orders/{order}/place', [\App\Http\Controllers\Api\Admin\ProcurementAdminController::class, 'placePurchaseOrder'])->whereNumber('order');
    Route::post('/purchase-orders/{order}/receive', [\App\Http\Controllers\Api\Admin\ProcurementAdminController::class, 'receivePurchaseOrder'])->whereNumber('order');
    Route::post('/purchase-orders/{order}/cancel', [\App\Http\Controllers\Api\Admin\ProcurementAdminController::class, 'cancelPurchaseOrder'])->whereNumber('order');
};
Route::middleware('admin.token')->prefix('admin')->group($procurementAdmin);
Route::middleware('admin.token')->prefix('v1/admin')->group($procurementAdmin);

/*
|--------------------------------------------------------------------------
| Coupons + Gift cards (2026-07-07 adaptation — additive)
|--------------------------------------------------------------------------
| Customer validate/check (api.token) — validate only, never mutate cart.
| Admin management (admin.token). Discounts computed server-side.
*/
Route::prefix('v1')->middleware('api.token')->group(function () {
    Route::post('/coupons/validate', [\App\Http\Controllers\Api\Promotion\PromotionController::class, 'validateCoupon'])->middleware('throttle:writes');
    Route::post('/gift-cards/check', [\App\Http\Controllers\Api\Promotion\PromotionController::class, 'checkGiftCard'])->middleware('throttle:writes');
    Route::post('/cart/apply-coupon', [\App\Http\Controllers\Api\Promotion\PromotionController::class, 'applyCoupon'])->middleware('throttle:writes');
    Route::delete('/cart/coupon', [\App\Http\Controllers\Api\Promotion\PromotionController::class, 'removeCoupon']);
    Route::post('/cart/apply-gift-card', [\App\Http\Controllers\Api\Promotion\PromotionController::class, 'applyGiftCard'])->middleware('throttle:writes');
});

$promotionAdmin = function () {
    Route::get('/coupons', [\App\Http\Controllers\Api\Admin\PromotionAdminController::class, 'coupons']);
    Route::post('/coupons', [\App\Http\Controllers\Api\Admin\PromotionAdminController::class, 'storeCoupon']);
    Route::patch('/coupons/{coupon}', [\App\Http\Controllers\Api\Admin\PromotionAdminController::class, 'updateCoupon'])->whereNumber('coupon');
    Route::get('/coupons/{coupon}/redemptions', [\App\Http\Controllers\Api\Admin\PromotionAdminController::class, 'couponRedemptions'])->whereNumber('coupon');
    Route::get('/gift-cards', [\App\Http\Controllers\Api\Admin\PromotionAdminController::class, 'giftCards']);
    Route::post('/gift-cards', [\App\Http\Controllers\Api\Admin\PromotionAdminController::class, 'storeGiftCard']);
    Route::post('/gift-cards/{giftCard}/disable', [\App\Http\Controllers\Api\Admin\PromotionAdminController::class, 'disableGiftCard'])->whereNumber('giftCard');
    Route::get('/gift-cards/{giftCard}/transactions', [\App\Http\Controllers\Api\Admin\PromotionAdminController::class, 'giftCardTransactions'])->whereNumber('giftCard');
};
Route::middleware('admin.token')->prefix('admin')->group($promotionAdmin);
Route::middleware('admin.token')->prefix('v1/admin')->group($promotionAdmin);

/*
|--------------------------------------------------------------------------
| ERP B2B: RFQ + Quotations (2026-07-07 adaptation — additive)
|--------------------------------------------------------------------------
| Customer RFQ submit + quote accept (api.token, ownership-checked).
| Admin RFQ review + quotation issue/send (admin.token). Totals server-side.
*/
Route::prefix('v1')->middleware('api.token')->group(function () {
    Route::post('/rfq', [\App\Http\Controllers\Api\Sales\RfqController::class, 'submit'])->middleware('throttle:writes');
    Route::get('/rfq', [\App\Http\Controllers\Api\Sales\RfqController::class, 'index']);
    Route::get('/quotations', [\App\Http\Controllers\Api\Sales\RfqController::class, 'quotes']);
    Route::get('/quotations/{quotation}', [\App\Http\Controllers\Api\Sales\RfqController::class, 'showQuote'])->whereNumber('quotation');
    Route::post('/quotations/{quotation}/accept', [\App\Http\Controllers\Api\Sales\RfqController::class, 'acceptQuote'])->whereNumber('quotation')->middleware('throttle:writes');
});

$salesAdmin = function () {
    Route::get('/rfq', [\App\Http\Controllers\Api\Admin\QuotationAdminController::class, 'rfqs']);
    Route::get('/rfq/{rfq}', [\App\Http\Controllers\Api\Admin\QuotationAdminController::class, 'showRfq'])->whereNumber('rfq');
    Route::get('/quotations', [\App\Http\Controllers\Api\Admin\QuotationAdminController::class, 'quotations']);
    Route::post('/quotations', [\App\Http\Controllers\Api\Admin\QuotationAdminController::class, 'storeQuotation']);
    Route::get('/quotations/{quotation}', [\App\Http\Controllers\Api\Admin\QuotationAdminController::class, 'showQuotation'])->whereNumber('quotation');
    Route::post('/quotations/{quotation}/send', [\App\Http\Controllers\Api\Admin\QuotationAdminController::class, 'sendQuotation'])->whereNumber('quotation');
    Route::post('/quotations/{quotation}/reject', [\App\Http\Controllers\Api\Admin\QuotationAdminController::class, 'rejectQuotation'])->whereNumber('quotation');
};
Route::middleware('admin.token')->prefix('admin')->group($salesAdmin);
Route::middleware('admin.token')->prefix('v1/admin')->group($salesAdmin);

/*
|--------------------------------------------------------------------------
| ERP finance: expenses + back-office reports (2026-07-07 — additive, admin)
|--------------------------------------------------------------------------
| Expense tracking + read-only procurement/quotation/expense/supplier reports.
*/
$financeAdmin = function () {
    Route::get('/expenses', [\App\Http\Controllers\Api\Admin\FinanceAdminController::class, 'expenses']);
    Route::post('/expenses', [\App\Http\Controllers\Api\Admin\FinanceAdminController::class, 'storeExpense']);
    Route::patch('/expenses/{expense}', [\App\Http\Controllers\Api\Admin\FinanceAdminController::class, 'updateExpense'])->whereNumber('expense');
    Route::get('/reports/procurement', [\App\Http\Controllers\Api\Admin\FinanceAdminController::class, 'reportProcurement']);
    Route::get('/reports/supplier-spend', [\App\Http\Controllers\Api\Admin\FinanceAdminController::class, 'reportSupplierSpend']);
    Route::get('/reports/quotations', [\App\Http\Controllers\Api\Admin\FinanceAdminController::class, 'reportQuotations']);
    Route::get('/reports/expenses', [\App\Http\Controllers\Api\Admin\FinanceAdminController::class, 'reportExpenses']);
};
Route::middleware('admin.token')->prefix('admin')->group($financeAdmin);
Route::middleware('admin.token')->prefix('v1/admin')->group($financeAdmin);

$productMediaAdmin = function () {
    Route::get('/products/{product}/images', [\App\Http\Controllers\Api\Admin\ProductImageAdminController::class, 'index'])->whereNumber('product')->middleware('admin.permission:catalog.manage');
    Route::post('/products/{product}/images', [\App\Http\Controllers\Api\Admin\ProductImageAdminController::class, 'store'])->whereNumber('product')->middleware(['admin.permission:catalog.manage', 'throttle:writes']);
    Route::patch('/products/{product}/images/reorder', [\App\Http\Controllers\Api\Admin\ProductImageAdminController::class, 'reorder'])->whereNumber('product')->middleware(['admin.permission:catalog.manage', 'throttle:writes']);
    Route::patch('/products/{product}/images/{image}', [\App\Http\Controllers\Api\Admin\ProductImageAdminController::class, 'update'])->whereNumber(['product', 'image'])->middleware(['admin.permission:catalog.manage', 'throttle:writes']);
    Route::post('/products/{product}/images/{image}/primary', [\App\Http\Controllers\Api\Admin\ProductImageAdminController::class, 'primary'])->whereNumber(['product', 'image'])->middleware(['admin.permission:catalog.manage', 'throttle:writes']);
    Route::delete('/products/{product}/images/{image}', [\App\Http\Controllers\Api\Admin\ProductImageAdminController::class, 'destroy'])->whereNumber(['product', 'image'])->middleware(['admin.permission:catalog.manage', 'throttle:writes']);
};
Route::middleware('admin.token')->prefix('admin')->group($productMediaAdmin);
Route::middleware('admin.token')->prefix('v1/admin')->group($productMediaAdmin);

/*
|--------------------------------------------------------------------------
| Public seller onboarding + commerce AI aliases
|--------------------------------------------------------------------------
| Exact /api/* paths requested by the public site brief. Versioned /api/v1/*
| routes above remain canonical for existing API clients.
*/
Route::post('/seller-applications', [SellerApplicationController::class, 'store'])->middleware('throttle:writes');
Route::post('/distributor-applications', [PublicDistributorApplicationController::class, 'store'])->middleware('throttle:writes');

Route::prefix('commerce-ai')->group(function () {
    Route::get('/examples', [CommerceAiDemoController::class, 'examples']);
    Route::post('/session', [CommerceAiDemoController::class, 'session'])->middleware('throttle:writes');
    Route::post('/message', [CommerceAiDemoController::class, 'message'])->middleware('throttle:writes');
    Route::post('/build-bom', [CommerceAiDemoController::class, 'buildBom'])->middleware('throttle:writes');
});

Route::middleware('admin.token')->prefix('admin')->group(function () {
    Route::get('/seller-applications', [OnboardingAdminController::class, 'sellerApplications']);
    Route::get('/seller-applications/{application}', [OnboardingAdminController::class, 'sellerApplication'])->whereNumber('application');
    Route::patch('/seller-applications/{application}/status', [OnboardingAdminController::class, 'updateSellerStatus'])->whereNumber('application')->middleware('throttle:writes');
    Route::post('/seller-applications/{application}/convert-to-vendor', [OnboardingAdminController::class, 'convertSellerToVendor'])->whereNumber('application')->middleware('throttle:writes');
    Route::get('/distributor-applications', [OnboardingAdminController::class, 'distributorApplications']);
    Route::get('/distributor-applications/{application}', [OnboardingAdminController::class, 'distributorApplication'])->whereNumber('application');
    Route::patch('/distributor-applications/{application}/status', [OnboardingAdminController::class, 'updateDistributorStatus'])->whereNumber('application')->middleware('throttle:writes');
    Route::post('/distributor-applications/{application}/convert-to-distributor', [OnboardingAdminController::class, 'convertDistributor'])->whereNumber('application')->middleware('throttle:writes');
    Route::get('/dashboard/seller-onboarding-summary', [OnboardingAdminController::class, 'sellerOnboardingSummary']);
    Route::get('/dashboard/ai-commerce-summary', [OnboardingAdminController::class, 'aiCommerceSummary']);
    Route::get('/products', [ProductAdminController::class, 'index']);
    Route::get('/products/pending', [ProductAdminController::class, 'pending']);
    Route::get('/products/{product}', [ProductAdminController::class, 'show'])->whereNumber('product');
    Route::post('/products/{product}/approve', [ProductAdminController::class, 'approve'])->whereNumber('product')->middleware('throttle:writes');
    Route::post('/products/{product}/reject', [ProductAdminController::class, 'reject'])->whereNumber('product')->middleware('throttle:writes');
    Route::get('/product-generic-groups', [ProductAdminController::class, 'genericGroups']);
    Route::post('/product-generic-groups', [ProductAdminController::class, 'storeGenericGroup'])->middleware('throttle:writes');
    Route::post('/products/{product}/generic-suggestions', [ProductAdminController::class, 'storeGenericSuggestion'])->whereNumber('product')->middleware('throttle:writes');
    Route::patch('/product-generic-suggestions/{suggestion}', [ProductAdminController::class, 'updateGenericSuggestion'])->whereNumber('suggestion')->middleware('throttle:writes');
    Route::delete('/product-generic-suggestions/{suggestion}', [ProductAdminController::class, 'deleteGenericSuggestion'])->whereNumber('suggestion')->middleware('throttle:writes');
});

/*
|--------------------------------------------------------------------------
| Auth aliases for public/customer/seller/distributor portals
|--------------------------------------------------------------------------
| Exact /api/* paths requested by the commerce foundation brief. The
| versioned /api/v1/* routes remain active above.
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [PublicAuthController::class, 'register'])->middleware('throttle:writes');
    Route::post('/login', [PublicAuthController::class, 'login'])->middleware('throttle:writes');
    Route::middleware('api.token')->group(function () {
        Route::get('/me', [PublicAuthController::class, 'me']);
        Route::post('/logout', [PublicAuthController::class, 'logout']);
    });
});

Route::prefix('seller')->group(function () {
    Route::post('/register', [SellerAuthController::class, 'register'])->middleware('throttle:writes');
    Route::post('/login', [SellerAuthController::class, 'login'])->middleware('throttle:writes');
    Route::middleware(['api.token', 'permission:seller.access'])->group(function () {
        Route::get('/me', [SellerAuthController::class, 'me']);
        Route::post('/logout', [SellerAuthController::class, 'logout']);
    });
});

Route::prefix('distributor')->group(function () {
    Route::post('/register', [DistributorAuthController::class, 'register'])->middleware('throttle:writes');
    Route::post('/login', [DistributorAuthController::class, 'login'])->middleware('throttle:writes');
    Route::middleware(['api.token', 'permission:distributor.access'])->group(function () {
        Route::get('/me', [DistributorAuthController::class, 'me']);
        Route::post('/logout', [DistributorAuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Payments abstraction: wallet + providers + vendor payouts (2026-07-07)
|--------------------------------------------------------------------------
| Additive. Customer wallet is read-only (api.token). Admin manages providers
| (no secrets), audit events, wallet adjustments, and vendor payouts (admin.token).
*/
Route::prefix('v1')->middleware('api.token')->group(function () {
    Route::get('/wallet', [\App\Http\Controllers\Api\Wallet\WalletController::class, 'show']);
    Route::get('/wallet/ledger', [\App\Http\Controllers\Api\Wallet\WalletController::class, 'ledger']);
});

$paymentsAdmin = function () {
    Route::get('/payment-providers', [\App\Http\Controllers\Api\Admin\PaymentAdminController::class, 'providers']);
    Route::patch('/payment-providers/{provider}', [\App\Http\Controllers\Api\Admin\PaymentAdminController::class, 'updateProvider'])->whereNumber('provider');
    Route::get('/payments/events', [\App\Http\Controllers\Api\Admin\PaymentAdminController::class, 'events']);
    Route::post('/wallets/adjust', [\App\Http\Controllers\Api\Admin\PaymentAdminController::class, 'adjustWallet']);
    Route::get('/vendor-payouts', [\App\Http\Controllers\Api\Admin\PaymentAdminController::class, 'vendorPayouts']);
    Route::post('/vendor-payouts', [\App\Http\Controllers\Api\Admin\PaymentAdminController::class, 'storeVendorPayout']);
    Route::post('/vendor-payouts/{vendorPayout}/approve', [\App\Http\Controllers\Api\Admin\PaymentAdminController::class, 'approveVendorPayout'])->whereNumber('vendorPayout');
    Route::post('/vendor-payouts/{vendorPayout}/mark-paid', [\App\Http\Controllers\Api\Admin\PaymentAdminController::class, 'markVendorPayoutPaid'])->whereNumber('vendorPayout');
};
Route::middleware('admin.token')->prefix('admin')->group($paymentsAdmin);
Route::middleware('admin.token')->prefix('v1/admin')->group($paymentsAdmin);

/*
|--------------------------------------------------------------------------
| Region Stock Visibility & Territory Allocation (2026-07-08)
|--------------------------------------------------------------------------
| Territory-based stock visibility, allocations, reservations, and alerts.
| Admin manages rules; sellers see their stock; distributors see territory stock.
*/
$regionStockAdmin = function () {
    // Region stock visibility rules
    Route::apiResource('region-stock-visibilities', \App\Http\Controllers\RegionStockVisibilityController::class);
    
    // Territory stock allocations
    Route::get('/territory-allocations', [\App\Http\Controllers\TerritoryStockAllocationController::class, 'index']);
    Route::post('/territory-allocations', [\App\Http\Controllers\TerritoryStockAllocationController::class, 'store']);
    Route::get('/territory-allocations/{id}', [\App\Http\Controllers\TerritoryStockAllocationController::class, 'show'])->whereNumber('id');
    Route::put('/territory-allocations/{id}', [\App\Http\Controllers\TerritoryStockAllocationController::class, 'update'])->whereNumber('id');
    Route::delete('/territory-allocations/{id}', [\App\Http\Controllers\TerritoryStockAllocationController::class, 'destroy'])->whereNumber('id');
    
    // Stock reservations
    Route::get('/stock-reservations', [\App\Http\Controllers\StockReservationController::class, 'index']);
    Route::post('/stock-reservations', [\App\Http\Controllers\StockReservationController::class, 'store']);
    Route::get('/stock-reservations/{id}', [\App\Http\Controllers\StockReservationController::class, 'show'])->whereNumber('id');
    Route::patch('/stock-reservations/{id}/confirm', [\App\Http\Controllers\StockReservationController::class, 'confirm'])->whereNumber('id');
    Route::patch('/stock-reservations/{id}/cancel', [\App\Http\Controllers\StockReservationController::class, 'cancel'])->whereNumber('id');
    
    // Low stock alerts
    Route::get('/low-stock-alerts', [\App\Http\Controllers\LowStockAlertController::class, 'index']);
    Route::post('/low-stock-alerts', [\App\Http\Controllers\LowStockAlertController::class, 'store']);
    Route::get('/low-stock-alerts/{id}', [\App\Http\Controllers\LowStockAlertController::class, 'show'])->whereNumber('id');
    Route::patch('/low-stock-alerts/{id}', [\App\Http\Controllers\LowStockAlertController::class, 'update'])->whereNumber('id');
    Route::delete('/low-stock-alerts/{id}', [\App\Http\Controllers\LowStockAlertController::class, 'destroy'])->whereNumber('id');
};
Route::middleware('admin.token')->prefix('v1/admin')->group($regionStockAdmin);

// Public/Authenticated: Get visible stock for current marketplace/region
Route::middleware('api.token')->prefix('v1')->group(function () {
    Route::get('/stock/visible', [\App\Http\Controllers\RegionStockVisibilityController::class, 'visibleStock']);
    Route::get('/stock/territory/{distributorId}', [\App\Http\Controllers\TerritoryStockAllocationController::class, 'territoryStock']);
});

/*
|--------------------------------------------------------------------------
| Password reset + email verification (2026-07-09)
|--------------------------------------------------------------------------
| Cherry-picked (reviewed) from the git-repository-access branch WITHOUT its
| Sanctum parts: standard Laravel Password broker + signed verification URLs
| against the existing custom-token auth. Additive.
*/
Route::prefix('v1/auth')->group(function () {
    Route::post('/forgot-password', [\App\Http\Controllers\Api\Auth\ForgotPasswordController::class, 'sendResetLink'])->middleware('throttle:writes');
    Route::post('/reset-password', [\App\Http\Controllers\Api\Auth\ResetPasswordController::class, 'reset'])->middleware('throttle:writes');

    Route::middleware(['api.token', 'throttle:6,1'])->group(function () {
        Route::post('/email/verification-notification', [\App\Http\Controllers\Api\Auth\EmailVerificationController::class, 'sendVerification'])->name('verification.send');
    });
    Route::get('/verify-email/{id}/{hash}', [\App\Http\Controllers\Api\Auth\EmailVerificationController::class, 'verify'])
        ->whereNumber('id')->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
});

/*
|--------------------------------------------------------------------------
| Customer support / chat (2026-07-10)
|--------------------------------------------------------------------------
| Customer side of the existing support module (admin inbox: /admin/support;
| seller side: SellerSupportTicketController). Ownership-checked, append-only
| transcript, AI-handoff placeholder via metadata.needs_human.
*/
Route::prefix('v1/support')->middleware('api.token')->group(function () {
    Route::get('/tickets', [\App\Http\Controllers\Api\Support\CustomerSupportController::class, 'index']);
    Route::post('/tickets', [\App\Http\Controllers\Api\Support\CustomerSupportController::class, 'store'])->middleware('throttle:writes');
    Route::get('/tickets/{id}', [\App\Http\Controllers\Api\Support\CustomerSupportController::class, 'show'])->whereNumber('id');
    Route::post('/tickets/{id}/messages', [\App\Http\Controllers\Api\Support\CustomerSupportController::class, 'reply'])->whereNumber('id')->middleware('throttle:writes');
    Route::post('/tickets/{id}/request-human', [\App\Http\Controllers\Api\Support\CustomerSupportController::class, 'requestHuman'])->whereNumber('id')->middleware('throttle:writes');
});

/*
|--------------------------------------------------------------------------
| Product reviews (2026-07-10)
|--------------------------------------------------------------------------
| Public read of APPROVED reviews + rating aggregate; authenticated submit
| lands in the /admin/reviews moderation queue (never auto-published).
*/
Route::prefix('v1/products/{product}')->group(function () {
    Route::get('/reviews', [\App\Http\Controllers\Api\Product\ProductReviewController::class, 'index'])->whereNumber('product');
    Route::post('/reviews', [\App\Http\Controllers\Api\Product\ProductReviewController::class, 'store'])->whereNumber('product')->middleware(['api.token', 'throttle:writes']);
});

// Add PCB routes before the final closing brackets
Route::prefix('v1/pcb')->middleware('api.token')->group(function () {
    // PCB Projects
    Route::apiResource('projects', \App\Http\Controllers\Pcb\PcbProjectController::class);
    Route::get('projects/{project}/activity', [\App\Http\Controllers\Pcb\PcbProjectController::class, 'activity']);
    
    // PCB Files (to be implemented)
    // Route::post('projects/{project}/files', [PcbFileController::class, 'store']);
    // Route::get('files/{file}/download', [PcbFileController::class, 'download']);
    
    // PCB Quotes (to be implemented)
    // Route::post('projects/{project}/quote', [PcbQuoteController::class, 'create']);
    // Route::get('quotes/{quote}', [PcbQuoteController::class, 'show']);
});

// Public PCB endpoints (no auth required for initial quote)
// Public PCB routes — no auth required, rate-limited
Route::prefix('v1')->group(function () {
    Route::post('quote/calculate', [\App\Http\Controllers\Pcb\PcbPublicQuoteController::class, 'calculate'])
        ->middleware('throttle:60,1')
        ->name('api.pcb.quote.calculate');
});

Route::prefix('v1/pcb/public')->group(function () {
    Route::post('quote/calculate', [\App\Http\Controllers\Pcb\PcbPublicQuoteController::class, 'calculate'])
        ->middleware('throttle:60,1');
    Route::get('capabilities', [\App\Http\Controllers\Pcb\PcbCapabilitiesController::class, 'index']);
});
