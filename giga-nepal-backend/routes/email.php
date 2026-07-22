<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Email\EmailDashboardController;
use App\Http\Controllers\Admin\Email\EmailSubscriberController;
use App\Http\Controllers\Admin\Email\EmailGroupController;
use App\Http\Controllers\Admin\Email\EmailSegmentController;
use App\Http\Controllers\Admin\Email\EmailCampaignController;
use App\Http\Controllers\Admin\Email\EmailTemplateController;
use App\Http\Controllers\Admin\Email\EmailImportController;
use App\Http\Controllers\Admin\Email\EmailSuppressionController;
use App\Http\Controllers\Admin\Email\EmailSenderController;
use App\Http\Controllers\Admin\Email\EmailProviderController;
use App\Http\Controllers\Admin\Email\EmailWebhookController;
use App\Http\Controllers\Admin\Email\EmailPreferenceController;
use App\Http\Controllers\Admin\Email\EmailAnalyticsController;

/*
|--------------------------------------------------------------------------
| Email Campaign Manager Routes
|--------------------------------------------------------------------------
*/

// Public preference centre routes
Route::prefix('email')->name('email.')->group(function () {
    Route::get('/preference/{token}', [EmailPreferenceController::class, 'show'])->name('preference.show');
    Route::post('/preference/{token}', [EmailPreferenceController::class, 'update'])->name('preference.update');
    Route::get('/unsubscribe/{token}', [EmailPreferenceController::class, 'unsubscribe'])->name('unsubscribe');
});

