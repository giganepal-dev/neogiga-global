<?php

use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Admin\CommerceOpsController as AdminCommerce;
use App\Http\Controllers\Admin\CustomerDataController as AdminCustomerData;
use App\Http\Controllers\Admin\CustomerImportController as AdminCustomerImport;
use App\Http\Controllers\Admin\DashboardController as AdminDash;
use App\Http\Controllers\Admin\ElecforestImportController as AdminElecforestImport;
use App\Http\Controllers\Admin\MarketingActionController as AdminMarketing;
use App\Http\Controllers\Admin\MarketplaceConfigController as AdminMarketplaceConfig;
use App\Http\Controllers\Admin\PartnerApprovalsController;
use App\Http\Controllers\Admin\PcbAdminController as AdminPcb;
use App\Http\Controllers\Admin\PosAdminController;
use App\Http\Controllers\Admin\PricingAdminController;
use App\Http\Controllers\Admin\ProductImageController as AdminProductImage;
use App\Http\Controllers\Admin\SmdAdminController;
use App\Http\Controllers\Api\Onboarding\DistributorApplicationController as PublicDistributorApplicationController;
use App\Http\Controllers\Api\Onboarding\PartnerCountryController;
use App\Http\Controllers\Api\Onboarding\SellerApplicationController as PublicSellerApplicationController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Pcb\PcbPublicQuoteController;
use App\Http\Controllers\Web\AiCommercePageController;
use App\Http\Controllers\Web\B2B\B2BPortalController;
use App\Http\Controllers\Web\BomPageController;
use App\Http\Controllers\Web\BrandPageController;
use App\Http\Controllers\Web\CartPageController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CompareController;
use App\Http\Controllers\Web\CustomerAuthController;
use App\Http\Controllers\Web\CustomerDashboardController;
use App\Http\Controllers\Web\Distributor\DistributorPortalController;
use App\Http\Controllers\Web\EmailPreferenceController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\LmsPageController;
use App\Http\Controllers\Web\Manufacturer\ManufacturerPortalController;
use App\Http\Controllers\Web\MarketplaceLandingController;
use App\Http\Controllers\Web\MarketplacePreferenceController;
use App\Http\Controllers\Web\OrderTrackingController;
use App\Http\Controllers\Web\PasswordResetController;
use App\Http\Controllers\Web\PcbPortalAuthController;
use App\Http\Controllers\Web\PcbPortalController;
use App\Http\Controllers\Web\POS\PosCashierController;
use App\Http\Controllers\Web\POS\PosReceiptController;
use App\Http\Controllers\Web\ProductPageController;
use App\Http\Controllers\Web\RedesignController;
use App\Http\Controllers\Web\Reseller\ResellerPortalController;
use App\Http\Controllers\Web\RfqPageController;
use App\Http\Controllers\Web\Seller\SellerPortalController;
use App\Http\Controllers\Web\SellOnNeoGigaController;
use App\Http\Controllers\Web\SeoLandingController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\SsoController;
use App\Http\Controllers\Web\TwoFactorController;
use App\Http\Middleware\CanonicalizeRegionalMarketplacePath;
use App\Http\Middleware\EnsureB2BWeb;
use App\Http\Middleware\EnsureDistributorWeb;
use App\Http\Middleware\EnsureManufacturerWeb;
use App\Http\Middleware\EnsureResellerWeb;
use App\Http\Middleware\EnsureSellerWeb;
use App\Services\CommerceAi\CommerceAiService;
use App\Services\Lms\CourseCatalogService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
| Admin console (server-rendered). Reachable at /admin on any host; on
| admin.neogiga.com the root redirects to it. Registered BEFORE the public
| landing route so the admin host resolves to the console, not the landing.
*/
Route::domain('admin.neogiga.com')->get('/', fn () => redirect('/admin'));
Route::get('/partner-country-options', [PartnerCountryController::class, 'index'])->middleware('throttle:60,1');
Route::post('/partner-applications/seller', [PublicSellerApplicationController::class, 'store'])->middleware('throttle:writes');
Route::post('/partner-applications/distributor', [PublicDistributorApplicationController::class, 'store'])->middleware('throttle:writes');

// PCB Platform — pcb.neogiga.com
if (config('pcb.enabled', true)) {
    Route::domain(config('pcb.domain', 'pcb.neogiga.com'))->group(function () {
        Route::get('/', fn () => redirect('/en'))->name('pcb.root');
        Route::get('/en', [PcbPortalController::class, 'landing'])->name('pcb.home');
        Route::get('/en/login', [PcbPortalAuthController::class, 'login'])->name('pcb.login');
        Route::post('/en/login', [PcbPortalAuthController::class, 'authenticate'])->middleware('throttle:6,1')->name('pcb.login.store');
        Route::get('/en/register', [PcbPortalAuthController::class, 'register'])->name('pcb.register');
        Route::post('/en/register', [PcbPortalAuthController::class, 'store'])->middleware('throttle:6,1')->name('pcb.register.store');
        Route::get('/en/capabilities', [PcbPublicQuoteController::class, 'capabilities'])->name('pcb.capabilities');
        Route::get('/en/design-rules', [PcbPublicQuoteController::class, 'designRules'])->name('pcb.design-rules');
        Route::middleware('pcb.auth')->group(function () {
            Route::post('/en/logout', [PcbPortalAuthController::class, 'logout'])->name('pcb.logout');
            Route::get('/en/projects', [PcbPortalController::class, 'index'])->name('pcb.projects.index');
            Route::get('/en/projects/create', [PcbPortalController::class, 'create'])->name('pcb.projects.create');
            Route::post('/en/projects', [PcbPortalController::class, 'store'])->middleware('throttle:10,1')->name('pcb.projects.store');
            Route::get('/en/projects/{project}', [PcbPortalController::class, 'show'])->name('pcb.projects.show');
            Route::patch('/en/projects/{project}', [PcbPortalController::class, 'update'])->middleware('throttle:20,1')->name('pcb.projects.update');
            Route::post('/en/projects/{project}/cancel', [PcbPortalController::class, 'cancel'])->middleware('throttle:10,1')->name('pcb.projects.cancel');
            Route::post('/en/projects/{project}/files', [PcbPortalController::class, 'upload'])->middleware('throttle:10,1')->name('pcb.files.store');
            Route::get('/en/projects/{project}/files/{file}/download', [PcbPortalController::class, 'download'])->middleware('signed')->name('pcb.files.download');
            Route::post('/en/projects/{project}/quotes', [PcbPortalController::class, 'submitQuote'])->middleware('throttle:10,1')->name('pcb.quotes.store');
            Route::post('/en/projects/{project}/quotes/{quote}/approve', [PcbPortalController::class, 'approveQuote'])->middleware('throttle:10,1')->name('pcb.quotes.approve');
            Route::post('/en/projects/{project}/quotes/{quote}/reject', [PcbPortalController::class, 'rejectQuote'])->middleware('throttle:10,1')->name('pcb.quotes.reject');
        });
    });
}

Route::get('/health', HealthController::class)->withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    ValidateCsrfToken::class,
]);

// Security vulnerability disclosure (RFC 9116)
Route::redirect('/security.txt', '/.well-known/security.txt', 301);

// Email preference management
Route::get('/email/unsubscribe/{token}', [EmailPreferenceController::class, 'unsubscribe'])
    ->middleware('throttle:60,1')->name('email.unsubscribe');
Route::post('/email/unsubscribe/{token}', [EmailPreferenceController::class, 'confirmUnsubscribe'])
    ->middleware('throttle:10,1')->name('email.unsubscribe.confirm');
Route::get('/email/preferences/{token}', [EmailPreferenceController::class, 'preferences'])
    ->middleware('throttle:60,1')->name('email.preferences');
Route::patch('/email/preferences/{token}', [EmailPreferenceController::class, 'updatePreferences'])
    ->middleware('throttle:10,1')->name('email.preferences.update');

