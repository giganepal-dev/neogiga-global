<?php

namespace App\Jobs;

use App\Models\EmailMarketing\EmailCampaign;
use App\Models\EmailMarketing\EmailCampaignRecipient;
use App\Services\CampaignSendingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public EmailCampaign $campaign;
    public int $chunkSize;
    
    // Job retry settings
    public $tries = 3;
    public $maxExceptions = 2;
    public $backoff = [60, 300]; // 1 min, 5 min
    public $timeout = 300; // 5 minutes timeout

    /**
     * Create a new job instance.
     */
    public function __construct(EmailCampaign $campaign, int $chunkSize = 100)
    {
        $this->campaign = $campaign;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Execute the job.
     */
    public function handle(CampaignSendingService $sendingService): void
    {
        // Re-fetch campaign to get latest status
        $campaign = EmailCampaign::find($this->campaign->id);
        
        if (!$campaign) {
            Log::error('Campaign not found', ['campaign_id' => $this->campaign->id]);
            return;
        }
        
        // Check if campaign should be processing
        if (!in_array($campaign->status, ['sending', 'queued'])) {
            Log::info('Campaign not in sending state', [
                'campaign_id' => $campaign->id,
                'status' => $campaign->status
            ]);
            return;
        }
        
        // Check scheduled time
        if ($campaign->scheduled_at && $campaign->scheduled_at > now()) {
            Log::info('Campaign scheduled for future', [
                'campaign_id' => $campaign->id,
                'scheduled_at' => $campaign->scheduled_at
            ]);
            $this->release($campaign->scheduled_at->diffInSeconds(now()));
            return;
        }
        
        try {
            DB::transaction(function () use ($campaign, $sendingService) {
                // Get pending recipients
                $recipients = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
                    ->whereIn('status', ['pending'])
                    ->limit($this->chunkSize)
                    ->get();
                
                if ($recipients->isEmpty()) {
                    // No more recipients to process - campaign complete
                    $campaign->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    
                    Log::info('Campaign completed', [
                        'campaign_id' => $campaign->id,
                        'total_sent' => $campaign->emails_sent,
                        'total_failed' => $campaign->emails_failed,
                    ]);
                    
                    return;
                }
                
                Log::info('Processing campaign chunk', [
                    'campaign_id' => $campaign->id,
                    'chunk_size' => $recipients->count(),
                ]);
                
                // Update campaign status to sending if still queued
                if ($campaign->status === 'queued') {
                    $campaign->update(['status' => 'sending']);
                }
                
                $successCount = 0;
                $failedCount = 0;
                
                foreach ($recipients as $recipient) {
                    try {
                        if ($sendingService->sendEmail($recipient, $campaign)) {
                            $successCount++;
                        } else {
                            $failedCount++;
                        }
                        
                        // Rate limiting - small delay between sends
                        usleep(100000); // 100ms
                        
                    } catch (Exception $e) {
                        Log::error('Failed to send individual email', [
                            'recipient_id' => $recipient->id,
                            'email' => $recipient->email,
                            'error' => $e->getMessage(),
                        ]);
                        $failedCount++;
                    }
                }
                
                Log::info('Campaign chunk processed', [
                    'campaign_id' => $campaign->id,
                    'success' => $successCount,
                    'failed' => $failedCount,
                ]);
            });
            
            // Dispatch next chunk if campaign still active
            $remainingRecipients = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
                ->whereIn('status', ['pending'])
                ->count();
            
            if ($remainingRecipients > 0 && in_array($campaign->fresh()->status, ['sending', 'queued'])) {
                self::dispatch($campaign, $this->chunkSize)
                    ->onQueue('emails-marketing')
                    ->delay(1);
            }
            
        } catch (Exception $e) {
            Log::error('Campaign processing failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Check if we should fail the campaign
            if ($this->attempts() >= $this->tries) {
                $campaign->update([
                    'status' => 'failed',
                    'failure_reason' => substr($e->getMessage(), 0, 500),
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Campaign job finally failed', [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
        
        $campaign = EmailCampaign::find($this->campaign->id);
        if ($campaign) {
            $campaign->update([
                'status' => 'failed',
                'failure_reason' => 'Job failed after ' . $this->attempts() . ' attempts: ' . substr($exception->getMessage(), 0, 400),
            ]);
        }
    }
}
