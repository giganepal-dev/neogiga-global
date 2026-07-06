<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\CampaignExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload = [])
    {
    }

    public function handle(CampaignExecutionService $campaigns): void
    {
        $campaignId = (int) ($this->payload['campaign_id'] ?? $this->payload['id'] ?? 0);
        if ($campaignId < 1) {
            Log::warning('SendEmailCampaignJob skipped without campaign_id', ['payload' => $this->payload]);
            return;
        }

        Log::info('SendEmailCampaignJob queued campaign in safe mode', $campaigns->sendEmailCampaign($campaignId));
    }
}
