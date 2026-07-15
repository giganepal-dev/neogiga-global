<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogIdentitySeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_links_to_brand_and_manufacturer_identity_pages(): void
    {
        $manufacturerId = DB::table('manufacturers')->insertGetId([
            'name' => 'Acme Components',
            'slug' => 'acme-components',
            'source_name' => 'QA fixture',
            'source_url' => 'https://example.com/acme',
            'confidence_level' => 'source_backed',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $brandId = DB::table('product_brands')->insertGetId([
            'name' => 'Acme',
            'slug' => 'acme',
            'manufacturer_id' => $manufacturerId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'name' => 'Acme Sensor Module',
            'slug' => 'acme-sensor-module',
            'sku' => 'NG-ACME-1',
            'mpn' => 'ACM-100',
            'normalized_mpn' => 'ACM-100',
            'brand_id' => $brandId,
            'manufacturer_id' => $manufacturerId,
            'manufacturer_name' => 'Acme Components',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/en/products/acme-sensor-module')
            ->assertOk()
            ->assertSee('/brand/acme', false)
            ->assertSee('/manufacturer/acme-components', false)
            ->assertSee('Acme Components')
            ->assertSee('"manufacturer"', false);

        $this->get('/brand/acme')
            ->assertOk()
            ->assertSee('Acme Sensor Module');

        $this->get('/manufacturer/acme-components')
            ->assertOk()
            ->assertSee('Acme Sensor Module');
    }
}
