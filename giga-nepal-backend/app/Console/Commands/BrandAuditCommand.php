<?php

namespace App\Console\Commands;

use App\Models\Marketplace\ProductBrand;
use App\Services\Catalog\BrandVisibilityService;
use Illuminate\Console\Command;

/**
 * Report-only data-quality audit for brands. Uses the SAME looksInvalid() and
 * normalizeName() logic as the public pages, so what it reports as invalid or
 * duplicate is exactly what the storefront already hides or collapses.
 * Never modifies anything — merges/deactivations stay a deliberate admin task.
 */
class BrandAuditCommand extends Command
{
    protected $signature = 'brand:audit {--json : Output the full report as JSON}';

    protected $description = 'Report-only audit of brand data quality: invalid names, duplicate groups, logo and product coverage.';

    public function handle(BrandVisibilityService $brands): int
    {
        $all = ProductBrand::query()
            ->withCount(['products as public_products_count' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->get();

        $invalid = $all->filter(fn (ProductBrand $brand) => $brands->looksInvalid((string) $brand->name))->values();
        $duplicates = $all->reject(fn (ProductBrand $brand) => $brands->looksInvalid((string) $brand->name))
            ->groupBy(fn (ProductBrand $brand) => $brands->normalizeName((string) $brand->name))
            ->filter(fn ($group) => $group->count() > 1)
            ->values();

        $summary = [
            'total' => $all->count(),
            'active' => $all->where('is_active', true)->count(),
            'inactive' => $all->where('is_active', false)->count(),
            'featured' => $all->where('is_featured', true)->count(),
            'with_logo' => $all->filter(fn (ProductBrand $brand) => (string) $brand->logo_path !== '')->count(),
            'invalid_names' => $invalid->count(),
            'duplicate_groups' => $duplicates->count(),
            'zero_product_active' => $all->where('is_active', true)->where('public_products_count', 0)->count(),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'summary' => $summary,
                'invalid' => $invalid->map(fn (ProductBrand $b) => ['id' => $b->id, 'name' => $b->name, 'slug' => $b->slug, 'products' => (int) $b->public_products_count])->all(),
                'duplicates' => $duplicates->map(fn ($group) => $group->map(fn (ProductBrand $b) => ['id' => $b->id, 'name' => $b->name, 'products' => (int) $b->public_products_count])->values()->all())->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['Metric', 'Count'], collect($summary)->map(fn ($value, $metric) => [$metric, $value])->values()->all());

        if ($invalid->isNotEmpty()) {
            $this->newLine();
            $this->warn('Invalid (publicly hidden) brand names — showing '.min(20, $invalid->count()).' of '.$invalid->count().':');
            $this->table(['ID', 'Name', 'Products'], $invalid->take(20)->map(fn (ProductBrand $b) => [$b->id, $b->name, (int) $b->public_products_count])->all());
        }

        if ($duplicates->isNotEmpty()) {
            $this->newLine();
            $this->warn('Duplicate display groups — showing '.min(10, $duplicates->count()).' of '.$duplicates->count().':');
            foreach ($duplicates->take(10) as $group) {
                $this->line('  • '.$group->map(fn (ProductBrand $b) => $b->name.' (#'.$b->id.', '.(int) $b->public_products_count.'p)')->implode('  |  '));
            }
        }

        $this->newLine();
        $this->info('Report only — nothing was modified. The storefront already hides invalid names and collapses duplicate groups.');

        return self::SUCCESS;
    }
}
