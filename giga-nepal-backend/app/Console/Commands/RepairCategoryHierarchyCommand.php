<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Splits slash-separated category names into proper parent-child hierarchies.
 * Safe: only processes categories with no parent (orphaned supplier imports).
 * Idempotent: won't duplicate categories on re-run.
 */
class RepairCategoryHierarchyCommand extends Command
{
    protected $signature = 'categories:repair-hierarchy
                            {--dry-run : Preview changes without saving}
                            {--max=50 : Max categories to repair per run}';

    protected $description = 'Split /-separated category names into parent-child hierarchy';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $max = (int) $this->option('max');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $orphans = DB::table('product_categories')
            ->whereNull('parent_id')
            ->where('name', 'like', '%/%')
            ->where('sort_order', 0)
            ->limit($max)
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('No orphan /-separated categories found.');
            return 0;
        }

        $this->info("Found {$orphans->count()} categories to repair.");
        $created = 0;
        $linked = 0;

        foreach ($orphans as $orphan) {
            $parts = array_map('trim', explode('/', $orphan->name));
            $parentName = $parts[0];
            $childName = implode(' / ', array_slice($parts, 1));

            if (empty($childName)) {
                $this->line("  Skip: {$orphan->name} — no child segment");
                continue;
            }

            // Find or create parent
            $parentId = DB::table('product_categories')
                ->where('name', $parentName)
                ->whereNull('parent_id')
                ->value('id');

            if (! $parentId && ! $dryRun) {
                $parentSlug = Str::slug($parentName);
                // Avoid slug collision
                $existingSlug = DB::table('product_categories')->where('slug', $parentSlug)->exists();
                if ($existingSlug) {
                    $parentSlug .= '-' . Str::random(4);
                }
                $parentId = DB::table('product_categories')->insertGetId([
                    'name' => $parentName,
                    'slug' => $parentSlug,
                    'parent_id' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->line("  Created parent: {$parentName} (id: {$parentId})");
                $created++;
            }

            // Create child under parent
            $childSlug = Str::slug($childName);
            $existingChild = DB::table('product_categories')
                ->where('name', $childName)
                ->where('parent_id', $parentId)
                ->value('id');

            if ($existingChild && ! $dryRun) {
                $this->line("  Skip: {$childName} — child already exists under parent");
                continue;
            }

            if (! $dryRun) {
                // Check for slug collision globally (unique constraint on slug)
                $slugCollision = DB::table('product_categories')
                    ->where('slug', $childSlug)
                    ->where('id', '!=', $orphan->id)
                    ->exists();
                if ($slugCollision) {
                    $childSlug .= '-' . $orphan->id;
                }

                // Update the orphan's name and parent — reuse the row
                DB::table('product_categories')->where('id', $orphan->id)->update([
                    'name' => $childName,
                    'slug' => $childSlug,
                    'parent_id' => $parentId,
                    'sort_order' => 1,
                    'updated_at' => now(),
                ]);
                $this->line("  Linked: {$childName} → parent {$parentName} (id: {$parentId})");
                $linked++;
            } elseif ($dryRun) {
                $this->line("  [dry-run] {$orphan->name} → {$parentName} / {$childName}");
            } else {
                $this->line("  Skip: {$orphan->name} — child already exists under parent");
            }
        }

        $this->newLine();
        $this->info("Done: {$created} parents created, {$linked} children linked.");

        return 0;
    }
}
