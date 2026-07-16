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

        $this->assertStringContainsString('->simplePaginate(24)', $controller);
        $this->assertStringContainsString("'catalogTotal'", $controller);
        $this->assertStringNotContainsString('$products->total()', $template);
    }
}
