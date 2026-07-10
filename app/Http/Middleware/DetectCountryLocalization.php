<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CountryLocalization;
use App\Models\Country;

/**
 * Detect and apply country localization based on domain or path.
 * 
 * Handles:
 * - Domain-based detection (de.neogiga.com, neogiga.de)
 * - Path-based detection (/de/, /germany/)
 * - Cookie-based user preference
 * - GeoIP fallback (when enabled)
 * - SEO hreflang headers
 */
class DetectCountryLocalization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $localization = $this->detectLocalization($request);

        if ($localization) {
            // Apply localization to request
            $request->attributes->set('country_localization', $localization);
            $request->attributes->set('country', $localization->country);

            // Set app locale based on primary language
            $primaryLanguage = $localization->country->primaryLanguage();
            if ($primaryLanguage) {
                App::setLocale($primaryLanguage->code);
            }

            // Store in session for future requests
            if (!$request->session()->has('selected_country_id')) {
                $request->session()->put('selected_country_id', $localization->country->id);
            }
        }

        $response = $next($request);

        // Add SEO headers
        if ($localization) {
            $this->addSeoHeaders($response, $localization, $request);
        }

        return $response;
    }

    /**
     * Detect localization from request.
     */
    protected function detectLocalization(Request $request): ?CountryLocalization
    {
        // 1. Check explicit user selection (cookie/session)
        if ($request->session()->has('selected_country_id')) {
            $countryId = $request->session()->get('selected_country_id');
            $cached = Cache::remember(
                "localization:country:{$countryId}",
                now()->addHours(24),
                fn () => CountryLocalization::where('country_id', $countryId)
                    ->active()
                    ->first()
            );

            if ($cached && $cached->country->is_active) {
                return $cached;
            }
        }

        // 2. Check domain
        $host = $request->getHost();
        $domainLocalization = CountryLocalization::findByDomain($host);
        if ($domainLocalization && $domainLocalization->country->is_active) {
            return $domainLocalization;
        }

        // 3. Check path prefix
        $path = '/' . $request->path();
        $pathLocalization = CountryLocalization::findByPath($path);
        if ($pathLocalization && $pathLocalization->country->is_active) {
            return $pathLocalization;
        }

        // 4. Check GeoIP (if enabled in config)
        if (config('neogiga.localization.use_geoip', false)) {
            $countryCode = $this->getGeoIpCountry($request);
            if ($countryCode) {
                $country = Country::findByCode($countryCode);
                if ($country && $country->is_active) {
                    return CountryLocalization::getOrCreateForCountry($country);
                }
            }
        }

        // 5. Fall back to default/global
        return null;
    }

    /**
     * Get country from GeoIP lookup.
     */
    protected function getGeoIpCountry(Request $request): ?string
    {
        // Implementation depends on GeoIP service
        // This is a placeholder for integration with MaxMind, ipapi, etc.
        
        if ($request->server->has('HTTP_CF_IPCOUNTRY')) {
            // Cloudflare
            return $request->server->get('HTTP_CF_IPCOUNTRY');
        }

        // Add other GeoIP providers here
        return null;
    }

    /**
     * Add SEO headers to response.
     */
    protected function addSeoHeaders(Response $response, CountryLocalization $localization, Request $request): void
    {
        // Add hreflang tags
        $hreflangs = $localization->getHreflangTags();
        if (!empty($hreflangs)) {
            foreach ($hreflangs as $lang => $path) {
                $url = $this->buildUrl($request, $path);
                $response->headers->set(
                    'Link',
                    "<{$url}>; rel=\"alternate\"; hreflang=\"{$lang}\"",
                    true
                );
            }
        }

        // Add canonical URL
        if ($localization->canonical_domain) {
            $canonical = $this->buildCanonicalUrl($request, $localization);
            if ($canonical) {
                $response->headers->set(
                    'Link',
                    "<{$canonical}>; rel=\"canonical\"",
                    true
                );
            }
        }
    }

    /**
     * Build URL for hreflang.
     */
    protected function buildUrl(Request $request, string $path): string
    {
        $scheme = $request->getScheme();
        $host = $request->getHost();
        
        // Remove existing path prefix if present
        $currentPath = $request->getPathInfo();
        
        return "{$scheme}://{$host}{$path}";
    }

    /**
     * Build canonical URL.
     */
    protected function buildCanonicalUrl(Request $request, CountryLocalization $localization): ?string
    {
        if (!$localization->canonical_domain) {
            return null;
        }

        $scheme = $request->getScheme();
        $path = $request->getPathInfo();
        
        return "{$scheme}://{$localization->canonical_domain}{$path}";
    }
}
