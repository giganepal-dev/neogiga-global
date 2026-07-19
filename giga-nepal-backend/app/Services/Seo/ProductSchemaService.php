<?php

namespace App\Services\Seo;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;

/**
 * Central Product JSON-LD builder shared by every regional product page.
 *
 * Real-data rules (Search Console remediation, 2026-07):
 *  - aggregateRating/review are emitted ONLY when approved public reviews
 *    exist for the product — never zero/placeholder/synthetic values.
 *  - offers is emitted ONLY when a real price exists; no 0.00 stub offers.
 *  - Return/shipping policy values come from config defaults overridable
 *    per marketplace via marketplaces.settings['commerce_schema'], and are
 *    keyed to the MARKETPLACE country — never the URL locale prefix.
 */
class ProductSchemaService
{
    /**
     * @param array{
     *   canonical:string, origin:string, base:string, images:array<int,string>,
     *   price:float|string|null, currency:string, country:?string,
     *   marketplace:?Marketplace, manufacturer:?object,
     *   reviewSummary:?object, reviews:iterable<int,object>
     * } $ctx
     */
    public function build(Product $product, array $ctx): array
    {
        $policy = $this->policy($ctx['marketplace'] ?? null);
        // The GLOBAL edition's pseudo-country (iso "GL") is not a real
        // shipping/return country — use the configured default instead.
        $isGlobal = strtoupper((string) ($ctx['marketplace']->code ?? '')) === 'GLOBAL';
        $country = (! $isGlobal ? strtoupper((string) ($ctx['country'] ?? '')) : '') ?: (string) $policy['default_country'];
        $currency = strtoupper((string) ($ctx['currency'] ?? 'USD'));
        $manufacturer = $ctx['manufacturer'] ?? null;

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'mpn' => $product->mpn,
            'gtin' => $product->gtin ?? null,
            'image' => array_values(array_filter($ctx['images'] ?? [])),
            'brand' => $product->brand?->name ? [
                '@type' => 'Brand',
                'name' => $product->brand->name,
                'url' => $ctx['origin'].$ctx['base'].'/brand/'.$product->brand->slug,
            ] : null,
            'manufacturer' => $manufacturer?->name ? [
                '@type' => 'Organization',
                'name' => $manufacturer->name,
                'url' => $ctx['origin'].$ctx['base'].'/manufacturer/'.$manufacturer->slug,
            ] : (($product->manufacturer_name ?: $product->brand?->name) ? [
                '@type' => 'Organization',
                'name' => $product->manufacturer_name ?: $product->brand->name,
            ] : null),
            'category' => $product->category?->name,
            'url' => $ctx['canonical'],
            'description' => strip_tags($product->short_description ?: $product->description ?: $product->name),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        $this->applyReviews($schema, $ctx['reviewSummary'] ?? null, $ctx['reviews'] ?? []);

        // Real price only — a product without a price gets no Offer at all.
        $price = (float) ($ctx['price'] ?? 0);
        if ($price > 0) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'url' => $ctx['canonical'],
                'priceCurrency' => $currency,
                'price' => number_format($price, 2, '.', ''),
                'availability' => ($product->stock_quantity ?? 0) > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@type' => 'Organization', 'name' => 'NeoGiga'],
                'hasMerchantReturnPolicy' => [
                    '@type' => 'MerchantReturnPolicy',
                    'applicableCountry' => $country,
                    'returnPolicyCategory' => $policy['return']['category'],
                    'merchantReturnDays' => (int) $policy['return']['days'],
                    'returnMethod' => $policy['return']['method'],
                    'returnFees' => $policy['return']['fees'],
                    'refundType' => $policy['return']['refund_type'],
                    'returnPolicyUrl' => $ctx['origin'].$policy['return']['policy_path'],
                ],
                'shippingDetails' => [
                    '@type' => 'OfferShippingDetails',
                    'shippingRate' => [
                        '@type' => 'MonetaryAmount',
                        'value' => (string) $policy['shipping']['rate'],
                        'currency' => $currency,
                    ],
                    'shippingDestination' => [
                        '@type' => 'DefinedRegion',
                        'addressCountry' => $country,
                    ],
                    'deliveryTime' => [
                        '@type' => 'ShippingDeliveryTime',
                        'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => (int) $policy['shipping']['handling_min'], 'maxValue' => (int) $policy['shipping']['handling_max'], 'unitCode' => 'DAY'],
                        'transitTime' => ['@type' => 'QuantitativeValue', 'minValue' => (int) $policy['shipping']['transit_min'], 'maxValue' => (int) $policy['shipping']['transit_max'], 'unitCode' => 'DAY'],
                    ],
                ],
            ];
        }

        return $schema;
    }

    /** aggregateRating + review only from genuine approved reviews. */
    private function applyReviews(array &$schema, ?object $summary, iterable $reviews): void
    {
        $count = (int) ($summary->count ?? 0);
        $average = $summary->average ?? null;
        if ($count < 1 || $average === null || (float) $average <= 0) {
            return;
        }

        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format((float) $average, 1, '.', ''),
            'reviewCount' => (string) $count,
            'bestRating' => '5',
            'worstRating' => '1',
        ];

        $items = [];
        foreach ($reviews as $review) {
            if (count($items) >= 5) {
                break;
            }
            $rating = max(1, min(5, (int) ($review->rating ?? 0)));
            $items[] = array_filter([
                '@type' => 'Review',
                // Public display name only — reviewer emails are never exposed.
                'author' => ['@type' => 'Person', 'name' => trim((string) ($review->reviewer_name ?? '')) ?: 'NeoGiga customer'],
                'datePublished' => $review->created_at ? substr((string) $review->created_at, 0, 10) : null,
                'reviewBody' => trim((string) ($review->body ?? '')) ?: null,
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (string) $rating, 'bestRating' => '5', 'worstRating' => '1'],
            ], fn ($value) => $value !== null);
        }
        if ($items !== []) {
            $schema['review'] = $items;
        }
    }

    /** Config defaults deep-merged with the marketplace's admin-set overrides. */
    private function policy(?Marketplace $marketplace): array
    {
        $defaults = (array) config('neogiga_global.schema_commerce', []);
        $override = (array) (($marketplace->settings['commerce_schema'] ?? null) ?: []);

        return array_replace_recursive($defaults, $override);
    }
}
