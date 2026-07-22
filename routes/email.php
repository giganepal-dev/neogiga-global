<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Email\EmailDashboardController;
use App\Http\Controllers\Admin\Email\EmailSubscriberController;
use App\Http\Controllers\Admin\Email\EmailGroupController;
use App\Http\Controllers\Admin\Email\EmailCampaignController;
use App\Http\Controllers\Admin\Email\EmailTemplateController;
use App\Http\Controllers\Admin\Email\EmailImportController;
use App\Http\Controllers\Admin\Email\EmailSegmentController;
use App\Http\Controllers\Admin\Email\EmailSuppressionController;
use App\Http\Controllers\Admin\Email\EmailProviderController;
use App\Http\Controllers\Admin\Email\EmailAnalyticsController;
use App\Http\Controllers\Email\EmailWebhookController;
use App\Http\Controllers\Email\EmailPreferenceController;

/*
|--------------------------------------------------------------------------
| Email Marketing API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/admin/email')->middleware(['auth:sanctum', 'can:access_email_marketing'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [EmailDashboardController::class, 'index']);
    
    // Subscribers
    Route::apiResource('subscribers', EmailSubscriberController::class);
    Route::post('/subscribers/bulk-action', [EmailSubscriberController::class, 'bulkAction']);
    Route::get('/subscribers/export', [EmailSubscriberController::class, 'export']);
    Route::post('/subscribers/{subscriber}/assign-region', [EmailSubscriberController::class, 'assignRegion']);
    
    // Groups
    Route::apiResource('groups', EmailGroupController::class);
    Route::post('/groups/{group}/subscribers', [EmailGroupController::class, 'addSubscribers']);
    Route::delete('/groups/{group}/subscribers/{subscriber}', [EmailGroupController::class, 'removeSubscriber']);
    Route::get('/groups/{group}/analytics', [EmailGroupController::class, 'analytics']);
    
    // Segments
    Route::apiResource('segments', EmailSegmentController::class);
    Route::get('/segments/{segment}/preview', [EmailSegmentController::class, 'preview']);
    
    // Campaigns
    Route::apiResource('campaigns', EmailCampaignController::class);
    Route::post('/campaigns/{campaign}/send', [EmailCampaignController::class, 'send']);
    Route::post('/campaigns/{campaign}/schedule', [EmailCampaignController::class, 'schedule']);
    Route::post('/campaigns/{campaign}/pause', [EmailCampaignController::class, 'pause']);
    Route::post('/campaigns/{campaign}/resume', [EmailCampaignController::class, 'resume']);
    Route::post('/campaigns/{campaign}/cancel', [EmailCampaignController::class, 'cancel']);
    Route::post('/campaigns/{campaign}/test', [EmailCampaignController::class, 'sendTest']);
    Route::post('/campaigns/{campaign}/duplicate', [EmailCampaignController::class, 'duplicate']);
    Route::get('/campaigns/{campaign}/recipients', [EmailCampaignController::class, 'recipients']);
    Route::get('/campaigns/{campaign}/analytics', [EmailCampaignController::class, 'analytics']);
    
    // Templates
    Route::apiResource('templates', EmailTemplateController::class);
    Route::post('/templates/{template}/preview', [EmailTemplateController::class, 'preview']);
    
    // Imports
    Route::get('/imports', [EmailImportController::class, 'index']);
    Route::get('/imports/create', [EmailImportController::class, 'create']);
    Route::post('/imports', [EmailImportController::class, 'store']);
    Route::get('/imports/{import}', [EmailImportController::class, 'show']);
    Route::get('/imports/{import}/preview', [EmailImportController::class, 'preview']);
    Route::post('/imports/{import}/process', [EmailImportController::class, 'process']);
    Route::post('/imports/{import}/cancel', [EmailImportController::class, 'cancel']);
    Route::get('/imports/{import}/download-errors', [EmailImportController::class, 'downloadErrors']);
    Route::get('/imports/{import}/download-report', [EmailImportController::class, 'downloadReport']);
    Route::delete('/imports/{import}', [EmailImportController::class, 'destroy']);
    
    // Suppressions
    Route::get('/suppressions', [EmailSuppressionController::class, 'index']);
    Route::post('/suppressions', [EmailSuppressionController::class, 'store']);
    Route::delete('/suppressions/{id}', [EmailSuppressionController::class, 'destroy']);
    Route::post('/suppressions/bulk-remove', [EmailSuppressionController::class, 'bulkRemove']);
    
    // Providers
    Route::get('/providers', [EmailProviderController::class, 'index']);
    Route::put('/providers/{provider}', [EmailProviderController::class, 'update']);
    Route::post('/providers/{provider}/test', [EmailProviderController::class, 'test']);
    
    // Analytics
    Route::get('/analytics', [EmailAnalyticsController::class, 'index']);
    Route::get('/analytics/subscribers', [EmailAnalyticsController::class, 'subscribers']);
    Route::get('/analytics/campaigns', [EmailAnalyticsController::class, 'campaigns']);
    Route::get('/analytics/delivery', [EmailAnalyticsController::class, 'delivery']);
    Route::get('/analytics/engagement', [EmailAnalyticsController::class, 'engagement']);
    Route::post('/analytics/export', [EmailAnalyticsController::class, 'export']);
});

/*
|--------------------------------------------------------------------------
| Public Email Routes (Webhooks & Preferences)
|--------------------------------------------------------------------------
*/

// Webhooks - no auth required but signature verified
Route::post('/webhooks/email/resend', [EmailWebhookController::class, 'handleResend']);
Route::post('/webhooks/email/ses', [EmailWebhookController::class, 'handleSes']);
Route::post('/webhooks/email/smtp', [EmailWebhookController::class, 'handleSmtp']);

// Preference Centre - publicly accessible with signed URL
Route::get('/email/preferences/{token}', [EmailPreferenceController::class, 'show'])->name('email.preferences.show');
Route::post('/email/preferences/{token}', [EmailPreferenceController::class, 'update'])->name('email.preferences.update');
Route::get('/email/unsubscribe/{token}', [EmailPreferenceController::class, 'unsubscribe'])->name('email.unsubscribe');
Route::post('/email/resubscribe/{token}', [EmailPreferenceController::class, 'resubscribe'])->name('email.resubscribe');
