<?php

namespace App\Services\Localization;

use App\Models\Country;
use App\Models\Currency;
use App\Models\ExchangeRateHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Currency Service
 * 
 * Handles currency conversion, exchange rate updates, and formatting.
 */
class CurrencyService
{
    /**
     * Convert amount from one currency to another.
     */
    public function convert(
        float $amount,
        string $fromCurrencyCode,
        string $toCurrencyCode,
        ?\DateTime $date = null
    ): float {
        $fromCurrencyCode = strtoupper($fromCurrencyCode);
        $toCurrencyCode = strtoupper($toCurrencyCode);

        // Same currency, no conversion needed
        if ($fromCurrencyCode === $toCurrencyCode) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrencyCode, $toCurrencyCode, $date);
        
        if (!$rate) {
            throw new \RuntimeException(
                "Exchange rate not available for {$fromCurrencyCode} to {$toCurrencyCode}"
            );
        }

        return $amount * $rate;
    }

    /**
     * Get exchange rate between two currencies.
     */
    public function getExchangeRate(
        string $fromCurrencyCode,
        string $toCurrencyCode,
        ?\DateTime $date = null
    ): ?float {
        $fromCurrencyCode = strtoupper($fromCurrencyCode);
        $toCurrencyCode = strtoupper($toCurrencyCode);

        // Check cache first
        $cacheKey = "exchange_rate:{$fromCurrencyCode}:{$toCurrencyCode}";
        if ($date === null || $date->isToday()) {
            return Cache::remember(
                $cacheKey,
                now()->addHours(6),
                fn () => $this->fetchOrCalculateRate($fromCurrencyCode, $toCurrencyCode, $date)
            );
        }

        return $this->fetchOrCalculateRate($fromCurrencyCode, $toCurrencyCode, $date);
    }

    /**
     * Fetch or calculate exchange rate.
     */
    protected function fetchOrCalculateRate(
        string $from,
        string $to,
        ?\DateTime $date = null
    ): ?float {
        // Try direct rate first
        if ($date) {
            $historical = ExchangeRateHistory::getRateForDate($from, $to, $date);
            if ($historical) {
                return $historical;
            }
        } else {
            $latest = ExchangeRateHistory::getLatestRate($from, $to);
            if ($latest) {
                return $latest;
            }
        }

        // Try via base currency (USD) if direct rate not available
        $baseRateFrom = $this->getRateViaBase($from, 'USD', $date);
        $baseRateTo = $this->getRateViaBase($to, 'USD', $date);

        if ($baseRateFrom && $baseRateTo) {
            return $baseRateTo / $baseRateFrom;
        }

        return null;
    }

    /**
     * Get rate via base currency.
     */
    protected function getRateViaBase(
        string $currency,
        string $base,
        ?\DateTime $date = null
    ): ?float {
        if ($date) {
            return ExchangeRateHistory::getRateForDate($currency, $base, $date);
        }
        
        return ExchangeRateHistory::getLatestRate($currency, $base);
    }

    /**
     * Update exchange rates from external API.
     * 
     * Integrates with ECB, Federal Reserve, or other sources.
     */
    public function updateExchangeRates(
        string $source = 'ecb',
        ?array $currencies = null
    ): int {
        $updated = 0;
        $baseCurrency = 'EUR'; // ECB uses EUR as base

        try {
            $rates = $this->fetchRatesFromSource($source);

            if (empty($rates)) {
                return 0;
            }

            $today = now();

            // Record rates relative to base currency
            foreach ($rates as $currency => $rate) {
                if ($currencies && !in_array(strtoupper($currency), $currencies)) {
                    continue;
                }

                // Base to target
                ExchangeRateHistory::recordRate(
                    $baseCurrency,
                    $currency,
                    $rate,
                    $source,
                    $today
                );

                // Target to base (inverse)
                if ($rate > 0) {
                    ExchangeRateHistory::recordRate(
                        $currency,
                        $baseCurrency,
                        1 / $rate,
                        $source,
                        $today
                    );
                }

                $updated++;
            }

            // Clear cache
            Cache::tags(['exchange_rates'])->flush();

        } catch (\Exception $e) {
            \Log::error('Failed to update exchange rates', [
                'source' => $source,
                'error' => $e->getMessage(),
            ]);
        }

        return $updated;
    }

    /**
     * Fetch rates from configured source.
     */
    protected function fetchRatesFromSource(string $source): array
    {
        return match ($source) {
            'ecb' => $this->fetchFromEcb(),
            'exchangerate-api' => $this->fetchFromExchangeRateApi(),
            default => [],
        };
    }

    /**
     * Fetch from European Central Bank.
     */
    protected function fetchFromEcb(): array
    {
        $response = Http::get('https://api.exchangerate.host/latest?base=EUR');
        
        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        return $data['rates'] ?? [];
    }

    /**
     * Fetch from ExchangeRate-API.
     */
    protected function fetchFromExchangeRateApi(): array
    {
        $apiKey = config('services.exchangerate_api.key');
        
        if (!$apiKey) {
            return [];
        }

        $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD");
        
        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        return $data['conversion_rates'] ?? [];
    }

    /**
     * Format amount with currency symbol.
     */
    public function format(
        float $amount,
        string $currencyCode,
        bool $showSymbol = true,
        int $decimals = null
    ): string {
        $currency = Currency::findByCode($currencyCode);

        if (!$currency) {
            return number_format($amount, 2);
        }

        if ($decimals === null) {
            $decimals = $currency->decimal_places;
        }

        return $currency->format($amount, $showSymbol);
    }

    /**
     * Get all active currencies with latest rates.
     */
    public function getActiveCurrenciesWithRates(string $baseCurrency = 'USD'): array
    {
        $currencies = Currency::active()->get();
        $result = [];

        foreach ($currencies as $currency) {
            $rate = $this->getExchangeRate($baseCurrency, $currency->code);
            
            $result[] = [
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'rate' => $rate,
                'formatted_rate' => $rate ? number_format($rate, 4) : null,
            ];
        }

        return $result;
    }

    /**
     * Validate currency code.
     */
    public function isValidCurrency(string $code): bool
    {
        return Currency::findByCode($code) !== null;
    }
}
