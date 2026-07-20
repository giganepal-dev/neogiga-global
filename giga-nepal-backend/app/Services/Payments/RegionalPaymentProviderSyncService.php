<?php

namespace App\Services\Payments;

use App\Models\Marketplace\Marketplace;
use App\Models\Payments\PaymentProvider;
use Illuminate\Support\Str;

class RegionalPaymentProviderSyncService
{
    /**
     * Maps neogiga_global.payment_gateways prefix keys to marketplace url_prefix values.
     *
     * @var array<string, string>
     */
    private array $prefixAliases = [
        'global' => 'global',
    ];

    /**
     * @return array{created:int, updated:int, skipped:int}
     */
    public function sync(): array
    {
        $gateways = config('neogiga_global.payment_gateways', []);
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($gateways as $prefix => $methods) {
            $marketplace = $this->resolveMarketplace((string) $prefix);
            if (! $marketplace) {
                $stats['skipped'] += count($methods);

                continue;
            }

            $currency = $marketplace->currency?->code
                ?? strtoupper((string) config("neogiga_global.prefixes.{$prefix}.currency", 'USD'));

            foreach ($methods as $index => $label) {
                $code = $this->gatewayCode((string) $label);
                $provider = PaymentProvider::query()->firstOrNew([
                    'marketplace_id' => $marketplace->id,
                    'code' => $code,
                ]);

                $wasExisting = $provider->exists;

                $provider->fill([
                    'name' => (string) $label,
                    'is_enabled' => true,
                    'is_live' => false,
                    'supported_currencies' => [$currency],
                    'sort_order' => ($index + 1) * 10,
                ])->save();

                $wasExisting ? $stats['updated']++ : $stats['created']++;
            }
        }

        return $stats;
    }

    private function resolveMarketplace(string $prefix): ?Marketplace
    {
        if ($prefix === 'global') {
            return Marketplace::query()
                ->where(function ($query) {
                    $query->where('code', 'GLOBAL')
                        ->orWhere('code', 'global')
                        ->orWhere('global_fallback', true)
                        ->orWhere('is_default', true);
                })
                ->orderByDesc('global_fallback')
                ->orderByDesc('is_default')
                ->first();
        }

        return Marketplace::query()
            ->where('url_prefix', $prefix)
            ->orWhere('code', strtoupper($prefix))
            ->orWhere('code', strtolower($prefix))
            ->first();
    }

    private function gatewayCode(string $label): string
    {
        $normalized = Str::slug(strtolower(trim($label)), '_');

        return match ($normalized) {
            'cash_on_delivery' => 'cod',
            'cash' => 'cod',
            'upi' => 'upi',
            'wire' => 'wire_transfer',
            'bank_transfer' => 'bank_transfer',
            default => $normalized,
        };
    }
}
