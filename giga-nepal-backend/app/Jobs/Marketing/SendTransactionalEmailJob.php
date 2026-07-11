<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\EmailProviderManager;
use App\Services\Marketing\EmailQueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendTransactionalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload = [])
    {
    }

    public function handle(EmailProviderManager $provider, EmailQueueService $queue): void
    {
        if (! Schema::hasTable('email_messages')) {
            return;
        }

        $messageId = (int) ($this->payload['email_message_id'] ?? 0);
        if ($messageId < 1 && ! empty($this->payload['to_email'])) {
            $messageId = $queue->queue(
                (string) $this->payload['to_email'],
                (string) ($this->payload['subject'] ?? 'NeoGiga notification'),
                (string) ($this->payload['html_body'] ?? $this->payload['text_body'] ?? ''),
                'transactional',
                $this->payload['metadata'] ?? [],
            );
        }

        $messages = DB::table('email_messages')
            ->where('message_type', 'transactional')
            ->whereIn('status', ['queued', 'scheduled'])
            ->when($messageId > 0, fn ($query) => $query->where('id', $messageId))
            ->when($messageId < 1, fn ($query) => $query->where(function ($inner) {
                $inner->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })->limit(max(1, min(250, (int) ($this->payload['limit'] ?? 100)))))
            ->orderBy('id')
            ->get();

        foreach ($messages as $message) {
            $result = $provider->send((array) $message);
            $status = ($result['status'] ?? 'queued') === 'test_queued' ? 'test_queued' : 'sent';

            DB::table('email_messages')->where('id', $message->id)->update([
                'provider' => $result['provider'] ?? $message->provider,
                'status' => $status,
                'sent_at' => now(),
                'metadata' => json_encode(array_merge(
                    json_decode((string) ($message->metadata ?? '{}'), true) ?: [],
                    ['provider_result' => $result],
                )),
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('email_message_events')) {
                DB::table('email_message_events')->insert([
                    'email_message_id' => $message->id,
                    'event_type' => $status,
                    'provider_event_id' => $result['provider_message_id'] ?? null,
                    'metadata' => json_encode($result),
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
