<?php

use App\Http\Controllers\Seller\DashboardController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\Seller\WarehouseController;
use App\Http\Controllers\Seller\OrderController;
use App\Http\Controllers\Seller\RfqController;
use App\Http\Controllers\Seller\FinanceController;
use App\Http\Controllers\Seller\TeamController;
use App\Http\Controllers\Seller\SupportController;
use App\Http\Controllers\Seller\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Seller Portal Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:vendor'])->prefix('seller')->name('seller.')->group(function () {
    
    // Dashboard & Readiness
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/readiness', [DashboardController::class, 'readiness'])->name('readiness');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/add', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/match', [ProductController::class, 'match'])->name('products.match');
    Route::post('/products/search-mpn', [ProductController::class, 'searchMpn'])->name('products.search-mpn');
    Route::get('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::post('/products/import', [ProductController::class, 'processImport'])->name('products.process-import');
    Route::get('/products/drafts', [ProductController::class, 'drafts'])->name('products.drafts');
    Route::get('/products/rejected', [ProductController::class, 'rejected'])->name('products.rejected');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{id}/pause', [ProductController::class, 'pause'])->name('products.pause');
    Route::post('/products/{id}/resume', [ProductController::class, 'resume'])->name('products.resume');
    Route::post('/products/{id}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');

    // Inventory
    Route::get('/inventory', [DashboardController::class, 'inventory'])->name('inventory.index');
    Route::get('/inventory/warehouse', [DashboardController::class, 'warehouseStock'])->name('inventory.warehouse');
    Route::get('/inventory/movements', [DashboardController::class, 'movements'])->name('inventory.movements');
    Route::get('/inventory/reservations', [DashboardController::class, 'reservations'])->name('inventory.reservations');
    Route::get('/inventory/alerts', [DashboardController::class, 'alerts'])->name('inventory.alerts');
    Route::post('/inventory/adjust', [DashboardController::class, 'adjustStock'])->name('inventory.adjust');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('/orders/{id}/reject', [OrderController::class, 'reject'])->name('orders.reject');
    Route::post('/orders/{id}/ship', [OrderController::class, 'ship'])->name('orders.ship');
    Route::get('/orders/returns', [OrderController::class, 'returns'])->name('orders.returns');
    Route::get('/orders/cancellations', [OrderController::class, 'cancellations'])->name('orders.cancellations');

    // RFQs & Quotations
    Route::get('/rfqs', [RfqController::class, 'index'])->name('rfqs.index');
    Route::get('/rfqs/{id}', [RfqController::class, 'show'])->name('rfqs.show');
    Route::post('/rfqs/{id}/quote', [RfqController::class, 'submitQuotation'])->name('rfqs.quote');
    Route::get('/quotations', [RfqController::class, 'quotations'])->name('quotations.index');
    Route::get('/quotations/{id}/revise', [RfqController::class, 'reviseQuotation'])->name('quotations.revise');
    Route::put('/quotations/{id}', [RfqController::class, 'updateQuotation'])->name('quotations.update');
    Route::post('/quotations/{id}/decline', [RfqController::class, 'declineQuotation'])->name('quotations.decline');

    // Warehouses
    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
    Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::get('/warehouses/{id}', [WarehouseController::class, 'show'])->name('warehouses.show');
    Route::get('/warehouses/{id}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
    Route::put('/warehouses/{id}', [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

    // Logistics (Dispatch, Shipments, Tracking)
    Route::get('/dispatch', [DashboardController::class, 'dispatch'])->name('dispatch.index');
    Route::get('/shipments', [DashboardController::class, 'shipments'])->name('shipments.index');
    Route::get('/shipments/{id}', [DashboardController::class, 'shipmentShow'])->name('shipments.show');
    Route::get('/pickups', [DashboardController::class, 'pickups'])->name('pickups.index');
    Route::get('/tracking', [DashboardController::class, 'tracking'])->name('tracking.index');

    // Finance (Earnings, Payouts, Statements, Commissions, Taxes)
    Route::get('/earnings', [FinanceController::class, 'dashboard'])->name('earnings.dashboard');
    Route::get('/payouts', [FinanceController::class, 'payouts'])->name('payouts.index');
    Route::get('/payouts/{id}', [FinanceController::class, 'showPayout'])->name('payouts.show');
    Route::get('/statements', [FinanceController::class, 'statements'])->name('statements.index');
    Route::get('/commissions', [FinanceController::class, 'commissions'])->name('commissions.index');
    Route::get('/taxes', [FinanceController::class, 'taxes'])->name('taxes.index');
    Route::get('/invoices', [FinanceController::class, 'invoices'])->name('invoices.index');
    Route::get('/invoices/{id}/download', [FinanceController::class, 'downloadInvoice'])->name('invoices.download');
    Route::post('/export', [FinanceController::class, 'export'])->name('export');

    // Marketplace Access
    Route::get('/marketplace', [DashboardController::class, 'marketplace'])->name('marketplace.index');
    Route::get('/pricing', [DashboardController::class, 'pricing'])->name('pricing.index');
    Route::get('/offers', [DashboardController::class, 'offers'])->name('offers.index');

    // Performance & Compliance
    Route::get('/performance', [DashboardController::class, 'performance'])->name('performance.index');
    Route::get('/compliance', [DashboardController::class, 'compliance'])->name('compliance.index');

    // Business Profile & Documents
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile.index');
    Route::put('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');
    Route::get('/documents', [DashboardController::class, 'documents'])->name('documents.index');
    Route::post('/documents', [DashboardController::class, 'uploadDocument'])->name('documents.upload');
    Route::delete('/documents/{id}', [DashboardController::class, 'deleteDocument'])->name('documents.delete');

    // Team Members
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::get('/team/create', [TeamController::class, 'create'])->name('team.create');
    Route::post('/team', [TeamController::class, 'store'])->name('team.store');
    Route::get('/team/{id}', [TeamController::class, 'show'])->name('team.show');
    Route::get('/team/{id}/edit', [TeamController::class, 'edit'])->name('team.edit');
    Route::put('/team/{id}', [TeamController::class, 'update'])->name('team.update');
    Route::delete('/team/{id}', [TeamController::class, 'destroy'])->name('team.destroy');
    Route::post('/team-invitations/{id}/resend', [TeamController::class, 'resendInvitation'])->name('team.resend-invitation');
    Route::post('/team-invitations/{id}/revoke', [TeamController::class, 'revokeInvitation'])->name('team.revoke-invitation');

    // Support
    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::get('/support/create', [SupportController::class, 'create'])->name('support.create');
    Route::post('/support', [SupportController::class, 'store'])->name('support.store');
    Route::get('/support/{id}', [SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{id}/reply', [SupportController::class, 'reply'])->name('support.reply');
    Route::post('/support/{id}/close', [SupportController::class, 'close'])->name('support.close');
    Route::post('/support/{id}/reopen', [SupportController::class, 'reopen'])->name('support.reopen');
    Route::post('/support/{id}/rate', [SupportController::class, 'rate'])->name('support.rate');

    // Settings
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings.index');
    Route::put('/settings', [DashboardController::class, 'updateSettings'])->name('settings.update');
});
