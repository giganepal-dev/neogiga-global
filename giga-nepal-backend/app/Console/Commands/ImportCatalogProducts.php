<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportCatalogProducts extends Command
{
    protected $signature = 'neogiga:import-catalog {file} {--source=} {--dry-run}';
    protected $description = 'Import scraped product catalog CSV into NeoGiga';

    private int $imported = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $file = $this->argument('file');
        $source = $this->option('source') ?: 'catalog_import';
        $dryRun = $this->option('dry-run');

        if (! file_exists($file)) { $this->error("File not found: $file"); return 1; }

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);
        $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");

        $this->info("Importing: $file | Source: $source");
        if ($dryRun) $this->warn("DRY RUN");

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $bar->advance();
            try {
                $data = array_combine($headers, $row);
                if (empty($data['name'] ?? '')) continue;
                $this->importRow($data, $source, $dryRun);
            } catch (\Throwable $e) {
                $this->errors++;
            }
        }

        fclose($handle);
        $bar->finish();
        $this->newLine(2);
        $this->info("Imported: $this->imported | Skipped: $this->skipped | Errors: $this->errors");
        return 0;
    }

    private function importRow(array $data, string $source, bool $dryRun): void
    {
        $name = $data['name'] ?? '';
        $sku = $data['sku'] ?? $data['supplier_sku'] ?? '';
        $mpn = $data['mpn'] ?? $data['sku'] ?? '';
        $manufacturer = $data['manufacturer'] ?? $data['source'] ?? '';
        $brand = $data['brand'] ?? $data['manufacturer'] ?? '';
        $category = $data['categories'] ?? $data['category'] ?? '';
        $shortDesc = $data['short_description'] ?? '';
        $longDesc = $data['description'] ?? '';
        $specs = $data['specifications'] ?? '';
        $imageUrl = $data['image_url'] ?? '';
        $imageUrls = $data['additional_images'] ?? '';
        $productUrl = $data['product_url'] ?? $data['source_url'] ?? '';
        $landedPrice = floatval($data['price_usd'] ?? $data['price'] ?? 0);
        $stockQty = intval($data['stock_quantity'] ?? $data['stock'] ?? 0);
        $datasheets = $data['datasheet_urls'] ?? '';
        $apps = $data['applications'] ?? '';

        if (empty($name)) { $this->skipped++; return; }

        $slug = Str::slug($name . '-' . ($sku ?: Str::random(6)));

        // Build a professional description
        $description = $this->buildDescription($name, $shortDesc, $longDesc, $specs, $manufacturer, $category);

        // Pricing: landed price is the imported cost. Sale price = landed * 1.2 (20% markup)
        $costPrice = $landedPrice > 0 ? $landedPrice : null;
        $basePrice = $costPrice ? round($costPrice * 1.2, 2) : null;

        // Resolve or create brand
        $brandName = $brand ?: ($manufacturer ?: 'Unknown');
        $brandId = DB::table('product_brands')->where('name', $brandName)->value('id');
        if (! $brandId && $brandName !== 'Unknown') {
            $brandId = DB::table('product_brands')->insertGetId([
                'name' => $brandName, 'slug' => Str::slug($brandName),
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Resolve or create manufacturer
        $mfrName = $manufacturer ?: $brandName;
        $mfrId = DB::table('manufacturers')->where('name', $mfrName)->value('id');
        if (! $mfrId) {
            $mfrId = DB::table('manufacturers')->insertGetId([
                'name' => $mfrName, 'slug' => Str::slug($mfrName),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Resolve category
        $categoryName = $category;
        if (str_contains($category, '>')) {
            $parts = array_map('trim', explode('>', $category));
            $categoryName = end($parts);
        }
        $categoryId = null;
        if ($categoryName) {
            $categoryId = DB::table('product_categories')->where('name', $categoryName)->value('id');
        }

        // Skip duplicates
        if (DB::table('products')->where('sku', $sku)->orWhere('name', $name)->exists()) {
            $this->skipped++; return;
        }

        if ($dryRun) { $this->imported++; return; }

        DB::table('products')->insert([
            'name' => $name, 'slug' => $slug,
            'sku' => $sku ?: 'IMP-' . Str::random(8),
            'mpn' => $mpn,
            'manufacturer_name' => $manufacturer,
            'brand_id' => $brandId,
            'category_id' => $categoryId,
            'description' => $description,
            'short_description' => $shortDesc,
            'cost_price' => $costPrice,
            'base_price' => $basePrice,
            'sale_price' => $basePrice,
            'stock_quantity' => $stockQty,
            'status' => 'draft',
            'visibility_status' => 'public',
            'source_url' => $productUrl,
            'source_name' => $source,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->imported++;
    }

    private function buildDescription(string $name, string $short, string $long, string $specs, string $mfr, string $cat): string
    {
        $parts = [];

        if ($short && $short !== $long) {
            $parts[] = '<p><strong>' . e($name) . '</strong> — ' . e($short) . '</p>';
        }

        if ($long) {
            $clean = strip_tags($long);
            if (strlen($clean) > 50) {
                $parts[] = '<div class="description">' . e($clean) . '</div>';
            }
        }

        if ($specs && $specs !== '{}' && strlen($specs) > 5) {
            $parts[] = '<div class="specifications"><h3>Technical Details</h3><p>' . e(strip_tags($specs)) . '</p></div>';
        }

        if ($mfr) {
            $parts[] = '<p class="manufacturer">Manufacturer: <strong>' . e($mfr) . '</strong></p>';
        }

        return implode("\n", $parts) ?: e($name);
    }
}
