<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group and admin authentication.
|
*/

Route::middleware(['auth:sanctum', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::post('/{id}/update-status', [OrderController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::get('/{id}/invoice', [OrderController::class, 'invoice'])->name('invoice');
        Route::get('/returns', [OrderController::class, 'returns'])->name('returns');
    });
    
    // AI Command Center (placeholders)
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/conversations', function() { return view('admin.ai.conversations'); })->name('conversations');
        Route::get('/bom', function() { return view('admin.ai.bom'); })->name('bom');
        Route::get('/pos', function() { return view('admin.ai.pos'); })->name('pos');
        Route::get('/logs', function() { return view('admin.ai.logs'); })->name('logs');
    });
    
    // POS System (placeholders)
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/manager', function() { return view('admin.pos.manager'); })->name('manager');
        Route::get('/branches', function() { return view('admin.pos.branches'); })->name('branches');
    });
    
    // Products (placeholders)
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/global', function() { return view('admin.products.global'); })->name('global');
        Route::get('/categories', function() { return view('admin.products.categories'); })->name('categories');
        Route::get('/brands', function() { return view('admin.products.brands'); })->name('brands');
    });
    
    // Sellers (placeholders)
    Route::prefix('sellers')->name('sellers.')->group(function () {
        Route::get('/applications', function() { return view('admin.sellers.applications'); })->name('applications');
        Route::get('/', function() { return view('admin.sellers.index'); })->name('index');
    });
    
    // Marketing (placeholders)
    Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::get('/campaigns', function() { return view('admin.marketing.campaigns'); })->name('campaigns');
    });
    
    // System
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/users', function() { return view('admin.system.users'); })->name('users');
        Route::get('/settings', function() { return view('admin.system.settings'); })->name('settings');
        Route::get('/queue', function() { return view('admin.system.queue'); })->name('queue');
    });
    
    // Profile
    Route::get('/profile', function() { return view('admin.profile'); })->name('profile');
});
