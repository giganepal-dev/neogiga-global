<?php

namespace App\Console\Commands;

use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductSeoMeta;
use App\Services\Seo\CatalogSeoTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RegenerateCatalogSeo extends Command
{
    protected $signature = 'seo:catalog-regenerate
        {--dry-run : Report changes without writing}
        {--products : Process products only}
        {--categories : Process categories only}
        {--marketplace=GLOBAL : Marketplace code used for persisted generated values}';

    protected $description = 'Regenerate generated product/category SEO while preserving manual and locked records.';

    public function handle(CatalogSeoTemplateService $templates): int
    {
        $marketplace = Marketplace::with('country')
            ->whereRaw('UPPER(code) = ?', [strtoupper((string) $this->option('marketplace'))])
            ->first() ?: Marketplace::with('country')->where('is_default', true)->first();
        $productsOnly = (bool) $this->option('products');
        $categoriesOnly = (bool) $this->option('categories');
        $runProducts = $productsOnly || ! $categoriesOnly;
        $runCategories = $categoriesOnly || ! $productsOnly;
        $dryRun = (bool) $this->option('dry-run');
        $stats = ['products_seen' => 0, 'products_changed' => 0, 'products_manual_skipped' => 0, 'categories_seen' => 0, 'categories_changed' => 0, 'categories_manual_skipped' => 0];

        if ($runProducts) {
            Product::query()->with('seoMeta')->orderBy('id')->chunkById(500, function ($products) use ($templates, $marketplace, $dryRun, &$stats) {
                $processChunk = function () use ($products, $templates, $marketplace, $dryRun, &$stats) {
                    foreach ($products as $product) {
                        $stats['products_seen']++;
                        $row = $product->seoMeta;
                        if ($row && $templates->isManualProductRow($row)) {
                            $stats['products_manual_skipped']++;

                            continue;
                        }

                        $generated = $templates->product($product, $marketplace, 'en');
                        $changed = ! $row
                            || $row->generated_title !== $generated['title']
                            || $row->generated_description !== $generated['description']
                            || $row->generated_canonical_url !== $generated['canonical']
                            || $row->generated_robots !== $generated['robots'];
                        if (! $changed) {
                            continue;
                        }
                        $stats['products_changed']++;
                        if ($dryRun) {
                            continue;
                        }

                        $record = $row ?: new ProductSeoMeta(['product_id' => $product->id]);
                        $metadata = is_array($record->metadata) ? $record->metadata : [];
                        $record->fill([
                            'title' => $generated['title'],
                            'meta_title' => $generated['title'],
                            'meta_description' => $generated['description'],
                            'canonical_url' => $generated['canonical'],
                            'robots' => $generated['robots'],
                            'generated_title' => $generated['title'],
                            'generated_description' => $generated['description'],
                            'generated_canonical_url' => $generated['canonical'],
                            'generated_robots' => $generated['robots'],
                            'robots_reason' => $generated['robots_reason'],
                            'template_version' => $generated['template_version'],
                            'active_source' => 'generated',
                            'confidence_level' => $generated['confidence_level'],
                            'generated_at' => now(),
                            'metadata' => array_merge($metadata, [
                                'source' => 'catalog_seo_template',
                                'source_notes' => $generated['source_notes'],
                                'confidence_level' => $generated['confidence_level'],
                                'last_updated' => $generated['last_updated'],
                                'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
                            ]),
                        ]);
                        $record->save();
                        $templates->recordVersion('product', $product->id, $generated + ['active_source' => 'generated'], 'generated', null, $marketplace?->id);
                    }
                };

                if ($dryRun) {
                    $processChunk();
                } else {
                    DB::transaction($processChunk, 3);
                }
            });
        }

        if ($runCategories) {
            ProductCategory::query()->orderBy('id')->chunkById(500, function ($categories) use ($templates, $marketplace, $dryRun, &$stats) {
                $processChunk = function () use ($categories, $templates, $marketplace, $dryRun, &$stats) {
                    foreach ($categories as $category) {
                        $stats['categories_seen']++;
                        if ($templates->isManualCategory($category)) {
                            $stats['categories_manual_skipped']++;

                            continue;
                        }

                        $generated = $templates->category($category, $marketplace, 'en');
                        $meta = is_array($category->seo_meta) ? $category->seo_meta : [];
                        $changed = ($meta['generated_title'] ?? null) !== $generated['title']
                            || ($meta['generated_description'] ?? null) !== $generated['description']
                            || ($meta['generated_canonical_url'] ?? null) !== $generated['canonical']
                            || ($meta['generated_robots'] ?? null) !== $generated['robots'];
                        if (! $changed) {
                            continue;
                        }
                        $stats['categories_changed']++;
                        if ($dryRun) {
                            continue;
                        }

                        $category->update(['seo_meta' => array_merge($meta, [
                            'title' => $generated['title'],
                            'description' => $generated['description'],
                            'canonical_url' => $generated['canonical'],
                            'robots' => $generated['robots'],
                            'generated_title' => $generated['title'],
                            'generated_description' => $generated['description'],
                            'generated_canonical_url' => $generated['canonical'],
                            'generated_robots' => $generated['robots'],
                            'robots_reason' => $generated['robots_reason'],
                            'template_version' => $generated['template_version'],
                            'active_source' => 'generated',
                            'source' => 'catalog_seo_template',
                            'source_notes' => $generated['source_notes'],
                            'confidence_level' => $generated['confidence_level'],
                            'last_updated' => $generated['last_updated'],
                            'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
                        ])]);
                        $templates->recordVersion('category', $category->id, $generated + ['active_source' => 'generated'], 'generated', null, $marketplace?->id);
                    }
                };

                if ($dryRun) {
                    $processChunk();
                } else {
                    DB::transaction($processChunk, 3);
                }
            });
        }

        if (! $dryRun && ($stats['products_changed'] > 0 || $stats['categories_changed'] > 0)) {
            Cache::forever('seo:sitemap-version', (string) now()->getTimestampMs());
        }

        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($value, $key) => [$key, $value])->values()->all());
        $this->info($dryRun ? 'Dry run complete; no SEO values were written.' : 'Generated SEO values updated; manual and locked records were preserved.');

        return self::SUCCESS;
    }
}
