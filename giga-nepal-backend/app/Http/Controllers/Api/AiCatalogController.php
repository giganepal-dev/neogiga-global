<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\MarketplaceUrlGenerator;
use App\Services\Product\ProductImageManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Bounded, read-only catalog contract for AI agents and MCP integrations.
 *
 * This is deliberately separate from commerce APIs: it returns only products
 * passing the shared public publication gate and never returns pricing, stock,
 * customer, order, cart, admin, or supplier-private fields.
 */
class AiCatalogController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly CatalogSearchService $catalogSearch,
        private readonly GlobalMarketplaceContextService $marketplaces,
        private readonly MarketplaceUrlGenerator $urlGenerator,
        private readonly ProductImageManager $imageManager,
    ) {
    }

    public function manifest(Request $request): JsonResponse
    {
        return $this->success([
            'service' => 'NeoGiga AI Catalog',
            'version' => 'v1',
            'read_only' => true,
            'advisory_only' => true,
            'marketplace' => $this->marketplacePayload($request),
            'endpoints' => [
                'marketplaces' => $this->apiUrl($request, 'marketplaces'),
                'search' => $this->apiUrl($request, 'products/search?q={query}'),
                'product' => $this->apiUrl($request, 'products/{slug}'),
            ],
            'discovery' => [
                'llms_txt' => $request->getSchemeAndHttpHost().'/llms.txt',
                'agent_skill' => $request->getSchemeAndHttpHost().'/agent-skill.md',
                'sitemap' => $request->getSchemeAndHttpHost().'/sitemap.xml',
            ],
            'commercial_data_policy' => 'Prices, stock, delivery estimates, tax, seller offers, and payment information must be confirmed on the live regional storefront before any commercial decision.',
            'usage_rules' => [
                'Cite the returned canonical_product_url or the live regional storefront URL.',
                'Do not treat this API as a source of live price, inventory, delivery, tax, or compliance commitments.',
                'Do not crawl, infer, or call admin, cart, checkout, order, customer, or authenticated endpoints.',
            ],
        ]);
    }

    public function marketplaces(Request $request): JsonResponse
    {
        $editions = $this->marketplaces->editions()
            ->filter(fn (array $edition) => $edition['is_visible'] && $edition['indexable'])
            ->map(fn (array $edition) => [
                'code' => $edition['code'],
                'name' => $edition['name'],
                'url' => $edition['url'],
                'locale' => $edition['locale'],
                'hreflang' => $edition['hreflang'],
                'country_code' => $edition['country_code'],
                'currency_code' => $edition['currency_code'],
            ])
            ->values();

        return $this->success($editions, meta: [
            'current_marketplace' => $this->marketplacePayload($request),
            'advisory_only' => true,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'stock' => ['sometimes', 'in:in,low,out'],
            'package' => ['sometimes', 'string', 'max:120'],
            'quality' => ['sometimes', 'in:high,needs_review'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ]);

        $products = $this->baseQuery()
            ->tap(fn (Builder $query) => $this->catalogSearch->applyPublicFilters($query, [
                'q' => $validated['q'],
                'stock' => $validated['stock'] ?? '',
                'package' => $validated['package'] ?? '',
                'quality' => $validated['quality'] ?? '',
            ]))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 12);

        return $this->success([
            'products' => $products->getCollection()
                ->map(fn (Product $product) => $this->productSummary($product, $request))
                ->values(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_more_pages' => $products->hasMorePages(),
            ],
        ], meta: [
            'query' => $validated['q'],
            'marketplace' => $this->marketplacePayload($request),
            'advisory_only' => true,
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $product = $this->baseQuery()
            ->where('slug', $slug)
            ->with([
                'specs:id,product_id,name,value,unit,sort_order',
                'activeImages:id,product_id,file_path,original_url,source_url,alt_text,caption,is_primary,sort_order',
            ])
            ->first();

        if (! $product) {
            return $this->error('Product not found', 404);
        }

        return $this->success(array_merge($this->productSummary($product, $request), [
            'description' => $this->plainText($product->description, 2500),
            'technical_specifications' => $product->specs
                ->sortBy('sort_order')
                ->map(fn ($spec) => array_filter([
                    'name' => $spec->name,
                    'value' => $spec->value,
                    'unit' => $spec->unit,
                ], fn ($value) => $value !== null && $value !== ''))
                ->values(),
            'images' => $product->activeImages
                ->take(8)
                ->map(fn ($image) => [
                    'url' => $this->imageManager->serialize($image)['url'] ?? $image->publicUrl(),
                    'alt_text' => $image->alt_text ?: $product->name,
                    'caption' => $image->caption,
                    'is_primary' => (bool) $image->is_primary,
                ])
                ->values(),
        ]), meta: [
            'marketplace' => $this->marketplacePayload($request),
            'advisory_only' => true,
        ]);
    }

    private function baseQuery(): Builder
    {
        return Product::query()
            ->published()
            ->with(['brand:id,name,slug', 'manufacturer:id,name,slug', 'category:id,name,slug']);
    }

    private function productSummary(Product $product, Request $request): array
    {
        $marketplace = $this->marketplaces->context($request)['current'];

        return [
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'mpn' => $product->mpn,
            'manufacturer' => $product->manufacturer?->name ?: $product->manufacturer_name,
            'brand' => $product->brand?->only(['name', 'slug']),
            'category' => $product->category?->only(['name', 'slug']),
            'short_description' => $this->plainText($product->short_description ?: $product->description, 500),
            'lifecycle_status' => $product->lifecycle_status,
            'canonical_product_url' => $marketplace
                ? $this->urlGenerator->forMarketplace($marketplace, '/'.($marketplace->locale ?: 'en').'/products/'.$product->slug)
                : $request->getSchemeAndHttpHost().'/en/products/'.$product->slug,
            'provenance' => [
                'source_notes' => $product->source_name ?: 'NeoGiga catalog',
                'confidence_level' => $product->confidence_level ?: 'catalog_record',
                'last_updated' => ($product->last_verified_at ?: $product->updated_at)?->toAtomString(),
                'advisory_disclaimer' => 'Advisory only. Confirm pricing, stock, delivery, tax, and compliance on the live regional storefront.',
            ],
        ];
    }

    private function marketplacePayload(Request $request): array
    {
        $context = $this->marketplaces->context($request);
        $marketplace = $context['current'];

        return [
            'code' => strtolower((string) $marketplace?->code ?: 'global'),
            'name' => $marketplace?->name ?: 'NeoGiga Global',
            'locale' => $context['locale'],
            'country_code' => $context['country_code'] ?: null,
            'currency_code' => $context['currency_code'],
        ];
    }

    private function apiUrl(Request $request, string $path): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/').'/api/v1/ai-catalog/'.ltrim($path, '/');
    }

    private function plainText(?string $value, int $limit): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)) ?: '');

        return $text === '' ? null : Str::limit($text, $limit, '...');
    }
}
