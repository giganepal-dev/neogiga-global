<?php

namespace App\Services\Marketing\Contracts;

interface MarketingEmailProviderInterface
{
    public function createOrUpdateContact(array $contact): array;

    public function deleteOrSuppressContact(string $email, array $context = []): array;

    public function addContactToList(string $email, string $list): array;

    public function removeContactFromList(string $email, string $list): array;

    public function sendCampaign(array $campaign): array;

    public function sendBatch(array $messages): array;

    public function scheduleCampaign(array $campaign, string $scheduledAt): array;

    public function cancelScheduledCampaign(string $providerCampaignId): array;

    public function getCampaignStatus(string $providerCampaignId): array;

    public function fetchEvents(array $cursor = []): array;

    public function verifyWebhook(string $payload, ?string $signature): bool;

    public function processWebhook(array $payload): array;

    public function testConnection(): array;
}
