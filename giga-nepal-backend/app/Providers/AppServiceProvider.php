<?php

namespace App\Providers;

use App\Models\Marketplace\ProductCategory;
use App\Services\Ai\AiToolsContract;
use App\Services\Ai\DatabaseAiTools;
use App\Services\Marketing\EmailProviderConfigurationService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\MarketplaceSeoRenderer;
use App\Services\MarketplaceResolverService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One resolved marketplace per request lifecycle.
        $this->app->scoped(MarketplaceResolverService::class);
        $this->app->scoped(GlobalMarketplaceContextService::class);

        // AI tool surface — DB-backed; agents can never invent price/stock.
        $this->app->bind(
            AiToolsContract::class,
            DatabaseAiTools::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The marketplace schema lives in a subdirectory and is NOT picked up
        // by Laravel automatically (audit finding DB-01).
        $this->loadMigrationsFrom(database_path('migrations/marketplace'));
        $this->loadMigrationsFrom(database_path('migrations/marketing'));
        $this->loadMigrationsFrom(database_path('migrations/admin_console'));
        $this->loadMigrationsFrom(database_path('migrations/inventory_pos'));
        $this->loadMigrationsFrom(database_path('migrations/lms'));
        $this->loadMigrationsFrom(database_path('migrations/b2b'));
        $this->loadMigrationsFrom(database_path('migrations/distributor'));

        // Admin-managed email transports are encrypted in the existing provider table.
        // The service safely falls back to environment configuration until that table exists.
        app(EmailProviderConfigurationService::class)->applyAll();

        // Default API limiter (SEC-05). Keyed by user when authenticated, IP otherwise.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Stricter limiter for anonymous write endpoints (vendor registration etc.).
        RateLimiter::for('writes', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(5)->by(($request->input('email') ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('marketing', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        View::composer('frontend.*', function ($view) {
            $request = request();
            $context = app(GlobalMarketplaceContextService::class)->context($request);

            $searchCategories = Cache::remember('layout:search-categories', 3600, function () {
                return ProductCategory::query()
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->where('sort_order', '>', 0)
                    ->orderBy('sort_order')
                    ->limit(8)
                    ->get(['name', 'slug'])
                    ->toArray();
            });

            $view->with('marketplaceContext', $context)
                ->with('locale', $context['locale'] ?? 'en')
                ->with('searchCategories', $searchCategories)
                ->with('marketplaceSeo', app(MarketplaceSeoRenderer::class)->tags($context['current'] ?? null, url()->current()));
        });
    }
}
