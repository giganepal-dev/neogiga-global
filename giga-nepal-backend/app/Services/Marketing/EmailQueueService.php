<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\DB;

class EmailQueueService
{
    public function queue(string $to, string $subject, string $html, string $type = 'transactional', array $metadata = []): int
    {
        return DB::table('email_messages')->insertGetId(['message_type' => $type, 'provider' => config('marketing.email.provider', 'log'), 'to_email' => $to, 'subject' => $subject, 'html_body' => $html, 'status' => 'queued', 'metadata' => json_encode($metadata), 'created_at' => now(), 'updated_at' => now()]);
    }
}
