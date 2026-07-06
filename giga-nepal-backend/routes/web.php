<?php

use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Admin\DashboardController as AdminDash;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\SitemapController;
use Illuminate\Support\Facades\Route;

/*
| Admin console (server-rendered). Reachable at /admin on any host; on
| admin.neogiga.com the root redirects to it. Registered BEFORE the public
| landing route so the admin host resolves to the console, not the landing.
*/
Route::domain('admin.neogiga.com')->get('/', fn () => redirect('/admin'));

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
    });
});

// Public marketplace
Route::get('/', LandingController::class);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->where('slug', '[a-z0-9\-]+');
Route::get('/sitemap.xml', SitemapController::class);
