<?php

namespace App\Services\Payments;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PaymentMethodPolicyService
{
    /**
     * Legacy methods are allowed only until the provider registry is populated.
     * Once payment_providers has rows, that registry is the server-side allowlist.
     *
     * @var array<string, string>
     */
    private array $legacyMethods = [
        'manual' => 'Manual invoice',
        'bank_transfer' => 'Bank transfer',
        'cod' => 'Cash on delivery',
    ];

    /**
     * @return Collection<int, array{code:string,name:string}>
     */
    public function allowedMethods(?int $marketplaceId = null, ?string $currencyCode = null): Collection
    {
        if (! Schema::hasTable('payment_providers')) {
            return $this->legacyCollection();
        }

        $providers = DB::table('payment_providers')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($providers->isEmpty()) {
            return $this->legacyCollection();
        }

        return $providers
            ->filter(fn ($provider) => (bool) $provider->is_enabled)
            ->filter(fn ($provider) => $this->supportsCurrency($provider->supported_currencies ?? null, $currencyCode))
            ->map(fn ($provider) => [
                'code' => (string) $provider->code,
                'name' => (string) $provider->name,
            ])
            ->values();
    }

    public function assertAllowed(string $method, ?int $marketplaceId = null, ?string $currencyCode = null): void
    {
        if (! $this->isAllowed($method, $marketplaceId, $currencyCode)) {
            throw ValidationException::withMessages([
                'payment_method' => 'The selected payment method is not enabled for this marketplace or currency.',
            ]);
        }
    }

    public function isAllowed(string $method, ?int $marketplaceId = null, ?string $currencyCode = null): bool
    {
        return $this->allowedMethods($marketplaceId, $currencyCode)
            ->pluck('code')
            ->contains($method);
    }

    /**
     * @return Collection<int, array{code:string,name:string}>
     */
    private function legacyCollection(): Collection
    {
        return collect($this->legacyMethods)
            ->map(fn (string $name, string $code) => ['code' => $code, 'name' => $name])
            ->values();
    }

    private function supportsCurrency(mixed $supportedCurrencies, ?string $currencyCode): bool
    {
        if (! $currencyCode || $supportedCurrencies === null || $supportedCurrencies === '') {
            return true;
        }

        $currencies = is_array($supportedCurrencies)
            ? $supportedCurrencies
            : json_decode((string) $supportedCurrencies, true);

        if (! is_array($currencies) || $currencies === []) {
            return true;
        }

        return in_array(strtoupper($currencyCode), array_map('strtoupper', $currencies), true);
    }
}
