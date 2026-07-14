<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegionStockVisibilityTest extends TestCase
{
    /**
     * The root route is intentionally canonicalized to the default locale.
     */
    public function test_root_redirects_to_canonical_locale(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/en');
    }
}
