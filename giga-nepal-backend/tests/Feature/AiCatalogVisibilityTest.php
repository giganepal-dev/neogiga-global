<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCatalogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_exposes_only_read_only_agent_discovery_contract(): void
    {
        $this->getJson('/api/v1/ai-catalog/manifest')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.read_only', true)
            ->assertJsonPath('data.advisory_only', true)
            ->assertJsonPath('data.endpoints.search', url('/api/v1/ai-catalog/products/search?q={query}'))
            ->assertJsonMissingPath('data.endpoints.cart')
            ->assertJsonMissingPath('data.endpoints.orders');
    }

    public function test_search_and_detail_expose_only_published_products_without_commercial_fields(): void
    {
        $published = $this->product([
            'name' => 'Precision Timer IC',
            'slug' => 'precision-timer-ic',
            'sku' => 'NG-TIMER-001',
            'mpn' => 'NEO-TIMER-001',
            'status' => 'active',
            'description' => '<p>A timing component for embedded systems.</p>',
            'short_description' => 'Timing component.',
            'base_price' => 19.95,
            'sale_price' => 20.95,
            'stock_quantity' => 100,
        ]);
        $hidden = $this->product([
            'name' => 'Precision Timer Draft',
            'slug' => 'precision-timer-draft',
            'sku' => 'NG-TIMER-DRAFT',
            'mpn' => 'NEO-TIMER-DRAFT',
            'status' => 'draft',
        ]);

        $this->getJson('/api/v1/ai-catalog/products/search?q=Precision')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.products.0.slug', $published->slug)
            ->assertJsonPath('data.products.0.provenance.advisory_disclaimer', 'Advisory only. Confirm pricing, stock, delivery, tax, and compliance on the live regional storefront.')
            ->assertJsonMissing(['slug' => $hidden->slug])
            ->assertJsonMissingPath('data.products.0.base_price')
            ->assertJsonMissingPath('data.products.0.sale_price')
            ->assertJsonMissingPath('data.products.0.stock_quantity');

        $this->getJson('/api/v1/ai-catalog/products/'.$published->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', $published->slug)
            ->assertJsonPath('data.description', 'A timing component for embedded systems.')
            ->assertJsonMissingPath('data.base_price')
            ->assertJsonMissingPath('data.sale_price')
            ->assertJsonMissingPath('data.stock_quantity');
    }

    public function test_draft_product_is_not_available_from_agent_detail_endpoint(): void
    {
        $draft = $this->product([
            'name' => 'Unpublished Agent Product',
            'slug' => 'unpublished-agent-product',
            'sku' => 'NG-UNPUBLISHED',
            'mpn' => 'NEO-UNPUBLISHED',
            'status' => 'draft',
        ]);

        $this->getJson('/api/v1/ai-catalog/products/'.$draft->slug)->assertNotFound();
    }

    private function product(array $attributes): Product
    {
        return Product::create(array_merge([
            'name' => 'Test AI Catalog Product',
            'slug' => 'test-ai-catalog-product',
            'sku' => 'NG-AI-CATALOG',
            'mpn' => 'NEO-AI-CATALOG',
            'status' => 'active',
            'track_inventory' => true,
            'stock_quantity' => 0,
        ], $attributes));
    }
}
