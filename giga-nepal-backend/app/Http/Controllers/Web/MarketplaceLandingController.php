<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\MarketplacePathResolver;
use App\Services\Marketplace\MarketplaceUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Country selector / marketplace landing page: GET /{prefix} for any of the
 * 25 seeded marketplace url_prefix values. Preview marketplaces render a
 * "coming soon" page — never a functional storefront. This is informational
 * only; nothing here redirects a visitor anywhere (Global Commerce Stage 1).
 */
class MarketplaceLandingController extends Controller
{
    public function show(
        Request $request,
        string $prefix,
        MarketplacePathResolver $pathResolver,
        MarketplaceUrlGenerator $urlGenerator,
        GlobalMarketplaceContextService $context,
    ): View {
        $marketplace = $pathResolver->byPrefix($prefix, activeOnly: false);

        abort_unless($marketplace, 404);

        return view('frontend.marketplace.landing', [
            'marketplace' => $marketplace,
            'isPreview' => ($marketplace->launch_status ?? 'preview') !== 'active',
            'brandedUrl' => $marketplace->domains->firstWhere('is_active', true)
                ? $urlGenerator->forMarketplace($marketplace)
                : null,
            'editions' => $context->allEditions(),
        ]);
    }
}
