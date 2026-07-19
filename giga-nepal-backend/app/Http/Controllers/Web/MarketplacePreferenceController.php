<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class MarketplacePreferenceController extends Controller
{
    public function store(Request $request, GlobalMarketplaceContextService $context): RedirectResponse
    {
        $data = $request->validate([
            'marketplace' => ['required', 'string', 'max:40'],
            'return_path' => ['nullable', 'string', 'max:500'],
            'action' => ['nullable', 'in:switch,stay'],
        ]);

        $edition = $context->marketplaceForPreference($data['marketplace']);
        if (! $edition) {
            return back()->with('error', 'Marketplace edition is not available.');
        }

        $returnPath = $this->safeReturnPath((string) ($data['return_path'] ?? '/'));
        $target = ($data['action'] ?? 'stay') === 'stay'
            ? url($returnPath)
            : $this->targetUrl($request, $edition, $returnPath);

        return redirect()->to($target)
            ->withCookie($this->cookie(GlobalMarketplaceContextService::PREFERENCE_COOKIE, $edition['code']))
            ->withCookie($this->cookie(GlobalMarketplaceContextService::SEEN_COOKIE, '1'));
    }

    private function targetUrl(Request $request, array $edition, string $returnPath): string
    {
        $returnPath = $this->localizedReturnPath($returnPath, $edition, hasDedicatedDomain: ! empty($edition['domain']));

        if (! empty($edition['domain'])) {
            return 'https://' . $edition['domain'] . $returnPath;
        }

        return $request->getSchemeAndHttpHost() . $returnPath;
    }

    private function safeReturnPath(string $path): string
    {
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/';
        }

        return $path;
    }

    private function localizedReturnPath(string $path, array $edition, bool $hasDedicatedDomain): string
    {
        $parts = parse_url($path);
        $rawPath = '/' . ltrim((string) ($parts['path'] ?? '/'), '/');
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        $segments = array_values(array_filter(explode('/', trim($rawPath, '/')), fn ($segment) => $segment !== ''));
        $knownPrefixes = array_keys(config('neogiga_global.prefixes', []));
        if ($segments && in_array(strtolower($segments[0]), $knownPrefixes, true)) {
            array_shift($segments);
        }

        if ($hasDedicatedDomain) {
            $localizedPath = $segments ? '/' . implode('/', $segments) : '/';

            return $localizedPath . $query;
        }

        $prefix = trim((string) ($edition['url_prefix'] ?: config('neogiga_global.default_prefix', 'en')), '/');
        $localizedPath = '/' . $prefix . ($segments ? '/' . implode('/', $segments) : '');

        return $localizedPath . $query;
    }

    private function cookie(string $name, string $value): Cookie
    {
        return cookie($name, $value, 60 * 24 * 180, '/', null, true, true, false, 'Lax');
    }
}
