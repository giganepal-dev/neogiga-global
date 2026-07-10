<?php

use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Admin\CommerceOpsController as AdminCommerce;
use App\Http\Controllers\Admin\DashboardController as AdminDash;
use App\Http\Controllers\Admin\MarketingActionController as AdminMarketing;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\LmsPageController;
use App\Http\Controllers\Web\PasswordResetController;
use App\Http\Controllers\Web\SellOnNeoGigaController;
use App\Http\Controllers\Web\SitemapController;
use Illuminate\Support\Facades\Route;

/*
| Admin console (server-rendered). Reachable at /admin on any host; on
| admin.neogiga.com the root redirects to it. Registered BEFORE the public
| landing route so the admin host resolves to the console, not the landing.
*/
Route::domain('admin.neogiga.com')->get('/', fn () => redirect('/admin'));

Route::get('/health', HealthController::class)->withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
]);

Route::prefix('admin')->group(function () {
    Route::get('login', [AdminAuth::class, 'showLogin'])->name('admin.login');
    Route::post('login', [AdminAuth::class, 'login'])->middleware('throttle:6,1');
    Route::post('logout', [AdminAuth::class, 'logout']);

    Route::middleware('admin.web')->group(function () {
        Route::get('/', [AdminDash::class, 'index']);
        Route::get('categories', [AdminDash::class, 'categories']);
        Route::get('products', [AdminDash::class, 'products']);
        Route::get('marketplaces', [AdminDash::class, 'marketplaces']);
        Route::get('vendors', [AdminDash::class, 'vendors']);
        Route::get('distributors', [AdminDash::class, 'distributors']);
        Route::get('users', [AdminDash::class, 'users']);
        Route::get('lms', [AdminDash::class, 'lms']);
        Route::get('inventory', [AdminDash::class, 'inventory']);
        Route::get('pos', [AdminDash::class, 'pos']);
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
        Route::post('products', [AdminCommerce::class, 'storeProduct'])->middleware('throttle:20,1');
        Route::post('products/{product}/duplicate', [AdminCommerce::class, 'duplicateProduct'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('products/{product}/toggle', [AdminCommerce::class, 'deactivateProduct'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('products/{product}/stock', [AdminCommerce::class, 'adjustProductStock'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('products/{product}/specs', [AdminCommerce::class, 'storeProductSpec'])->whereNumber('product')->middleware('throttle:20,1');
        Route::delete('products/{product}/specs/{spec}', [AdminCommerce::class, 'deleteProductSpec'])->whereNumber(['product', 'spec'])->middleware('throttle:20,1');
        Route::post('products/{product}/documents', [AdminCommerce::class, 'storeProductDocument'])->whereNumber('product')->middleware('throttle:20,1');
        Route::delete('products/{product}/documents/{document}', [AdminCommerce::class, 'deleteProductDocument'])->whereNumber(['product', 'document'])->middleware('throttle:20,1');
        Route::post('products/{product}/related', [AdminCommerce::class, 'storeProductRelated'])->whereNumber('product')->middleware('throttle:20,1');
        Route::delete('products/{product}/related/{related}', [AdminCommerce::class, 'deleteProductRelated'])->whereNumber(['product', 'related'])->middleware('throttle:20,1');
        Route::post('products/{product}/lms-links', [AdminCommerce::class, 'storeProductLmsLink'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('products/{product}/seo', [AdminCommerce::class, 'updateProductSeo'])->whereNumber('product')->middleware('throttle:20,1');
        Route::post('vendors', [AdminCommerce::class, 'storeVendor'])->middleware('throttle:20,1');
        Route::post('vendors/{vendor}/status', [AdminCommerce::class, 'updateVendorStatus'])->whereNumber('vendor')->middleware('throttle:20,1');
        Route::post('users', [AdminCommerce::class, 'storeUser'])->middleware('throttle:20,1');
        Route::post('lms/courses', [AdminCommerce::class, 'storeLmsCourse'])->middleware('throttle:20,1');
        Route::post('lms/courses/{course}/toggle', [AdminCommerce::class, 'toggleLmsCourse'])->whereNumber('course')->middleware('throttle:20,1');
        Route::post('lms/lessons', [AdminCommerce::class, 'storeLmsLesson'])->middleware('throttle:20,1');
        Route::post('inventory/warehouses', [AdminCommerce::class, 'storeWarehouse'])->middleware('throttle:20,1');
        Route::post('inventory/stocks', [AdminCommerce::class, 'adjustInventoryStock'])->middleware('throttle:20,1');
        Route::post('pos/terminals', [AdminCommerce::class, 'storePosTerminal'])->middleware('throttle:20,1');
        Route::post('pos/sessions/open', [AdminCommerce::class, 'openPosSession'])->middleware('throttle:20,1');
        Route::post('pos/sessions/{session}/close', [AdminCommerce::class, 'closePosSession'])->whereNumber('session')->middleware('throttle:20,1');

        Route::get('marketing', [AdminDash::class, 'marketing']);
        Route::get('marketing/crm', [AdminDash::class, 'crm']);
        Route::get('marketing/newsletter', [AdminDash::class, 'newsletter']);
        Route::get('marketing/email', [AdminDash::class, 'emailMarketing']);
        Route::get('marketing/automation', [AdminDash::class, 'automation']);
        Route::get('marketing/abandoned-carts', [AdminDash::class, 'abandonedCarts']);
        Route::get('marketing/whatsapp', [AdminDash::class, 'whatsapp']);
        Route::get('marketing/analytics', [AdminDash::class, 'marketingAnalytics']);
        Route::get('marketing/settings', [AdminDash::class, 'marketingSettings']);
        Route::get('marketing/audit', [AdminDash::class, 'marketingAudit']);

        Route::post('marketing/segments', [AdminMarketing::class, 'storeSegment'])->middleware('throttle:10,1');
        Route::post('marketing/segments/{segment}/refresh', [AdminMarketing::class, 'refreshSegment'])->whereNumber('segment')->middleware('throttle:10,1');
        Route::post('marketing/newsletter/templates', [AdminMarketing::class, 'storeNewsletterTemplate'])->middleware('throttle:10,1');
        Route::post('marketing/newsletter/campaigns', [AdminMarketing::class, 'storeNewsletterCampaign'])->middleware('throttle:10,1');
        Route::post('marketing/newsletter/campaigns/{campaign}/queue', [AdminMarketing::class, 'queueNewsletterCampaign'])->whereNumber('campaign')->middleware('throttle:10,1');
        Route::post('marketing/newsletter/campaigns/{campaign}/send-test', [AdminMarketing::class, 'sendNewsletterCampaignTest'])->whereNumber('campaign')->middleware('throttle:10,1');
        Route::post('marketing/email/templates', [AdminMarketing::class, 'storeEmailTemplate'])->middleware('throttle:10,1');
        Route::post('marketing/email/campaigns', [AdminMarketing::class, 'storeEmailCampaign'])->middleware('throttle:10,1');
        Route::post('marketing/email/campaigns/{campaign}/queue', [AdminMarketing::class, 'queueEmailCampaign'])->whereNumber('campaign')->middleware('throttle:10,1');
        Route::post('marketing/email/campaigns/{campaign}/send-test', [AdminMarketing::class, 'sendEmailCampaignTest'])->whereNumber('campaign')->middleware('throttle:10,1');
        Route::post('marketing/whatsapp/templates', [AdminMarketing::class, 'storeWhatsappTemplate'])->middleware('throttle:10,1');
        Route::post('marketing/whatsapp/campaigns', [AdminMarketing::class, 'storeWhatsappCampaign'])->middleware('throttle:10,1');
        Route::post('marketing/whatsapp/campaigns/{campaign}/queue', [AdminMarketing::class, 'queueWhatsappCampaign'])->whereNumber('campaign')->middleware('throttle:10,1');
        Route::post('marketing/whatsapp/campaigns/{campaign}/send-test', [AdminMarketing::class, 'sendWhatsappCampaignTest'])->whereNumber('campaign')->middleware('throttle:10,1');
        Route::post('marketing/settings', [AdminMarketing::class, 'updateMarketingSettings'])->middleware('throttle:10,1');

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
        Route::post('reviews/{review}/moderate', [AdminCommerce::class, 'moderateReview'])->whereNumber('review')->middleware('throttle:20,1');
        Route::get('rfqs', [AdminDash::class, 'rfqs']);
        Route::get('rfqs/{id}', [AdminDash::class, 'rfq'])->whereNumber('id');
        Route::post('rfqs/{rfq}/status', [AdminCommerce::class, 'updateRfqStatus'])->whereNumber('rfq')->middleware('throttle:20,1');
        Route::post('rfqs/{rfq}/quotations', [AdminCommerce::class, 'storeRfqQuotation'])->whereNumber('rfq')->middleware('throttle:20,1');
        Route::post('quotations/{quotation}/status', [AdminCommerce::class, 'updateQuotationStatus'])->whereNumber('quotation')->middleware('throttle:20,1');
        Route::post('quotations/{quotation}/items', [AdminCommerce::class, 'storeQuotationItem'])->whereNumber('quotation')->middleware('throttle:20,1');
        Route::delete('quotations/{quotation}/items/{item}', [AdminCommerce::class, 'deleteQuotationItem'])->whereNumber(['quotation', 'item'])->middleware('throttle:20,1');
        Route::post('support/tickets', [AdminCommerce::class, 'storeSupportTicket'])->middleware('throttle:20,1');
        Route::post('support/tickets/{ticket}', [AdminCommerce::class, 'updateSupportTicket'])->whereNumber('ticket')->middleware('throttle:20,1');
        Route::post('support/tickets/{ticket}/messages', [AdminCommerce::class, 'storeSupportTicketMessage'])->whereNumber('ticket')->middleware('throttle:20,1');
    });
});

// Public marketplace
Route::get('/learn', [LmsPageController::class, 'index']);
Route::get('/learn/projects/{slug}', [LmsPageController::class, 'project']);
Route::get('/', LandingController::class);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->where('slug', '[a-z0-9\-]+');
Route::get('/products', [\App\Http\Controllers\Web\ProductPageController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [\App\Http\Controllers\Web\ProductPageController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('products.show');
Route::get('/rfq', [\App\Http\Controllers\Web\RfqPageController::class, 'create'])->name('rfq.create');
Route::post('/rfq', [\App\Http\Controllers\Web\RfqPageController::class, 'store'])->middleware('throttle:6,1')->name('rfq.store');
Route::get('/sitemap.xml', SitemapController::class);

// Password reset pages (the reset email links to the named password.reset route)
Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequest'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');

// Public seller/partner landing pages
Route::get('/sell-on-neogiga', [SellOnNeoGigaController::class, 'sell']);
Route::get('/distributors', [SellOnNeoGigaController::class, 'distributors']);
Route::get('/ai-commerce', [SellOnNeoGigaController::class, 'aiCommerce']);
Route::get('/seller-early-access', [SellOnNeoGigaController::class, 'earlyAccess']);
