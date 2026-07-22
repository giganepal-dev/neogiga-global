<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Services\Marketplace\MarketplaceUrlGenerator;
use App\Services\MarketplaceResolverService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Google Merchant Center product feed (RSS 2.0 + g: namespace). Host-aware, so
 * each marketplace domain serves its own currency-correct feed — point the
 * Merchant Center "scheduled fetch" at https://<domain>/feeds/google-merchant.xml.
 *
 * Prices come from the per-marketplace marketplace_product_prices row (the same
 * stored price the product page renders), so feed price == landing price — a
 * Merchant Center approval requirement. Items lacking a positive price or a real
 * image are skipped rather than sent and disapproved.
 */
class GoogleMerchantFeedController extends Controller
{
    private const MAX_ITEMS = 50000; // Google's hard limit per feed file.

    public function __invoke(): Response
    {
        $marketplace = $this->marketplace();
        // Same visibility gate as the sitemap: no feed for inactive/no-index editions.
        abort_unless(! $marketplace || ($marketplace->is_active && $marketplace->indexable), 404);

        $version = (string) Cache::get('seo:sitemap-version', '1');
        $cacheKey = 'feed:google-merchant:v1:'.$version.':'.request()->getHost();

        $xml = Cache::remember($cacheKey, 3600, fn () => $this->build($marketplace));

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600, stale-while-revalidate=600');
    }

    private function build(?Marketplace $marketplace): string
    {
        $fallbackCurrency = strtoupper((string) ($marketplace?->currency_code ?: 'USD'));

        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'."\n"
            .'<channel>'."\n"
            .'  <title>'.e(($marketplace?->name ?: 'NeoGiga').' product feed').'</title>'."\n"
            .'  <link>'.e($this->url($marketplace, '/en/products')).'</link>'."\n"
            .'  <description>NeoGiga electronic components and engineering products</description>'."\n";

        $count = 0;
        Product::query()
            ->published()
            ->whereNotNull('slug')->where('slug', '!=', '')
            ->with([
                'brand:id,name,slug',
                'images' => fn ($query) => $query->where('is_active', true)->orderByDesc('is_primary')->orderBy('sort_order'),
                'marketplacePrices' => fn ($query) => $query->where('is_active', true)
                    ->when($marketplace, fn ($inner) => $inner->where('marketplace_id', $marketplace->id)),
            ])
            ->orderBy('id')
            ->chunkById(1000, function ($products) use (&$out, &$count, $marketplace, $fallbackCurrency) {
                foreach ($products as $product) {
                    if ($count >= self::MAX_ITEMS) {
                        return false; // stop the walk at Google's per-file limit
                    }
                    if ($item = $this->item($product, $marketplace, $fallbackCurrency)) {
                        $out .= $item;
                        $count++;
                    }
                }
            });

        return $out.'</channel>'."\n".'</rss>'."\n";
    }

    private function item(Product $product, ?Marketplace $marketplace, string $fallbackCurrency): ?string
    {
        $price = $product->marketplacePrices->first();
        $amount = (float) ($price->base_price ?? 0);
        if ($amount <= 0) {
            return null; // no real price → Merchant Center would reject the item
        }
        $currency = strtoupper((string) (($price->currency_code ?? '') ?: $fallbackCurrency));

        $image = $product->images->firstWhere('is_primary', true) ?: $product->images->first();
        $imageUrl = $image?->publicUrl();
        if (! $imageUrl || str_contains($imageUrl, 'neogiga-product-placeholder')) {
            return null; // Google requires a genuine product image
        }

        $gtin = trim((string) $product->gtin);
        $mpn = trim((string) $product->mpn);
        $brand = trim((string) ($product->brand?->name ?: $product->manufacturer_name));
        $description = trim(strip_tags((string) ($product->short_description ?: $product->description ?: $product->name)));

        $item = '  <item>'."\n"
            .$this->tag('g:id', (string) ($product->sku ?: $product->id))
            .$this->tag('g:title', $product->name)
            .$this->tag('g:description', $description !== '' ? $description : $product->name)
            .$this->tag('g:link', $this->url($marketplace, '/en/products/'.$product->slug))
            .$this->tag('g:image_link', $imageUrl)
            .$this->tag('g:availability', ((int) $product->stock_quantity) > 0 ? 'in_stock' : 'out_of_stock')
            .$this->tag('g:price', number_format($amount, 2, '.', '').' '.$currency)
            .$this->tag('g:condition', 'new');

        if ($brand !== '') {
            $item .= $this->tag('g:brand', $brand);
        }
        if ($gtin !== '') {
            $item .= $this->tag('g:gtin', $gtin);
        }
        if ($mpn !== '') {
            $item .= $this->tag('g:mpn', $mpn);
        }
        // Google requires this flag when a product has neither GTIN nor MPN.
        if ($gtin === '' && $mpn === '') {
            $item .= $this->tag('g:identifier_exists', 'no');
        }

        // Optional sale price when a sale window is active and genuinely cheaper.
        $sale = (float) ($price->sale_price ?? 0);
        if ($sale > 0 && $sale < $amount && $this->saleActive($price)) {
            $item .= $this->tag('g:sale_price', number_format($sale, 2, '.', '').' '.$currency);
        }

        return $item.'  </item>'."\n";
    }

    private function saleActive(object $price): bool
    {
        $today = now()->toDateString();
        $start = $price->sale_start_date ? substr((string) $price->sale_start_date, 0, 10) : null;
        $end = $price->sale_end_date ? substr((string) $price->sale_end_date, 0, 10) : null;

        return (! $start || $start <= $today) && (! $end || $end >= $today);
    }

    private function tag(string $name, string $value): string
    {
        return '    <'.$name.'>'.e($value).'</'.$name.'>'."\n";
    }

    private function marketplace(): ?Marketplace
    {
        try {
            return app(MarketplaceResolverService::class)->resolve(request());
        } catch (Throwable) {
            return null;
        }
    }

    private function url(?Marketplace $marketplace, string $path): string
    {
        return $marketplace
            ? app(MarketplaceUrlGenerator::class)->forMarketplace($marketplace, $path)
            : url($path);
    }
}
