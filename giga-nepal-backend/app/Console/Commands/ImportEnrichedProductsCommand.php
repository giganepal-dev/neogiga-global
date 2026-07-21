<?php

namespace App\Console\Commands;

use App\Models\Manufacturer;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductImage;
use App\Models\Marketplace\ProductResource;
use App\Models\Marketplace\ProductSeoMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportEnrichedProductsCommand extends Command
{
    protected $signature = 'neogiga:import-enriched
                            {--file= : Path to JSON dataset (default: ~/Downloads/product/NeoGiga_MPN_Enrichment_Dataset.json)}
                            {--limit=0 : Max products to import (0=all)}
                            {--dry-run : Validate only, no writes}
                            {--skip-images : Skip image download}
                            {--delay=1 : Seconds between image downloads}';

    protected $description = 'Import enriched products from NeoGiga MPN Enrichment Dataset with images, datasheets, specs, SEO, and pricing.';

    private array $stats = [
        'total' => 0,
        'processed' => 0,
        'created' => 0,
        'skipped_duplicate' => 0,
        'manufacturers_created' => 0,
        'categories_created' => 0,
        'images_downloaded' => 0,
        'images_failed' => 0,
        'datasheets_linked' => 0,
        'seo_created' => 0,
        'prices_created' => 0,
        'errors' => 0,
    ];

    private ?int $defaultMarketplaceId = null;

    public function handle(): int
    {
        $filePath = $this->option('file')
            ?? getenv('HOME') . '/Downloads/product/NeoGiga_MPN_Enrichment_Dataset.json';

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($filePath), true);
        if (! is_array($data)) {
            $this->error('Invalid JSON: expected array of products.');

            return self::FAILURE;
        }

        $this->stats['total'] = count($data);
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info("DRY RUN — {$this->stats['total']} products found. No writes.");
            $this->previewData($data);

            return self::SUCCESS;
        }

        $this->defaultMarketplaceId = Marketplace::where('code', 'global')->value('id')
            ?? Marketplace::first()?->id;

        $this->info("Importing {$this->stats['total']} enriched products...");
        $bar = $this->output->createProgressBar(min($this->stats['total'], $limit ?: $this->stats['total']));

        foreach ($data as $i => $item) {
            if ($limit > 0 && $this->stats['processed'] >= $limit) {
                break;
            }

            try {
                $this->importProduct($item);
                $this->stats['processed']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $mpn = $item['mpn'] ?? 'unknown';
                $this->warn("  Error importing {$mpn}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->printStats();

        return self::SUCCESS;
    }

    private function importProduct(array $item): void
    {
        $mpn = $item['mpn'];
        $normalizedMpn = Str::upper(trim($mpn));

        // Check for existing product by MPN
        $existing = Product::where('mpn', $mpn)
            ->orWhere('normalized_mpn', $normalizedMpn)
            ->first();

        if ($existing) {
            $this->stats['skipped_duplicate']++;

            return;
        }

        DB::beginTransaction();
        try {
            // 1. Find or create manufacturer
            $manufacturerId = $this->findOrCreateManufacturer($item['manufacturer']);

            // 2. Find or create category
            $categoryId = $this->resolveCategory($item);

            // 3. Create the product
            $product = $this->createProduct($item, $manufacturerId, $categoryId);

            // 4. Download image
            if (! $this->option('skip-images')) {
                $this->downloadImage($product, $item['urls']['image'] ?? null);
            }

            // 5. Link datasheet
            if (! empty($item['urls']['datasheet'])) {
                $this->linkDatasheet($product, $item['urls']['datasheet'], $item['manufacturer']);
            }

            // 6. Create SEO metadata
            $this->createSeoMeta($product, $item);

            // 7. Create marketplace pricing
            $this->createPricing($product, $item);

            $this->stats['created']++;
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function findOrCreateManufacturer(string $name): int
    {
        $slug = Str::slug($name);

        $mfr = Manufacturer::where('slug', $slug)->first();
        if ($mfr) {
            return $mfr->id;
        }

        // Try case-insensitive name match (DB-agnostic: works on SQLite and PostgreSQL)
        $mfr = Manufacturer::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
        if ($mfr) {
            return $mfr->id;
        }

        $mfr = Manufacturer::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);

        $this->stats['manufacturers_created']++;

        return $mfr->id;
    }

    private function resolveCategory(array $item): ?int
    {
        // Prefer manufacturer_category (16 distinct ones) over neogiga_category (all "DISTRIBUTION PWR STRIP")
        $categoryName = $item['manufacturer_category'] ?? $item['neogiga_category'] ?? null;
        if (empty($categoryName)) {
            return null;
        }

        $slug = Str::slug($categoryName);

        $cat = ProductCategory::where('slug', $slug)->first();
        if ($cat) {
            return $cat->id;
        }

        $cat = ProductCategory::create([
            'name' => $categoryName,
            'slug' => $slug,
            'is_active' => true,
        ]);

        $this->stats['categories_created']++;

        return $cat->id;
    }

    private function createProduct(array $item, int $manufacturerId, ?int $categoryId): Product
    {
        $specs = $item['technical_specs'] ?? [];
        $pricing = $item['pricing'] ?? [];
        $urls = $item['urls'] ?? [];

        $name = $item['mpn'] . ' — ' . ($specs['Product_Category'] ?? $item['manufacturer_category'] ?? '') . ' by ' . $item['manufacturer'];

        // Build description from specs
        $descParts = [];
        if (! empty($specs['Product_Category'])) {
            $descParts[] = "**Category:** {$specs['Product_Category']}";
        }
        if (! empty($specs['RoHS_Compliance'])) {
            $descParts[] = "**RoHS:** {$specs['RoHS_Compliance']}";
        }
        if (! empty($specs['Packaging_Type'])) {
            $descParts[] = "**Packaging:** {$specs['Packaging_Type']}";
        }
        if (! empty($specs['Availability'])) {
            $descParts[] = "**Availability:** {$specs['Availability']}";
        }
        if (! empty($specs['Warranty'])) {
            $descParts[] = "**Warranty:** {$specs['Warranty']}";
        }
        if (! empty($specs['Shipping'])) {
            $descParts[] = "**Shipping:** {$specs['Shipping']}";
        }
        if (! empty($specs['Country_of_Origin']) && $specs['Country_of_Origin'] !== 'See Manufacturer Datasheet') {
            $descParts[] = "**Country of Origin:** {$specs['Country_of_Origin']}";
        }
        $description = implode("  \n", $descParts);

        // Short description from SEO
        $shortDescription = $item['seo']['description'] ?? null;

        $costPrice = (float) ($pricing['purchase_cost'] ?? $specs['Purchase_Cost_USD'] ?? 0);
        $basePrice = (float) ($pricing['resale_price'] ?? $specs['Resale_Price_USD'] ?? 0);

        $normalizedMpn = Str::upper(trim($item['mpn']));

        $product = Product::create([
            'name' => $name,
            'slug' => Str::slug($item['mpn'] . ' ' . $item['manufacturer']),
            'mpn' => $item['mpn'],
            'normalized_mpn' => $normalizedMpn,
            'sku' => $item['mpn'],
            'type' => 'simple',
            'status' => 'approved',
            'manufacturer_id' => $manufacturerId,
            'manufacturer_name' => $item['manufacturer'],
            'category_id' => $categoryId,
            'description' => $description,
            'short_description' => $shortDescription,
            'base_price' => $basePrice,
            'sale_price' => $basePrice,
            'cost_price' => $costPrice,
            'source_name' => 'neogiga_mpn_enrichment',
            'source_url' => $urls['product'] ?? null,
            'source_page_url' => $urls['product'] ?? null,
            'imported_at' => now(),
            'attributes' => $specs,
            'metadata' => [
                'structured_data' => $item['structured_data'] ?? null,
                'seo_keywords' => $item['seo']['keywords'] ?? [],
                'margin_percent' => $pricing['margin_percent'] ?? $specs['Margin_Percent'] ?? null,
                'minimum_order_quantity' => $specs['Minimum_Order_Quantity'] ?? 1,
                'manufacturer_category' => $item['manufacturer_category'] ?? null,
            ],
            'marketplace_visibility' => ['global'],
            'is_featured' => false,
            'approved_at' => now(),
        ]);

        return $product;
    }

    private function downloadImage(Product $product, ?string $imageUrl): void
    {
        if (empty($imageUrl)) {
            $this->stats['images_failed']++;

            return;
        }

        $delay = (int) $this->option('delay');
        if ($delay > 0 && $this->stats['images_downloaded'] > 0) {
            usleep($delay * 1000 * 1000);
        }

        try {
            $response = Http::withUserAgent('NeoGigaCatalog/1.0 (+https://neogiga.com)')
                ->timeout(8)
                ->connectTimeout(5)
                ->get($imageUrl);

            if (! $response->successful()) {
                // Store URL as external reference even when download fails
                ProductImage::create([
                    'product_id' => $product->id,
                    'file_path' => '',
                    'original_url' => $imageUrl,
                    'source_url' => $imageUrl,
                    'source_name' => 'neogiga_mpn_enrichment',
                    'alt_text' => $product->mpn . ' — ' . ($product->manufacturer_name ?? ''),
                    'sort_order' => 0,
                    'is_primary' => true,
                    'is_active' => true,
                    'storage_disk' => 'public',
                    'imported_at' => now(),
                ]);
                $this->stats['images_failed']++;

                return;
            }

            $contentType = $response->header('Content-Type');
            $extension = match (true) {
                str_contains((string) $contentType, 'png') => 'png',
                str_contains((string) $contentType, 'webp') => 'webp',
                str_contains((string) $contentType, 'gif') => 'gif',
                str_contains((string) $contentType, 'svg') => 'svg',
                default => 'jpg',
            };

            $filename = 'products/enriched/' . $product->id . '_' . Str::slug($product->mpn) . '.' . $extension;
            $path = 'products/enriched/' . $product->id . '_' . Str::slug($product->mpn) . '.' . $extension;

            Storage::disk('public')->put($path, $response->body());

            ProductImage::create([
                'product_id' => $product->id,
                'file_path' => $path,
                'file_name' => basename($path),
                'mime_type' => $contentType ?: 'image/jpeg',
                'file_size' => strlen($response->body()),
                'original_url' => $imageUrl,
                'source_url' => $imageUrl,
                'source_name' => 'neogiga_mpn_enrichment',
                'alt_text' => $product->mpn . ' — ' . ($product->manufacturer_name ?? ''),
                'sort_order' => 0,
                'is_primary' => true,
                'is_active' => true,
                'storage_disk' => 'public',
                'downloaded_at' => now(),
                'imported_at' => now(),
            ]);

            $this->stats['images_downloaded']++;
        } catch (\Exception $e) {
            // Store external URL reference so frontend can still show image
            ProductImage::create([
                'product_id' => $product->id,
                'file_path' => '',
                'original_url' => $imageUrl,
                'source_url' => $imageUrl,
                'source_name' => 'neogiga_mpn_enrichment',
                'alt_text' => $product->mpn . ' — ' . ($product->manufacturer_name ?? ''),
                'sort_order' => 0,
                'is_primary' => true,
                'is_active' => true,
                'storage_disk' => 'public',
                'imported_at' => now(),
            ]);
            $this->stats['images_failed']++;
            $this->warn("  Image: {$product->mpn} — timeout/unreachable, stored external URL");
        }
    }

    private function linkDatasheet(Product $product, string $datasheetUrl, string $manufacturer): void
    {
        // Check if already linked
        $exists = ProductResource::where('product_id', $product->id)
            ->where('external_url', $datasheetUrl)
            ->exists();

        if ($exists) {
            return;
        }

        ProductResource::create([
            'product_id' => $product->id,
            'type' => 'datasheet',
            'title' => $product->mpn . ' Datasheet — ' . $manufacturer,
            'external_url' => $datasheetUrl,
            'is_downloadable' => true,
            'is_verified' => false,
            'metadata' => [
                'source' => 'neogiga_mpn_enrichment',
                'manufacturer' => $manufacturer,
            ],
        ]);

        $this->stats['datasheets_linked']++;
    }

    private function createSeoMeta(Product $product, array $item): void
    {
        $seo = $item['seo'] ?? [];
        $mfr = $item['manufacturer'] ?? '';
        $mpn = $item['mpn'];

        // Check if SEO meta already exists for this product
        $exists = ProductSeoMeta::where('product_id', $product->id)->exists();
        if ($exists) {
            return;
        }

        $marketplaces = Marketplace::where('is_active', true)->get();
        if ($marketplaces->isEmpty()) {
            $marketplaces = collect([null]); // fallback: one global record
        }

        foreach ($marketplaces as $marketplace) {
            $countryName = $marketplace?->name ?? 'Global';
            $countryCode = $marketplace?->country_iso2 ?? 'global';
            $domain = $marketplace?->canonical_domain ?? $marketplace?->domain ?? 'neogiga.com';
            $locale = $marketplace?->locale ?? 'en';

            // Localize title per region
            $regionalTitle = match (true) {
                $marketplace === null => ($seo['title'] ?? "{$mpn} — {$mfr} | NeoGiga"),
                default => "Buy {$mpn} Online in {$countryName} — {$mfr} | NeoGiga",
            };

            $regionalDesc = match (true) {
                $marketplace === null => ($seo['description'] ?? null),
                default => "Buy {$mpn} by {$mfr} online in {$countryName}. Fast shipping, competitive pricing & bulk discounts available. Technical specifications and datasheet included.",
            };

            ProductSeoMeta::create([
                'product_id' => $product->id,
                'title' => $regionalTitle,
                'meta_title' => $regionalTitle,
                'meta_description' => $regionalDesc,
                'canonical_url' => "https://{$domain}/products/{$product->slug}",
                'schema_type' => 'Product',
                'schema_json' => $item['structured_data'] ?? null,
                'confidence_level' => 80,
                'generated_title' => $regionalTitle,
                'generated_description' => $regionalDesc,
                'generated_canonical_url' => "https://{$domain}/products/{$product->slug}",
                'is_manual_override' => false,
                'is_locked' => false,
                'active_source' => 'neogiga_mpn_enrichment',
                'generated_at' => now(),
                'metadata' => [
                    'keywords' => $seo['keywords'] ?? [],
                    'marketplace_code' => $marketplace?->code ?? 'global',
                    'country_iso2' => $countryCode,
                    'locale' => $locale,
                ],
            ]);

            $this->stats['seo_created']++;
        }
    }

    private function createPricing(Product $product, array $item): void
    {
        if (! $this->defaultMarketplaceId) {
            return;
        }

        $pricing = $item['pricing'] ?? [];
        $costPrice = (float) ($pricing['purchase_cost'] ?? $item['technical_specs']['Purchase_Cost_USD'] ?? 0);
        $basePrice = (float) ($pricing['resale_price'] ?? $item['technical_specs']['Resale_Price_USD'] ?? 0);

        // Skip if prices already exist for this product+marketplace
        $exists = MarketplaceProductPrice::where('product_id', $product->id)
            ->where('marketplace_id', $this->defaultMarketplaceId)
            ->exists();

        if ($exists) {
            return;
        }

        MarketplaceProductPrice::create([
            'marketplace_id' => $this->defaultMarketplaceId,
            'product_id' => $product->id,
            'base_price' => $basePrice,
            'cost_price' => $costPrice,
            'currency_code' => 'USD',
            'is_active' => true,
            'source_name' => 'neogiga_mpn_enrichment',
            'imported_at' => now(),
        ]);

        $this->stats['prices_created']++;
    }

    private function previewData(array $data): void
    {
        $manufacturers = collect($data)->pluck('manufacturer')->unique()->sort();
        $categories = collect($data)->pluck('neogiga_category')->unique()->filter()->sort();
        $withImages = collect($data)->filter(fn ($i) => ! empty($i['urls']['image'] ?? null))->count();
        $withDatasheets = collect($data)->filter(fn ($i) => ! empty($i['urls']['datasheet'] ?? null))->count();
        $pricingRange = [
            'min_cost' => collect($data)->min(fn ($i) => (float) ($i['pricing']['purchase_cost'] ?? 0)),
            'max_cost' => collect($data)->max(fn ($i) => (float) ($i['pricing']['purchase_cost'] ?? 0)),
        ];

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Products', count($data)],
                ['Unique Manufacturers', $manufacturers->count()],
                ['Unique Categories', $categories->count()],
                ['With Images', $withImages],
                ['With Datasheets', $withDatasheets],
                ['Cost Range', '$' . number_format($pricingRange['min_cost'], 2) . ' – $' . number_format($pricingRange['max_cost'], 2)],
            ]
        );

        $this->newLine();
        $this->info('Manufacturers:');
        foreach ($manufacturers as $m) {
            $count = collect($data)->filter(fn ($i) => $i['manufacturer'] === $m)->count();
            $this->line("  {$m} ({$count} products)");
        }

        $this->newLine();
        $this->info('Categories:');
        foreach ($categories as $c) {
            $count = collect($data)->filter(fn ($i) => ($i['neogiga_category'] ?? '') === $c)->count();
            $this->line("  {$c} ({$count} products)");
        }

        // Check for duplicates already in DB
        $existingMpns = Product::whereIn('mpn', collect($data)->pluck('mpn')->toArray())
            ->pluck('mpn')
            ->toArray();

        if ($existingMpns) {
            $this->newLine();
            $this->warn(count($existingMpns) . ' MPNs already exist in products table (will be skipped).');
        }
    }

    private function printStats(): void
    {
        $this->info('=== Import Statistics ===');
        foreach ($this->stats as $key => $val) {
            $label = str_replace('_', ' ', $key);
            $this->line("  <info>{$label}</info>: {$val}");
        }
    }
}
