<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Seller\WarehouseController;
use App\Http\Controllers\Api\Seller\OrderController;
use App\Http\Controllers\Api\Seller\PayoutController;
use App\Http\Controllers\Api\Seller\RfqController;
use App\Http\Controllers\Api\Seller\TeamController;
use App\Http\Controllers\Api\Seller\NotificationController;
use App\Http\Controllers\Api\Seller\BulkImportController;

/*
|--------------------------------------------------------------------------
| Seller API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and are assigned
| to the "sanctum" middleware group. All seller API endpoints require
| authentication via Sanctum tokens.
|
*/

Route::prefix('seller')->middleware(['auth:sanctum'])->group(function () {

    // ==================== WAREHOUSES ====================
    Route::prefix('warehouses')->controller(WarehouseController::class)->group(function () {
        Route::get('/', 'index');              // List all warehouses
        Route::post('/', 'store');             // Create warehouse
        Route::get('/{warehouse}', 'show');    // Get single warehouse
        Route::put('/{warehouse}', 'update');  // Update warehouse
        Route::post('/{warehouse}/submit-verification', 'submitForVerification');
        Route::delete('/{warehouse}', 'destroy');
    });

    // ==================== ORDERS ====================
    Route::prefix('orders')->controller(OrderController::class)->group(function () {
        Route::get('/', 'index');                      // List orders
        Route::get('/{order}', 'show');                // Get single order
        Route::post('/{order}/confirm', 'confirm');    // Confirm order
        Route::post('/{order}/reject', 'reject');      // Reject order
        Route::post('/{order}/prepare', 'prepareForShipment');
        Route::post('/{order}/shipment', 'createShipment');
        Route::post('/{order}/cancel', 'cancel');
        Route::get('/stats', 'stats');                 // Order statistics
    });

    // ==================== PAYOUTS ====================
    Route::prefix('payouts')->controller(PayoutController::class)->group(function () {
        Route::get('/', 'index');                    // Payout history
        Route::get('/{payout}', 'show');             // Single payout
        Route::post('/request', 'requestPayout');    // Request payout
        Route::get('/earnings', 'earningsSummary');  // Earnings summary
        Route::get('/balance', 'balance');           // Payable balance
        Route::post('/statements', 'generateStatement');
        Route::get('/{payout}/download', 'downloadStatement');
    });

    // ==================== RFQs & QUOTATIONS ====================
    Route::prefix('rfqs')->controller(RfqController::class)->group(function () {
        Route::get('/available', 'available');           // Available RFQs to quote
        Route::get('/my-quotations', 'myQuotations');    // Seller's quotations
        Route::post('/{rfq}/quote', 'submitQuotation');  // Submit quotation
        Route::get('/quotations/{quotation}', 'showQuotation');
        Route::put('/quotations/{quotation}', 'updateQuotation');
        Route::post('/quotations/{quotation}/submit', 'submitDraftQuotation');
        Route::post('/quotations/{quotation}/revise', 'reviseQuotation');
        Route::get('/quotations/stats', 'stats');
    });

    // ==================== TEAM MANAGEMENT ====================
    Route::prefix('team')->controller(TeamController::class)->group(function () {
        Route::get('/', 'index');                        // List team members
        Route::post('/invite', 'invite');                // Invite member
        Route::get('/invitations/pending', 'pendingInvitations');
        Route::post('/invitations/{invitation}/resend', 'resendInvitation');
        Route::post('/invitations/{invitation}/cancel', 'cancelInvitation');
        Route::put('/members/{member}/role', 'updateRole');
        Route::post('/members/{member}/deactivate', 'deactivate');
        Route::post('/members/{member}/reactivate', 'reactivate');
        Route::delete('/members/{member}', 'destroy');
        Route::get('/roles', 'roles');                   // Available roles
    });

    // ==================== NOTIFICATIONS ====================
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index');                        // List notifications
        Route::get('/unread', 'unreadCount');            // Unread count
        Route::post('/{notification}/read', 'markAsRead');
        Route::post('/read-all', 'markAllAsRead');
        Route::delete('/{notification}', 'destroy');
    });

    // ==================== BULK IMPORTS ====================
    Route::prefix('imports')->controller(BulkImportController::class)->group(function () {
        Route::post('/products/preview', 'previewProducts');
        Route::post('/products', 'importProducts');
        Route::post('/offers/preview', 'previewOffers');
        Route::post('/offers', 'importOffers');
        Route::post('/stock/preview', 'previewStock');
        Route::post('/stock', 'importStock');
        Route::get('/reports/{filename}', 'downloadReport');
    });

});

// Public invitation acceptance route
Route::get('/seller/invite/{token}', [TeamController::class, 'acceptInvitation'])
    ->name('seller.invitations.accept');
