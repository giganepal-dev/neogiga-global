<?php

namespace App\Console\Commands;

use App\Services\Pricing\ExchangeRateService;
use App\Services\Pricing\ManualExchangeRateProvider;
use Illuminate\Console\Command;

/**
 * Deliberately NOT scheduled: putting this on the scheduler (and choosing a
 * live provider) is an operational decision — see EXCHANGE_RATE_GUIDE.md.
 */
class RefreshExchangeRates extends Command
{
    protected $signature = 'pricing:refresh-exchange-rates';

    protected $description = 'Record exchange rates from the configured provider into the append-only exchange_rates table';

    public function handle(ExchangeRateService $rates): int
    {
        $provider = new ManualExchangeRateProvider();
        $recorded = $rates->refreshFromProvider($provider);

        if ($recorded === []) {
            $this->warn("Provider '{$provider->name()}' returned no rates (pricing.manual_rates is empty?). Nothing recorded.");

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Recorded %d rate(s) from %s: %s -> %s',
            count($recorded),
            $provider->name(),
            $rates->baseCurrency(),
            implode(', ', $recorded),
        ));

        return self::SUCCESS;
    }
}
