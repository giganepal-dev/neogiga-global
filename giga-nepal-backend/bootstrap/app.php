<?php

use App\Http\Middleware\EnsureAdminToken;
use App\Http\Middleware\EnsureAdminTokenPermission;
use App\Http\Middleware\EnsureAdminWeb;
use App\Http\Middleware\EnsureAdminWebPermission;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\ForceMarketplaceRecommendationRedirect;
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
    ->withCommands([
        \App\Console\Commands\AuditCategoryHierarchyCommand::class,
        \App\Console\Commands\AuditBrandLogosCommand::class,
        \App\Console\Commands\ImportTiParametricCatalog::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Baseline security headers on every response (SEC-07).
        $middleware->append(SecurityHeaders::class);
        $middleware->append(ForceMarketplaceRecommendationRedirect::class);

        // Interim admin gate; replace with Sanctum + policies in Phase 1 (SEC-01/02).
        $middleware->alias([
            'admin.token' => EnsureAdminToken::class,
            'admin.permission' => EnsureAdminTokenPermission::class,
            'admin.web' => EnsureAdminWeb::class,
            'admin.web.permission' => EnsureAdminWebPermission::class,
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
