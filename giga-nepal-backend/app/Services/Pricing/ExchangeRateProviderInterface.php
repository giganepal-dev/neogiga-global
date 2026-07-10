<?php

namespace App\Services\Pricing;

interface ExchangeRateProviderInterface
{
    /**
     * Identifier stored in exchange_rates.source for every rate this
     * provider returns.
     */
    public function name(): string;

    /**
     * Rates from the base currency, keyed by target currency code, e.g.
     * ['NPR' => 133.0, 'INR' => 83.5]. Return only pairs the provider
     * actually knows — never guess or fill gaps.
     *
     * @param  list<string>  $targetCurrencies
     * @return array<string, float>
     */
    public function rates(string $baseCurrency, array $targetCurrencies): array;
}
