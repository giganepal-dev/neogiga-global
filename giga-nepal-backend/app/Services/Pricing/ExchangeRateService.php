<?php

namespace App\Services\Pricing;

use App\Models\Marketplace\Currency;
use App\Models\Marketplace\ExchangeRate;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class ExchangeRateService
{
    public function baseCurrency(): string
    {
        return strtoupper((string) config('pricing.base_currency', 'USD'));
    }

    /**
     * Latest recorded rate for the pair regardless of age. Use freshRate()
     * for anything that feeds a price calculation.
     */
    public function latestRate(string $from, string $to): ?ExchangeRate
    {
        return ExchangeRate::query()
            ->where('from_currency_code', strtoupper($from))
            ->where('to_currency_code', strtoupper($to))
            ->where('is_active', true)
            ->orderByDesc('fetched_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Latest rate only if it is within the configured staleness window —
     * stale rates are refused (null) so calculations fail loudly rather than
     * silently price off an old rate.
     */
    public function freshRate(string $from, string $to): ?ExchangeRate
    {
        $rate = $this->latestRate($from, $to);

        if (! $rate || ! $rate->fetched_at) {
            return null;
        }

        $maxAgeHours = (int) config('pricing.rate_staleness_hours', 48);

        return $rate->fetched_at->gte(now()->subHours($maxAgeHours)) ? $rate : null;
    }

    /**
     * Append one rate row (never mutates history) and, when the pair is
     * base→X, refresh the currencies.exchange_rate convenience cache.
     */
    public function record(string $from, string $to, float $rate, string $source, ?CarbonInterface $fetchedAt = null): ExchangeRate
    {
        if ($rate <= 0) {
            throw new InvalidArgumentException("Refusing to record non-positive exchange rate {$rate} for {$from}->{$to}.");
        }

        $from = strtoupper($from);
        $to = strtoupper($to);
        $fetchedAt = $fetchedAt ?? now();

        $row = ExchangeRate::create([
            'from_currency_code' => $from,
            'to_currency_code' => $to,
            'rate' => $rate,
            'source' => $source,
            'fetched_at' => $fetchedAt,
            'is_active' => true,
        ]);

        if ($from === $this->baseCurrency()) {
            Currency::query()->where('code', $to)->update([
                'exchange_rate' => $rate,
                'exchange_rate_updated_at' => $fetchedAt,
            ]);
        }

        return $row;
    }

    /**
     * Pull rates from a provider and record each one. Targets default to all
     * active currencies except the base. Returns the recorded target codes.
     *
     * @param  list<string>|null  $targetCurrencies
     * @return list<string>
     */
    public function refreshFromProvider(ExchangeRateProviderInterface $provider, ?array $targetCurrencies = null): array
    {
        $base = $this->baseCurrency();

        $targets = $targetCurrencies ?? Currency::query()
            ->where('is_active', true)
            ->where('code', '!=', $base)
            ->pluck('code')
            ->all();

        $recorded = [];

        foreach ($provider->rates($base, $targets) as $code => $rate) {
            $this->record($base, $code, $rate, $provider->name());
            $recorded[] = strtoupper($code);
        }

        return $recorded;
    }
}
