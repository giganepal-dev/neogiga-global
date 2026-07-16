<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductImage;
use App\Services\CatalogImport\Ti\TiParametricCatalogImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TiParametricCatalogImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_ti_data_as_quote_only_canonical_products_without_unverified_commerce_data(): void
    {
        $semiconductors = ProductCategory::create(['name' => 'Semiconductors', 'slug' => 'semiconductors', 'is_active' => true]);
        $amplifiers = ProductCategory::create(['name' => 'Amplifiers', 'slug' => '191-amplifiers', 'parent_id' => $semiconductors->id, 'is_active' => true]);
        ProductCategory::create(['name' => 'Current Sense Amplifiers', 'slug' => '266-current-sense-amplifiers', 'parent_id' => $amplifiers->id, 'is_active' => true]);

        $file = tempnam(sys_get_temp_dir(), 'ti-parametrics-');
        $stream = fopen($file, 'wb');
        fputcsv($stream, ['sku', 'mpn', 'name', 'brand', 'category', 'subcategory', 'short_description', 'description', 'specifications_json', 'rating', 'operating_temperature_range_c', 'package_type', 'pin_count', 'package_area_mm2', 'package_size_mm', 'manufacturer_price_usd', 'sale_price_usd', 'manufacturer_status', 'manufacturer_product_url', 'datasheet_pdf_url', 'datasheet_html_url', 'image_url', 'image_fetch_mode']);
        fputcsv($stream, ['INA950-SEP', 'INA950-SEP', 'INA950-SEP ultra-precise current-sense amplifier', 'Texas Instruments', 'Amplifiers', 'Analog current-sense amplifiers', 'source short', 'source description', '{"manufacturer":"Texas Instruments","common_mode_voltage_max_v":80,"voltage_gain_v_v":20}', 'Space', '-55 to 125', 'TSSOP', '8', '19.2', '3 x 6.4', '99.9', '1', 'ACTIVE', 'https://www.ti.com/product/INA950-SEP', 'https://www.ti.com/lit/gpn/INA950-SEP', 'https://www.ti.com/document-viewer/INA950-SEP/datasheet', '', 'OG_IMAGE_FROM_PRODUCT_PAGE']);
        fclose($stream);

        $importer = app(TiParametricCatalogImporter::class);
        $dryRun = $importer->import($file, ['dry_run' => true]);
        $this->assertSame(0, Product::count());
        $this->assertSame(1, $dryRun['seen']);

        $importer->import($file, ['publish' => true]);
        $product = Product::query()->where('normalized_mpn', 'INA950-SEP')->firstOrFail();

        $this->assertSame('approved', $product->status);
        if (Schema::hasColumn('products', 'visibility_status')) {
            $this->assertSame('quote_only', $product->visibility_status);
        }
        $this->assertStringStartsWith('NG-TI-', $product->sku);
        $this->assertSame(0.0, (float) $product->base_price);
        $this->assertFalse($product->track_inventory);
        $this->assertSame('Texas Instruments parametric export', $product->source_name);
        $this->assertCount(0, ProductImage::where('product_id', $product->id)->get());
        $this->assertGreaterThanOrEqual(8, $product->specs()->count());
        $this->assertNotNull($product->seoMeta);
        $this->assertSame('index,follow', $product->seoMeta->robots);

        $product->forceFill(['manufacturer_id' => null, 'source_name' => 'Legacy manual catalog'])->save();
        $importer->import($file, ['publish' => true]);
        $this->assertSame(1, Product::where('normalized_mpn', 'INA950-SEP')->count());

        @unlink($file);
    }
}
