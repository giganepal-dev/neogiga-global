<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\WhatsAppCampaignExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload = []) {}

    public function handle(WhatsAppCampaignExecutionService $campaigns): void
    {
        $campaignId = (int) ($this->payload['campaign_id'] ?? $this->payload['id'] ?? 0);
        if ($campaignId < 1) {
            Log::warning('SendWhatsAppCampaignJob skipped without campaign_id', ['payload' => $this->payload]);

            return;
        }

        Log::info('SendWhatsAppCampaignJob queued campaign for manual export', $campaigns->queueCampaign($campaignId));
    }
}
