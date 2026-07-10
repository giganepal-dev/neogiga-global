<?php

namespace App\Services\Pricing;

/**
 * Operator-entered rates from config('pricing.manual_rates') — no network
 * calls. Empty by default, so the refresh command is a harmless no-op until
 * an operator supplies real rates on the server.
 */
class ManualExchangeRateProvider implements ExchangeRateProviderInterface
{
    public function name(): string
    {
        return 'manual-config';
    }

    public function rates(string $baseCurrency, array $targetCurrencies): array
    {
        $configured = (array) config('pricing.manual_rates', []);
        $rates = [];

        foreach ($targetCurrencies as $code) {
            $code = strtoupper($code);
            if (isset($configured[$code]) && (float) $configured[$code] > 0) {
                $rates[$code] = (float) $configured[$code];
            }
        }

        return $rates;
    }
}
