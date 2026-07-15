<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Services\Catalog\BrandVisibilityService;
use App\Services\Marketplace\MarketplaceUrlGenerator;
use App\Services\MarketplaceResolverService;
use App\Services\Product\ProductVisibilityService;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Dynamic, host-aware sitemap index. Catalog URLs are sharded so imports can
 * grow beyond the 50,000-URL sitemap protocol limit without silently dropping
 * products. Only routes already public through ProductVisibilityService are
 * published.
 */
class SitemapController extends Controller
{
    private const CHUNK_SIZE = 10000;

    private const CACHE_VERSION = 'v3';

    public function __invoke(): Response
    {
        $marketplace = $this->marketplace();
        $state = $this->catalogState($this->includeCatalog($marketplace));
        $version = (string) Cache::get('seo:sitemap-version', '1');
        $cacheKey = 'seo:sitemap-index:'.self::CACHE_VERSION.':'.$version.':'.request()->getHost().':'.sha1(json_encode($state));

        $xml = Cache::remember($cacheKey, 3600, function () use ($marketplace, $state) {
            $maps = [[
                'loc' => $this->canonicalUrl($marketplace, '/sitemaps/pages-1.xml'),
                'lastmod' => null,
            ]];

            foreach (['categories', 'brands', 'manufacturers', 'products'] as $section) {
                $count = (int) $state[$section.'_count'];
                for ($page = 1; $page <= (int) ceil($count / self::CHUNK_SIZE); $page++) {
                    $maps[] = [
                        'loc' => $this->canonicalUrl($marketplace, "/sitemaps/{$section}-{$page}.xml"),
                        'lastmod' => $state[$section.'_lastmod'],
                    ];
                }
            }

            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach ($maps as $map) {
                $out .= '  <sitemap><loc>'.e($map['loc']).'</loc>';
                if ($map['lastmod']) {
                    $out .= '<lastmod>'.e($map['lastmod']).'</lastmod>';
                }
                $out .= "</sitemap>\n";
            }

            return $out.'</sitemapindex>';
        });

        return $this->xmlResponse($xml);
    }

    public function section(string $section, int $page): Response
    {
        abort_unless(in_array($section, ['pages', 'categories', 'brands', 'manufacturers', 'products'], true) && $page > 0, 404);

        $marketplace = $this->marketplace();
        $includeCatalog = $this->includeCatalog($marketplace);
        abort_if($section !== 'pages' && ! $includeCatalog, 404);

        $state = $this->catalogState($includeCatalog);
        $count = $section === 'pages' ? count($this->staticPaths()) : (int) $state[$section.'_count'];
        abort_if($page > max(1, (int) ceil($count / self::CHUNK_SIZE)), 404);

        $fingerprint = $section === 'pages'
            ? 'static-v1'
            : $count.'|'.($state[$section.'_lastmod'] ?? '');
        $version = (string) Cache::get('seo:sitemap-version', '1');
        $cacheKey = 'seo:sitemap-section:'.self::CACHE_VERSION.':'.$version.':'.request()->getHost().":{$section}:{$page}:".sha1($fingerprint);

        $xml = Cache::remember($cacheKey, 3600, function () use ($marketplace, $section, $page) {
            $urls = $this->sectionUrls($marketplace, $section, $page);
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach ($urls as $url) {
                $out .= '  <url><loc>'.e($url['loc']).'</loc>';
                if ($url['lastmod']) {
                    $out .= '<lastmod>'.e($url['lastmod']).'</lastmod>';
                }
                $out .= '<priority>'.e($url['priority'])."</priority></url>\n";
            }

            return $out.'</urlset>';
        });

        return $this->xmlResponse($xml);
    }

