<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportCatalogProducts extends Command
{
    protected $signature = 'neogiga:import-catalog {file} {--source=} {--dry-run} {--download-images}';
    protected $description = 'Import scraped product catalog CSV following NeoGiga rules';

    private int $imported = 0;
    private int $skipped = 0;
    private int $errors = 0;
    private int $nextSku = 0;

    public function handle(): int
    {
        $file = $this->argument('file');
        $source = $this->option('source') ?: 'catalog_import';
        $dryRun = $this->option('dry-run');
        $downloadImages = $this->option('download-images');

        if (! file_exists($file)) { $this->error("File not found: $file"); return 1; }

        // Get next SKU number
        $maxSku = DB::table('products')->where('sku', 'like', 'NG-%')->max('sku');
        $this->nextSku = $maxSku ? (int) substr($maxSku, 3) + 1 : 1;

        $this->info("Importing: $file | Source: $source | Next SKU: NG-{$this->nextSku}");
        if ($dryRun) $this->warn("DRY RUN");

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle, null, ',', '"', '');
        $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $bar->advance();
            try {
                $data = array_combine($headers, array_slice($row, 0, count($headers)) + array_fill(0, count($headers), ""));
                if (empty($data['name'] ?? '')) continue;
                $this->importRow($data, $source, $dryRun, $downloadImages);
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

    private function importRow(array $data, string $source, bool $dryRun, bool $downloadImages): void
    {
        $name = $data['name'] ?? '';
        $sku = $data['sku'] ?? $data['supplier_sku'] ?? '';
        $mpn = $data['mpn'] ?? $data['sku'] ?? '';
        $manufacturer = $data['manufacturer'] ?? $data['source'] ?? '';
        $brand = $data['brand'] ?? $data['manufacturer'] ?? '';
        $categoryPath = $data['categories'] ?? $data['category'] ?? '';
        $shortDesc = $data['short_description'] ?? '';
        $longDesc = $data['description'] ?? '';
        $specsRaw = $data['specifications'] ?? '';
        $imageUrl = $data['image_url'] ?? '';
        $imageUrls = $data['additional_images'] ?? ($data['image_urls'] ?? '');
        $productUrl = $data['product_url'] ?? $data['source_url'] ?? '';
        $landedPrice = floatval($data['price_usd'] ?? $data['price'] ?? 0);
        $stockQty = intval($data['stock_quantity'] ?? $data['stock'] ?? 0);
        $rohs = $data['rohs'] ?? '';
        $lifecycle = $data['lifecycle_status'] ?? '';
        $datasheets = $data['datasheet_urls'] ?? '';
        $apps = $data['applications'] ?? '';

        if (empty($name)) { $this->skipped++; return; }

        // Skip duplicates
        if (DB::table('products')->where('name', $name)->where('source_name', $source)->exists()) {
            $this->skipped++; return;
        }

        // === 1. NEOGIGA SKU: NG-XXXXXXX ===
        $neogigaSku = 'NG-' . $this->nextSku;
        $this->nextSku++;

        $slug = Str::slug($name);
        $baseSlug = $slug;
        $counter = 1;
        while (DB::table('products')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        // === 2. CATEGORIES: create hierarchy from breadcrumb path ===
        $categoryId = $this->resolveCategory($categoryPath);

        // === 3. BRAND & MANUFACTURER ===
        $brandName = $brand ?: ($manufacturer ?: 'Unknown');
        $brandId = $this->resolveBrand($brandName);
        $mfrName = $manufacturer ?: $brandName;
        $mfrId = $this->resolveManufacturer($mfrName);

        // === 4. PRICING: landed × 1.2 ===
        $costPrice = $landedPrice > 0 ? $landedPrice : null;
        $salePrice = $costPrice ? round($costPrice * 1.2, 2) : null;

        // === 5. DESCRIPTION ===
        $description = $this->buildDescription($name, $shortDesc, $longDesc, $manufacturer);

        if ($dryRun) { $this->imported++; return; }

        $productId = DB::table('products')->insertGetId([
            'name' => $name, 'slug' => $slug,
            'sku' => $neogigaSku, 'mpn' => $mpn,
            'manufacturer_name' => $manufacturer,
            'manufacturer_id' => $mfrId,
            'brand_id' => $brandId, 'category_id' => $categoryId,
            'description' => $description, 'short_description' => $shortDesc,
            'base_price' => $salePrice, 'sale_price' => $salePrice,
            'stock_quantity' => $stockQty,
            'status' => 'draft', 'visibility_status' => 'public',
            'source_url' => $productUrl, 'source_name' => $source,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // === 6. SEO: follow NeoGiga template ===
        $this->insertSeo($productId, $name, $manufacturer, $brandName, $shortDesc, $categoryPath);

        // === 7. SPECIFICATIONS ===
        $this->insertSpecs($productId, $specsRaw);

        // === 8. IMAGES: download and store ===
        if ($downloadImages && $imageUrl) {
            $this->downloadImage($productId, $imageUrl, $name, true);
        }
        if ($downloadImages && $imageUrls) {
            foreach (explode('|', $imageUrls) as $i => $url) {
                $url = trim($url);
                if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                    $this->downloadImage($productId, $url, $name, false);
                }
            }
        }

        $this->imported++;
    }

    private function resolveCategory(string $path): ?int
    {
        if (empty($path)) return null;

        // Breadcrumb: "Kits & Projects > MiniPOV" or "Home > Components > Cables"
        $parts = array_map('trim', explode('>', str_replace('Home > ', '', $path)));
        $parentId = null;
        $lastId = null;

        foreach ($parts as $name) {
            if (empty($name)) continue;
            $slug = Str::slug($name);
            $cat = DB::table('product_categories')->where('slug', $slug)->first();

            if (! $cat) {
                $lastId = DB::table('product_categories')->insertGetId([
                    'name' => $name, 'slug' => $slug,
                    'parent_id' => $parentId, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                $lastId = $cat->id;
            }
            $parentId = $lastId;
        }

        return $lastId;
    }

    private function resolveBrand(string $name): ?int
    {
        if ($name === 'Unknown') return null;
        $slug = Str::slug($name);
        $id = DB::table('product_brands')->where('slug', $slug)->value('id');
        if (! $id) {
            $id = DB::table('product_brands')->insertGetId([
                'name' => $name, 'slug' => $slug,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return $id;
    }

    private function resolveManufacturer(string $name): ?int
    {
        $slug = Str::slug($name);
        $id = DB::table('manufacturers')->where('slug', $slug)->value('id');
        if (! $id) {
            $id = DB::table('manufacturers')->insertGetId([
                'name' => $name, 'slug' => $slug,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return $id;
    }

    private function buildDescription(string $name, string $short, string $long, string $mfr): string
    {
        $parts = [];
        if ($short) {
            $parts[] = '<p>' . e(strip_tags($short)) . '</p>';
        }
        if ($long && $long !== $short && strlen(strip_tags($long)) > 50) {
            $parts[] = '<div class="full-description">' . e(strip_tags($long)) . '</div>';
        }
        if ($mfr) {
            $parts[] = '<p class="manufacturer">Manufacturer: <strong>' . e($mfr) . '</strong></p>';
        }
        return implode("\n", $parts) ?: e($name);
    }

    private function insertSeo(int $productId, string $name, string $mfr, string $brand, string $desc, string $cat): void
    {
        $title = "Buy {$name}";
        if ($mfr) $title .= " by {$mfr}";
        $title .= " | NeoGiga Engineering Marketplace";

        $metaDesc = $desc ? Str::limit(strip_tags($desc), 155) : "Shop {$name} on NeoGiga — genuine parts, regional stock and engineering support.";

        DB::table('product_seo_meta')->updateOrInsert(
            ['product_id' => $productId],
            [
                'title' => $title,
                'meta_title' => $title,
                'meta_description' => $metaDesc,
                'og_title' => $title,
                'og_description' => $metaDesc,
                'meta_keywords' => implode(', ', array_filter([$name, $mfr, $brand, 'electronic components', 'NeoGiga'])),
                'robots' => 'index,follow',
                'confidence_level' => 'auto_generated',
                'created_at' => now(), 'updated_at' => now(),
            ]
        );
    }

    private function insertSpecs(int $productId, string $specsRaw): void
    {
        if (empty($specsRaw) || $specsRaw === '{}') return;

        $sortOrder = 0;

        // Try parsing as JSON first (SparkFun format)
        if (Str::startsWith(trim($specsRaw), '{')) {
            $parsed = json_decode($specsRaw, true);
            if (is_array($parsed)) {
                foreach ($parsed as $key => $val) {
                    $value = is_array($val) ? json_encode($val) : (string) $val;
                    if (empty($value) || $value === '[]') continue;
                    DB::table('product_specs')->insert([
                        'product_id' => $productId, 'name' => Str::title(str_replace('_', ' ', $key)),
                        'value' => $value, 'sort_order' => $sortOrder++,
                        'is_visible' => true, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                return;
            }
        }

        // Try parsing pipe-separated format (Adafruit)
        if (str_contains($specsRaw, '|')) {
            foreach (explode('|', $specsRaw) as $part) {
                $part = trim($part);
                if (empty($part)) continue;
                $colonPos = strpos($part, ':');
                if ($colonPos !== false) {
                    $key = trim(substr($part, 0, $colonPos));
                    $val = trim(substr($part, $colonPos + 1));
                    if ($key && $val) {
                        DB::table('product_specs')->insert([
                            'product_id' => $productId, 'name' => Str::title($key),
                            'value' => $val, 'sort_order' => $sortOrder++,
                            'is_visible' => true, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    private function downloadImage(int $productId, string $url, string $productName, bool $isPrimary): void
    {
        try {
            $filename = Str::slug($productName) . '-' . Str::random(6) . '.' . (pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg');
            $path = 'products/' . $filename;

            $response = Http::timeout(15)->get($url);
            if ($response->successful()) {
                Storage::disk('public')->put($path, $response->body());
                DB::table('product_images')->insert([
                    'product_id' => $productId,
                    'file_path' => $path,
                    'file_name' => $filename,
                    'mime_type' => $response->header('Content-Type') ?: 'image/jpeg',
                    'file_size' => strlen($response->body()),
                    'is_primary' => $isPrimary,
                    'is_active' => true,
                    'source_url' => $url,
                    'source_name' => 'catalog_import',
                    'sort_order' => $isPrimary ? 0 : 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        } catch (\Throwable) {
            // Image download failed — skip
        }
    }
}
