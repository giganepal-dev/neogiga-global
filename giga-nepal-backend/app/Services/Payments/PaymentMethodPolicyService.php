<?php

namespace App\Services\Payments;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

        $query = DB::table('payment_providers')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($marketplaceId !== null && Schema::hasColumn('payment_providers', 'marketplace_id')) {
            $scoped = (clone $query)
                ->where('marketplace_id', $marketplaceId)
                ->exists();

            if ($scoped) {
                $query->where('marketplace_id', $marketplaceId);
            } elseif ($this->isGlobalMarketplace($marketplaceId)) {
                $query->whereNull('marketplace_id');
            } else {
                return $this->configFallbackForMarketplace($marketplaceId, $currencyCode);
            }
        }

        $providers = $query->get();

        if ($providers->isEmpty()) {
            if ($marketplaceId !== null) {
                $fallback = $this->configFallbackForMarketplace($marketplaceId, $currencyCode);
                if ($fallback->isNotEmpty()) {
                    return $fallback;
                }
            }

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

    /**
     * @return Collection<int, array{code:string,name:string}>
     */
    private function configFallbackForMarketplace(int $marketplaceId, ?string $currencyCode): Collection
    {
        $prefix = DB::table('marketplaces')->where('id', $marketplaceId)->value('url_prefix');
        if (! $prefix) {
            return collect();
        }

        $gateways = config('neogiga_global.payment_gateways.'.strtolower((string) $prefix));
        if (! is_array($gateways) || $gateways === []) {
            return collect();
        }

        return collect($gateways)
            ->values()
            ->map(fn (string $name, int $index) => [
                'code' => Str::slug(strtolower($name), '_'),
                'name' => $name,
            ]);
    }

    private function isGlobalMarketplace(int $marketplaceId): bool
    {
        $marketplace = DB::table('marketplaces')->where('id', $marketplaceId)->first(['code', 'global_fallback', 'is_default']);

        if (! $marketplace) {
            return false;
        }

        $code = strtolower((string) $marketplace->code);

        return (bool) ($marketplace->global_fallback ?? false)
            || (bool) ($marketplace->is_default ?? false)
            || in_array($code, ['global', 'en'], true);
    }

    private function supportsCurrency(mixed $supportedCurrencies, ?string $currencyCode): bool
    {
        if (! $currencyCode || $supportedCurrencies === null || $supportedCurrencies === '') {
            return true;
        }

        if (is_string($supportedCurrencies)) {
            $supportedCurrencies = json_decode($supportedCurrencies, true);
        }

        if (! is_array($supportedCurrencies) || $supportedCurrencies === []) {
            return true;
        }

        return in_array(strtoupper($currencyCode), array_map('strtoupper', $supportedCurrencies), true);
    }
}
