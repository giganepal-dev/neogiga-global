<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payments\PaymentProvider;
use App\Services\Payments\Contracts\PaymentGateway;

/**
 * Safe default adapter for every provider until a real integration is wired.
 * Performs NO network calls and reads NO credentials — it simply reports
 * "unconfigured" so checkout falls back to the manual/pending flow.
 */
class PlaceholderGateway implements PaymentGateway
{
    public function __construct(private readonly PaymentProvider $provider)
    {
    }

    public function code(): string
    {
        return $this->provider->code;
    }

    public function provider(): PaymentProvider
    {
        return $this->provider;
    }

    public function initiate(int $orderId, float $amount, string $currency, array $context = []): array
    {
        return [
            'status' => 'unconfigured',
            'message' => "Gateway [{$this->provider->code}] is not yet configured; use manual settlement.",
        ];
    }

    public function verify(array $payload): array
    {
        // Signature validation placeholder — always unverified until credentials exist.
        return ['verified' => false, 'status' => 'unconfigured'];
    }

    public function refund(int $paymentId, float $amount, array $context = []): array
    {
        return ['status' => 'unconfigured', 'message' => 'Refund must be processed manually.'];
    }
}
