<?php

namespace Tests\Unit;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Services\Seo\ProductSchemaService;
use Tests\TestCase;

/**
 * DB-free unit tests for the central Product JSON-LD builder.
 * Guards the Search Console remediation rules: no synthetic ratings,
 * no zero-price offers, marketplace-country (not URL-prefix) regionality.
 */
class ProductSchemaServiceTest extends TestCase
{
    private function ctx(array $overrides = []): array
    {
        return $overrides + [
            'canonical' => 'https://neogiga.com/en/products/test-widget-1',
            'origin' => 'https://neogiga.com',
            'base' => '/en',
            'images' => ['https://neogiga.com/images/products/widget.jpg'],
            'price' => 2.50,
            'currency' => 'USD',
            'country' => null,
            'marketplace' => null,
            'manufacturer' => null,
            'reviewSummary' => (object) ['count' => 0, 'average' => null],
            'reviews' => [],
        ];
    }

    private function product(): Product
    {
        return (new Product)->forceFill([
            'name' => 'Test Widget',
            'sku' => 'TW-1',
            'mpn' => 'TW1-MPN',
            'short_description' => 'A test widget.',
            'stock_quantity' => 5,
        ]);
    }

    public function test_no_reviews_omits_aggregate_rating_and_review(): void
    {
        $schema = app(ProductSchemaService::class)->build($this->product(), $this->ctx());

        $this->assertArrayNotHasKey('aggregateRating', $schema);
        $this->assertArrayNotHasKey('review', $schema);
        $this->assertSame('Product', $schema['@type']);
        $this->assertJson(json_encode($schema));
    }

    public function test_genuine_reviews_emit_rating_and_review_without_private_data(): void
    {
        $schema = app(ProductSchemaService::class)->build($this->product(), $this->ctx([
            'reviewSummary' => (object) ['count' => 3, 'average' => 4.333],
            'reviews' => [
                (object) ['reviewer_name' => 'Asha', 'reviewer_email' => 'private@example.com', 'rating' => 5, 'body' => 'Great part.', 'created_at' => '2026-07-15 10:00:00'],
                (object) ['reviewer_name' => '', 'rating' => 4, 'body' => 'Works.', 'created_at' => '2026-07-14 09:00:00'],
            ],
        ]));

        $this->assertSame('4.3', $schema['aggregateRating']['ratingValue']);
        $this->assertSame('3', $schema['aggregateRating']['reviewCount']);
        $this->assertSame('5', $schema['aggregateRating']['bestRating']);
        $this->assertCount(2, $schema['review']);
        $this->assertSame('Asha', $schema['review'][0]['author']['name']);
        $this->assertSame('NeoGiga customer', $schema['review'][1]['author']['name']);
        $this->assertSame('2026-07-15', $schema['review'][0]['datePublished']);
        $this->assertStringNotContainsString('private@example.com', json_encode($schema));
    }

    public function test_zero_price_emits_no_offer_at_all(): void
    {
        $schema = app(ProductSchemaService::class)->build($this->product(), $this->ctx(['price' => 0]));

        $this->assertArrayNotHasKey('offers', $schema);
    }

    public function test_offer_uses_marketplace_country_and_currency(): void
    {
        $schema = app(ProductSchemaService::class)->build($this->product(), $this->ctx([
            'country' => 'np', 'currency' => 'npr', 'price' => 120,
        ]));

        $offer = $schema['offers'];
        $this->assertSame('NPR', $offer['priceCurrency']);
        $this->assertSame('120.00', $offer['price']);
        $this->assertSame('https://schema.org/InStock', $offer['availability']);
        $this->assertSame('NeoGiga', $offer['seller']['name']);
        $this->assertSame('NP', $offer['hasMerchantReturnPolicy']['applicableCountry']);
        $this->assertSame('NP', $offer['shippingDetails']['shippingDestination']['addressCountry']);
        $this->assertNotEmpty($offer['hasMerchantReturnPolicy']['returnFees']);
        $this->assertSame('https://neogiga.com/returns', $offer['hasMerchantReturnPolicy']['returnPolicyUrl']);
        $this->assertSame('NPR', $offer['shippingDetails']['shippingRate']['currency']);
    }

    public function test_no_country_falls_back_to_configured_default(): void
    {
        $schema = app(ProductSchemaService::class)->build($this->product(), $this->ctx());

        $this->assertSame(
            strtoupper((string) config('neogiga_global.schema_commerce.default_country')),
            $schema['offers']['hasMerchantReturnPolicy']['applicableCountry'],
        );
    }

    public function test_marketplace_settings_override_return_policy(): void
    {
        $marketplace = (new Marketplace)->forceFill([
            'settings' => ['commerce_schema' => ['return' => ['days' => 7, 'fees' => 'https://schema.org/ReturnShippingFees']]],
        ]);

        $schema = app(ProductSchemaService::class)->build($this->product(), $this->ctx([
            'marketplace' => $marketplace, 'country' => 'PK',
        ]));

        $policy = $schema['offers']['hasMerchantReturnPolicy'];
        $this->assertSame(7, $policy['merchantReturnDays']);
        $this->assertSame('https://schema.org/ReturnShippingFees', $policy['returnFees']);
        // Non-overridden keys keep global defaults.
        $this->assertSame('https://schema.org/ReturnByMail', $policy['returnMethod']);
    }
}
