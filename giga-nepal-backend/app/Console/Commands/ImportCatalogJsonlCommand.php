<?php

namespace App\Console\Commands;

use App\Models\Manufacturer;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceProductPrice;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductSeoMeta;
use App\Models\Marketplace\ProductSpec;
use App\Models\Marketplace\Warehouse;
use App\Services\Pricing\CentralPricingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import the NeoGiga catalog from the JSONL produced by xlsx_to_jsonl.py.
 *
 * One product per line. Creates product + specs + SEO + a USD base price on
 * the global-fallback marketplace, then splits a random stock quantity across
 * two warehouses (China / other). Images & datasheets are intentionally NOT
 * imported — the source URLs are fabricated templates (mostly 404); real
 * assets are fetched in a later MPN-keyed enrichment pass.
 *
 * Pricing rules:
 *   sale_price   = resale_price * (1 - discount/100)   (default 2% off)
 *   base USD row = base_price(resale) + sale_price + cost_price(purchase)
 * Regional prices are derived by the system engine via --regional-pricing
 * (CentralPricingService: USD base x FX + margin + duty + tax), a deliberate
 * opt-in step so prod margin config is confirmed first.
 */
class ImportCatalogJsonlCommand extends Command
{
    protected $signature = 'neogiga:import-catalog-jsonl
                            {--file= : Path to catalog .jsonl (required)}
                            {--limit=0 : Max products to import (0=all)}
                            {--dry-run : Count/preview only, no writes}
                            {--discount=2 : Percent below resale_price for sale_price}
                            {--stock-min=1000 : Min random stock per product}
                            {--stock-max=10000 : Max random stock per product}
                            {--china-share=60 : Percent of stock to the China warehouse}
                            {--china-warehouse= : id|code|name of the China warehouse (60%)}
                            {--other-warehouse= : id|code|name of the remaining warehouse (40%)}
                            {--create-warehouses : Create the two warehouses if not found}
                            {--no-stock : Skip inventory/warehouse creation}
                            {--regional-pricing : After import, materialize regional prices via CentralPricingService}';

    protected $description = 'Import NeoGiga catalog from JSONL: products, specs, SEO, USD base price, 60/40 warehouse stock split.';

    private array $stats = [
        'total' => 0, 'created' => 0, 'skipped_duplicate' => 0, 'errors' => 0,
        'manufacturers_created' => 0, 'categories_created' => 0, 'specs_created' => 0,
        'seo_created' => 0, 'prices_created' => 0, 'stock_rows_created' => 0,
        'regional_prices_created' => 0,
    ];

    private ?int $globalMarketplaceId = null;

    private ?string $baseCurrency = null;

    private ?int $chinaWarehouseId = null;

    private ?int $otherWarehouseId = null;

