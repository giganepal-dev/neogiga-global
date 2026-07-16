<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProductStatusBadgesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $viewPath = dirname(__DIR__, 2).'/resources/views';
        config(['view.paths' => [$viewPath]]);
        app('view')->getFinder()->setPaths([$viewPath]);
    }

    public function test_prioritizes_lifecycle_stock_and_discount_badges(): void
    {
        $product = new Product([
            'lifecycle_status' => 'DISCONTINUED',
            'track_inventory' => true,
            'stock_quantity' => 42,
        ]);

        $html = view('components.product-status-badges', [
            'product' => $product,
            'stockRows' => new Collection([(object) ['quantity_available' => 42]]),
            'referencePrice' => 100,
            'salePrice' => 80,
            'placement' => 'overlay',
        ])->render();

        $this->assertStringContainsString('Discontinued', $html);
        $this->assertStringContainsString('Regional Stock', $html);
        $this->assertStringContainsString('20% Off', $html);
        $this->assertStringNotContainsString('Datasheet Available', $html);
    }

    public function test_does_not_claim_stock_or_discount_without_supported_values(): void
    {
        $product = new Product([
            'lifecycle_status' => 'UNKNOWN',
            'track_inventory' => false,
            'stock_quantity' => 99,
        ]);

        $html = view('components.product-status-badges', ['product' => $product])->render();

        $this->assertStringContainsString('Status Unverified', $html);
        $this->assertStringContainsString('RFQ Pricing', $html);
        $this->assertStringNotContainsString('In Stock', $html);
        $this->assertStringNotContainsString('% Off', $html);
    }
}
