<?php

namespace App\Services\Catalog;

use App\Models\Marketplace\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CategoryHierarchyAuditService
{
    /** @return array{summary:array<string,int>,files:array<string,string>,plan_hash:string} */
    public function write(string $directory): array
    {
        File::ensureDirectoryExists($directory);
        $categories = ProductCategory::query()->with('parent')->orderBy('id')->get();
        $byId = $categories->keyBy('id');
        $roots = $categories->whereNull('parent_id');
        $intended = (array) config('category_resolution.intended_root_slugs', []);
        $misplaced = $roots->filter(fn (ProductCategory $category): bool => ! in_array($category->slug, $intended, true));
        $productCounts = DB::table('products')->select('category_id', DB::raw('count(*) as aggregate'))->whereNotNull('category_id')->groupBy('category_id')->pluck('aggregate', 'category_id');

        $plan = $misplaced->map(function (ProductCategory $category) use ($categories, $byId, $productCounts): array {
            [$parent, $confidence, $reason, $requiresReview] = $this->proposedParent($category, $categories, $byId);

            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_slug' => $category->slug,
                'old_parent_id' => '',
                'old_parent_name' => 'ROOT',
                'proposed_parent_id' => $parent?->id ?? '',
                'proposed_parent_name' => $parent?->name ?? 'MANUAL REVIEW REQUIRED',
                'confidence' => number_format($confidence, 2, '.', ''),
                'action' => $parent && ! $requiresReview ? 'move_category_only' : 'manual_review',
                'reason' => $reason,
                'products_affected' => (int) ($productCounts[$category->id] ?? 0),
            ];
        })->values()->all();

        $duplicates = $categories->groupBy(fn (ProductCategory $category) => $this->normalize($category->name))
            ->filter(fn ($group) => $group->count() > 1)
            ->map(fn ($group, $normalized) => [
                'normalized_name' => $normalized,
                'category_ids' => $group->pluck('id')->implode('|'),
                'names' => $group->pluck('name')->implode('|'),
                'parent_ids' => $group->pluck('parent_id')->map(fn ($id) => $id ?? 'ROOT')->implode('|'),
                'count' => $group->count(),
            ])->values()->all();
        $orphans = $categories->filter(fn (ProductCategory $category) => $category->parent_id && ! $byId->has($category->parent_id))
            ->map(fn (ProductCategory $category) => ['category_id' => $category->id, 'category_name' => $category->name, 'parent_id' => $category->parent_id, 'slug' => $category->slug])->values()->all();

        $remaps = [];
        foreach ($plan as $row) {
            $products = DB::table('products')->where('category_id', $row['category_id'])->orderBy('id')->get(['id', 'sku', 'mpn', 'name']);
            foreach ($products as $product) {
                $remaps[] = [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'mpn' => $product->mpn,
                    'product_name' => $product->name,
                    'category_id' => $row['category_id'],
                    'old_parent' => 'ROOT',
                    'proposed_parent_id' => $row['proposed_parent_id'],
                    'proposed_parent' => $row['proposed_parent_name'],
                    'confidence' => $row['confidence'],
                    'reason' => $row['reason'],
                ];
            }
        }

        $files = [
            'audit' => $directory.'/CATEGORY_HIERARCHY_AUDIT.md',
            'mapping_plan' => $directory.'/CATEGORY_MAPPING_PLAN.csv',
            'duplicates' => $directory.'/CATEGORY_DUPLICATES.csv',
            'orphans' => $directory.'/CATEGORY_ORPHANS.csv',
            'product_remap' => $directory.'/PRODUCT_CATEGORY_REMAP.csv',
        ];
        $summary = [
            'total_categories' => $categories->count(),
            'root_categories' => $roots->count(),
            'child_categories' => $categories->count() - $roots->count(),
            'misplaced_root_categories' => count($plan),
            'orphan_categories' => count($orphans),
            'duplicate_name_groups' => count($duplicates),
            'products_on_misplaced_roots' => count($remaps),
        ];
        $planHash = hash('sha256', json_encode($plan, JSON_UNESCAPED_SLASHES));
        $this->writeCsv($files['mapping_plan'], $plan);
        $this->writeCsv($files['duplicates'], $duplicates);
        $this->writeCsv($files['orphans'], $orphans);
        $this->writeCsv($files['product_remap'], $remaps);
        File::put($files['audit'], $this->markdown($summary, $plan, $duplicates, $orphans, $planHash));

        return compact('summary', 'files', 'planHash');
    }

    /** @return array{0:?ProductCategory,1:float,2:string,3:bool} */
    private function proposedParent(ProductCategory $category, $categories, $byId): array
    {
        $normalized = $this->normalize($category->name);
        if (str_contains($category->name, ',')) {
            return [null, 0.20, 'Composite supplier category has more than one classification; choose a canonical category in admin review.', true];
        }
        $rules = [
            ['/(audio|general purpose|high speed|precision|power|video|fully differential|sample hold|transimpedance).*amp/', '266-operational-amplifiers'],
            ['/current sense/', '191-amplifiers'],
            ['/instrumentation|difference|isolated|logarithmic|variable gain|transconductance|line driver/', '191-amplifiers'],
            ['/comparator/', 'semiconductors'],
            ['/rf .*amplifier|rf gain/', 'rf-wireless'],
            ['/4 20 ma|frequency converter/', 'industrial-automation'],
            ['/digital power monitor/', 'power-management'],
        ];
        foreach ($rules as [$pattern, $slug]) {
            if (preg_match($pattern, $normalized) === 1) {
                $parent = $categories->firstWhere('slug', $slug);
                if ($parent) {
                    return [$parent, 0.90, "Recognized technical category; move below existing {$parent->name}.", false];
                }

                return [null, 0.40, "Recognized category group requires existing parent slug {$slug}, which is absent.", true];
            }
        }

        return [null, 0.10, 'No high-confidence existing parent rule; manual taxonomy review required.', true];
    }

    private function normalize(string $value): string
    {
        $value = \Illuminate\Support\Str::ascii(mb_strtolower($value));
        $value = preg_replace('/\([^)]*\)/', ' ', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function writeCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'wb');
        $headers = $rows === [] ? ['no_rows'] : array_keys($rows[0]);
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($header) => $row[$header] ?? '', $headers));
        }
        fclose($handle);
    }

    private function markdown(array $summary, array $plan, array $duplicates, array $orphans, string $planHash): string
    {
        $lines = ['# NeoGiga Category Hierarchy Audit', '', 'Generated: '.now()->toIso8601String(), '', '## Summary', ''];
        foreach ($summary as $name => $value) {
            $lines[] = '- '.str_replace('_', ' ', ucfirst($name)).': '.$value;
        }
        $lines[] = '';
        $lines[] = '## Dry-run guard';
        $lines[] = '';
        $lines[] = 'No category, product, SEO, redirect, or product relationship was modified. This report is a proposed move/merge plan only.';
        $lines[] = 'Plan SHA-256: `'.$planHash.'`';
        $lines[] = '';
        $lines[] = '## Mapping preview';
        $lines[] = '';
        $lines[] = '| Category | Proposed parent | Confidence | Action |';
        $lines[] = '| --- | --- | --- | --- |';
        foreach (array_slice($plan, 0, 40) as $row) {
            $lines[] = '| '.str_replace('|', '\\|', $row['category_name']).' | '.str_replace('|', '\\|', $row['proposed_parent_name']).' | '.$row['confidence'].' | '.$row['action'].' |';
        }
        $lines[] = '';
        $lines[] = 'Duplicate groups: '.count($duplicates).'. Orphans: '.count($orphans).'. Full details are in the adjacent CSV files.';

        return implode("\n", $lines)."\n";
    }
}