    public function handle(): int
    {
        $file = $this->option('file');
        if (! $file || ! is_readable($file)) {
            $this->error('Missing/unreadable --file. Pass the .jsonl path.');

            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');
        $discount = (float) $this->option('discount');
        if ($discount < 0 || $discount >= 100) {
            $this->error('--discount must be in [0, 100).');

            return self::FAILURE;
        }

        $this->stats['total'] = $this->countLines($file);
        $this->info(($dryRun ? 'DRY RUN — ' : '').'Importing up to '.($limit ?: $this->stats['total'])." of {$this->stats['total']} products from ".basename($file));

        // Resolve the global-fallback marketplace + base currency for the USD price row.
        $global = Marketplace::query()->where('global_fallback', true)->first()
            ?? Marketplace::query()->where('code', 'global')->first()
            ?? Marketplace::query()->first();
        $this->globalMarketplaceId = $global?->id;
        $this->baseCurrency = strtoupper((string) (config('pricing.base_currency') ?? 'USD'));
        if (! $this->globalMarketplaceId) {
            $this->warn('No marketplace found — USD base price rows will be skipped (product.base_price/sale_price still set).');
        }

        // Resolve warehouses unless stock is disabled.
        if (! $this->option('no-stock') && ! $this->resolveWarehouses()) {
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->previewSample($file);

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($limit ?: $this->stats['total']);
        $regional = [];

        foreach ($this->readJsonl($file) as $item) {
            if ($limit > 0 && $limit <= $this->stats['created'] + $this->stats['skipped_duplicate'] + $this->stats['errors']) {
                break;
            }
            try {
                $productId = $this->importOne($item, $discount);
                if ($productId && $this->option('regional-pricing')) {
                    $regional[] = $productId;
                }
            } catch (\Throwable $e) {
                $this->stats['errors']++;
                $this->warn('  '.($item['mpn'] ?? '?').": {$e->getMessage()}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        if ($regional) {
            $this->materializeRegional($regional);
        }

        // Raw stock inserts bypass the InventoryStock model hook, so bust the
        // catalog page cache once here (see InventoryStock::booted()).
        if ($this->stats['stock_rows_created'] > 0 || $this->stats['created'] > 0) {
            Cache::forever('catalog:page-cache-version', (string) now()->getTimestampMs());
        }

        $this->printStats();

        return self::SUCCESS;
    }

    private function importOne(array $item, float $discount): ?int
    {
        $mpn = (string) ($item['mpn'] ?? '');
        $sku = (string) ($item['sku'] ?? $mpn);
        $normalizedMpn = Str::upper(trim($mpn));

        if (Product::query()->where('sku', $sku)->orWhere('normalized_mpn', $normalizedMpn)->exists()) {
            $this->stats['skipped_duplicate']++;

            return null;
        }

        $resale = (float) ($item['resale_price'] ?? 0);
        $cost = (float) ($item['purchase_cost'] ?? 0);
        $sale = round($resale * (1 - $discount / 100), 4);

        // Random stock split before create so product.stock_quantity is exact.
        $total = 0;
        if (! $this->option('no-stock')) {
            $total = random_int((int) $this->option('stock-min'), (int) $this->option('stock-max'));
        }

        return DB::transaction(function () use ($item, $mpn, $sku, $normalizedMpn, $resale, $cost, $sale, $total) {
            $manufacturerId = $this->findOrCreateManufacturer($item['manufacturer'] ?? 'Unknown');
            $categoryId = $this->resolveCategory($item);

            $product = Product::create([
                'name' => $item['title'] ?: ($mpn.' — '.($item['manufacturer'] ?? '')),
                'slug' => $this->uniqueSlug($item['seo']['slug'] ?? Str::slug($mpn.' '.($item['manufacturer'] ?? ''))),
                'sku' => $sku,
                'mpn' => $mpn,
                'normalized_mpn' => $normalizedMpn,
                'type' => 'simple',
                'status' => 'published',
                'manufacturer_id' => $manufacturerId,
                'manufacturer_name' => $item['manufacturer'] ?? null,
                'category_id' => $categoryId,
                'description' => $this->buildDescription($item),
                'short_description' => $item['short_description'] ?? null,
                'base_price' => $resale,
                'sale_price' => $sale,
                'cost_price' => $cost,
                'track_inventory' => ! $this->option('no-stock'),
                'stock_quantity' => $total,
                'low_stock_threshold' => 50,
                'weight' => $item['weight_kg'] ?? null,
                'weight_unit' => 'kg',
                'marketplace_visibility' => ['global'],
                'source_name' => 'neogiga_catalog_xlsx',
                'imported_at' => now(),
                'approved_at' => now(),
                'attributes' => [
                    'rohs_status' => $item['rohs_status'] ?? null,
                    'reach_status' => $item['reach_status'] ?? null,
                    'packaging' => $item['packaging'] ?? null,
                    'country_of_origin' => $item['country_of_origin'] ?? null,
                ],
                'metadata' => [
                    'supplier' => $item['supplier'] ?? null,
                    'minimum_order_quantity' => $item['moq'] ?? 1,
                    'lead_time_days' => $item['lead_time_days'] ?? null,
                    'category_path' => $item['category_path'] ?? null,
                    'source_product_id' => $item['product_id'] ?? null,
                    'features' => $item['features'] ?? null,
                    'applications' => $item['applications'] ?? null,
                ],
            ]);

            $this->createSpecs($product->id, $item['specs'] ?? []);
            $this->createSeo($product, $item);
            $this->createBasePrice($product->id, $resale, $sale, $cost);
            if ($total > 0) {
                $this->createStock($product->id, $sku, $total);
            }

            $this->stats['created']++;

            return $product->id;
        });
    }

    private function findOrCreateManufacturer(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $slug = Str::slug($name);
        $mfr = Manufacturer::query()->where('slug', $slug)->first()
            ?? Manufacturer::query()->whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
        if ($mfr) {
            return $mfr->id;
        }
        $mfr = Manufacturer::create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
        $this->stats['manufacturers_created']++;

        return $mfr->id;
    }

    private function resolveCategory(array $item): ?int
    {
        // ponytail: flat leaf category from the path; build the full tree later if
        // category pages need breadcrumb nesting.
        $path = $item['category_path'] ?? null;
        $leaf = $path ? trim(Str::afterLast($path, '>')) : null;
        if (! $leaf) {
            return null;
        }
        $slug = Str::slug($leaf);
        $cat = ProductCategory::query()->where('slug', $slug)->first();
        if ($cat) {
            return $cat->id;
        }
        $cat = ProductCategory::create(['name' => $leaf, 'slug' => $slug, 'is_active' => true]);
        $this->stats['categories_created']++;

        return $cat->id;
    }

    private function uniqueSlug(string $slug): string
    {
        $base = $slug !== '' ? $slug : 'product';
        $slug = $base;
        $i = 1;
        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function buildDescription(array $item): ?string
    {
        $parts = array_filter([
            $item['full_description'] ?? null,
            ! empty($item['features']) ? '<h4>Features</h4>'.$item['features'] : null,
            ! empty($item['applications']) ? '<h4>Applications</h4><p>'.$item['applications'].'</p>' : null,
        ]);

        return $parts ? implode("\n", $parts) : null;
    }

    private function createSpecs(int $productId, array $specs): void
    {
        $order = 0;
        foreach ($specs as $spec) {
            if (empty($spec['name']) || ! isset($spec['value'])) {
                continue;
            }
            ProductSpec::create([
                'product_id' => $productId,
                'name' => Str::limit((string) $spec['name'], 190, ''),
                'value' => Str::limit((string) $spec['value'], 190, ''),
                'unit' => $spec['unit'] ?? null,
                'sort_order' => $order++,
            ]);
            $this->stats['specs_created']++;
        }
    }

    private function createSeo(Product $product, array $item): void
    {
        $seo = $item['seo'] ?? [];
        $schema = $seo['schema_json'] ?? null;
        if (is_string($schema)) {
            $schema = json_decode($schema, true) ?: null;
        }
        ProductSeoMeta::create([
            'product_id' => $product->id,
            'title' => $seo['title'] ?? $product->name,
            'meta_title' => $seo['title'] ?? $product->name,
            'meta_description' => $seo['meta_description'] ?? $product->short_description,
            'canonical_url' => $seo['canonical_url'] ?? "https://neogiga.com/products/{$product->slug}",
            'schema_type' => 'Product',
            'schema_json' => $schema,
            'confidence_level' => 70,
            'generated_title' => $seo['title'] ?? null,
            'generated_description' => $seo['meta_description'] ?? null,
            'generated_canonical_url' => $seo['canonical_url'] ?? null,
            'is_manual_override' => false,
            'is_locked' => false,
            'active_source' => 'neogiga_catalog_xlsx',
            'generated_at' => now(),
            'metadata' => ['keywords' => $seo['meta_keywords'] ?? null],
        ]);
        $this->stats['seo_created']++;
    }

    private function createBasePrice(int $productId, float $resale, float $sale, float $cost): void
    {
        if (! $this->globalMarketplaceId) {
            return;
        }
        MarketplaceProductPrice::create([
            'marketplace_id' => $this->globalMarketplaceId,
            'product_id' => $productId,
            'base_price' => $resale,
            'sale_price' => $sale,
            'cost_price' => $cost,
            'currency_code' => $this->baseCurrency,
            'is_active' => true,
            'source_name' => 'neogiga_catalog_xlsx',
            'imported_at' => now(),
        ]);
        $this->stats['prices_created']++;
    }

    /**
     * Raw insert — the InventoryStock model's $fillable is drifted from the
     * table (missing NOT-NULL `sku`, and `reorder_level` vs real `reorder_point`),
     * so a mass-assign create() would fail. Real columns only.
     */
    private function createStock(int $productId, string $sku, int $total): void
    {
        $chinaShare = max(0, min(100, (int) $this->option('china-share')));
        $chinaQty = (int) round($total * $chinaShare / 100);
        $otherQty = $total - $chinaQty;

        $rows = [];
        foreach ([[$this->chinaWarehouseId, $chinaQty], [$this->otherWarehouseId, $otherQty]] as [$whId, $qty]) {
            if (! $whId || $qty <= 0) {
                continue;
            }
            $rows[] = [
                'product_id' => $productId,
                'warehouse_id' => $whId,
                'sku' => $sku,
                'quantity_available' => $qty,
                'quantity_on_hand' => $qty,
                'reorder_point' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if ($rows) {
            DB::table('inventory_stocks')->insert($rows);
            $this->stats['stock_rows_created'] += count($rows);
        }
    }

    private function materializeRegional(array $productIds): void
    {
        /** @var CentralPricingService $svc */
        $svc = app(CentralPricingService::class);
        $marketplaces = Marketplace::query()
            ->where('is_active', true)
            ->where('global_fallback', false)
            ->get();

        if ($marketplaces->isEmpty()) {
            $this->warn('Regional pricing: no active regional marketplaces — skipped.');

            return;
        }

        $this->info('Materializing regional prices for '.count($productIds).' products x '.$marketplaces->count().' marketplaces...');
        $bar = $this->output->createProgressBar(count($productIds));
        foreach ($productIds as $pid) {
            foreach ($marketplaces as $mp) {
                $log = $svc->calculate($pid, $mp);
                if ($log && $svc->apply($log)) {
                    $this->stats['regional_prices_created']++;
                }
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
    }

    private function resolveWarehouses(): bool
    {
        $this->chinaWarehouseId = $this->resolveWarehouse($this->option('china-warehouse'), 'China DC', 'CN');
        $this->otherWarehouseId = $this->resolveWarehouse($this->option('other-warehouse'), 'International DC', 'INTL');

        if (! $this->chinaWarehouseId || ! $this->otherWarehouseId) {
            $this->error('Could not resolve both warehouses. Pass --china-warehouse and --other-warehouse (id|code|name), or add --create-warehouses.');

            return false;
        }
        $this->line("  China warehouse #{$this->chinaWarehouseId}, other warehouse #{$this->otherWarehouseId}");

        return true;
    }

    private function resolveWarehouse(?string $ref, string $defaultName, string $defaultCode): ?int
    {
        if ($ref !== null && $ref !== '') {
            $wh = ctype_digit($ref)
                ? Warehouse::query()->find((int) $ref)
                : Warehouse::query()->where('code', $ref)->orWhere('name', $ref)->first();
            if ($wh) {
                return $wh->id;
            }
            if (! $this->option('create-warehouses')) {
                return null;
            }
            [$defaultName, $defaultCode] = [$ref, Str::upper(Str::slug($ref))];
        } elseif (! $this->option('create-warehouses')) {
            return null;
        }

        $wh = Warehouse::create([
            'name' => $defaultName,
            'code' => $defaultCode,
            'address_line1' => $defaultName,
            'is_active' => true,
        ]);
        $this->line("  Created warehouse #{$wh->id} {$wh->code} ({$wh->name})");

        return $wh->id;
    }

    /** @return \Generator<int,array> */
    private function readJsonl(string $file): \Generator
    {
        $fh = fopen($file, 'r');
        try {
            $i = 0;
            while (($line = fgets($fh)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $row = json_decode($line, true);
                if (is_array($row)) {
                    yield $i++ => $row;
                }
            }
        } finally {
            fclose($fh);
        }
    }

    private function countLines(string $file): int
    {
        $n = 0;
        $fh = fopen($file, 'r');
        while (! feof($fh)) {
            $n += substr_count((string) fread($fh, 1 << 20), "\n");
        }
        fclose($fh);

        return $n;
    }

    private function previewSample(string $file): void
    {
        foreach ($this->readJsonl($file) as $item) {
            $resale = (float) ($item['resale_price'] ?? 0);
            $sale = round($resale * (1 - (float) $this->option('discount') / 100), 4);
            $this->table(['field', 'value'], [
                ['mpn', $item['mpn'] ?? ''],
                ['title', Str::limit($item['title'] ?? '', 60)],
                ['manufacturer', $item['manufacturer'] ?? ''],
                ['category (leaf)', $item['category_path'] ? Str::afterLast($item['category_path'], '>') : ''],
                ['cost / resale', ($item['purchase_cost'] ?? '?').' / '.$resale],
                ["sale (-{$this->option('discount')}%)", $sale],
                ['specs', count($item['specs'] ?? [])],
                ['seo slug', $item['seo']['slug'] ?? ''],
            ]);
            break;
        }
    }

    private function printStats(): void
    {
        $this->info('=== Import Statistics ===');
        foreach ($this->stats as $k => $v) {
            $this->line('  '.str_replace('_', ' ', $k).": {$v}");
        }
    }
}
