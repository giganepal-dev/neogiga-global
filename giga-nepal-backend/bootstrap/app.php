<?php

use App\Http\Middleware\EnsureAdminToken;
use App\Http\Middleware\EnsureAdminTokenPermission;
use App\Http\Middleware\EnsureAdminWeb;
use App\Http\Middleware\EnsureAdminWebPermission;
use App\Http\Middleware\EnsureDistributorWeb;
use App\Http\Middleware\EnsureResellerWeb;
use App\Http\Middleware\EnsureSellerWeb;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\ForceMarketplaceRecommendationRedirect;
use App\Http\Middleware\CachePublicPages;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Full-page cache FIRST — skip entire Laravel stack for cached pages.
        $middleware->prepend(CachePublicPages::class);

        $middleware->append(SecurityHeaders::class);
        $middleware->append(ForceMarketplaceRecommendationRedirect::class);

        // Interim admin gate; replace with Sanctum + policies in Phase 1 (SEC-01/02).
        $middleware->alias([
            'admin.token' => EnsureAdminToken::class,
            'admin.permission' => EnsureAdminTokenPermission::class,
            'admin.web' => EnsureAdminWeb::class,
            'admin.web.permission' => EnsureAdminWebPermission::class,
            'seller.web' => EnsureSellerWeb::class,
            'distributor.web' => EnsureDistributorWeb::class,
            'reseller.web' => EnsureResellerWeb::class,
            'api.token' => AuthenticateApiToken::class,
            'permission' => EnsurePermission::class,
            'pcb.auth' => \App\Http\Middleware\EnsurePcbWebAuth::class,
        ]);

        // Default API rate limit (limiter defined in AppServiceProvider).
        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