// Seller web portal (session guard mirrors the admin console; vendor scope
// enforced by seller.web / SellerContextService — sellers see only their data).
Route::prefix('seller')->group(function () {
    Route::get('login', [SellerPortalController::class, 'showLogin'])->name('seller.login');
    Route::post('login', [SellerPortalController::class, 'login'])->middleware('throttle:6,1');
    Route::post('logout', [SellerPortalController::class, 'logout']);

    Route::get('/', [SellerPortalController::class, 'dashboard'])->middleware(EnsureSellerWeb::class);
    Route::get('profile', [SellerPortalController::class, 'profile'])->middleware(EnsureSellerWeb::class);
    Route::post('profile', [SellerPortalController::class, 'updateProfile'])->middleware(EnsureSellerWeb::class);
    Route::get('orders', [SellerPortalController::class, 'orders'])->middleware(EnsureSellerWeb::class);
    Route::get('products', [SellerPortalController::class, 'products'])->middleware(EnsureSellerWeb::class);
    Route::get('inventory', [SellerPortalController::class, 'inventory'])->middleware(EnsureSellerWeb::class);
    Route::get('payouts', [SellerPortalController::class, 'payouts'])->middleware(EnsureSellerWeb::class);
    Route::get('support', [SellerPortalController::class, 'support'])->middleware(EnsureSellerWeb::class);
    Route::post('support', [SellerPortalController::class, 'storeSupport'])->middleware([EnsureSellerWeb::class, 'throttle:10,1']);
});

// Reseller web portal
Route::prefix('reseller')->group(function () {
    Route::get('apply', [ResellerPortalController::class, 'showApply'])->name('reseller.apply');
    Route::post('apply', [ResellerPortalController::class, 'storeApply'])->middleware('throttle:6,1');
    Route::get('login', [ResellerPortalController::class, 'showLogin'])->name('reseller.login');
    Route::post('login', [ResellerPortalController::class, 'login'])->middleware('throttle:6,1');
    Route::post('logout', [ResellerPortalController::class, 'logout']);
    Route::middleware(EnsureResellerWeb::class)->group(function () {
        Route::get('/', [ResellerPortalController::class, 'dashboard']);
        Route::get('profile', [ResellerPortalController::class, 'profile']);
        Route::post('profile', [ResellerPortalController::class, 'updateProfile']);
        Route::get('products', [ResellerPortalController::class, 'products']);
        Route::get('products/create', [ResellerPortalController::class, 'createProduct']);
        Route::post('products', [ResellerPortalController::class, 'storeProduct']);
        Route::post('products/import', [ResellerPortalController::class, 'importProducts']);
        Route::get('orders', [ResellerPortalController::class, 'orders']);
        Route::get('rfqs', [ResellerPortalController::class, 'rfqs']);
        Route::post('rfqs/{assignment}/bid', [ResellerPortalController::class, 'bidRfq'])->whereNumber('assignment');
        Route::get('territories', [ResellerPortalController::class, 'territories']);
        Route::post('territories/request', [ResellerPortalController::class, 'requestTerritory']);
        Route::get('support', [ResellerPortalController::class, 'support']);
        Route::post('support', [ResellerPortalController::class, 'storeSupport']);
        Route::get('messages', [ResellerPortalController::class, 'messages']);
        Route::get('messages/{conversation}', [ResellerPortalController::class, 'showMessage'])->whereNumber('conversation');
        Route::post('messages/{conversation}', [ResellerPortalController::class, 'replyMessage'])->whereNumber('conversation');
    });
});

// Manufacturer web portal
Route::prefix('manufacturer')->group(function () {
    Route::get('login', [ManufacturerPortalController::class, 'showLogin'])->name('manufacturer.login');
    Route::post('login', [ManufacturerPortalController::class, 'login'])->middleware('throttle:6,1');
    Route::post('logout', [ManufacturerPortalController::class, 'logout']);

    Route::middleware(EnsureManufacturerWeb::class)->group(function () {
        Route::get('/', [ManufacturerPortalController::class, 'dashboard']);
        Route::get('profile', [ManufacturerPortalController::class, 'profile']);
        Route::post('profile', [ManufacturerPortalController::class, 'updateProfile']);
        Route::get('products', [ManufacturerPortalController::class, 'products']);
        Route::get('inventory', [ManufacturerPortalController::class, 'inventory']);
        Route::post('inventory/sync', [ManufacturerPortalController::class, 'syncInventory'])->middleware('throttle:10,1');
        Route::get('allocations', [ManufacturerPortalController::class, 'allocations']);
        Route::post('allocations', [ManufacturerPortalController::class, 'storeAllocation'])->middleware('throttle:20,1');
    });
});

// B2B / Business Customer portal
Route::prefix('b2b')->group(function () {
    Route::get('apply', [B2BPortalController::class, 'showApply'])->name('b2b.apply');
    Route::post('apply', [B2BPortalController::class, 'storeApply'])->middleware('throttle:6,1');
    Route::get('login', [B2BPortalController::class, 'showLogin'])->name('b2b.login');
    Route::post('login', [B2BPortalController::class, 'login'])->middleware('throttle:6,1');
    Route::post('logout', [B2BPortalController::class, 'logout']);
    Route::get('/', [B2BPortalController::class, 'dashboard'])->middleware(EnsureB2BWeb::class);
    Route::get('orders', [B2BPortalController::class, 'orders'])->middleware(EnsureB2BWeb::class);
    Route::get('rfqs', [B2BPortalController::class, 'rfqs'])->middleware(EnsureB2BWeb::class);
    Route::get('rfqs/create', [B2BPortalController::class, 'createRfq'])->middleware(EnsureB2BWeb::class);
    Route::post('rfqs', [B2BPortalController::class, 'storeRfq'])->middleware(EnsureB2BWeb::class);
    Route::get('quotations', [B2BPortalController::class, 'quotations'])->middleware(EnsureB2BWeb::class);
    Route::get('quotations/{quotation}', [B2BPortalController::class, 'showQuotation'])->whereNumber('quotation')->middleware(EnsureB2BWeb::class);
    Route::post('quotations/{quotation}/accept', [B2BPortalController::class, 'acceptQuotation'])->whereNumber('quotation')->middleware(EnsureB2BWeb::class);
    Route::post('quotations/{quotation}/pay', [B2BPortalController::class, 'payQuotation'])->whereNumber('quotation')->middleware(EnsureB2BWeb::class);
    Route::get('products', [B2BPortalController::class, 'products'])->middleware(EnsureB2BWeb::class);
});

