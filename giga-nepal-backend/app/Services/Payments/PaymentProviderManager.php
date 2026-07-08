<?php

namespace App\Services\Payments;

use App\Models\Payments\PaymentProvider;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\Gateways\PlaceholderGateway;
use Illuminate\Support\Facades\DB;

/**
 * Registry + factory for payment gateways. Every provider currently resolves to
 * the PlaceholderGateway (no live calls); real adapters can be registered here
 * once credentials are configured in .env. Also records a sanitized audit event
 * against the existing payments table (payment_transaction_events).
 */
class PaymentProviderManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    private array $adapters = [
        // 'esewa' => \App\Services\Payments\Gateways\EsewaGateway::class, // wired later
    ];

    /** @return \Illuminate\Support\Collection<int, PaymentProvider> */
    public function enabled()
    {
        return PaymentProvider::where('is_enabled', true)->orderBy('sort_order')->get();
    }

    public function all()
    {
        return PaymentProvider::orderBy('sort_order')->get();
    }

    public function gateway(string $code): PaymentGateway
    {
        $provider = PaymentProvider::where('code', $code)->firstOrFail();
        $class = $this->adapters[$code] ?? PlaceholderGateway::class;

        return new $class($provider);
    }

    /**
     * Append a sanitized event to the audit trail for the EXISTING payment row.
     * Never store secrets/tokens in the payload.
     */
    public function recordEvent(?int $paymentId, ?int $orderId, ?string $providerCode, string $event, ?float $amount = null, ?string $currency = null, array $payload = []): void
    {
        DB::table('payment_transaction_events')->insert([
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'provider_code' => $providerCode,
            'event' => $event,
            'amount' => $amount,
            'currency' => $currency,
            'payload' => json_encode($this->sanitize($payload)),
            'created_at' => now(),
        ]);
    }

    private function sanitize(array $payload): array
    {
        $blocked = ['secret', 'token', 'key', 'signature', 'password', 'authorization'];

        return collect($payload)->reject(function ($v, $k) use ($blocked) {
            $k = strtolower((string) $k);
            foreach ($blocked as $needle) {
                if (str_contains($k, $needle)) {
                    return true;
                }
            }

            return false;
        })->all();
    }
}
