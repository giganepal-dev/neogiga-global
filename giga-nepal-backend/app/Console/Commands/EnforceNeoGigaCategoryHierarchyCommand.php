<?php

namespace App\Console\Commands;

use App\Models\Marketplace\ProductCategory;
use Database\Seeders\ProductSeeders\CategoryTaxonomySeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnforceNeoGigaCategoryHierarchyCommand extends Command
{
    protected $signature = 'catalog:enforce-category-hierarchy
                            {--apply : Apply the displayed deterministic plan}
                            {--yes : Confirm the production write}
                            {--expected-plan-hash= : Required plan hash from a fresh dry run}
                            {--backup-reference= : Required path or identifier for a verified database backup}';

    protected $description = 'Converge canonical NeoGiga roots and subcategories without deleting categories or changing product assignments';

    public function handle(): int
    {
        $plan = $this->buildPlan();
        $hash = hash('sha256', json_encode($plan, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->table(
            ['Action', 'Slug', 'Parent', 'Sort'],
            collect($plan)->map(fn (array $item) => [
                $item['action'],
                $item['slug'],
                $item['parent_slug'] ?? 'ROOT',
                $item['sort_order'],
            ])->all(),
        );
        $this->info('Planned changes: '.count($plan));
        $this->line('Plan hash: '.$hash);

        if (! $this->option('apply')) {
            $this->warn('Dry run only. No category records were changed.');

            return self::SUCCESS;
        }

        if (! $this->option('yes')) {
            $this->error('Apply requires --yes.');

            return self::FAILURE;
        }

        if (! hash_equals($hash, (string) $this->option('expected-plan-hash'))) {
            $this->error('Plan hash mismatch. Run a fresh dry run and review it before applying.');

            return self::FAILURE;
        }

        if (trim((string) $this->option('backup-reference')) === '') {
            $this->error('Apply requires --backup-reference pointing to a verified backup.');

            return self::FAILURE;
        }

        DB::transaction(function (): void {
            (new CategoryTaxonomySeeder)->run();
        });

        $remaining = $this->buildPlan();
        if ($remaining !== []) {
            $this->error('Hierarchy verification failed: '.count($remaining).' planned changes remain.');

            return self::FAILURE;
        }

        $this->info('Canonical NeoGiga hierarchy applied and verified. No categories or product assignments were deleted.');

        return self::SUCCESS;
    }

    /** @return list<array{action:string,slug:string,parent_slug:?string,sort_order:int}> */
    private function buildPlan(): array
    {
        $existing = ProductCategory::query()
            ->get(['id', 'parent_id', 'slug', 'sort_order', 'is_active', 'seo_meta'])
            ->keyBy('slug');
        $plan = [];
        $rootSort = 0;

        foreach (CategoryTaxonomySeeder::taxonomy() as $root) {
            $rootSlug = $root['slug'] ?? Str::slug($root['name']);
            $rootSort += 10;
            $rootRow = $existing->get($rootSlug);

            if (! $rootRow || $rootRow->parent_id !== null || (int) $rootRow->sort_order !== $rootSort || ! $rootRow->is_active || data_get($rootRow->seo_meta, 'neogiga_taxonomy_level') !== 'root') {
                $plan[] = [
                    'action' => $rootRow ? 'update' : 'create',
                    'slug' => $rootSlug,
                    'parent_slug' => null,
                    'sort_order' => $rootSort,
                ];
            }

            foreach ($root['children'] ?? [] as $index => $childName) {
                $childSlug = Str::slug($childName);
                $childRow = $existing->get($childSlug);
                $expectedParentId = $rootRow?->id;
                $expectedSort = ($index + 1) * 10;

                if (! $childRow || ! $rootRow || (int) $childRow->parent_id !== (int) $expectedParentId || (int) $childRow->sort_order !== $expectedSort || ! $childRow->is_active || data_get($childRow->seo_meta, 'neogiga_taxonomy_level') !== 'subcategory') {
                    $plan[] = [
                        'action' => $childRow ? 'update' : 'create',
                        'slug' => $childSlug,
                        'parent_slug' => $rootSlug,
                        'sort_order' => $expectedSort,
                    ];
                }
            }
        }

        return $plan;
    }
}