    /** @return array<int, array{loc:string,lastmod:?string,priority:string}> */
    private function sectionUrls(?Marketplace $marketplace, string $section, int $page): array
    {
        if ($section === 'pages') {
            return collect($this->staticPaths())->map(fn (array $page) => [
                'loc' => $this->canonicalUrl($marketplace, $page['path']),
                'lastmod' => null,
                'priority' => $page['priority'],
            ])->all();
        }

        if ($section === 'categories') {
            return ProductCategory::query()
                ->where('is_active', true)
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->orderBy('id')
                ->forPage($page, self::CHUNK_SIZE)
                ->get(['slug', 'updated_at'])
                ->map(fn (ProductCategory $category) => [
                    'loc' => $this->canonicalUrl($marketplace, '/en/categories/'.$category->slug),
                    'lastmod' => $this->lastmod($category->updated_at),
                    'priority' => '0.7',
                ])->all();
        }

        if ($section === 'brands') {
            return app(BrandVisibilityService::class)
                ->visibleFor($marketplace, false, 'en', request()->getHost())
                ->filter(fn (ProductBrand $brand) => trim((string) $brand->slug) !== '')
                ->forPage($page, self::CHUNK_SIZE)
                ->map(fn (ProductBrand $brand) => [
                    'loc' => $this->canonicalUrl($marketplace, '/en/brand/'.$brand->slug),
                    'lastmod' => $this->lastmod($brand->updated_at),
                    'priority' => '0.7',
                ])->all();
        }

        if ($section === 'manufacturers') {
            return $this->manufacturerIdentities()
                ->forPage($page, self::CHUNK_SIZE)
                ->map(fn (object $manufacturer) => [
                    'loc' => $this->canonicalUrl(
                        $marketplace,
                        '/en/manufacturer/'.$manufacturer->slug
                    ),
                    'lastmod' => $this->lastmod($manufacturer->updated_at),
                    'priority' => '0.7',
                ])->values()->all();
        }

        return app(ProductVisibilityService::class)
            ->publicProducts()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('id')
            ->forPage($page, self::CHUNK_SIZE)
            ->get(['slug', 'updated_at'])
            ->map(fn (object $product) => [
                'loc' => $this->canonicalUrl($marketplace, '/en/products/'.$product->slug),
                'lastmod' => $this->lastmod($product->updated_at ?? null),
                'priority' => '0.6',
            ])->all();
    }

    /** @return array<int, array{path:string,priority:string}> */
    private function staticPaths(): array
    {
        return [
            ['path' => '/en', 'priority' => '1.0'],
            ['path' => '/en/products', 'priority' => '0.9'],
            ['path' => '/en/categories', 'priority' => '0.8'],
            ['path' => '/en/brands', 'priority' => '0.8'],
            ['path' => '/en/lms', 'priority' => '0.7'],
            ['path' => '/en/rfq', 'priority' => '0.6'],
            ['path' => '/en/sell-on-neogiga', 'priority' => '0.6'],
            ['path' => '/en/distributors', 'priority' => '0.6'],
            ['path' => '/en/ai-commerce', 'priority' => '0.7'],
        ];
    }

    /** @return array{categories_count:int,categories_lastmod:?string,brands_count:int,brands_lastmod:?string,manufacturers_count:int,manufacturers_lastmod:?string,products_count:int,products_lastmod:?string} */
    private function catalogState(bool $includeCatalog): array
    {
        $state = [
            'categories_count' => 0,
            'categories_lastmod' => null,
            'brands_count' => 0,
            'brands_lastmod' => null,
            'manufacturers_count' => 0,
            'manufacturers_lastmod' => null,
            'products_count' => 0,
            'products_lastmod' => null,
        ];

        if (! $includeCatalog) {
            return $state;
        }

        try {
            if (Schema::hasTable('product_categories')) {
                $categories = ProductCategory::query()->where('is_active', true)->whereNotNull('slug')->where('slug', '!=', '');
                $state['categories_count'] = (clone $categories)->count();
                $state['categories_lastmod'] = $this->lastmod((clone $categories)->max('updated_at'));
            }
            if (Schema::hasTable('product_brands')) {
                $brands = app(BrandVisibilityService::class)
                    ->visibleFor($this->marketplace(), false, 'en', request()->getHost())
                    ->filter(fn (ProductBrand $brand) => trim((string) $brand->slug) !== '');
                $state['brands_count'] = $brands->count();
                $state['brands_lastmod'] = $this->lastmod($brands->max('updated_at'));
            }
            if (Schema::hasTable('products')) {
                $products = app(ProductVisibilityService::class)->publicProducts()->whereNotNull('slug')->where('slug', '!=', '');
                $state['products_count'] = (clone $products)->count();
                $state['products_lastmod'] = $this->lastmod((clone $products)->max('updated_at'));

                $manufacturers = $this->manufacturerIdentities();
                $state['manufacturers_count'] = $manufacturers->count();
                $state['manufacturers_lastmod'] = $this->lastmod($manufacturers->max('updated_at'));
            }
        } catch (Throwable) {
            // The pages sitemap remains available before database provisioning.
        }

        return $state;
    }

