<?php

use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Admin\DashboardController as AdminDash;
use App\Http\Controllers\Admin\MarketingActionController as AdminMarketing;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\LmsPageController;
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
        Route::get('users', [AdminDash::class, 'users']);
        Route::get('lms', [AdminDash::class, 'lms']);
        Route::get('inventory', [AdminDash::class, 'inventory']);
        Route::get('pos', [AdminDash::class, 'pos']);
        Route::get('settings', [AdminDash::class, 'settings']);
        Route::get('media', [AdminDash::class, 'media']);
        Route::get('seo', [AdminDash::class, 'seo']);

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
    });
});

// Public marketplace
Route::get('/learn', [LmsPageController::class, 'index']);
Route::get('/learn/projects/{slug}', [LmsPageController::class, 'project']);
Route::get('/', LandingController::class);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->where('slug', '[a-z0-9\-]+');
Route::get('/sitemap.xml', SitemapController::class);
