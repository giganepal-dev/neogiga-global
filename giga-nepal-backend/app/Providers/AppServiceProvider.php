<?php

namespace App\Providers;

use App\Services\MarketplaceResolverService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // AI tool surface — DB-backed; agents can never invent price/stock.
        $this->app->bind(
            \App\Services\Ai\AiToolsContract::class,
            \App\Services\Ai\DatabaseAiTools::class,
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
    }
}
