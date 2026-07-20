<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\MarketplacePathResolver;
use App\Services\Marketplace\MarketplaceSeoRenderer;
use App\Services\Marketplace\MarketplaceUrlGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class LandingController extends Controller
{
    /**
     * Render the shared NeoGiga storefront for the global and every regional
     * host. Marketplace context controls currency, canonical URL, robots and
     * hreflang; the catalog is one shared product master.
     */
    public function __invoke(): Response
    {
        $marketplaceContext = app(GlobalMarketplaceContextService::class)->context(request());
        $current = $marketplaceContext['current'] ?? null;
        $prefix = strtolower((string) request()->segment(1));
        $requestedMarketplace = $prefix !== ''
            ? app(MarketplacePathResolver::class)->byPrefix($prefix, activeOnly: false)
            : null;

        if ($requestedMarketplace && (! $current || strtoupper((string) $current->code) === 'GLOBAL' && $prefix !== 'en')) {
            $current = $requestedMarketplace;
            $marketplaceContext['current'] = $current;
            $marketplaceContext['currency_code'] = $current->currency?->code ?? 'USD';
            $marketplaceContext['country_id'] = $current->country_id;
            $marketplaceContext['country_code'] = strtoupper((string) ($current->country?->iso_code_2 ?? ''));
            $marketplaceContext['locale'] = $current->locale ?: 'en';
        }

        // Preview/inactive regional editions never render a functional
        // storefront: bd.neogiga.com (host-resolved) and /bd (prefix-resolved)
        // both land on the "Coming soon" marketplace page.
        if ($current
            && strtoupper((string) $current->code) !== 'GLOBAL'
            && ($current->launch_status ?? 'active') !== 'active') {
            return response()->view('frontend.marketplace.landing', [
                'marketplace' => $current,
                'isPreview' => true,
                'brandedUrl' => $current->domains->firstWhere('is_active', true)
                    ? app(MarketplaceUrlGenerator::class)->forMarketplace($current)
                    : null,
                'editions' => app(GlobalMarketplaceContextService::class)->allEditions(),
            ]);
        }

        $marketplaceSeo = app(MarketplaceSeoRenderer::class)->tags($current, url()->current());
        $canonicalUrl = $marketplaceSeo['canonical'];
        $locale = $marketplaceContext['locale'] ?? 'en';

        $categories = $this->categories();
        $products = $this->products((int) ($current?->id ?? 0));
        $brands = $this->brands();
        $stats = [
            'marketplaces' => $this->safeCount(Marketplace::class),
            'products' => $this->safePublishedProductCount(),
            'categories' => $this->safeCount(ProductCategory::class),
            'brands' => $this->safeCount(ProductBrand::class),
        ];

        $homeSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => 'https://neogiga.com/#organization',
                    'name' => config('seo.site_name'),
                    'legalName' => config('seo.organization.legal_name'),
                    'url' => 'https://neogiga.com/en',
                    'logo' => url('/images/brand/neogiga-icon-512.png'),
                    'email' => config('seo.organization.email'),
                    'sameAs' => array_values(array_filter([
                        config('seo.social.twitter'),
                        config('seo.social.facebook'),
                        config('seo.social.instagram'),
                        config('seo.social.linkedin'),
                        config('seo.social.youtube'),
                        config('seo.social.github'),
                    ])),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $canonicalUrl.'#website',
                    'url' => $canonicalUrl,
                    'name' => $current?->regional_brand_name ?: ($current?->name ?: config('seo.site_name')),
                    'publisher' => ['@id' => 'https://neogiga.com/#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => rtrim($canonicalUrl, '/').'/products?q={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@type' => 'ItemList',
                    'name' => 'NeoGiga engineering categories',
                    'itemListElement' => $categories->values()->map(fn (array $category, int $index) => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $category['name'],
                        'url' => rtrim($canonicalUrl, '/').'/products?category='.$category['slug'],
                    ])->all(),
                ],
            ],
        ];

        $welcomeMessage = $current?->welcomeFor($locale);

        return response()
            ->view('landing', compact(
                'categories',
                'products',
                'brands',
                'stats',
                'homeSchema',
                'canonicalUrl',
                'locale',
                'marketplaceContext',
                'marketplaceSeo',
                'welcomeMessage',
            ))
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=600');
    }

    private function categories(): Collection
    {
        try {
            if (Schema::hasTable('product_categories')) {
                $categories = ProductCategory::query()
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->withCount('children')
                    ->orderByDesc('is_featured')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->limit(8)
                    ->get()
                    ->map(fn (ProductCategory $category) => [
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'icon' => strtoupper(substr((string) $category->name, 0, 2)),
                        'blurb' => $category->description ?: 'Browse verified parts, specifications and regional availability.',
                        'children_count' => (int) $category->children_count,
                    ]);

                if ($categories->isNotEmpty()) {
                    return $categories;
                }
            }
        } catch (\Throwable) {
            // The homepage must remain available while an optional catalog
            // table is being migrated or restored.
        }

        return collect([
            ['name' => 'Semiconductors', 'slug' => 'semiconductors', 'icon' => 'SC', 'blurb' => 'ICs, MCUs, discretes, memory and logic from leading manufacturers.', 'children_count' => 0],
            ['name' => 'Electronic Components', 'slug' => 'electronic-components', 'icon' => 'EC', 'blurb' => 'Passives, connectors, displays and board-level components.', 'children_count' => 0],
            ['name' => 'IoT & Wireless', 'slug' => 'iot-wireless', 'icon' => 'IoT', 'blurb' => 'Wi-Fi, BLE, LoRa and cellular modules for connected products.', 'children_count' => 0],
            ['name' => 'Robotics', 'slug' => 'robotics', 'icon' => 'RB', 'blurb' => 'Motors, drivers, actuators, chassis and robot controllers.', 'children_count' => 0],
            ['name' => 'Industrial Automation', 'slug' => 'industrial-automation', 'icon' => 'IA', 'blurb' => 'PLCs, HMIs, sensors, relays and factory control systems.', 'children_count' => 0],
            ['name' => 'Battery Technology', 'slug' => 'battery-technology', 'icon' => 'BT', 'blurb' => 'Cells, packs, BMS and charging for every chemistry.', 'children_count' => 0],
        ]);
    }

    private function products(int $marketplaceId): Collection
    {
        try {
            if (! Schema::hasTable('products')) {
                return collect();
            }

            $globalMarketplaceId = (int) Marketplace::query()->where('code', 'GLOBAL')->value('id');
            $priceMarketplaceIds = array_values(array_unique(array_filter([$marketplaceId, $globalMarketplaceId])));

            return Product::query()
                ->published()
                ->with([
                    'brand:id,name,slug',
                    'images' => fn ($query) => $query
                        ->where('is_active', true)
                        ->orderByDesc('is_primary')
                        ->orderBy('sort_order')
                        ->limit(1),
                    'marketplacePrices' => fn ($query) => $query
                        ->where('is_active', true)
                        ->when($priceMarketplaceIds !== [], fn ($priceQuery) => $priceQuery->whereIn('marketplace_id', $priceMarketplaceIds))
                        ->when($priceMarketplaceIds === [], fn ($priceQuery) => $priceQuery->whereRaw('1 = 0'))
                        ->when($marketplaceId > 0, fn ($priceQuery) => $priceQuery->orderByRaw('CASE WHEN marketplace_id = ? THEN 0 ELSE 1 END', [$marketplaceId]))
                        ->orderBy('id')
                        ->limit(1),
                ])
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->orderByDesc('is_featured')
                ->orderByDesc('updated_at')
                ->limit(6)
                ->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    private function brands(): Collection
    {
        try {
            if (! Schema::hasTable('product_brands')) {
                return collect();
            }

            return ProductBrand::query()
                ->where('is_active', true)
                ->where('landing_page_enabled', true)
                ->orderByDesc('is_featured')
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'slug', 'logo_path']);
        } catch (\Throwable) {
            return collect();
        }
    }

    private function safeCount(string $model): int
    {
        try {
            return (int) $model::query()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function safePublishedProductCount(): int
    {
        try {
            return (int) Product::query()->published()->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
