<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CountryResolver
{
    /**
     * Data-driven across all seeded marketplaces (Global Commerce Stage 1) —
     * no hardcoded country list. Matches the CF-IPCountry edge header, then
     * the Accept-Language region subtag, against whichever ACTIVE marketplace
     * exists for that ISO country code.
     */
    public function recommendationCode(Request $request): ?string
    {
        $country = strtoupper((string) $request->header('CF-IPCountry', ''));
        $code = $country !== '' ? $this->codeForCountry($country) : null;

        if ($code) {
            return $code;
        }

        $acceptLanguage = strtolower((string) $request->header('Accept-Language', ''));
        if (preg_match('/[a-z]{2}-([a-z]{2})/', $acceptLanguage, $match)) {
            return $this->codeForCountry(strtoupper($match[1]));
        }

        return null;
    }

    private function codeForCountry(string $countryCode): ?string
    {
        $code = Cache::remember("marketplace:country-code:{$countryCode}", 3600, function () use ($countryCode) {
            return Marketplace::query()
                ->join('countries', 'countries.id', '=', 'marketplaces.country_id')
                ->where('countries.iso_code_2', $countryCode)
                ->where('marketplaces.is_active', true)
                ->value('marketplaces.code');
        });

        return $code ? strtolower($code) : null;
    }

    public function isCrawler(Request $request): bool
    {
        $agent = strtolower((string) $request->userAgent());
        if ($agent === '') {
            return false;
        }

        foreach (['googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot', 'facebookexternalhit', 'twitterbot'] as $crawler) {
            if (str_contains($agent, $crawler)) {
                return true;
            }
        }

        return false;
    }
}
