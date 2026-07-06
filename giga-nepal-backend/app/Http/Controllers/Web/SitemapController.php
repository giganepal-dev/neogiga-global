<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\ProductCategory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Dynamic sitemap (Blueprint §42). Currently: landing page + active
 * category slugs (future category pages). Grows into sharded sitemaps
 * driven by catalog events in Phase 2.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('seo:sitemap', 3600, function () {
            $urls = [[
                'loc' => url('/'),
                'lastmod' => now()->toAtomString(),
                'priority' => '1.0',
            ]];

            // Category slugs are included only once the schema is migrated.
            // On early hosting deployments, DB credentials may intentionally
            // be absent; sitemap must still return the canonical homepage.
            try {
                if (Schema::hasTable('product_categories')) {
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
            } catch (Throwable) {
                // Keep sitemap available before database provisioning.
            }

            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            foreach ($urls as $url) {
                $out .= "  <url><loc>{$url['loc']}</loc>"
                    . "<lastmod>{$url['lastmod']}</lastmod>"
                    . "<priority>{$url['priority']}</priority></url>\n";
            }

            return $out . '</urlset>';
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
