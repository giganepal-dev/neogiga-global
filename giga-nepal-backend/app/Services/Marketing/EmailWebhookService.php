<?php

namespace App\Services\Marketing;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class EmailWebhookService
{
    public function __construct(private MarketingEmailProviderManager $providers, private EmailSuppressionService $suppressions) {}

    public function ingest(string $provider, string $rawPayload, ?string $signature): array
    {
        $adapter = $this->providers->provider($provider);
        if (! $adapter->verifyWebhook($rawPayload, $signature)) {
            throw new RuntimeException('Webhook signature verification failed.');
        }
        $payload = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        $events = array_is_list($payload) ? $payload : [$payload];
        $ids = [];

        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }
            $normalized = $this->normalize($event);
            $providerEventId = $normalized['provider_event_id'];
            $dedup = hash('sha256', $provider.'|'.($providerEventId ?: json_encode($event, JSON_UNESCAPED_SLASHES)));
            $existing = DB::table('email_webhook_events')->where('deduplication_key', $dedup)->value('id');
            if ($existing) {
                $ids[] = (int) $existing;

                continue;
            }
            $message = $this->findMessage($normalized['provider_message_id']);
            $ids[] = DB::table('email_webhook_events')->insertGetId([
                'provider' => $provider,
                'provider_event_id' => $providerEventId,
                'deduplication_key' => $dedup,
                'event_type' => $normalized['event_type'],
                'normalized_event_type' => $normalized['normalized_event_type'],
                'provider_message_id' => $normalized['provider_message_id'],
                'email_message_id' => $message->id ?? null,
                'email_campaign_id' => $message->email_campaign_id ?? null,
                'email_campaign_recipient_id' => $this->metadataValue($message->metadata ?? null, 'email_campaign_recipient_id'),
                'newsletter_campaign_id' => $message->newsletter_campaign_id ?? null,
                'newsletter_campaign_recipient_id' => $this->metadataValue($message->metadata ?? null, 'newsletter_campaign_recipient_id'),
                'recipient_hash' => $normalized['email'] ? hash('sha256', $normalized['email']) : null,
                'raw_payload_encrypted' => Crypt::encryptString(json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
                'normalized_payload' => json_encode($normalized),
                'signature_verified' => true,
                'processing_status' => 'pending',
                'provider_occurred_at' => $normalized['occurred_at'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return ['event_ids' => array_values(array_unique($ids)), 'received' => count($events)];
    }

    public function process(int $eventId): void
    {
        $record = DB::table('email_webhook_events')->find($eventId);
        if (! $record || $record->processing_status === 'processed') {
            return;
        }

        try {
            $event = json_decode((string) $record->normalized_payload, true) ?: [];
            $email = $event['email'] ?? null;
            $message = $record->email_message_id ? DB::table('email_messages')->find($record->email_message_id) : null;
            $type = (string) $record->normalized_event_type;
            DB::transaction(function () use ($record, $event, $email, $message, $type): void {
                if ($message) {
                    $changes = ['updated_at' => now()];
                    if ($type === 'delivered') {
                        $changes += ['status' => 'delivered', 'delivered_at' => now()];
                    }
                    if (in_array($type, ['hard_bounce', 'soft_bounce', 'complaint', 'failed'], true)) {
                        $changes += ['status' => $type, 'failed_at' => now(), 'failure_reason' => $event['diagnostic'] ?? $type];
                    }
                    DB::table('email_messages')->where('id', $message->id)->update($changes);
                    DB::table('email_message_events')->updateOrInsert(
                        ['email_message_id' => $message->id, 'provider_event_id' => $record->provider_event_id ?: $record->deduplication_key],
                        ['email_webhook_event_id' => $record->id, 'event_type' => $record->event_type, 'normalized_event_type' => $type, 'metadata' => json_encode($event), 'occurred_at' => $record->provider_occurred_at ?: now(), 'updated_at' => now(), 'created_at' => now()]
                    );
                }
                if ($email && in_array($type, ['hard_bounce', 'soft_bounce'], true)) {
                    $this->recordBounce($record, $message, $email, $event, $type);
                }
                if ($email && $type === 'complaint') {
                    $this->recordComplaint($record, $message, $email, $event);
                }
                if ($email && $type === 'unsubscribe') {
                    $this->recordProviderUnsubscribe($record, $email);
                }
                if ($record->email_campaign_recipient_id) {
                    DB::table('email_campaign_recipients')->where('id', $record->email_campaign_recipient_id)->update(['status' => $type, 'updated_at' => now()]);
                }
                if ($record->newsletter_campaign_recipient_id) {
                    DB::table('newsletter_campaign_recipients')->where('id', $record->newsletter_campaign_recipient_id)->update(['status' => $type, 'updated_at' => now()]);
                }
                DB::table('email_webhook_events')->where('id', $record->id)->update(['processing_status' => 'processed', 'processed_at' => now(), 'processing_error' => null, 'updated_at' => now()]);
            });
        } catch (Throwable $exception) {
            DB::table('email_webhook_events')->where('id', $eventId)->update([
                'processing_status' => 'failed',
                'processing_error' => mb_substr($exception->getMessage(), 0, 2000),
                'updated_at' => now(),
            ]);
            throw $exception;
        }
    }

    private function recordBounce(object $record, ?object $message, string $email, array $event, string $type): void
    {
        $address = DB::table('contact_email_addresses')->where('normalized_email', $email)->first();
        DB::table('email_bounces')->insert([
            'email_webhook_event_id' => $record->id, 'email_message_id' => $message->id ?? null,
            'contact_email_address_id' => $address->id ?? null, 'email_hash' => hash('sha256', $email),
            'bounce_type' => $type, 'provider_code' => $event['provider_code'] ?? null,
            'diagnostic' => $event['diagnostic'] ?? null, 'bounced_at' => $record->provider_occurred_at ?: now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($type === 'hard_bounce') {
            if ($address) {
                DB::table('contact_email_addresses')->where('id', $address->id)->update(['is_valid' => false, 'status' => 'hard_bounced', 'last_bounced_at' => now(), 'updated_at' => now()]);
            }
            $this->suppressions->suppress($email, 'hard_bounce', 'global', ['source' => 'provider_webhook', 'provider' => $record->provider, 'provider_reference' => $record->provider_event_id, 'email_webhook_event_id' => $record->id]);

            return;
        }
        if ($address) {
            $count = ((int) $address->soft_bounce_count) + 1;
            DB::table('contact_email_addresses')->where('id', $address->id)->update(['soft_bounce_count' => $count, 'last_bounced_at' => now(), 'status' => $count >= config('marketing.webhooks.soft_bounce_threshold', 3) ? 'soft_bounced' : $address->status, 'updated_at' => now()]);
            if ($count >= config('marketing.webhooks.soft_bounce_threshold', 3)) {
                $this->suppressions->suppress($email, 'soft_bounce_threshold', 'marketing', ['source' => 'provider_webhook', 'provider' => $record->provider, 'provider_reference' => $record->provider_event_id, 'email_webhook_event_id' => $record->id]);
            }
        }
    }

    private function recordComplaint(object $record, ?object $message, string $email, array $event): void
    {
        $address = DB::table('contact_email_addresses')->where('normalized_email', $email)->first();
        DB::table('email_complaints')->insert([
            'email_webhook_event_id' => $record->id, 'email_message_id' => $message->id ?? null,
            'contact_email_address_id' => $address->id ?? null, 'email_hash' => hash('sha256', $email),
            'complaint_type' => $event['complaint_type'] ?? 'abuse', 'provider_feedback_id' => $event['provider_event_id'] ?? null,
            'complained_at' => $record->provider_occurred_at ?: now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($address) {
            DB::table('contact_email_addresses')->where('id', $address->id)->update(['is_valid' => false, 'status' => 'complained', 'updated_at' => now()]);
        }
        $this->suppressions->suppress($email, 'complaint', 'global', ['source' => 'provider_webhook', 'provider' => $record->provider, 'provider_reference' => $record->provider_event_id, 'email_webhook_event_id' => $record->id]);
    }

    private function recordProviderUnsubscribe(object $record, string $email): void
    {
        DB::table('unsubscribes')->insert([
            'email' => $email, 'channel' => 'email', 'reason' => 'provider_unsubscribe', 'scope' => 'all_marketing',
            'source' => 'provider_webhook', 'unsubscribed_at' => now(), 'confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('customer_profiles')->whereRaw('LOWER(email) = ?', [$email])->update(['marketing_opt_in' => false, 'marketing_status' => 'unsubscribed', 'updated_at' => now()]);
        $this->suppressions->suppress($email, 'unsubscribe', 'marketing', ['source' => 'provider_webhook', 'provider' => $record->provider, 'provider_reference' => $record->provider_event_id, 'email_webhook_event_id' => $record->id]);
    }

    private function normalize(array $event): array
    {
        $rawType = mb_strtolower((string) ($event['event'] ?? $event['event_type'] ?? $event['type'] ?? 'unknown'));
        $normalized = match (true) {
            str_contains($rawType, 'complaint'), str_contains($rawType, 'spam') => 'complaint',
            str_contains($rawType, 'hard') && str_contains($rawType, 'bounce') => 'hard_bounce',
            str_contains($rawType, 'soft') && str_contains($rawType, 'bounce') => 'soft_bounce',
            $rawType === 'bounce', $rawType === 'bounced' => (($event['bounce_type'] ?? $event['severity'] ?? '') === 'hard' ? 'hard_bounce' : 'soft_bounce'),
            str_contains($rawType, 'unsubscribe') => 'unsubscribe',
            str_contains($rawType, 'deliver') => 'delivered',
            str_contains($rawType, 'click') => 'clicked',
            str_contains($rawType, 'open') => 'opened',
            str_contains($rawType, 'fail'), str_contains($rawType, 'reject'), str_contains($rawType, 'drop') => 'failed',
            default => $rawType ?: 'unknown',
        };
        $email = mb_strtolower(trim((string) ($event['email'] ?? $event['recipient'] ?? $event['to'] ?? data_get($event, 'data.email') ?? '')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = null;
        }
        $time = $event['timestamp'] ?? $event['occurred_at'] ?? $event['created_at'] ?? null;
        try {
            $occurred = $time ? CarbonImmutable::parse($time)->utc() : now();
        } catch (Throwable) {
            $occurred = now();
        }

        return [
            'provider_event_id' => (string) ($event['id'] ?? $event['event_id'] ?? data_get($event, 'data.id') ?? '') ?: null,
            'provider_message_id' => (string) ($event['message_id'] ?? $event['provider_message_id'] ?? data_get($event, 'data.message_id') ?? '') ?: null,
            'event_type' => $rawType ?: 'unknown', 'normalized_event_type' => $normalized, 'email' => $email,
            'provider_code' => $event['code'] ?? data_get($event, 'data.code'),
            'diagnostic' => mb_substr((string) ($event['diagnostic'] ?? $event['reason'] ?? data_get($event, 'data.reason') ?? ''), 0, 2000) ?: null,
            'complaint_type' => $event['complaint_type'] ?? $event['feedback_type'] ?? null,
            'occurred_at' => $occurred,
        ];
    }

    private function findMessage(?string $providerMessageId): ?object
    {
        if (! $providerMessageId) {
            return null;
        }

        // A signed recipient address may drive suppression, but it is not a
        // sufficiently strong identifier for linking an event to a message or
        // campaign. Only the provider-issued message ID can establish that link.
        return DB::table('email_messages')->where('provider_message_id', $providerMessageId)->first();
    }

    private function metadataValue(mixed $metadata, string $key): mixed
    {
        $values = json_decode((string) ($metadata ?? '{}'), true) ?: [];

        return $values[$key] ?? null;
    }
}
