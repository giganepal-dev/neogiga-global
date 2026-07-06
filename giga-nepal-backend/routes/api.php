<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Marketplace\MarketplaceController;
use App\Http\Controllers\Api\Product\CategoryController;
use App\Http\Controllers\Api\Product\BrandController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\Vendor\VendorController;
use App\Http\Controllers\Api\Inventory\InventoryController;
use App\Http\Controllers\Api\Cart\CartController;
use App\Http\Controllers\Api\Order\OrderController;
use App\Http\Controllers\Api\AI\AiCommerceController;
use App\Http\Controllers\Api\POS\PosController;
use App\Http\Controllers\Api\LMS\LmsController;
use App\Http\Controllers\Api\Admin\ImportExportController;

/*
|--------------------------------------------------------------------------
| API Routes for NeoGiga Marketplace — v1
|--------------------------------------------------------------------------
| Versioned per Blueprint §8. All routes inherit throttle:api (60/min);
| anonymous writes get the stricter `writes` limiter; /admin/* requires
| the admin token gate until Sanctum + RBAC land in Phase 1.
*/

Route::prefix('v1')->group(function () {

    // Auth (Phase 1 foundation): first-party bearer token auth.
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:writes');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:writes');
        Route::middleware('api.token')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Marketplace resolution (public, read-only)
    Route::prefix('marketplaces')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index']);
        Route::get('/current', [MarketplaceController::class, 'current']);
        Route::get('/by-domain', [MarketplaceController::class, 'byDomain']);
    });

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

    // Inventory (availability reads public; mutations Phase 1)
    Route::prefix('inventory')->group(function () {
        Route::get('/product/{product}', [InventoryController::class, 'byProduct'])->whereNumber('product');
        Route::get('/marketplace/{marketplace}', [InventoryController::class, 'byMarketplace'])->whereNumber('marketplace');
        Route::get('/warehouse/{warehouse}', [InventoryController::class, 'byWarehouse'])->whereNumber('warehouse');
        Route::post('/reserve', [InventoryController::class, 'reserve'])->middleware('api.token');
        Route::post('/release-reservation', [InventoryController::class, 'releaseReservation'])->middleware('api.token');
    });

    // Commerce (contract stable; 501 until Phase 1 — requires auth + payments)
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

    // AI Commerce (contract stable; 501 until Phase 2 orchestrator)
    Route::prefix('ai')->group(function () {
        Route::post('/session', [AiCommerceController::class, 'createSession']);
        Route::post('/message', [AiCommerceController::class, 'sendMessage']);
        Route::post('/build-bom', [AiCommerceController::class, 'buildBom']);
        Route::post('/add-bom-to-cart', [AiCommerceController::class, 'addBomToCart']);
        Route::post('/create-pos-invoice', [AiCommerceController::class, 'createPosInvoice']);
    });

    // POS (contract stable; 501 until device auth + payments)
    Route::prefix('pos')->group(function () {
        Route::post('/sessions/open', [PosController::class, 'openSession']);
        Route::post('/sessions/close', [PosController::class, 'closeSession']);
        Route::get('/products/search', [PosController::class, 'searchProducts']);
        Route::post('/sales', [PosController::class, 'createSale']);
        Route::get('/sales/{sale}', [PosController::class, 'showSale'])->whereNumber('sale');
        Route::post('/sales/{sale}/payment', [PosController::class, 'processPayment'])->whereNumber('sale');
        Route::post('/sales/{sale}/refund', [PosController::class, 'processRefund'])->whereNumber('sale');
    });

    // LMS (501 until schema reconciliation)
    Route::prefix('lms')->group(function () {
        Route::get('/courses', [LmsController::class, 'courses']);
        Route::get('/projects', [LmsController::class, 'projects']);
        Route::get('/projects/{slug}', [LmsController::class, 'showProject']);
        Route::get('/projects/{slug}/components', [LmsController::class, 'projectComponents']);
        Route::get('/projects/{slug}/code-samples', [LmsController::class, 'projectCodeSamples']);
    });

    // Admin (fail-closed token gate; replace with Sanctum + RBAC in Phase 1)
    Route::middleware('admin.token')->group(function () {
        Route::prefix('admin/imports')->group(function () {
            Route::post('/dry-run', [ImportExportController::class, 'dryRun']);
            Route::post('/execute', [ImportExportController::class, 'execute']);
            Route::get('/{import}', [ImportExportController::class, 'show'])->whereNumber('import');
            Route::get('/{import}/errors', [ImportExportController::class, 'errors'])->whereNumber('import');
        });

        Route::prefix('admin/exports')->group(function () {
            Route::post('/create', [ImportExportController::class, 'createExport']);
        });
    });
});