// Admin email management routes
Route::prefix('email')->name('admin.email.')->middleware(['admin.web'])->group(function () {
    
    // Dashboard
    Route::get('/', [EmailDashboardController::class, 'index'])->name('dashboard');
    
    // Subscribers
    Route::get('/subscribers', [EmailSubscriberController::class, 'index'])->name('subscribers.index');
    Route::get('/subscribers/create', [EmailSubscriberController::class, 'create'])->name('subscribers.create');
    Route::post('/subscribers', [EmailSubscriberController::class, 'store'])->name('subscribers.store');
    Route::get('/subscribers/{subscriber}', [EmailSubscriberController::class, 'show'])->name('subscribers.show');
    Route::get('/subscribers/{subscriber}/edit', [EmailSubscriberController::class, 'edit'])->name('subscribers.edit');
    Route::put('/subscribers/{subscriber}', [EmailSubscriberController::class, 'update'])->name('subscribers.update');
    Route::delete('/subscribers/{subscriber}', [EmailSubscriberController::class, 'destroy'])->name('subscribers.destroy');
    Route::post('/subscribers/bulk-action', [EmailSubscriberController::class, 'bulkAction'])->name('subscribers.bulk-action');
    Route::get('/subscribers/export', [EmailSubscriberController::class, 'export'])->name('subscribers.export');
    
    // Groups
    Route::get('/groups', [EmailGroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [EmailGroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [EmailGroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [EmailGroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/edit', [EmailGroupController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}', [EmailGroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [EmailGroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('/groups/{group}/add-subscribers', [EmailGroupController::class, 'addSubscribers'])->name('groups.add-subscribers');
    Route::post('/groups/{group}/remove-subscribers', [EmailGroupController::class, 'removeSubscribers'])->name('groups.remove-subscribers');
    Route::get('/groups/{group}/export', [EmailGroupController::class, 'export'])->name('groups.export');
    
    // Segments
    Route::get('/segments', [EmailSegmentController::class, 'index'])->name('segments.index');
    Route::get('/segments/create', [EmailSegmentController::class, 'create'])->name('segments.create');
    Route::post('/segments', [EmailSegmentController::class, 'store'])->name('segments.store');
    Route::get('/segments/{segment}', [EmailSegmentController::class, 'show'])->name('segments.show');
    Route::get('/segments/{segment}/edit', [EmailSegmentController::class, 'edit'])->name('segments.edit');
    Route::put('/segments/{segment}', [EmailSegmentController::class, 'update'])->name('segments.update');
    Route::delete('/segments/{segment}', [EmailSegmentController::class, 'destroy'])->name('segments.destroy');
    Route::post('/segments/{segment}/recalculate', [EmailSegmentController::class, 'recalculate'])->name('segments.recalculate');
    Route::get('/segments/{segment}/preview', [EmailSegmentController::class, 'preview'])->name('segments.preview');
    
    // Campaigns
    Route::get('/campaigns', [EmailCampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [EmailCampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [EmailCampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}', [EmailCampaignController::class, 'show'])->name('campaigns.show');
    Route::get('/campaigns/{campaign}/edit', [EmailCampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('/campaigns/{campaign}', [EmailCampaignController::class, 'update'])->name('campaigns.update');
    Route::delete('/campaigns/{campaign}', [EmailCampaignController::class, 'destroy'])->name('campaigns.destroy');
    Route::post('/campaigns/{campaign}/launch', [EmailCampaignController::class, 'launch'])->name('campaigns.launch');
    Route::post('/campaigns/{campaign}/pause', [EmailCampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('/campaigns/{campaign}/resume', [EmailCampaignController::class, 'resume'])->name('campaigns.resume');
    Route::post('/campaigns/{campaign}/cancel', [EmailCampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::post('/campaigns/{campaign}/test', [EmailCampaignController::class, 'sendTest'])->name('campaigns.test');
    Route::post('/campaigns/{campaign}/duplicate', [EmailCampaignController::class, 'duplicate'])->name('campaigns.duplicate');
    Route::get('/campaigns/{campaign}/recipients', [EmailCampaignController::class, 'recipients'])->name('campaigns.recipients');
    Route::get('/campaigns/{campaign}/analytics', [EmailCampaignController::class, 'analytics'])->name('campaigns.analytics');
    
    // Templates
    Route::get('/templates', [EmailTemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [EmailTemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [EmailTemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}', [EmailTemplateController::class, 'show'])->name('templates.show');
    Route::get('/templates/{template}/edit', [EmailTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [EmailTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [EmailTemplateController::class, 'destroy'])->name('templates.destroy');
    Route::post('/templates/{template}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('templates.duplicate');
    
    // Imports
    Route::get('/imports', [EmailImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/create', [EmailImportController::class, 'create'])->name('imports.create');
    Route::post('/imports/upload', [EmailImportController::class, 'upload'])->name('imports.upload');
    Route::post('/imports/preview', [EmailImportController::class, 'preview'])->name('imports.preview');
    Route::post('/imports/process', [EmailImportController::class, 'process'])->name('imports.process');
    Route::get('/imports/{import}', [EmailImportController::class, 'show'])->name('imports.show');
    Route::get('/imports/{import}/download-success', [EmailImportController::class, 'downloadSuccess'])->name('imports.download-success');
    Route::get('/imports/{import}/download-errors', [EmailImportController::class, 'downloadErrors'])->name('imports.download-errors');
    Route::get('/imports/{import}/download-duplicates', [EmailImportController::class, 'downloadDuplicates'])->name('imports.download-duplicates');
    
    // Suppressions
    Route::get('/suppressions', [EmailSuppressionController::class, 'index'])->name('suppressions.index');
    Route::post('/suppressions', [EmailSuppressionController::class, 'store'])->name('suppressions.store');
    Route::delete('/suppressions/{suppression}', [EmailSuppressionController::class, 'destroy'])->name('suppressions.destroy');
    Route::post('/suppressions/bulk-remove', [EmailSuppressionController::class, 'bulkRemove'])->name('suppressions.bulk-remove');
    Route::get('/suppressions/export', [EmailSuppressionController::class, 'export'])->name('suppressions.export');
    
    // Sender Identities
    Route::get('/senders', [EmailSenderController::class, 'index'])->name('senders.index');
    Route::get('/senders/create', [EmailSenderController::class, 'create'])->name('senders.create');
    Route::post('/senders', [EmailSenderController::class, 'store'])->name('senders.store');
    Route::get('/senders/{sender}', [EmailSenderController::class, 'show'])->name('senders.show');
    Route::get('/senders/{sender}/edit', [EmailSenderController::class, 'edit'])->name('senders.edit');
    Route::put('/senders/{sender}', [EmailSenderController::class, 'update'])->name('senders.update');
    Route::delete('/senders/{sender}', [EmailSenderController::class, 'destroy'])->name('senders.destroy');
    Route::post('/senders/{sender}/verify', [EmailSenderController::class, 'verify'])->name('senders.verify');
    Route::post('/senders/{sender}/set-default', [EmailSenderController::class, 'setDefault'])->name('senders.set-default');
    
    // Providers
    Route::get('/providers', [EmailProviderController::class, 'index'])->name('providers.index');
    Route::get('/providers/create', [EmailProviderController::class, 'create'])->name('providers.create');
    Route::post('/providers', [EmailProviderController::class, 'store'])->name('providers.store');
    Route::get('/providers/{provider}', [EmailProviderController::class, 'show'])->name('providers.show');
    Route::get('/providers/{provider}/edit', [EmailProviderController::class, 'edit'])->name('providers.edit');
    Route::put('/providers/{provider}', [EmailProviderController::class, 'update'])->name('providers.update');
    Route::delete('/providers/{provider}', [EmailProviderController::class, 'destroy'])->name('providers.destroy');
    Route::post('/providers/{provider}/test', [EmailProviderController::class, 'test'])->name('providers.test');
    Route::post('/providers/{provider}/set-default', [EmailProviderController::class, 'setDefault'])->name('providers.set-default');
    
    // Analytics
    Route::get('/analytics', [EmailAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/campaigns', [EmailAnalyticsController::class, 'campaigns'])->name('analytics.campaigns');
    Route::get('/analytics/subscribers', [EmailAnalyticsController::class, 'subscribers'])->name('analytics.subscribers');
    Route::get('/analytics/delivery', [EmailAnalyticsController::class, 'delivery'])->name('analytics.delivery');
    Route::get('/analytics/export', [EmailAnalyticsController::class, 'export'])->name('analytics.export');
});

// Webhook routes (no auth required - verified by signature)
Route::prefix('webhooks/email')->name('webhooks.email.')->group(function () {
    Route::post('/resend', [EmailWebhookController::class, 'resend'])->name('resend');
    Route::post('/ses', [EmailWebhookController::class, 'ses'])->name('ses');
    Route::post('/{provider}', [EmailWebhookController::class, 'generic'])->name('generic');
});
