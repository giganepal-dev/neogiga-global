<?php

namespace App\Services\Marketing\Providers;

use App\Services\Marketing\Contracts\MarketingEmailProviderInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GenericHttpMarketingEmailProvider implements MarketingEmailProviderInterface
{
    public function createOrUpdateContact(array $contact): array
    {
        return $this->request('put', '/contacts', $contact);
    }

    public function deleteOrSuppressContact(string $email, array $context = []): array
    {
        return $this->request('post', '/suppressions', ['email' => $email] + $context);
    }

    public function addContactToList(string $email, string $list): array
    {
        return $this->request('post', '/lists/'.rawurlencode($list).'/contacts', ['email' => $email]);
    }

    public function removeContactFromList(string $email, string $list): array
    {
        return $this->request('delete', '/lists/'.rawurlencode($list).'/contacts', ['email' => $email]);
    }

    public function sendCampaign(array $campaign): array
    {
        return $this->request('post', '/campaigns/send', $campaign);
    }

    public function sendBatch(array $messages): array
    {
        return $this->request('post', '/messages/batch', ['messages' => $messages]);
    }

    public function scheduleCampaign(array $campaign, string $scheduledAt): array
    {
        return $this->request('post', '/campaigns/schedule', $campaign + ['scheduled_at' => $scheduledAt]);
    }

    public function cancelScheduledCampaign(string $providerCampaignId): array
    {
        return $this->request('post', '/campaigns/'.rawurlencode($providerCampaignId).'/cancel');
    }

    public function getCampaignStatus(string $providerCampaignId): array
    {
        return $this->request('get', '/campaigns/'.rawurlencode($providerCampaignId));
    }

    public function fetchEvents(array $cursor = []): array
    {
        return $this->request('get', '/events', $cursor);
    }

    public function processWebhook(array $payload): array
    {
        return ['provider' => 'generic_http', 'status' => 'received', 'event' => $payload];
    }

    public function testConnection(): array
    {
        return $this->request('get', '/health');
    }

    public function verifyWebhook(string $payload, ?string $signature): bool
    {
        $secret = (string) config('marketing.email.webhook_secret', config('marketing.webhooks.secret'));

        return $secret !== '' && is_string($signature) && hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $response = $method === 'get'
            ? $this->client()->get($path, $payload)
            : $this->client()->send(strtoupper($method), $path, ['json' => $payload]);
        if (! $response->successful()) {
            throw new RuntimeException('Marketing provider request failed with HTTP '.$response->status().'.');
        }
        $body = $response->json();

        return ['provider' => 'generic_http', 'status' => $body['status'] ?? 'accepted', 'provider_message_id' => $body['id'] ?? $body['message_id'] ?? null, 'sandbox' => false] + (is_array($body) ? $body : []);
    }

    private function client(): PendingRequest
    {
        $baseUrl = rtrim((string) config('marketing.email.api_base_url'), '/');
        $apiKey = (string) config('marketing.email.api_key');
        if ($baseUrl === '' || $apiKey === '') {
            throw new RuntimeException('Marketing provider API URL and key are required.');
        }

        return Http::baseUrl($baseUrl)->withToken($apiKey)->acceptJson()->asJson()
            ->withHeaders(array_filter(['X-Account-ID' => config('marketing.email.account_id')]))
            ->timeout((int) config('marketing.email.timeout', 30))->retry(2, 250, throw: false);
    }
}