// Distributor web portal
Route::prefix('distributor')->group(function () {
    Route::get('login', [DistributorPortalController::class, 'showLogin'])->name('distributor.login');
    Route::post('login', [DistributorPortalController::class, 'login'])->middleware('throttle:6,1');
    Route::post('logout', [DistributorPortalController::class, 'logout']);

    Route::middleware(EnsureDistributorWeb::class)->group(function () {
        Route::get('/', [DistributorPortalController::class, 'dashboard']);
        Route::get('profile', [DistributorPortalController::class, 'profile']);
        Route::post('profile', [DistributorPortalController::class, 'updateProfile']);
        Route::get('products', [DistributorPortalController::class, 'products']);
        Route::get('orders', [DistributorPortalController::class, 'orders']);
        Route::get('territory-stock', [DistributorPortalController::class, 'territoryStock']);
        Route::get('territories', [DistributorPortalController::class, 'territories']);
        Route::post('territories/request', [DistributorPortalController::class, 'requestTerritory'])->middleware('throttle:10,1');
        Route::get('commissions', [DistributorPortalController::class, 'commissions']);
        Route::get('payouts', [DistributorPortalController::class, 'payouts']);
        Route::get('downlines', [DistributorPortalController::class, 'downlines']);
        Route::get('leads', [DistributorPortalController::class, 'leads']);
        Route::get('support', [DistributorPortalController::class, 'support']);
        Route::post('support', [DistributorPortalController::class, 'storeSupport'])->middleware('throttle:20,1');
        Route::get('messages', [DistributorPortalController::class, 'messages']);
        Route::get('messages/{conversation}', [DistributorPortalController::class, 'showMessage'])->whereNumber('conversation');
        Route::post('messages/{conversation}', [DistributorPortalController::class, 'replyMessage'])->whereNumber('conversation')->middleware('throttle:20,1');
    });
});
Route::prefix('admin')->group(function () {
    Route::get('login', [AdminAuth::class, 'showLogin'])->name('admin.login');
    Route::post('login', [AdminAuth::class, 'login'])->middleware('throttle:6,1');
    Route::post('logout', [AdminAuth::class, 'logout']);

    Route::middleware('admin.web')->group(function () {
        Route::get('/', [AdminDash::class, 'index']);
        Route::get('system-health', [AdminDash::class, 'systemHealth']);
        // PCB Admin
        Route::get('pcb', [AdminPcb::class, 'index'])->name('admin.pcb.index');
        Route::get('pcb/projects/{project}', [AdminPcb::class, 'show'])->name('admin.pcb.show');
        Route::post('pcb/projects/{project}/status', [AdminPcb::class, 'status'])->middleware('throttle:20,1')->name('admin.pcb.status');
        Route::post('pcb/projects/{project}/quotes/{quote}', [AdminPcb::class, 'quote'])->middleware('throttle:20,1')->name('admin.pcb.quote');
        Route::get('pcb/projects/{project}/files/{file}/download', [AdminPcb::class, 'download'])->name('admin.pcb.files.download');
        Route::get('categories', [AdminDash::class, 'categories']);
        Route::get('categories/{id}', [AdminDash::class, 'category'])->whereNumber('id');
        Route::get('products', [AdminDash::class, 'products']);
        Route::get('products/{id}', [AdminDash::class, 'product'])->whereNumber('id');
        Route::get('brands', [AdminDash::class, 'brands'])->name('admin.brands');
        Route::get('brands/{id}', [AdminDash::class, 'brand'])->whereNumber('id')->name('admin.brand.edit');
        Route::post('brands/{id}', [AdminDash::class, 'updateBrand'])->whereNumber('id')->name('admin.brand.update');
        Route::get('brand-logos', [AdminDash::class, 'brandLogos'])->name('admin.brand-logos');
        Route::get('imports/jlcpcb', [AdminDash::class, 'jlcpcbImports']);
        Route::get('imports/elecforest', [AdminElecforestImport::class, 'index']);
        Route::post('imports/elecforest/start', [AdminElecforestImport::class, 'start'])->middleware('throttle:4,1');
        Route::post('imports/elecforest/runs/{run}/retry', [AdminElecforestImport::class, 'retry'])->whereUuid('run')->middleware('throttle:4,1');
        Route::post('imports/elecforest/runs/{run}/pause', [AdminElecforestImport::class, 'pause'])->whereUuid('run')->middleware('throttle:4,1');
        Route::post('imports/elecforest/runs/{run}/resume', [AdminElecforestImport::class, 'resume'])->whereUuid('run')->middleware('throttle:4,1');
        Route::post('imports/elecforest/generate-seo', [AdminElecforestImport::class, 'generateSeo'])->middleware('throttle:4,1');
        Route::post('imports/elecforest/download-images', [AdminElecforestImport::class, 'downloadImages'])->middleware('throttle:4,1');
        Route::post('imports/elecforest/publish-qualified', [AdminElecforestImport::class, 'publish'])->middleware('throttle:4,1');
        Route::post('imports/elecforest/map-category', [AdminElecforestImport::class, 'mapCategory'])->middleware('throttle:10,1');
        Route::post('imports/jlcpcb/bulk-approve', [AdminCommerce::class, 'bulkApproveJlcpcbImports'])->middleware('throttle:10,1');
        Route::post('imports/jlcpcb/bulk-publish', [AdminCommerce::class, 'bulkPublishJlcpcbImports'])->middleware('throttle:10,1');
        Route::post('imports/jlcpcb/search-rebuild', [AdminCommerce::class, 'queueJlcpcbSearchRebuild'])->middleware('throttle:5,1');
        Route::post('imports/jlcpcb/{source}/approve', [AdminCommerce::class, 'approveJlcpcbImport'])->whereNumber('source')->middleware('throttle:20,1');
        Route::post('imports/jlcpcb/{source}/publish', [AdminCommerce::class, 'publishJlcpcbImport'])->whereNumber('source')->middleware('throttle:10,1');
        Route::post('imports/jlcpcb/{source}/reject', [AdminCommerce::class, 'rejectJlcpcbImport'])->whereNumber('source')->middleware('throttle:20,1');
        Route::get('marketplaces', [AdminMarketplaceConfig::class, 'index']);
        Route::post('marketplaces/bulk', [AdminMarketplaceConfig::class, 'bulk'])->middleware('throttle:20,1');
        // Marketplace domain/SEO/status configuration UI (codex §3, §11).
        Route::get('marketplaces/{id}/config', [AdminMarketplaceConfig::class, 'edit'])->whereNumber('id');
        Route::post('marketplaces/{id}/config', [AdminMarketplaceConfig::class, 'update'])->whereNumber('id')->middleware('throttle:30,1');
        Route::post('marketplaces/{id}/enable', [AdminMarketplaceConfig::class, 'enable'])->whereNumber('id')->middleware('throttle:20,1');
        Route::post('marketplaces/{id}/disable', [AdminMarketplaceConfig::class, 'disable'])->whereNumber('id')->middleware('throttle:20,1');
        Route::post('marketplaces/{id}/generate-domain', [AdminMarketplaceConfig::class, 'generateDomain'])->whereNumber('id')->middleware('throttle:20,1');
        Route::post('marketplaces/{id}/verify-domain', [AdminMarketplaceConfig::class, 'verifyDomain'])->whereNumber('id')->middleware('throttle:20,1');

        // SMD Marking Code Identification
        Route::get('smd', [SmdAdminController::class, 'index']);
        Route::get('smd/markings', [SmdAdminController::class, 'markings']);
        Route::get('smd/queue', [SmdAdminController::class, 'queue']);
        Route::post('smd/verify/{id}', [SmdAdminController::class, 'verify'])->whereNumber('id');
        Route::post('smd/reject/{id}', [SmdAdminController::class, 'reject'])->whereNumber('id');
        Route::post('marketplaces/{id}/generate-seo', [AdminMarketplaceConfig::class, 'generateSeo'])->whereNumber('id')->middleware('throttle:20,1');
        Route::post('marketplaces/{id}/clear-cache', [AdminMarketplaceConfig::class, 'clearCache'])->whereNumber('id')->middleware('throttle:20,1');
        Route::get('vendors', [AdminDash::class, 'vendors']);
        Route::get('distributors', [AdminDash::class, 'distributors']);
        Route::get('users', [AdminDash::class, 'users']);
        Route::get('lms', [AdminDash::class, 'lms']);
        Route::get('lms/courses/{course}', [AdminDash::class, 'lmsCourse'])->whereNumber('course');
        Route::get('bom-imports', [AdminDash::class, 'bomImports']);
        Route::get('inventory', [AdminDash::class, 'inventory']);
        Route::get('pos', [AdminDash::class, 'pos']);
        Route::get('pos/sales/{sale}', [AdminDash::class, 'posSale'])->whereNumber('sale');
        Route::get('settings', [AdminDash::class, 'settings']);
        Route::get('media', [AdminDash::class, 'media']);
        Route::get('seo', [AdminDash::class, 'seo']);
        Route::post('settings/admin-settings', [AdminCommerce::class, 'storeAdminSetting'])->middleware('throttle:20,1');
        Route::delete('settings/admin-settings/{setting}', [AdminCommerce::class, 'deleteAdminSetting'])->whereNumber('setting')->middleware('throttle:20,1');
        Route::post('media/assets', [AdminCommerce::class, 'storeMediaAsset'])->middleware('throttle:10,1');
        Route::delete('media/assets/{asset}', [AdminCommerce::class, 'deleteMediaAsset'])->whereNumber('asset')->middleware('throttle:20,1');
        Route::post('seo/pages', [AdminCommerce::class, 'storeSeoPage'])->middleware('throttle:20,1');
        Route::post('seo/redirects', [AdminCommerce::class, 'storeSeoRedirect'])->middleware('throttle:20,1');
        Route::delete('seo/redirects/{redirect}', [AdminCommerce::class, 'deleteSeoRedirect'])->whereNumber('redirect')->middleware('throttle:20,1');
        Route::post('categories', [AdminCommerce::class, 'storeCategory'])->middleware('throttle:20,1');
        Route::post('categories/{category}/toggle', [AdminCommerce::class, 'deactivateCategory'])->whereNumber('category')->middleware('throttle:20,1');
        Route::post('categories/{category}/lms-links', [AdminCommerce::class, 'storeCategoryLmsLink'])->whereNumber('category')->middleware('throttle:20,1');
        Route::delete('categories/{category}/lms-links/{link}', [AdminCommerce::class, 'deleteCategoryLmsLink'])->whereNumber(['category', 'link'])->middleware('throttle:20,1');
        Route::post('categories/{category}/spec-templates', [AdminCommerce::class, 'storeCategorySpecTemplate'])->whereNumber('category')->middleware('throttle:20,1');
        Route::delete('categories/{category}/spec-templates/{template}', [AdminCommerce::class, 'deleteCategorySpecTemplate'])->whereNumber(['category', 'template'])->middleware('throttle:20,1');
        Route::post('categories/{category}/spec-templates/{template}/fields', [AdminCommerce::class, 'storeCategorySpecField'])->whereNumber(['category', 'template'])->middleware('throttle:20,1');
        Route::delete('categories/{category}/spec-templates/{template}/fields/{field}', [AdminCommerce::class, 'deleteCategorySpecField'])->whereNumber(['category', 'template', 'field'])->middleware('throttle:20,1');
        Route::post('products', [AdminCommerce::class, 'storeProduct'])->middleware('throttle:20,1');
        Route::post('products/{product}/duplicate', [AdminCommerce::class, 'duplicateProduct'])->whereNumber('product')->middleware('throttle:20,1');
        // Tax & Tariff management
        Route::get('tax', [AdminCommerce::class, 'taxIndex']);
        Route::post('tax/zones', [AdminCommerce::class, 'storeTaxZone'])->middleware('throttle:20,1');
        Route::post('tax/zones/{zone}/toggle', [AdminCommerce::class, 'toggleTaxZone'])->whereNumber('zone')->middleware('throttle:20,1');
        Route::post('tax/rules', [AdminCommerce::class, 'storeTaxRule'])->middleware('throttle:20,1');
        Route::post('tax/duties', [AdminCommerce::class, 'storeDutyRule'])->middleware('throttle:20,1');

        // Pricing engine — rules, floors, rounding
        Route::get('pricing', [PricingAdminController::class, 'index'])->name('admin.pricing.index');
        Route::post('pricing/rules', [PricingAdminController::class, 'store'])->middleware('throttle:20,1')->name('admin.pricing.store');
        Route::post('pricing/rules/{rule}/toggle', [PricingAdminController::class, 'toggle'])->whereNumber('rule')->name('admin.pricing.toggle');
        Route::post('pricing/rules/{rule}/approve', [PricingAdminController::class, 'approve'])->whereNumber('rule')->name('admin.pricing.approve');
        Route::delete('pricing/rules/{rule}', [PricingAdminController::class, 'destroy'])->whereNumber('rule')->name('admin.pricing.destroy');
        Route::post('pricing/margin-floor', [PricingAdminController::class, 'storeMarginFloor'])->name('admin.pricing.margin-floor');
        Route::post('pricing/price-floor', [PricingAdminController::class, 'storePriceFloor'])->name('admin.pricing.price-floor');
        Route::post('pricing/rounding', [PricingAdminController::class, 'storeRounding'])->name('admin.pricing.rounding');

        // POS management
        Route::get('pos/manage', [PosAdminController::class, 'index'])->name('admin.pos.manage');
        Route::post('pos/registers', [PosAdminController::class, 'storeRegister'])->name('admin.pos.store-register');
        Route::post('pos/registers/{id}/toggle', [PosAdminController::class, 'toggleRegister'])->whereNumber('id')->name('admin.pos.toggle-register');
        Route::post('pos/shifts/open', [PosAdminController::class, 'openShift'])->name('admin.pos.open-shift');
        Route::post('pos/shifts/close', [PosAdminController::class, 'closeShift'])->name('admin.pos.close-shift');

        Route::post('products/{product}/toggle', [AdminCommerce::class, 'deactivateProduct'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('products/{product}/stock', [AdminCommerce::class, 'adjustProductStock'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('products/{product}/regional-stock', [AdminCommerce::class, 'storeProductRegionalStock'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('products/{product}/specs', [AdminCommerce::class, 'storeProductSpec'])->whereNumber('product')->middleware('throttle:20,1');
        Route::delete('products/{product}/specs/{spec}', [AdminCommerce::class, 'deleteProductSpec'])->whereNumber(['product', 'spec'])->middleware('throttle:20,1');
        Route::post('products/{product}/advanced-specs', [AdminCommerce::class, 'storeAdvancedProductSpec'])->whereNumber('product')->middleware('throttle:20,1');
        Route::delete('products/{product}/advanced-specs/{spec}', [AdminCommerce::class, 'deleteAdvancedProductSpec'])->whereNumber(['product', 'spec'])->middleware('throttle:20,1');
        Route::post('products/{product}/reviews/{review}', [AdminCommerce::class, 'updateProductReview'])->whereNumber(['product', 'review'])->middleware('throttle:20,1');
        Route::post('products/{product}/marketplace-prices', [AdminCommerce::class, 'storeMarketplaceProductPrice'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('products/{product}/marketplace-prices/{price}/toggle', [AdminCommerce::class, 'toggleMarketplaceProductPrice'])->whereNumber(['product', 'price'])->middleware('throttle:20,1');
        Route::post('products/{product}/vendor-prices', [AdminCommerce::class, 'storeVendorProductPrice'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('products/{product}/vendor-prices/{price}/toggle', [AdminCommerce::class, 'toggleVendorProductPrice'])->whereNumber(['product', 'price'])->middleware('throttle:20,1');
        Route::post('products/{product}/documents', [AdminCommerce::class, 'storeProductDocument'])->whereNumber('product')->middleware('throttle:20,1');
        Route::delete('products/{product}/documents/{document}', [AdminCommerce::class, 'deleteProductDocument'])->whereNumber(['product', 'document'])->middleware('throttle:20,1');
        Route::post('products/{product}/related', [AdminCommerce::class, 'storeProductRelated'])->whereNumber('product')->middleware('throttle:20,1');
        Route::delete('products/{product}/related/{related}', [AdminCommerce::class, 'deleteProductRelated'])->whereNumber(['product', 'related'])->middleware('throttle:20,1');
        Route::post('products/{product}/lms-links', [AdminCommerce::class, 'storeProductLmsLink'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('products/{product}/seo', [AdminCommerce::class, 'updateProductSeo'])->whereNumber('product')->middleware(['admin.web.permission:catalog.manage', 'throttle:20,1']);
        Route::post('products/{product}/seo/versions/{version}/rollback', [AdminCommerce::class, 'rollbackProductSeo'])->whereNumber(['product', 'version'])->middleware(['admin.web.permission:catalog.manage', 'throttle:10,1']);
        Route::post('products/{product}/images', [AdminProductImage::class, 'store'])->whereNumber('product')->middleware(['admin.web.permission:catalog.manage', 'throttle:10,1']);
        Route::patch('products/{product}/images/reorder', [AdminProductImage::class, 'reorder'])->whereNumber('product')->middleware(['admin.web.permission:catalog.manage', 'throttle:20,1']);
        Route::patch('products/{product}/images/{image}', [AdminProductImage::class, 'update'])->whereNumber(['product', 'image'])->middleware(['admin.web.permission:catalog.manage', 'throttle:20,1']);
        Route::post('products/{product}/images/{image}/primary', [AdminProductImage::class, 'primary'])->whereNumber(['product', 'image'])->middleware(['admin.web.permission:catalog.manage', 'throttle:20,1']);
        Route::delete('products/{product}/images/{image}', [AdminProductImage::class, 'destroy'])->whereNumber(['product', 'image'])->middleware(['admin.web.permission:catalog.manage', 'throttle:20,1']);
        Route::post('vendors', [AdminCommerce::class, 'storeVendor'])->middleware('throttle:20,1');
        Route::post('vendors/{vendor}/status', [AdminCommerce::class, 'updateVendorStatus'])->whereNumber('vendor')->middleware('throttle:20,1');
        Route::post('vendor-documents/{document}/status', [AdminCommerce::class, 'updateVendorDocumentStatus'])->whereNumber('document')->middleware('throttle:20,1');
        Route::post('vendor-products/{product}/approve', [AdminCommerce::class, 'approveVendorProduct'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('vendor-products/{product}/reject', [AdminCommerce::class, 'rejectVendorProduct'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('users', [AdminCommerce::class, 'storeUser'])->middleware('throttle:20,1');
        Route::post('users/invitations', [AdminCommerce::class, 'storeAdminInvitation'])->middleware('throttle:20,1');
        Route::post('users/permissions', [AdminCommerce::class, 'storePermission'])->middleware('throttle:20,1');
        Route::post('users/roles/{role}/permissions/{permission}', [AdminCommerce::class, 'toggleRolePermission'])->whereNumber(['role', 'permission'])->middleware('throttle:20,1');
        Route::post('users/{user}/country-access', [AdminCommerce::class, 'assignUserCountryAccess'])->whereNumber('user')->middleware('throttle:20,1');
        Route::post('users/{user}/seller-access', [AdminCommerce::class, 'assignUserSellerAccess'])->whereNumber('user')->middleware('throttle:20,1');
        Route::post('lms/courses', [AdminCommerce::class, 'storeLmsCourse'])->middleware('throttle:20,1');
        Route::post('lms/courses/{course}/toggle', [AdminCommerce::class, 'toggleLmsCourse'])->whereNumber('course')->middleware('throttle:20,1');
        Route::post('lms/courses/{course}/modules', [AdminCommerce::class, 'storeLmsModule'])->whereNumber('course')->middleware('throttle:20,1');
        Route::post('lms/courses/{course}/projects', [AdminCommerce::class, 'storeLmsProject'])->whereNumber('course')->middleware('throttle:20,1');
        Route::post('lms/courses/{course}/products', [AdminCommerce::class, 'storeLmsProductLink'])->whereNumber('course')->middleware('throttle:20,1');
        Route::post('lms/courses/{course}/lessons/{lesson}/files', [AdminCommerce::class, 'storeLmsLessonFile'])->whereNumber(['course', 'lesson'])->middleware('throttle:20,1');
        Route::post('lms/lessons', [AdminCommerce::class, 'storeLmsLesson'])->middleware('throttle:20,1');
        Route::post('lms/enrollments/{enrollment}/certificate', [AdminCommerce::class, 'issueLmsCertificate'])->whereNumber('enrollment')->middleware('throttle:20,1');
        Route::post('lms/certificates/{certificate}/revoke', [AdminCommerce::class, 'revokeLmsCertificate'])->whereNumber('certificate')->middleware('throttle:20,1');
        Route::post('inventory/warehouses', [AdminCommerce::class, 'storeWarehouse'])->middleware('throttle:20,1');
        Route::post('inventory/stocks', [AdminCommerce::class, 'adjustInventoryStock'])->middleware('throttle:20,1');
        Route::post('inventory/transfers', [AdminCommerce::class, 'transferInventoryStock'])->middleware('throttle:20,1');
        Route::post('inventory/reservations', [AdminCommerce::class, 'reserveInventoryStock'])->middleware('throttle:20,1');
        Route::post('inventory/reservations/{reservation}/release', [AdminCommerce::class, 'releaseInventoryReservation'])->whereNumber('reservation')->middleware('throttle:20,1');
        Route::post('inventory/low-stock/generate', [AdminCommerce::class, 'generateLowStockAlerts'])->middleware('throttle:10,1');
        Route::post('inventory/low-stock/{alert}/action', [AdminCommerce::class, 'updateLowStockAlert'])->whereNumber('alert')->middleware('throttle:20,1');
        Route::post('pos/terminals', [AdminCommerce::class, 'storePosTerminal'])->middleware('throttle:20,1');
        Route::post('pos/sessions/open', [AdminCommerce::class, 'openPosSession'])->middleware('throttle:20,1');
        Route::post('pos/sessions/{session}/close', [AdminCommerce::class, 'closePosSession'])->whereNumber('session')->middleware('throttle:20,1');
        Route::post('pos/payment-methods', [AdminCommerce::class, 'storePosPaymentMethod'])->middleware('throttle:20,1');
        Route::post('pos/payment-methods/{method}/toggle', [AdminCommerce::class, 'togglePosPaymentMethod'])->whereNumber('method')->middleware('throttle:20,1');
        Route::post('pos/sales/{sale}/refunds', [AdminCommerce::class, 'storePosRefund'])->whereNumber('sale')->middleware('throttle:20,1');
        Route::post('pos/offline-sync-events', [AdminCommerce::class, 'storePosOfflineSyncEvent'])->middleware('throttle:20,1');
        Route::post('pos/offline-sync-events/{event}/status', [AdminCommerce::class, 'updatePosOfflineSyncEvent'])->whereNumber('event')->middleware('throttle:20,1');

        Route::get('marketing', [AdminDash::class, 'marketing'])->middleware('admin.web.permission:campaigns.view');
        Route::get('marketing/crm', [AdminDash::class, 'crm'])->middleware('admin.web.permission:customers.view');
        Route::get('marketing/customer-imports', [AdminCustomerImport::class, 'index'])->middleware('admin.web.permission:customers.import');
        Route::post('marketing/customer-imports/preview', [AdminCustomerImport::class, 'preview'])->middleware(['admin.web.permission:customers.import', 'throttle:10,1']);
        Route::post('marketing/customer-imports', [AdminCustomerImport::class, 'execute'])->middleware(['admin.web.permission:customers.import', 'throttle:5,1']);
        Route::get('marketing/customer-imports/{import}', [AdminCustomerImport::class, 'show'])->whereNumber('import')->middleware('admin.web.permission:customers.view');
        Route::get('marketing/customer-imports/{import}/errors.csv', [AdminCustomerImport::class, 'errors'])->whereNumber('import')->middleware('admin.web.permission:customers.export');
        Route::get('marketing/customers/export', [AdminCustomerData::class, 'export'])->middleware('admin.web.permission:customers.export');
        Route::get('marketing/newsletter', [AdminDash::class, 'newsletter'])->middleware('admin.web.permission:campaigns.view');
        Route::get('marketing/email', [AdminDash::class, 'emailMarketing'])->middleware('admin.web.permission:campaigns.view');
        Route::get('marketing/automation', [AdminDash::class, 'automation'])->middleware('admin.web.permission:campaigns.view');
        Route::get('marketing/abandoned-carts', [AdminDash::class, 'abandonedCarts'])->middleware('admin.web.permission:campaigns.view');
        Route::get('marketing/whatsapp', [AdminDash::class, 'whatsapp'])->middleware('admin.web.permission:campaigns.view');
        Route::get('marketing/analytics', [AdminDash::class, 'marketingAnalytics'])->middleware('admin.web.permission:campaigns.view');
        Route::get('marketing/settings', [AdminDash::class, 'marketingSettings'])->middleware('admin.web.permission:email.providers.manage');
        Route::get('marketing/audit', [AdminDash::class, 'marketingAudit'])->middleware('admin.web.permission:campaigns.view');

        Route::post('marketing/segments', [AdminMarketing::class, 'storeSegment'])->middleware(['admin.web.permission:campaigns.create', 'throttle:10,1']);
        Route::post('marketing/segments/{segment}/refresh', [AdminMarketing::class, 'refreshSegment'])->whereNumber('segment')->middleware(['admin.web.permission:campaigns.create', 'throttle:10,1']);
        Route::post('marketing/newsletter/templates', [AdminMarketing::class, 'storeNewsletterTemplate'])->middleware(['admin.web.permission:email.templates.manage', 'throttle:10,1']);
        Route::post('marketing/newsletter/campaigns', [AdminMarketing::class, 'storeNewsletterCampaign'])->middleware(['admin.web.permission:campaigns.create', 'throttle:10,1']);
        Route::post('marketing/newsletter/campaigns/{campaign}/queue', [AdminMarketing::class, 'queueNewsletterCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.send', 'throttle:10,1']);
        Route::post('marketing/newsletter/campaigns/{campaign}/send-test', [AdminMarketing::class, 'sendNewsletterCampaignTest'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.test', 'throttle:10,1']);
        Route::post('marketing/newsletter/campaigns/{campaign}/approve', [AdminMarketing::class, 'approveNewsletterCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.approve', 'throttle:10,1']);
        Route::post('marketing/newsletter/campaigns/{campaign}/pause', [AdminMarketing::class, 'pauseNewsletterCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.send', 'throttle:10,1']);
        Route::post('marketing/newsletter/campaigns/{campaign}/resume', [AdminMarketing::class, 'resumeNewsletterCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.send', 'throttle:10,1']);
        Route::post('marketing/newsletter/campaigns/{campaign}/cancel', [AdminMarketing::class, 'cancelNewsletterCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.send', 'throttle:10,1']);
        Route::post('marketing/email/templates', [AdminMarketing::class, 'storeEmailTemplate'])->middleware(['admin.web.permission:email.templates.manage', 'throttle:10,1']);
        Route::post('marketing/email/campaigns', [AdminMarketing::class, 'storeEmailCampaign'])->middleware(['admin.web.permission:campaigns.create', 'throttle:10,1']);
        Route::post('marketing/email/campaigns/{campaign}/queue', [AdminMarketing::class, 'queueEmailCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.send', 'throttle:10,1']);
        Route::post('marketing/email/campaigns/{campaign}/send-test', [AdminMarketing::class, 'sendEmailCampaignTest'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.test', 'throttle:10,1']);
        Route::post('marketing/email/campaigns/{campaign}/approve', [AdminMarketing::class, 'approveEmailCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.approve', 'throttle:10,1']);
        Route::post('marketing/email/campaigns/{campaign}/pause', [AdminMarketing::class, 'pauseEmailCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.send', 'throttle:10,1']);
        Route::post('marketing/email/campaigns/{campaign}/resume', [AdminMarketing::class, 'resumeEmailCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.send', 'throttle:10,1']);
        Route::post('marketing/email/campaigns/{campaign}/cancel', [AdminMarketing::class, 'cancelEmailCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.send', 'throttle:10,1']);
        Route::post('marketing/whatsapp/templates', [AdminMarketing::class, 'storeWhatsappTemplate'])->middleware(['admin.web.permission:email.templates.manage', 'throttle:10,1']);
        Route::post('marketing/whatsapp/campaigns', [AdminMarketing::class, 'storeWhatsappCampaign'])->middleware(['admin.web.permission:campaigns.create', 'throttle:10,1']);
        Route::post('marketing/whatsapp/campaigns/{campaign}/queue', [AdminMarketing::class, 'queueWhatsappCampaign'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.send', 'throttle:10,1']);
        Route::post('marketing/whatsapp/campaigns/{campaign}/send-test', [AdminMarketing::class, 'sendWhatsappCampaignTest'])->whereNumber('campaign')->middleware(['admin.web.permission:campaigns.test', 'throttle:10,1']);
        Route::post('marketing/settings', [AdminMarketing::class, 'updateMarketingSettings'])->middleware(['admin.web.permission:email.providers.manage', 'throttle:10,1']);
        Route::post('marketing/settings/email-provider', [AdminMarketing::class, 'updateEmailProvider'])->middleware(['admin.web.permission:email.providers.manage', 'throttle:10,1']);
        Route::post('marketing/settings/email-provider/test-marketing', [AdminMarketing::class, 'testMarketingProvider'])->middleware(['admin.web.permission:email.providers.manage', 'throttle:5,1']);
        Route::post('marketing/settings/email-provider/test-transactional', [AdminMarketing::class, 'testTransactionalProvider'])->middleware(['admin.web.permission:email.providers.manage', 'throttle:5,1']);
        Route::post('marketing/settings/senders/{sender}', [AdminMarketing::class, 'updateEmailSenderProfile'])->whereNumber('sender')->middleware(['admin.web.permission:email.providers.manage', 'throttle:10,1']);

        // Commerce ops (adaptation modules) — read pages
        Route::get('affiliate', [AdminDash::class, 'affiliate']);
        Route::get('promotions', [AdminDash::class, 'promotions']);
        Route::get('procurement', [AdminDash::class, 'procurement']);
        Route::get('quotations', [AdminDash::class, 'quotations']);
        Route::get('quotations/{quotation}/preview', [AdminDash::class, 'quotationPreview'])->whereNumber('quotation');
        Route::get('expenses', [AdminDash::class, 'expenses']);
        Route::get('payments', [AdminDash::class, 'payments']);
        Route::get('support', [AdminDash::class, 'support']);
        Route::get('applications', [AdminDash::class, 'applications']);
        Route::get('partner-approvals', [PartnerApprovalsController::class, 'index']);
        Route::post('partner-approvals/account-applications/{application}/approve', [PartnerApprovalsController::class, 'approveAccountApplication'])->whereNumber('application')->middleware('throttle:20,1');
        Route::post('partner-approvals/account-applications/{application}/review', [PartnerApprovalsController::class, 'reviewAccountApplication'])->whereNumber('application')->middleware('throttle:20,1');
        Route::get('partner-approvals/account-documents/{document}', [PartnerApprovalsController::class, 'downloadAccountDocument'])->whereNumber('document');
        Route::post('partner-approvals/reseller-applications/{application}/approve', [PartnerApprovalsController::class, 'approveResellerApplication'])->whereNumber('application')->middleware('throttle:20,1');
        Route::post('partner-approvals/reseller-applications/{application}/reject', [PartnerApprovalsController::class, 'rejectResellerApplication'])->whereNumber('application')->middleware('throttle:20,1');
        Route::post('partner-approvals/reseller-territories/{territoryRequest}/approve', [PartnerApprovalsController::class, 'approveResellerTerritory'])->whereNumber('territoryRequest')->middleware('throttle:20,1');
        Route::post('partner-approvals/reseller-territories/{territoryRequest}/reject', [PartnerApprovalsController::class, 'rejectResellerTerritory'])->whereNumber('territoryRequest')->middleware('throttle:20,1');
        Route::post('partner-approvals/distributor-territories/{territoryRequest}/approve', [PartnerApprovalsController::class, 'approveDistributorTerritory'])->whereNumber('territoryRequest')->middleware('throttle:20,1');
        Route::post('partner-approvals/distributor-territories/{territoryRequest}/reject', [PartnerApprovalsController::class, 'rejectDistributorTerritory'])->whereNumber('territoryRequest')->middleware('throttle:20,1');
        Route::get('region-stock', [AdminDash::class, 'regionStock']);

        // Orders (permission placeholders pending web-console RBAC:
        // admin.orders.view / admin.orders.update — currently guarded by admin.web roles)
        Route::get('orders', [AdminDash::class, 'orders']);
        Route::get('orders/{id}', [AdminDash::class, 'order'])->whereNumber('id');
        Route::get('orders/{id}/invoice', [AdminDash::class, 'invoice'])->whereNumber('id');

        // Commerce ops — guarded config actions (server-side; no live gateways)
        Route::post('payments/providers/{provider}/toggle', [AdminCommerce::class, 'toggleProvider'])->whereNumber('provider')->middleware('throttle:20,1');
        Route::post('payments/payouts/{payout}/approve', [AdminCommerce::class, 'approvePayout'])->whereNumber('payout')->middleware('throttle:20,1');
        Route::post('payments/payouts/{payout}/mark-paid', [AdminCommerce::class, 'markPayoutPaid'])->whereNumber('payout')->middleware('throttle:20,1');
        Route::post('promotions/coupons', [AdminCommerce::class, 'storeCoupon'])->middleware('throttle:20,1');
        Route::post('promotions/coupons/{coupon}/toggle', [AdminCommerce::class, 'toggleCoupon'])->whereNumber('coupon')->middleware('throttle:20,1');
        Route::post('affiliate/{affiliate}/approve', [AdminCommerce::class, 'approveAffiliate'])->whereNumber('affiliate')->middleware('throttle:20,1');
        Route::post('affiliate/commissions/{commission}/approve', [AdminCommerce::class, 'approveCommission'])->whereNumber('commission')->middleware('throttle:20,1');
        Route::post('expenses', [AdminCommerce::class, 'storeExpense'])->middleware('throttle:20,1');
        Route::post('applications/{type}/{id}/status', [AdminCommerce::class, 'updateApplicationStatus'])->whereNumber('id')->middleware('throttle:20,1');
        Route::post('region-stock/rules/{rule}/toggle', [AdminCommerce::class, 'toggleStockRule'])->whereNumber('rule')->middleware('throttle:20,1');
        Route::post('users/{user}/send-reset', [AdminCommerce::class, 'sendPasswordReset'])->whereNumber('user')->middleware('throttle:20,1');
        Route::post('orders/{order}/status', [AdminCommerce::class, 'updateOrderStatus'])->whereNumber('order')->middleware('throttle:20,1');
        Route::post('orders/{order}/tracking', [AdminCommerce::class, 'updateOrderTracking'])->whereNumber('order')->middleware('throttle:20,1');
        Route::get('reviews', [AdminDash::class, 'reviews']);
        Route::get('rfqs', [AdminDash::class, 'rfqs']);
        Route::get('rfqs/{id}', [AdminDash::class, 'rfq'])->whereNumber('id');
        Route::post('rfqs/{rfq}/status', [AdminCommerce::class, 'updateRfqStatus'])->whereNumber('rfq')->middleware('throttle:20,1');
        Route::post('rfqs/{rfq}/quotations', [AdminCommerce::class, 'storeRfqQuotation'])->whereNumber('rfq')->middleware('throttle:20,1');
        Route::post('quotations/{quotation}/status', [AdminCommerce::class, 'updateQuotationStatus'])->whereNumber('quotation')->middleware('throttle:20,1');
        Route::post('quotations/{quotation}/items', [AdminCommerce::class, 'storeQuotationItem'])->whereNumber('quotation')->middleware('throttle:20,1');
        Route::delete('quotations/{quotation}/items/{item}', [AdminCommerce::class, 'deleteQuotationItem'])->whereNumber(['quotation', 'item'])->middleware('throttle:20,1');
        Route::post('support/tickets', [AdminCommerce::class, 'storeSupportTicket'])->middleware('throttle:20,1');
        Route::post('support/tickets/{ticket}', [AdminCommerce::class, 'updateSupportTicket'])->whereNumber('ticket')->middleware('throttle:20,1');
        Route::post('support/tickets/{ticket}/escalate', [AdminCommerce::class, 'escalateSupportTicket'])->whereNumber('ticket')->middleware('throttle:20,1');
        Route::post('support/tickets/{ticket}/messages', [AdminCommerce::class, 'storeSupportTicketMessage'])->whereNumber('ticket')->middleware('throttle:20,1');
    });
});

// Public marketplace
Route::post('/marketplace/preference', [MarketplacePreferenceController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('marketplace.preference');
Route::get('/sso/start', [SsoController::class, 'start'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('sso.start');
Route::get('/sso/consume', [SsoController::class, 'consume'])
    ->middleware('throttle:20,1')
    ->name('sso.consume');

// SMD Marking Code Identifier — public engineering tool
Route::get('/tools/smd-marking-code-identifier', fn () => view('frontend.tools.smd-identifier'))->name('tools.smd');
Route::get('/tools/smd-marking-code-identifier/{marking}', fn (string $marking) => view('frontend.tools.smd-identifier', ['presetMarking' => $marking]))->where('marking', '[A-Za-z0-9\.\-\+_ ]{1,20}');

// POS cashier UI + session-backed cashier API (admin)
Route::get('/pos/receipt/{token}', [PosReceiptController::class, 'show'])->where('token', '[a-z0-9]{32}');
Route::middleware('admin.web')->prefix('pos/cashier')->group(function () {
    Route::get('/', [PosCashierController::class, 'show']);
    Route::get('terminals', [PosCashierController::class, 'terminals']);
    Route::post('session/open', [PosCashierController::class, 'openSession'])->middleware('throttle:30,1');
    Route::get('customers/search', [PosCashierController::class, 'searchCustomers']);
    Route::post('sales', [PosCashierController::class, 'createSale'])->middleware('throttle:60,1');
    Route::post('sales/{sale}/refund', [PosCashierController::class, 'refund'])->whereNumber('sale')->middleware('throttle:30,1');
});

Route::redirect('/learn', '/en/lms', 301);
Route::redirect('/learning', '/en/lms', 301);
Route::get('/learn/projects/{slug}', [LmsPageController::class, 'project']);
Route::redirect('/', '/en', 301);
// Stitch "Precision Engineering" redesign preview (noindex; does not touch the
// live homepage/layout). Promote to the live home only after review.
Route::get('/preview/home', [RedesignController::class, 'home']);
Route::get('/categories', fn (Request $request) => redirect()->to('/en/categories'.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301));
Route::get('/categories/{slug}', fn (Request $request, string $slug) => redirect()->to('/en/categories/'.$slug.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301))->where('slug', '[a-z0-9\-]+');
Route::get('/manufacturer/{slug}', [SeoLandingController::class, 'manufacturer'])->where('slug', '[a-z0-9\-]+')->name('manufacturer.show');
Route::get('/manufacturers/{slug}', fn (Request $request, string $slug) => redirect()->to('/manufacturer/'.$slug.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301))->where('slug', '[a-z0-9\-]+');
Route::get('/brands', [BrandPageController::class, 'index'])->name('brands.index');
Route::get('/brand/{slug}', [BrandPageController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('brand.show');
Route::get('/brands/{slug}', fn (Request $request, string $slug) => redirect()->to('/brand/'.$slug.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301))->where('slug', '[a-z0-9\-]+');
Route::get('/mpn/{mpn}', [SeoLandingController::class, 'mpn'])->where('mpn', '[A-Za-z0-9\\.\\-_\\+\\%\\(\\)\\,\\s\\/]+');
Route::get('/mpn/{mpn}/{suffix}', [SeoLandingController::class, 'mpn'])->where('mpn', '[A-Za-z0-9\\.\\-_\\+]+')->where('suffix', '[A-Za-z0-9\\.\\-_\\+\\%\\(\\)\\,]+');
Route::get('/technologies/{slug}', [SeoLandingController::class, 'technology'])->where('slug', '[a-z0-9\-]+');
Route::get('/applications/{slug}', [SeoLandingController::class, 'application'])->where('slug', '[a-z0-9\-]+');
Route::get('/countries/{code}', [SeoLandingController::class, 'country'])->where('code', '[A-Za-z]{2,3}');
Route::get('/products', fn (Request $request) => redirect()->to('/en/products'.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301))->name('products.index');
Route::post('/products/{slug}/reviews', [ProductPageController::class, 'storeReview'])->where('slug', '[a-z0-9\-]+')->middleware('throttle:4,1')->name('products.reviews.store');
Route::get('/products/{slug}', fn (Request $request, string $slug) => redirect()->to('/en/products/'.$slug.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301))->where('slug', '[a-z0-9\-]+')->name('products.show');
Route::get('/cart', [CartPageController::class, 'show'])->name('cart.show');
Route::post('/cart/items', [CartPageController::class, 'add'])->middleware('throttle:30,1')->name('cart.items.add');
Route::patch('/cart/items/{item}', [CartPageController::class, 'update'])->whereNumber('item')->middleware('throttle:30,1')->name('cart.items.update');
Route::delete('/cart/items/{item}', [CartPageController::class, 'remove'])->whereNumber('item')->middleware('throttle:30,1')->name('cart.items.remove');
Route::get('/checkout', [CartPageController::class, 'checkout'])->name('checkout.show');
Route::post('/checkout', [CartPageController::class, 'placeOrder'])->middleware('throttle:10,1')->name('checkout.place');
Route::get('/checkout/thank-you/{orderNumber}', [CartPageController::class, 'thankYou'])->where('orderNumber', '[A-Z0-9\\-]+')->name('checkout.thank-you');
Route::get('/rfq', fn (Request $request) => redirect()->to('/en/rfq'.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301))->name('rfq.create');
Route::post('/rfq', [RfqPageController::class, 'store'])->middleware('throttle:6,1')->name('rfq.store');
Route::get('/sitemap.xml', SitemapController::class);
Route::get('/sitemaps/{section}-{page}.xml', [SitemapController::class, 'section'])
    ->whereIn('section', ['pages', 'categories', 'brands', 'manufacturers', 'products'])
    ->whereNumber('page');

// Customer auth pages
Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:6,1');
Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:6,1');
Route::prefix('account')->middleware('auth')->name('account.')->group(function () {
    Route::get('/', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('orders', [CustomerDashboardController::class, 'orders'])->name('orders');
    Route::get('orders/{order}', [CustomerDashboardController::class, 'showOrder'])->whereNumber('order')->name('orders.show');
    Route::get('rfqs', [CustomerDashboardController::class, 'rfqs'])->name('rfqs');
    Route::get('rfqs/{rfq}', [CustomerDashboardController::class, 'showRfq'])->whereNumber('rfq')->name('rfqs.show');
    Route::get('quotations', [CustomerDashboardController::class, 'quotations'])->name('quotations');
    Route::get('quotations/{quotation}', [CustomerDashboardController::class, 'showQuotation'])->whereNumber('quotation')->name('quotations.show');
    Route::get('bom', [CustomerDashboardController::class, 'bom'])->name('bom');
    Route::get('saved', [CustomerDashboardController::class, 'saved'])->name('saved');
    Route::get('notifications', [CustomerDashboardController::class, 'notifications'])->name('notifications');
    Route::patch('notifications', [CustomerDashboardController::class, 'updateNotificationPreferences'])->middleware('throttle:10,1')->name('notifications.update');
    Route::get('support', [CustomerDashboardController::class, 'support'])->name('support');
    Route::post('support', [CustomerDashboardController::class, 'storeSupport'])->middleware('throttle:10,1')->name('support.store');
    Route::get('support/{ticket}', [CustomerDashboardController::class, 'showSupport'])->whereNumber('ticket')->name('support.show');
    Route::post('support/{ticket}/reply', [CustomerDashboardController::class, 'replySupport'])->whereNumber('ticket')->middleware('throttle:20,1')->name('support.reply');
    Route::get('payments', [CustomerDashboardController::class, 'payments'])->name('payments');
    Route::get('profile', [CustomerDashboardController::class, 'profile'])->name('profile');
    Route::patch('profile', [CustomerDashboardController::class, 'updateProfile'])->middleware('throttle:10,1')->name('profile.update');
    Route::get('security', [CustomerDashboardController::class, 'security'])->name('security');
    Route::patch('password', [CustomerDashboardController::class, 'updatePassword'])->middleware('throttle:6,1')->name('password.update');
    Route::get('addresses', [CustomerDashboardController::class, 'addresses'])->name('addresses');
    Route::post('addresses', [CustomerDashboardController::class, 'storeAddress'])->middleware('throttle:10,1')->name('addresses.store');
    Route::delete('addresses/{address}', [CustomerDashboardController::class, 'deleteAddress'])->whereNumber('address')->name('addresses.delete');
    Route::get('applications', [CustomerDashboardController::class, 'applications'])->name('applications');
    Route::post('applications', [CustomerDashboardController::class, 'storeApplication'])->middleware('throttle:4,1')->name('applications.store');
    Route::post('applications/{application}/resubmit', [CustomerDashboardController::class, 'resubmitApplication'])->whereNumber('application')->middleware('throttle:4,1')->name('applications.resubmit');
    Route::post('role', [CustomerDashboardController::class, 'switchRole'])->middleware('throttle:20,1')->name('role.switch');
});
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
Route::get('/logout', fn () => redirect('/login'))->name('logout.get');

// Two-Factor Authentication (root level)
Route::middleware('auth')->group(function () {
    Route::get('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::get('/2fa/manage', [TwoFactorController::class, 'manage'])->name('2fa.manage');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
    Route::post('/2fa/new-codes', [TwoFactorController::class, 'newRecoveryCodes'])->name('2fa.new-codes');
});
Route::get('/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');

// Also register under localized prefix for neogiga.com routing
Route::get('/{locale}/2fa/challenge', [TwoFactorController::class, 'challenge'])->where('locale', '[a-z]{2}')->name('localized.2fa.challenge');
Route::post('/{locale}/2fa/verify', [TwoFactorController::class, 'verify'])->where('locale', '[a-z]{2}')->name('localized.2fa.verify');

// Password reset pages (the reset email links to the named password.reset route)
Route::get('/track-order', [OrderTrackingController::class, 'index'])->name('track.order');
Route::post('/track-order', [OrderTrackingController::class, 'lookup'])->name('track.order.lookup');
Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequest'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');

// Public seller/partner landing pages
Route::redirect('/sell-on-neogiga', '/en/sell-on-neogiga', 301);
Route::redirect('/distributors', '/en/distributors', 301);
Route::redirect('/ai-commerce', '/en/ai-commerce', 301);
Route::post('/ai-commerce/build', [AiCommercePageController::class, 'build'])->middleware('throttle:12,1');
Route::post('/ai-commerce/save', [AiCommercePageController::class, 'save'])->middleware('throttle:8,1');
Route::redirect('/seller-early-access', '/en/seller-early-access', 301);

if (config('neogiga_global.features.locale_prefix_routes', true)) {
    $localePrefixes = array_keys(config('neogiga_global.prefixes', []));

    Route::prefix('{localePrefix}')
        ->whereIn('localePrefix', $localePrefixes)
        ->middleware(CanonicalizeRegionalMarketplacePath::class)
        ->group(function () {
            Route::get('/', fn (string $localePrefix) => app(LandingController::class)())->name('localized.home');
            Route::get('/products', fn (string $localePrefix, Request $request) => app(ProductPageController::class)->index($request))->name('localized.products.index');
            Route::get('/products/suggest', fn (string $localePrefix, Request $request) => app(ProductPageController::class)->suggest($request));
            Route::get('/products/{slug}', fn (string $localePrefix, string $slug) => app(ProductPageController::class)->show($slug))->where('slug', '[a-z0-9\-]+')->name('localized.products.show');
            Route::get('/categories', fn (string $localePrefix) => app(CategoryController::class)->index())->name('localized.categories.index');
            Route::get('/categories/{slug}', fn (string $localePrefix, string $slug) => app(CategoryController::class)->show($slug))->where('slug', '[a-z0-9\-]+')->name('localized.categories.show');
            Route::get('/manufacturer/{slug}', fn (string $localePrefix, string $slug) => app(SeoLandingController::class)->manufacturer($slug))->where('slug', '[a-z0-9\-]+')->name('localized.manufacturer.show');
            Route::get('/brand/{slug}', fn (string $localePrefix, string $slug, Request $request) => app(BrandPageController::class)->show($request, $slug))->where('slug', '[a-z0-9\-]+')->name('localized.brand.show');
            Route::get('/brands', fn (string $localePrefix, Request $request) => app(BrandPageController::class)->index($request))->name('localized.brands.index');
            Route::get('/brands/{slug}', fn (Request $request, string $localePrefix, string $slug) => redirect()->to('/'.$localePrefix.'/brand/'.$slug.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301))->where('slug', '[a-z0-9\-]+')->name('localized.brands.legacy');
            Route::get('/lms', fn (string $localePrefix) => app(LmsPageController::class)->index(app(CourseCatalogService::class)))->name('localized.lms.index');
            Route::get('/projects', fn (string $localePrefix) => redirect('/learn'))->name('localized.projects.index');
            Route::get('/rfq', fn (string $localePrefix, Request $request) => app(RfqPageController::class)->create($request))->name('localized.rfq.create');
            Route::get('/bom', function (Request $request) {
                return app(BomPageController::class)->index($request);
            })->name('localized.bom.index');
            Route::get('/compare', [CompareController::class, 'index'])->name('localized.compare');
            Route::post('/bom', function (Request $request) {
                return app(BomPageController::class)->match($request);
            })->name('localized.bom.match');
            Route::get('/sell-on-neogiga', fn (string $localePrefix) => app(SellOnNeoGigaController::class)->sell())->name('localized.seller');
            Route::get('/seller-early-access', fn (string $localePrefix) => app(SellOnNeoGigaController::class)->earlyAccess())->name('localized.seller.early-access');
            Route::get('/distributors', fn (string $localePrefix) => app(SellOnNeoGigaController::class)->distributors())->name('localized.distributors');
            Route::get('/ai-commerce', fn (string $localePrefix, Request $request) => app(AiCommercePageController::class)->index($request, app(CommerceAiService::class)))->name('localized.ai');
            Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->middleware('auth')->name('localized.dashboard');
            Route::get('/account', [CustomerDashboardController::class, 'index'])->middleware('auth')->name('localized.account');
        });
}

// Global Commerce Stage 1: marketplace country selector / landing page.
// Constrained to the 25 seeded url_prefix codes only — cannot collide with
// any existing top-level route above (none of them are 2-8 letter codes).
Route::get('/{prefix}', [MarketplaceLandingController::class, 'show'])
    ->whereIn('prefix', ['in', 'np', 'bd', 'lk', 'pk', 'bt', 'mv', 'ae', 'sa', 'qa', 'om', 'kw', 'us', 'ca', 'uk', 'de', 'fr', 'it', 'es', 'nl', 'au', 'nz', 'br', 'za', 'ke'])
    ->middleware(CanonicalizeRegionalMarketplacePath::class)
    ->name('marketplace.landing');

// Footer information pages (config-driven; see config/neogiga_pages.php)
Route::get('/{pageSlug}', function (string $pageSlug) {
    $page = config('neogiga_pages.'.$pageSlug);
    abort_unless(is_array($page), 404);

    return view('frontend.pages.static', ['page' => $page]);
})->where('pageSlug', 'about|contact|quality-assurance|how-to-order|shipping|returns|payment-terms|faq|cookie-notice|terms|privacy')->name('pages.static');
