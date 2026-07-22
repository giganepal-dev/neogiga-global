<?php

namespace App\Jobs\Email\Webhook;

use App\Services\Email\Webhook\DeliveryWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDeliveryWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    protected string $provider;
    protected array $payload;

    public function __construct(string $provider, array $payload)
    {
        $this->provider = $provider;
        $this->payload = $payload;
    }

    public function handle(DeliveryWebhookService $webhookService): void
    {
        Log::info("Processing {$this->provider} webhook", [
            'provider' => $this->provider,
            'event_type' => $this->payload['type'] ?? $this->payload['event'] ?? 'unknown',
        ]);

        try {
            match ($this->provider) {
                'resend' => $webhookService->handleResendWebhook($this->payload),
                'ses' => $webhookService->handleSesWebhook($this->payload),
                'smtp' => $webhookService->handleSmtpWebhook($this->payload),
                default => throw new \Exception("Unknown provider: {$this->provider}"),
            };

            Log::info("Successfully processed {$this->provider} webhook");
            
        } catch (\Exception $e) {
            Log::error("Failed to process {$this->provider} webhook: " . $e->getMessage(), [
                'provider' => $this->provider,
                'payload' => $this->payload,
            ]);

            throw $e;
        }
    }
}
