<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;
use GeoIp2\Database\Reader;
use MaxMind\Db\Reader\InvalidDatabaseException;

class MarketplaceResolver
{
    protected ?Marketplace $currentMarketplace = null;
    protected ?Country $detectedCountry = null;
    protected array $countryMap = [];

    public function __construct()
    {
        $this->loadCountryMap();
    }

    protected function loadCountryMap(): void
    {
        $this->countryMap = Cache::remember('marketplace_country_map', 3600, function () {
            return Country::where('is_active', true)
                ->pluck('iso_code')
                ->toArray();
        });
    }

    /**
     * Detect marketplace from request and resolve
     */
    public function resolveFromRequest(?string $ipAddress = null, ?string $host = null): Marketplace
    {
        // 1. Check subdomain first (explicit user choice or previous redirect)
        $marketplace = $this->resolveFromSubdomain($host);
        if ($marketplace) {
            return $this->currentMarketplace = $marketplace;
        }

        // 2. Check user account preference
        $marketplace = $this->resolveFromUserPreference();
        if ($marketplace) {
            return $this->currentMarketplace = $marketplace;
        }

        // 3. Check cookie preference
        $marketplace = $this->resolveFromCookie();
        if ($marketplace) {
            return $this->currentMarketplace = $marketplace;
        }

        // 4. Geo-IP detection
        $marketplace = $this->resolveFromGeoIp($ipAddress);
        if ($marketplace) {
            return $this->currentMarketplace = $marketplace;
        }

        // 5. Fallback to global default (en)
        return $this->currentMarketplace = $this->getDefaultMarketplace();
    }

    protected function resolveFromSubdomain(?string $host): ?Marketplace
    {
        if (!$host) {
            return null;
        }

        // Extract subdomain from host (e.g., np.neogiga.com -> np)
        $parts = explode('.', $host);
        if (count($parts) < 2) {
            return null;
        }

        $subdomain = strtolower($parts[0]);

        // Skip www
        if ($subdomain === 'www' || $subdomain === 'neogiga') {
            return null;
        }

        return Cache::remember("marketplace_subdomain_{$subdomain}", 3600, function () use ($subdomain) {
            return Marketplace::where('subdomain', $subdomain)
                ->where('is_active', true)
                ->first();
        });
    }

    protected function resolveFromUserPreference(): ?Marketplace
    {
        if (!auth()->check()) {
            return null;
        }

        $user = auth()->user();
        if (!$user->preferred_marketplace_id) {
            return null;
        }

        return Marketplace::find($user->preferred_marketplace_id);
    }

    protected function resolveFromCookie(): ?Marketplace
    {
        $marketplaceId = Cookie::get('neogiga_marketplace');
        if (!$marketplaceId) {
            return null;
        }

        return Marketplace::find($marketplaceId);
    }

    protected function resolveFromGeoIp(?string $ipAddress): ?Marketplace
    {
        if (!$ipAddress) {
            return null;
        }

        try {
            // Use MaxMind GeoIP2 database
            $reader = new Reader(storage_path('app/geo/GeoLite2-Country.mmdb'));
            $record = $reader->country($ipAddress);
            $countryCode = $record->country->isoCode;

            if (!$countryCode) {
                return null;
            }

            $this->detectedCountry = Country::where('iso_code', $countryCode)
                ->where('is_active', true)
                ->first();

            if (!$this->detectedCountry) {
                return null;
            }

            return Marketplace::where('country_code', $countryCode)
                ->where('is_active', true)
                ->first();

        } catch (InvalidDatabaseException $e) {
            // GeoIP database not found or invalid, skip geo detection
            return null;
        } catch (\Exception $e) {
            // Other errors, skip geo detection
            return null;
        }
    }

    protected function getDefaultMarketplace(): Marketplace
    {
        return Cache::remember('marketplace_default', 3600, function () {
            return Marketplace::where('is_default', true)
                ->orWhere('subdomain', 'en')
                ->where('is_active', true)
                ->first()
                ?? Marketplace::where('is_active', true)->first();
        });
    }

    /**
     * Get current marketplace
     */
    public function getCurrentMarketplace(): ?Marketplace
    {
        return $this->currentMarketplace;
    }

    /**
     * Get detected country
     */
    public function getDetectedCountry(): ?Country
    {
        return $this->detectedCountry;
    }

    /**
     * Set marketplace preference (for user or session)
     */
    public function setPreference(Marketplace $marketplace, bool $persist = true): void
    {
        $this->currentMarketplace = $marketplace;

        if ($persist) {
            // Set cookie for 1 year
            Cookie::queue('neogiga_marketplace', $marketplace->id, 525600);

            // Update user preference if logged in
            if (auth()->check()) {
                auth()->user()->update([
                    'preferred_marketplace_id' => $marketplace->id,
                ]);
            }
        }
    }

    /**
     * Get redirect URL for detected country
     */
    public function getRedirectUrl(?string $currentHost = null): ?string
    {
        if (!$this->currentMarketplace) {
            return null;
        }

        // Don't redirect if already on correct subdomain
        if ($currentHost && str_starts_with($currentHost, "{$this->currentMarketplace->subdomain}.")) {
            return null;
        }

        return $this->currentMarketplace->base_url;
    }

    /**
     * Get all active marketplaces
     */
    public function getActiveMarketplaces(): array
    {
        return Cache::remember('marketplaces_active_list', 3600, function () {
            return Marketplace::where('is_active', true)
                ->orderBy('short_name')
                ->get()
                ->toArray();
        });
    }

    /**
     * Clear cache for specific marketplace
     */
    public function clearCache(string $marketplaceId): void
    {
        Cache::forget("marketplace_{$marketplaceId}");
        Cache::forget('marketplaces_active_list');
        Cache::forget('marketplace_default');
        Cache::forget('marketplace_country_map');
    }
}
