<?php

namespace App\Http\Middleware;

use App\Services\Marketplace\MarketplaceResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketplaceRoutingMiddleware
{
    protected MarketplaceResolver $resolver;

    public function __construct(MarketplaceResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve marketplace from subdomain, geo-IP, or cookies
        $marketplace = $this->resolver->resolveFromRequest(
            $request->ip(),
            $request->getHost()
        );

        // Share marketplace with all views
        view()->share('currentMarketplace', $marketplace);
        
        // Set app locale based on marketplace
        if ($marketplace && $marketplace->locale) {
            app()->setLocale($marketplace->locale);
        }

        // Store marketplace in request for controllers
        $request->merge(['marketplace' => $marketplace]);

        // Auto-redirect logic for root domain only
        if ($this->shouldRedirect($request, $marketplace)) {
            $redirectUrl = $this->resolver->getRedirectUrl($request->getHost());
            
            if ($redirectUrl) {
                // Preserve the current path and query string
                $targetUrl = rtrim($redirectUrl, '/') . $request->path();
                if ($request->getQueryString()) {
                    $targetUrl .= '?' . $request->getQueryString();
                }
                
                return redirect($targetUrl, 302);
            }
        }

        return $next($request);
    }

    /**
     * Determine if request should be redirected to country subdomain
     */
    protected function shouldRedirect(Request $request, $marketplace): bool
    {
        // Only redirect on main domain (neogiga.com)
        $host = $request->getHost();
        
        // Don't redirect if already on a subdomain
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            return false;
        }

        // Don't redirect API requests
        if (str_starts_with($request->path(), 'api/')) {
            return false;
        }

        // Don't redirect admin routes
        if (str_starts_with($request->path(), 'admin/')) {
            return false;
        }

        // Don't redirect if user explicitly chose a marketplace (cookie set)
        if ($request->cookie('neogiga_marketplace')) {
            return false;
        }

        // Redirect if we detected a specific marketplace
        return $marketplace !== null && !$marketplace->is_default;
    }
}
