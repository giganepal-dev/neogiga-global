<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use App\Services\Product\ProductCertificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductCertificationMarksTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_verified_product_certification_resources_become_marks(): void
    {
        $productId = DB::table('products')->insertGetId([
            'name' => 'Certified Controller',
            'slug' => 'certified-controller',
            'sku' => 'CERT-001',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_resources')->insert([
            [
                'product_id' => $productId,
                'type' => 'certification',
                'title' => 'CE Declaration of Conformity',
                'is_verified' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $productId,
                'type' => 'certification',
                'title' => 'Unreviewed FCC document',
                'is_verified' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $marks = app(ProductCertificationService::class)->verifiedFor(Product::findOrFail($productId));

        $this->assertSame(['CE'], $marks->pluck('label')->all());
        $html = Blade::render('<x-product-certification-marks :certifications="$marks" />', compact('marks'));
        $this->assertStringContainsString('is-verified', $html);
        $this->assertStringContainsString('CE', $html);
        $this->assertStringNotContainsString('FCC', $html);
    }

    public function test_all_product_surfaces_include_compliance_mark_and_footer_is_non_claiming(): void
    {
        foreach ([
            'frontend/products/index.blade.php',
            'frontend/products/show.blade.php',
            'frontend/categories/show.blade.php',
            'frontend/brands/show.blade.php',
            'frontend/seo/landing.blade.php',
            'frontend/compare/index.blade.php',
        ] as $view) {
            $this->assertStringContainsString('product-certification-marks', File::get(resource_path('views/'.$view)));
        }

        $footer = File::get(resource_path('views/frontend/layout.blade.php'));
        $this->assertStringContainsString('International standards &amp; certification references', $footer);
        $this->assertStringContainsString('do not claim NeoGiga membership or corporate certification', $footer);
    }
}
