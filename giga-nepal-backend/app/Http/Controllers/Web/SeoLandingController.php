<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoLandingController extends Controller
{
    private const VISIBLE = ['active', 'approved', 'published'];

    public function manufacturer(string $slug): View
    {
        $name = $this->titleFromSlug($slug);
        $products = $this->baseProducts()
            ->where(function ($query) use ($slug, $name) {
                $query->whereRaw('LOWER(manufacturer_name) = ?', [strtolower($name)])
                    ->orWhereRaw('LOWER(REPLACE(manufacturer_name, \' \', \'-\')) = ?', [strtolower($slug)]);
            })
            ->limit(24)
            ->get();

        return $this->view('manufacturer', $name, $products, [
            'title' => $name . ' Parts, MPNs and Engineering Components',
            'description' => 'Source ' . $name . ' components on NeoGiga with global catalog, regional stock routing, RFQ support, LMS links and AI engineering assistance.',
            'eyebrow' => 'Manufacturer catalog',
            'empty' => 'Manufacturer catalog data is ready for onboarding. Search the global catalog or submit an RFQ for this manufacturer.',
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
            'title' => $normalized . ' Datasheet, Stock, Alternatives and RFQ',
            'description' => 'Find ' . $normalized . ' on NeoGiga: datasheets, regional stock, compatible alternatives, RFQ sourcing and AI engineering guidance.',
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
            'title' => 'NeoGiga ' . $country->name . ' Marketplace and Regional Stock',
            'description' => 'Shop NeoGiga ' . $country->name . ' regional electronics, semiconductors, IoT, robotics and engineering products with local warehouse routing and RFQ support.',
            'eyebrow' => 'Country storefront',
            'empty' => 'Regional stock pages are ready. Warehouses and product availability will appear as inventory is onboarded.',
        ]);
    }

    private function keywordLanding(string $type, string $slug, string $eyebrow): View
    {
        $name = $this->titleFromSlug($slug);
        $products = $this->baseProducts()
            ->where(function ($query) use ($name, $slug) {
                $needle = '%' . str_replace('-', ' ', strtolower($slug)) . '%';
                $query->whereRaw('LOWER(name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(short_description) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(search_keywords) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(tags::text) LIKE ?', [$needle]);
            })
            ->limit(24)
            ->get();

        return $this->view($type, $name, $products, [
            'title' => $name . ' Products, Projects, BOM and RFQ',
            'description' => 'Build and source ' . $name . ' projects on NeoGiga with global catalog search, AI BOM generation, LMS tutorials, regional stock and RFQ support.',
            'eyebrow' => $eyebrow,
            'empty' => 'This landing page is ready for catalog, LMS and RFQ content as data is onboarded.',
        ]);
    }

    private function view(string $type, string $name, $products, array $copy): View
    {
        return view('frontend.seo.landing', [
            'type' => $type,
            'name' => $name,
            'products' => $products,
            'copy' => $copy,
            'schema' => $this->schema($type, $name, $copy),
        ]);
    }

    private function baseProducts()
    {
        return Product::query()
            ->with(['brand', 'category'])
            ->whereIn('status', self::VISIBLE)
            ->orderByDesc('is_featured')
            ->orderByDesc('id');
    }

    private function titleFromSlug(string $slug): string
    {
        return Str::of($slug)->replace(['-', '_'], ' ')->title()->toString();
    }

    private function schema(string $type, string $name, array $copy): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $copy['title'],
            'description' => $copy['description'],
            'url' => url()->current(),
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
