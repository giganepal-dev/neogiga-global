<?php

namespace App\Services\Marketing;

use App\Jobs\Marketing\SendTransactionalEmailJob;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EmailQueueService
{
    public function __construct(private RegionalEmailBrandingService $branding) {}

    public function queue(string $to, string $subject, string $html, string $type = 'transactional', array $metadata = [], bool $dispatch = true): int
    {
        $to = mb_strtolower(trim($to));
        if (isset($metadata['sensitive_html'])) {
            $metadata['sensitive_body_encrypted'] = Crypt::encryptString((string) $metadata['sensitive_html']);
            unset($metadata['sensitive_html']);
            $html = 'This security message is encrypted while queued and is removed from the message archive after processing.';
        }
        $idempotencyKey = (string) ($metadata['idempotency_key'] ?? hash('sha256', implode('|', [$type, $to, $subject, $metadata['related_type'] ?? '', $metadata['related_id'] ?? '', $metadata['event_id'] ?? ''])));
        $existing = DB::table('email_messages')->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing->id;
        }
        $regional = $this->branding->context(isset($metadata['marketplace_id']) ? (int) $metadata['marketplace_id'] : null, $type === 'transactional' ? 'transactional' : 'marketing');
        $id = DB::table('email_messages')->insertGetId([
            'idempotency_key' => $idempotencyKey,
            'message_type' => $type,
            'provider' => config('marketing.transactional.mailer', 'log'),
            'to_email' => $to,
            'subject' => $subject,
            'html_body' => $html,
            'status' => 'queued',
            'queue_name' => $type === 'transactional' ? config('marketing.transactional.queue', 'transactional') : config('marketing.email.queue', 'marketing'),
            'sender_profile_id' => $metadata['sender_profile_id'] ?? $regional['sender_profile_id'],
            'related_type' => $metadata['related_type'] ?? null,
            'related_id' => $metadata['related_id'] ?? null,
            'marketplace_id' => $metadata['marketplace_id'] ?? null,
            'country_id' => $metadata['country_id'] ?? null,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($dispatch && $type === 'transactional') {
            // afterCommit: never let a worker race a still-open transaction —
            // the job reads email_messages by id and must see the committed row.
            SendTransactionalEmailJob::dispatch(['email_message_id' => $id])
                ->onQueue(config('marketing.transactional.queue', 'transactional'))
                ->afterCommit();
        }

        return $id;
    }
}
