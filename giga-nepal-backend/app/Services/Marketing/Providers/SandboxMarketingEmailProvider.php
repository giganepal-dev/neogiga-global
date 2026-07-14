<?php

namespace App\Services\Marketing\Providers;

use App\Services\Marketing\Contracts\MarketingEmailProviderInterface;

class SandboxMarketingEmailProvider implements MarketingEmailProviderInterface
{
    public function createOrUpdateContact(array $contact): array
    {
        return $this->result('contact_sandboxed');
    }

    public function deleteOrSuppressContact(string $email, array $context = []): array
    {
        return $this->result('suppression_sandboxed');
    }

    public function addContactToList(string $email, string $list): array
    {
        return $this->result('list_add_sandboxed');
    }

    public function removeContactFromList(string $email, string $list): array
    {
        return $this->result('list_remove_sandboxed');
    }

    public function sendCampaign(array $campaign): array
    {
        return $this->result('campaign_sandboxed');
    }

    public function sendBatch(array $messages): array
    {
        return $this->result('batch_sandboxed', ['accepted' => count($messages)]);
    }

    public function scheduleCampaign(array $campaign, string $scheduledAt): array
    {
        return $this->result('schedule_sandboxed', ['scheduled_at' => $scheduledAt]);
    }

    public function cancelScheduledCampaign(string $providerCampaignId): array
    {
        return $this->result('cancel_sandboxed');
    }

    public function getCampaignStatus(string $providerCampaignId): array
    {
        return $this->result('sandbox');
    }

    public function fetchEvents(array $cursor = []): array
    {
        return $this->result('sandbox', ['events' => []]);
    }

    public function processWebhook(array $payload): array
    {
        return $this->result('webhook_sandboxed', ['event' => $payload]);
    }

    public function testConnection(): array
    {
        return $this->result('sandbox_ok', ['connected' => true]);
    }

    public function verifyWebhook(string $payload, ?string $signature): bool
    {
        $secret = (string) config('marketing.webhooks.secret');

        return $secret !== '' && is_string($signature) && hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }

    private function result(string $status, array $extra = []): array
    {
        return ['provider' => 'sandbox', 'status' => $status, 'sandbox' => true, 'provider_message_id' => null] + $extra;
    }
}