    /**
     * Merge persisted manufacturer identities with source-backed virtual
     * identities from public products. Every product query passes through the
     * shared publication gate; inactive persisted identities continue to win
     * identity ownership so a product name cannot silently republish one.
     *
     * @return Collection<int, object{slug:string,updated_at:mixed}>
     */
    private function manufacturerIdentities(): Collection
    {
        $identities = collect();
        $knownNames = [];
        $knownSlugs = [];

        if (Schema::hasTable('manufacturers')) {
            $persisted = DB::table('manufacturers')
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->orderBy('id')
                ->get(['name', 'slug', 'is_active', 'updated_at']);

            foreach ($persisted as $manufacturer) {
                $knownNames[$this->manufacturerIdentityKey($manufacturer->name)] = true;
                $slug = strtolower(trim((string) $manufacturer->slug));
                if ($slug !== '') {
                    $knownSlugs[$slug] = true;
                }
                if (! (bool) $manufacturer->is_active || $slug === '') {
                    continue;
                }

                $identities->put($slug, (object) [
                    'slug' => $slug,
                    'updated_at' => $manufacturer->updated_at,
                ]);
            }
        }

        $virtual = app(ProductVisibilityService::class)
            ->publicProducts()
            ->whereNotNull('products.manufacturer_name')
            ->where('products.manufacturer_name', '!=', '')
            ->select('products.manufacturer_name', DB::raw('max(products.updated_at) as updated_at'))
            ->groupBy('products.manufacturer_name')
            ->orderBy('products.manufacturer_name')
            ->get();

        foreach ($virtual as $manufacturer) {
            $name = trim((string) $manufacturer->manufacturer_name);
            $slug = strtolower(Str::slug($name));
            if ($name === '' || $slug === ''
                || isset($knownNames[$this->manufacturerIdentityKey($name)])
                || isset($knownSlugs[$slug])
                || $identities->has($slug)) {
                continue;
            }

            $identities->put($slug, (object) [
                'slug' => $slug,
                'updated_at' => $manufacturer->updated_at,
            ]);
        }

        return $identities->sortKeys()->values();
    }

    private function manufacturerIdentityKey(mixed $name): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', ' ', (string) $name) ?? ''));
    }

    private function marketplace(): ?Marketplace
    {
        try {
            return app(MarketplaceResolverService::class)->resolve(request());
        } catch (Throwable) {
            return null;
        }
    }

    private function includeCatalog(?Marketplace $marketplace): bool
    {
        return ! $marketplace
            || ($marketplace->is_active && $marketplace->indexable && ($marketplace->sitemap_enabled ?? true));
    }

    private function canonicalUrl(?Marketplace $marketplace, string $path): string
    {
        return $marketplace
            ? app(MarketplaceUrlGenerator::class)->forMarketplace($marketplace, $path)
            : url($path);
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600, stale-while-revalidate=600');
    }

    private function lastmod(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toAtomString();
        }

        if (is_string($value) && $value !== '') {
            $timestamp = strtotime($value);

            return $timestamp ? date(DATE_ATOM, $timestamp) : null;
        }

        return null;
    }
}
