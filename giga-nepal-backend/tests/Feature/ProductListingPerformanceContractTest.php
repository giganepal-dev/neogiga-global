<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductListingPerformanceContractTest extends TestCase
{
    public function test_public_listing_avoids_a_full_catalog_count_for_each_request(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $controller = file_get_contents($projectRoot.'/app/Http/Controllers/Web/ProductPageController.php');
        $template = file_get_contents($projectRoot.'/resources/views/frontend/products/index.blade.php');
        $migration = file_get_contents($projectRoot.'/database/migrations/2026_07_16_124000_add_public_product_listing_order_index.php');
        $landing = file_get_contents($projectRoot.'/app/Http/Controllers/Web/LandingController.php');
        $landingMigration = file_get_contents($projectRoot.'/database/migrations/2026_07_16_125000_add_landing_product_order_index.php');

        $this->assertStringContainsString('->simplePaginate(24)', $controller);
        $this->assertStringContainsString("'catalogTotal'", $controller);
        $this->assertStringNotContainsString('$products->total()', $template);
        $this->assertStringContainsString('CREATE INDEX CONCURRENTLY', $migration);
        $this->assertStringContainsString('products_public_listing_order_idx', $migration);
        $this->assertStringContainsString('catalog:landing-products:v1:', $landing);
        $this->assertStringContainsString('addHours(6)', $landing);
        $this->assertStringContainsString('products_landing_order_idx', $landingMigration);
    }
}
