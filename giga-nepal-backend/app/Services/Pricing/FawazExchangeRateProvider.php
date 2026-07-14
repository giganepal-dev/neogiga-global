<?php

namespace App\Services\Pricing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Fawaz Ahmed Exchange Rate API Provider
 * 
 * Uses https://github.com/fawazahmed0/exchange-api.git
 * Free, no API key required, USD-based rates
 */
class FawazExchangeRateProvider implements ExchangeRateProviderInterface
{
    private const BASE_URL = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies';
    
    public function name(): string
    {
        return 'fawaz_exchange_api';
    }

    /**
     * Fetch rates from base currency to target currencies
     * 
     * @param string $baseCurrency Base currency code (e.g., 'USD')
     * @param array $targetCurrencies Array of target currency codes
     * @return array<string, float> Keyed by target currency code
     */
    public function rates(string $baseCurrency, array $targetCurrencies): array
    {
        $baseCurrency = strtoupper($baseCurrency);
        
        // The Fawaz API provides rates FROM a specific base currency
        // URL format: https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/{base}.json
        $url = sprintf('%s/%s.json', self::BASE_URL, strtolower($baseCurrency));
        
        try {
            $response = Http::timeout(10)->get($url);
            
            if (! $response->successful()) {
                Log::warning('Fawaz exchange rate API failed', [
                    'base' => $baseCurrency,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);
                return [];
            }
            
            $data = $response->json();
            
            // Validate response structure
            if (! isset($data['date']) || ! isset($data[$baseCurrency])) {
                Log::warning('Invalid Fawaz API response structure', [
                    'base' => $baseCurrency,
                    'keys' => array_keys($data),
                ]);
                return [];
            }
            
            $ratesData = $data[$baseCurrency];
            $result = [];
            
            foreach ($targetCurrencies as $target) {
                $target = strtoupper($target);
                $targetLower = strtolower($target);
                
                if (isset($ratesData[$targetLower])) {
                    $rate = (float) $ratesData[$targetLower];
                    
                    // Validate rate is positive
                    if ($rate > 0) {
                        $result[$target] = $rate;
                    } else {
                        Log::warning('Invalid rate value from Fawaz API', [
                            'base' => $baseCurrency,
                            'target' => $target,
                            'rate' => $rate,
                        ]);
                    }
                }
            }
            
            Log::info('Fawaz exchange rates fetched successfully', [
                'base' => $baseCurrency,
                'targets' => count($result),
                'sample' => array_slice($result, 0, 3, true),
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Fawaz exchange rate API exception', [
                'base' => $baseCurrency,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
}
