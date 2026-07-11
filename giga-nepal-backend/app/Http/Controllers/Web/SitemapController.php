<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\ProductCategory;
use App\Services\Product\ProductVisibilityService;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Dynamic sitemap (Blueprint §42). Includes only public catalog surfaces.
 * Large catalogs should move to sitemap indexes/shards before high-scale
 * publication, but this gate keeps hidden/review-pending imports out now.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $host = request()->getHost();

        // Resolve the marketplace for this host (defensive). A marketplace that
        // is not active+indexable+sitemap_enabled is excluded from catalog
        // indexing (codex §7); the homepage is still listed. An unresolved host
        // keeps the legacy full behavior.
        $marketplace = null;
        try {
            $marketplace = app(\App\Services\MarketplaceResolverService::class)->resolve(request());
        } catch (Throwable) {
            $marketplace = null;
        }
        $includeCatalog = ! $marketplace
            || ($marketplace->is_active && $marketplace->indexable && ($marketplace->sitemap_enabled ?? true));

        $cacheKey = 'seo:sitemap:' . $host . ':' . ($includeCatalog ? 'full' : 'min');
        $xml = Cache::remember($cacheKey, 3600, function () use ($includeCatalog) {
            $urls = [[
                'loc' => url('/'),
                'lastmod' => now()->toAtomString(),
                'priority' => '1.0',
            ]];

            // Category slugs are included only once the schema is migrated.
            // On early hosting deployments, DB credentials may intentionally
            // be absent; sitemap must still return the canonical homepage.
            try {
                if ($includeCatalog && Schema::hasTable('product_categories')) {
                    ProductCategory::query()
                        ->where('is_active', true)
                        ->orderBy('slug')
                        ->get(['slug', 'updated_at'])
                        ->each(function (ProductCategory $category) use (&$urls) {
                            $urls[] = [
                                'loc' => url('/categories/' . $category->slug),
                                'lastmod' => optional($category->updated_at)->toAtomString() ?? now()->toAtomString(),
                                'priority' => '0.7',
                            ];
                        });
                }

                if ($includeCatalog && Schema::hasTable('products')) {
                    app(ProductVisibilityService::class)
                        ->publicProducts()
                        ->whereNotNull('slug')
                        ->where('slug', '!=', '')
                        ->orderByDesc('updated_at')
                        ->limit(5000)
                        ->get(['slug', 'updated_at'])
                        ->each(function (object $product) use (&$urls) {
                            $urls[] = [
                                'loc' => url('/products/' . $product->slug),
                                'lastmod' => $this->lastmod($product->updated_at ?? null),
                                'priority' => '0.6',
                            ];
                        });
                }
            } catch (Throwable) {
                // Keep sitemap available before database provisioning.
            }

            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            foreach ($urls as $url) {
                $loc = e($url['loc']);
                $lastmod = e($url['lastmod']);
                $priority = e($url['priority']);
                $out .= "  <url><loc>{$loc}</loc>"
                    . "<lastmod>{$lastmod}</lastmod>"
                    . "<priority>{$priority}</priority></url>\n";
            }

            return $out . '</urlset>';
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function lastmod(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toAtomString();
        }

        if (is_string($value) && $value !== '') {
            return date(DATE_ATOM, strtotime($value) ?: time());
        }

        return now()->toAtomString();
    }
}
