<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Manufacturer;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Seo\CatalogSeoTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoLandingController extends Controller
{
    public function manufacturer(string $slug): View
    {
        $manufacturer = Schema::hasTable('manufacturers')
            ? Manufacturer::active()->where('slug', $slug)->first()
            : null;
        $name = $manufacturer?->name ?: $this->titleFromSlug($slug);
        $marketplaceContext = app(GlobalMarketplaceContextService::class)->context(request());
        $seoEntity = $manufacturer ?: new Manufacturer(['name' => $name, 'slug' => $slug, 'is_active' => true]);
        $generatedSeo = app(CatalogSeoTemplateService::class)->manufacturer(
            $seoEntity,
            $marketplaceContext['current'] ?? null,
            $marketplaceContext['locale'] ?? 'en',
        );
        $products = $this->baseProducts()
            ->where(function ($query) use ($slug, $name) {
                if (Schema::hasColumn('products', 'manufacturer_id') && Schema::hasTable('manufacturers')) {
                    $query->whereExists(function ($sub) use ($slug) {
                        $sub->selectRaw('1')
                            ->from('manufacturers')
                            ->whereColumn('manufacturers.id', 'products.manufacturer_id')
                            ->where('manufacturers.slug', $slug);
                    });
                }

                $query->orWhereRaw('LOWER(manufacturer_name) = ?', [strtolower($name)])
                    ->orWhereRaw('LOWER(REPLACE(manufacturer_name, \' \', \'-\')) = ?', [strtolower($slug)]);
            })
            ->limit(24)
            ->get();

        return $this->view('manufacturer', $name, $products, [
            'title' => ($manufacturer?->seo_title) ?: $generatedSeo['title'],
            'description' => ($manufacturer?->seo_description) ?: $generatedSeo['description'],
            'eyebrow' => 'Manufacturer catalog',
            'empty' => 'Manufacturer catalog data is ready for onboarding. Search the global catalog or submit an RFQ for this manufacturer.',
        ], [
            'url' => $generatedSeo['canonical'],
            'canonical' => $generatedSeo['canonical'],
            'robots' => $generatedSeo['robots'],
            'robots_reason' => $generatedSeo['robots_reason'],
            'source_name' => $manufacturer?->source_name,
            'source_url' => $manufacturer?->source_url,
            'last_verified_at' => $manufacturer?->last_verified_at,
            'source_notes' => $generatedSeo['source_notes'],
            'confidence_level' => $generatedSeo['confidence_level'],
            'last_updated' => $generatedSeo['last_updated'],
        ]);
    }

    public function brand(string $slug): View
    {
        $brand = ProductBrand::active()->where('slug', $slug)->firstOrFail();
        $products = $this->baseProducts()
            ->where('brand_id', $brand->id)
            ->limit(24)
            ->get();

        $seoMeta = is_array($brand->seo_meta) ? $brand->seo_meta : [];

        return $this->view('brand', $brand->name, $products, [
            'title' => ($seoMeta['title'] ?? null) ?: $brand->name.' Products, Stock, RFQ and Engineering Components',
            'description' => ($seoMeta['description'] ?? null) ?: 'Browse '.$brand->name.' products on NeoGiga with regional availability, RFQ sourcing, technical references and marketplace stock routing.',
            'eyebrow' => 'Brand catalog',
            'empty' => 'This brand page is ready. Public products will appear when approved and visible catalog rows are published.',
        ], [
            'url' => url()->current(),
            'source_name' => $brand->manufacturer?->source_name,
            'source_url' => $brand->manufacturer?->source_url ?: $brand->website_url,
            'last_verified_at' => $brand->updated_at,
        ]);
    }

    public function mpn(string $mpn): View
    {
        $normalized = strtoupper($mpn);
        $products = $this->baseProducts()
            ->whereRaw('LOWER(mpn) = ?', [strtolower($mpn)])
            ->limit(24)
            ->get();

        return $this->view('mpn', $normalized, $products, [
            'title' => $normalized.' Datasheet, Stock, Alternatives and RFQ',
            'description' => 'Find '.$normalized.' on NeoGiga: datasheets, regional stock, compatible alternatives, RFQ sourcing and AI engineering guidance.',
            'eyebrow' => 'Manufacturer Part Number',
            'empty' => 'This MPN page is ready. Use RFQ or AI sourcing while catalog data is being verified.',
        ]);
    }

    public function technology(string $slug): View
    {
        return $this->keywordLanding('technology', $slug, 'Technology marketplace');
    }

    public function application(string $slug): View
    {
        return $this->keywordLanding('application', $slug, 'Application sourcing');
    }

    public function country(string $code): View
    {
        $country = DB::table('countries')
            ->whereRaw('LOWER(iso_code_2) = ?', [strtolower($code)])
            ->orWhereRaw('LOWER(iso_code_3) = ?', [strtolower($code)])
            ->first();

        abort_unless($country, 404);

        $products = $this->baseProducts()
            ->whereExists(function ($query) use ($country) {
                $query->selectRaw('1')
                    ->from('inventory_stocks')
                    ->whereColumn('inventory_stocks.product_id', 'products.id')
                    ->where('inventory_stocks.country_id', $country->id)
                    ->where('inventory_stocks.quantity_available', '>', 0);
            })
            ->limit(24)
            ->get();

        return $this->view('country', $country->name, $products, [
            'title' => 'NeoGiga '.$country->name.' Marketplace and Regional Stock',
            'description' => 'Shop NeoGiga '.$country->name.' regional electronics, semiconductors, IoT, robotics and engineering products with local warehouse routing and RFQ support.',
            'eyebrow' => 'Country storefront',
            'empty' => 'Regional stock pages are ready. Warehouses and product availability will appear as inventory is onboarded.',
        ]);
    }

    private function keywordLanding(string $type, string $slug, string $eyebrow): View
    {
        $name = $this->titleFromSlug($slug);
        $products = $this->baseProducts()
            ->where(function ($query) use ($slug) {
                $needle = '%'.str_replace('-', ' ', strtolower($slug)).'%';
                $query->whereRaw('LOWER(name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(short_description) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(search_keywords) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(tags::text) LIKE ?', [$needle]);
            })
            ->limit(24)
            ->get();

        return $this->view($type, $name, $products, [
            'title' => $name.' Products, Projects, BOM and RFQ',
            'description' => 'Build and source '.$name.' projects on NeoGiga with global catalog search, AI BOM generation, LMS tutorials, regional stock and RFQ support.',
            'eyebrow' => $eyebrow,
            'empty' => 'This landing page is ready for catalog, LMS and RFQ content as data is onboarded.',
        ]);
    }

    private function view(string $type, string $name, $products, array $copy, array $meta = []): View
    {
        return view('frontend.seo.landing', [
            'type' => $type,
            'name' => $name,
            'products' => $products,
            'copy' => $copy,
            'meta' => $meta,
            'schema' => $this->schema($type, $name, $copy, $meta),
            'canonical' => $meta['canonical'] ?? ($meta['url'] ?? url()->current()),
            'robots' => $meta['robots'] ?? null,
            'robotsReason' => $meta['robots_reason'] ?? null,
        ]);
    }

    private function baseProducts()
    {
        return Product::query()
            ->with(['brand', 'category'])
            ->published()
            ->orderByDesc('is_featured')
            ->orderByDesc('id');
    }

    private function titleFromSlug(string $slug): string
    {
        return Str::of($slug)->replace(['-', '_'], ' ')->title()->toString();
    }

    private function schema(string $type, string $name, array $copy, array $meta = []): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $copy['title'],
            'description' => $copy['description'],
            'url' => $meta['url'] ?? url()->current(),
            'about' => [
                '@type' => 'Thing',
                'name' => $name,
            ],
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'NeoGiga',
                'url' => url('/'),
            ],
            'additionalType' => $type,
        ];
    }
}
