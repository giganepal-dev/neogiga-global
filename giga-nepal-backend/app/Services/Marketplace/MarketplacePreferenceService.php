<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class MarketplacePreferenceService
{
    public const COOKIE = GlobalMarketplaceContextService::PREFERENCE_COOKIE;

    public const SEEN_COOKIE = GlobalMarketplaceContextService::SEEN_COOKIE;

    public function preferredCode(Request $request): ?string
    {
        $code = strtolower((string) $request->cookie(self::COOKIE, ''));

        return $code !== '' ? $code : null;
    }

    public function preferredMarketplace(Request $request): ?Marketplace
    {
        $code = $this->preferredCode($request);
        if (! $code) {
            return null;
        }

        return Marketplace::query()
            ->with(['country', 'currency', 'domains'])
            ->whereRaw('LOWER(code) = ?', [$code])
            ->where('is_active', true)
            ->first();
    }

    public function cookie(string $code): Cookie
    {
        return cookie(self::COOKIE, strtolower($code), $this->cookieMinutes(), '/', null, true, true, false, 'Lax');
    }

    public function seenCookie(): Cookie
    {
        return cookie(self::SEEN_COOKIE, '1', $this->cookieMinutes(), '/', null, true, true, false, 'Lax');
    }

    /** Admin-configurable persistence; spec requires at least 30 days. */
    private function cookieMinutes(): int
    {
        $days = max(30, (int) config('neogiga_global.geo_routing.cookie_days', 180));

        return 60 * 24 * $days;
    }
}
