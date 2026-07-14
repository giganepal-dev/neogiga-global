<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\EmailProviderConfigurationService;
use App\Services\Marketing\MarketingEmailProviderManager;
use App\Services\Marketing\RegionalEmailBrandingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendNewsletterEmailBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(public int $campaignId)
    {
        $this->onQueue((string) config('marketing.email.queue', 'marketing'));
    }

    public function handle(MarketingEmailProviderManager $providers, RegionalEmailBrandingService $branding, ?EmailProviderConfigurationService $configuration = null): void
    {
        $configuration ??= app(EmailProviderConfigurationService::class);
        $configuration->apply('marketing');
        $campaign = DB::table('newsletter_campaigns')->find($this->campaignId);
        if (! $campaign || $campaign->paused_at || $campaign->cancelled_at) {
            return;
        }
        if (! config('marketing.email.sending_enabled', false) || ! $campaign->approved_at || ! $campaign->production_send_enabled) {
            return;
        }
        $providerName = (string) config('marketing.email.provider', 'sandbox');
        if (in_array($providerName, ['sandbox', 'log'], true)) {
            return;
        }

        $breaker = DB::table('email_delivery_circuit_breakers')->where('provider', $providerName)->where('channel', 'marketing')->first();
        if ($breaker && $breaker->state === 'open' && $breaker->retry_after && now()->lt($breaker->retry_after)) {
            self::dispatch($this->campaignId)->delay($breaker->retry_after);

            return;
        }

        $sender = $branding->context($campaign->marketplace_id ? (int) $campaign->marketplace_id : null, 'marketing');
        if (! $sender['verified'] || ! $sender['enabled']) {
            throw new RuntimeException('Newsletter sender profile is not verified and enabled.');
        }
        $sentToday = DB::table('email_messages')->whereIn('message_type', ['marketing', 'newsletter'])->whereDate('sent_at', today())->count();
        $dailyRemaining = max(0, (int) config('marketing.email.daily_limit', 5000) - $sentToday);
        if ($dailyRemaining < 1) {
            self::dispatch($this->campaignId)->delay(now()->addDay()->startOfDay());

            return;
        }
        $limit = max(1, min($dailyRemaining, (int) config('marketing.email.rate_limit_per_minute', 60), 250));
        $messages = DB::table('email_messages')
            ->where('newsletter_campaign_id', $this->campaignId)
            ->where('message_type', 'newsletter')
            ->where('status', 'queued')
            ->orderBy('id')->limit($limit)->get();
        if ($messages->isEmpty()) {
            return;
        }

        try {
            $payload = $messages->map(fn ($message) => [
                'client_reference' => (string) $message->id,
                'to' => $message->to_email,
                'subject' => $message->subject,
                'html' => $message->html_body,
                'text' => $message->text_body,
                'from' => ['name' => $sender['from_name'], 'email' => $sender['from_email']],
                'reply_to' => $campaign->reply_to ?: $sender['reply_to'],
            ])->all();
            $result = $providers->provider($providerName)->sendBatch($payload);
            DB::transaction(function () use ($messages, $result, $providerName): void {
                $providerIds = collect(is_array($result['messages'] ?? null) ? $result['messages'] : [])
                    ->filter(fn ($item) => is_array($item) && isset($item['client_reference']))
                    ->mapWithKeys(fn ($item) => [(string) $item['client_reference'] => $item['id'] ?? $item['message_id'] ?? null]);
                $batchId = $result['batch_id'] ?? $result['provider_message_id'] ?? null;
                foreach ($messages as $message) {
                    $metadata = json_decode((string) ($message->metadata ?? '{}'), true) ?: [];
                    if ($batchId) {
                        $metadata['provider_batch_id'] = (string) $batchId;
                    }
                    $messageId = $providerIds->get((string) $message->id);
                    if (! $messageId && $messages->count() === 1) {
                        $messageId = $result['provider_message_id'] ?? null;
                    }
                    DB::table('email_messages')->where('id', $message->id)->where('status', 'queued')->update([
                        'provider' => $providerName,
                        'provider_message_id' => $messageId,
                        'metadata' => json_encode($metadata),
                        'status' => 'accepted',
                        'attempts' => DB::raw('attempts + 1'),
                        'sent_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::table('newsletter_campaigns')->where('id', $this->campaignId)->update(['status' => 'sending', 'send_cursor' => (int) $messages->max('id'), 'updated_at' => now()]);
                DB::table('email_delivery_circuit_breakers')->updateOrInsert(
                    ['provider' => $providerName, 'channel' => 'marketing'],
                    ['state' => 'closed', 'consecutive_failures' => 0, 'opened_at' => null, 'retry_after' => null, 'updated_at' => now(), 'created_at' => now()],
                );
            });
        } catch (Throwable $exception) {
            $currentBreaker = DB::table('email_delivery_circuit_breakers')->where('provider', $providerName)->where('channel', 'marketing')->first();
            $failures = ((int) ($currentBreaker->consecutive_failures ?? 0)) + 1;
            DB::table('email_delivery_circuit_breakers')->updateOrInsert(
                ['provider' => $providerName, 'channel' => 'marketing'],
                ['state' => $failures >= 3 ? 'open' : 'closed', 'consecutive_failures' => $failures, 'opened_at' => $failures >= 3 ? now() : null, 'retry_after' => $failures >= 3 ? now()->addMinutes(30) : now()->addMinutes(5), 'last_failure_code' => 'newsletter_batch_failure', 'updated_at' => now(), 'created_at' => now()],
            );
            throw $exception;
        }

        if (DB::table('email_messages')->where('newsletter_campaign_id', $this->campaignId)->where('message_type', 'newsletter')->where('status', 'queued')->exists()) {
            self::dispatch($this->campaignId)->delay(now()->addMinute());
        } else {
            DB::table('newsletter_campaigns')->where('id', $this->campaignId)->where('status', 'sending')->update(['status' => 'completed', 'sent_at' => now(), 'updated_at' => now()]);
        }
    }
}
