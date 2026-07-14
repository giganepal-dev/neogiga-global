<?php

namespace App\Jobs\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PrepareScheduledEmailCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue((string) config('marketing.email.preparation_queue', 'campaign-preparation'));
    }

    public function handle(): void
    {
        DB::table('email_campaigns')->where('status', 'scheduled')->whereNotNull('approved_at')->where('production_send_enabled', true)
            ->whereNull('paused_at')->whereNull('cancelled_at')->where('scheduled_at', '<=', now())->orderBy('id')->limit(50)->get()->each(function ($campaign): void {
                $claimed = DB::table('email_campaigns')->where('id', $campaign->id)->where('status', 'scheduled')->update(['status' => 'preparing', 'updated_at' => now()]);
                if ($claimed) {
                    SendEmailCampaignJob::dispatch(['campaign_id' => $campaign->id]);
                }
            });

        DB::table('newsletter_campaigns')->where('status', 'scheduled')->whereNotNull('approved_at')->where('production_send_enabled', true)
            ->whereNull('paused_at')->whereNull('cancelled_at')->where('scheduled_at', '<=', now())->orderBy('id')->limit(50)->get()->each(function ($campaign): void {
                $claimed = DB::table('newsletter_campaigns')->where('id', $campaign->id)->where('status', 'scheduled')->update(['status' => 'preparing', 'updated_at' => now()]);
                if ($claimed) {
                    SendNewsletterCampaignJob::dispatch(['campaign_id' => $campaign->id]);
                }
            });
    }
}
