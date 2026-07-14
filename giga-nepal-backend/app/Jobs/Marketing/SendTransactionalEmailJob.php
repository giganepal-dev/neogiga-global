<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\EmailEligibilityService;
use App\Services\Marketing\EmailProviderManager;
use App\Services\Marketing\EmailQueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SendTransactionalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public function __construct(public array $payload = [])
    {
        $this->onQueue((string) config('marketing.transactional.queue', 'transactional'));
    }

    public function handle(EmailProviderManager $provider, EmailQueueService $queue, ?EmailEligibilityService $eligibility = null): void
    {
        if (! Schema::hasTable('email_messages')) {
            return;
        }
        $eligibility ??= app(EmailEligibilityService::class);
        $messageId = (int) ($this->payload['email_message_id'] ?? 0);
        if ($messageId < 1 && ! empty($this->payload['to_email'])) {
            $messageId = $queue->queue((string) $this->payload['to_email'], (string) ($this->payload['subject'] ?? 'NeoGiga notification'), (string) ($this->payload['html_body'] ?? $this->payload['text_body'] ?? ''), 'transactional', $this->payload['metadata'] ?? [], false);
        }
        $messages = DB::table('email_messages')->where('message_type', 'transactional')->whereIn('status', ['queued', 'scheduled'])
            ->when($messageId > 0, fn ($query) => $query->where('id', $messageId))
            ->when($messageId < 1, fn ($query) => $query->where(function ($inner) {
                $inner->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })->limit(max(1, min(250, (int) ($this->payload['limit'] ?? 100)))))
            ->orderBy('id')->get();

        foreach ($messages as $message) {
            $decision = $eligibility->transactional($message->to_email);
            $logKey = $message->idempotency_key ?: hash('sha256', 'transactional|'.$message->id);
            if (! $decision['allowed']) {
                DB::table('email_messages')->where('id', $message->id)->update(['status' => 'suppressed', 'failed_at' => now(), 'failure_reason' => implode(',', $decision['reasons']), 'updated_at' => now()]);
                $this->log($message, $logKey, 'suppressed', null, $decision);

                continue;
            }
            $providerMessage = (array) $message;
            $storedMetadata = json_decode((string) ($message->metadata ?? '{}'), true) ?: [];
            if (! empty($storedMetadata['sensitive_body_encrypted'])) {
                $providerMessage['html_body'] = Crypt::decryptString($storedMetadata['sensitive_body_encrypted']);
            }
            $result = $provider->send($providerMessage);
            $status = (string) ($result['status'] ?? 'failed');
            if (! empty($storedMetadata['sensitive_body_encrypted']) && in_array($status, ['sent', 'test_queued'], true)) {
                unset($storedMetadata['sensitive_body_encrypted']);
                $storedMetadata['sensitive_body_redacted_at'] = now()->toIso8601String();
            }
            DB::table('email_messages')->where('id', $message->id)->update([
                'provider' => $result['provider'] ?? $message->provider,
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'status' => $status,
                'attempts' => ((int) ($message->attempts ?? 0)) + 1,
                'sent_at' => in_array($status, ['sent', 'test_queued'], true) ? now() : null,
                'failed_at' => $status === 'failed' ? now() : null,
                'failure_reason' => $result['failure_reason'] ?? null,
                'metadata' => json_encode(array_merge($storedMetadata, ['provider_result' => $result])),
                'updated_at' => now(),
            ]);
            DB::table('email_message_events')->insert(['email_message_id' => $message->id, 'event_type' => $status, 'normalized_event_type' => $status, 'provider_event_id' => $result['provider_message_id'] ?? null, 'metadata' => json_encode($result), 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $this->log($message, $logKey, $status, $result, $decision);
            if ($status === 'failed' && ($result['retryable'] ?? false)) {
                DB::table('communication_failures')->insert(['email_message_id' => $message->id, 'provider' => $result['provider'] ?? null, 'failure_code' => $result['failure_code'] ?? 'transport_error', 'failure_reason' => $result['failure_reason'] ?? 'Transactional transport failed.', 'is_retryable' => true, 'attempt' => ((int) ($message->attempts ?? 0)) + 1, 'retry_at' => now()->addMinutes(2), 'safe_context' => json_encode(['recipient_hash' => hash('sha256', mb_strtolower($message->to_email))]), 'created_at' => now(), 'updated_at' => now()]);
                throw new RuntimeException('Transactional email transport failed and will retry.');
            }
        }
    }

    private function log(object $message, string $key, string $status, ?array $result, array $decision): void
    {
        $messageMetadata = json_decode((string) ($message->metadata ?? '{}'), true) ?: [];
        DB::table('communication_logs')->updateOrInsert(['idempotency_key' => $key], [
            'event_type' => $messageMetadata['event_type'] ?? $message->message_type,
            'channel' => 'email',
            'message_class' => 'transactional',
            'email_message_id' => $message->id,
            'recipient_hash' => hash('sha256', mb_strtolower($message->to_email)),
            'provider' => $result['provider'] ?? $message->provider,
            'provider_message_id' => $result['provider_message_id'] ?? null,
            'status' => $status,
            'attempts' => ((int) ($message->attempts ?? 0)) + 1,
            'related_type' => $message->related_type ?? null,
            'related_id' => $message->related_id ?? null,
            'marketplace_id' => $message->marketplace_id ?? null,
            'country_id' => $message->country_id ?? null,
            'sent_at' => in_array($status, ['sent', 'test_queued'], true) ? now() : null,
            'failure_reason' => $status === 'suppressed' ? implode(',', $decision['reasons']) : ($result['failure_reason'] ?? null),
            'metadata' => json_encode(['eligibility' => $decision, 'sandbox' => $result['sandbox'] ?? null]),
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }
}
