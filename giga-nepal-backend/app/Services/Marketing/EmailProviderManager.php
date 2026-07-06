<?php

namespace App\Services\Marketing;

class EmailProviderManager
{
    public function provider(): string { return config('marketing.email.provider', 'log'); }
    public function testMode(): bool { return (bool) config('marketing.email.test_mode', true); }
    public function send(array $message): array
    {
        return ['provider' => $this->provider(), 'status' => $this->testMode() ? 'test_queued' : 'queued', 'provider_message_id' => null];
    }
}
