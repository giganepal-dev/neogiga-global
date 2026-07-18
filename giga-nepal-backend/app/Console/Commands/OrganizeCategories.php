<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizeCategories extends Command
{
    protected $signature = 'neogiga:organize-categories {--dry-run}';
    protected $description = 'Clean up imported categories: fix hierarchy, names, slugs, SEO';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? 'DRY RUN' : 'Cleaning categories...');

        // De-duplicate: find categories with the same cleaned name
        $cats = DB::table('product_categories')
            ->where('created_at', '>=', '2026-07-18')
            ->orderBy('id')
            ->get();

        $seen = [];
        $fixed = 0; $deleted = 0;

        foreach ($cats as $cat) {
            // Clean name: remove duplications, fix separators
            $cleanName = $this->cleanName($cat->name);
            $cleanSlug = Str::slug($cleanName);

            // Skip home/special categories
            if (in_array(strtolower($cleanName), ['home', 'special categories', ''])) {
                continue;
            }

            // Check if this cleaned name already exists in the hierarchy
            if (isset($seen[$cleanSlug])) {
                // Duplicate - merge into existing
                $existingId = $seen[$cleanSlug];
                if (! $dryRun) {
                    DB::table('products')->where('category_id', $cat->id)->update(['category_id' => $existingId]);
                    DB::table('product_categories')->where('id', $cat->id)->delete();
                }
                $deleted++;
                if ($this->output->isVerbose()) {
                    $this->line("  Merged duplicate: {$cat->name} → {$cleanName}");
                }
            } else {
                // Fix name and slug
                if ($cleanName !== $cat->name || $cleanSlug !== $cat->slug) {
                    if (! $dryRun) {
                        DB::table('product_categories')->where('id', $cat->id)->update([
                            'name' => $cleanName, 'slug' => $cleanSlug,
                            'updated_at' => now(),
                        ]);
                    }
                    $fixed++;
                    if ($this->output->isVerbose()) {
                        $this->line("  Fixed: {$cat->name} → {$cleanName}");
                    }
                }
                $seen[$cleanSlug] = $cat->id;
            }
        }

        // Now rebuild hierarchy from slash-separated names
        $allCats = DB::table('product_categories')
            ->where('created_at', '>=', '2026-07-18')
            ->orderBy('id')->get();

        $parentMap = []; // slug => id
        $hierarchies = 0;
        foreach ($allCats as $cat) {
            $parts = array_map('trim', explode('/', $cat->name));
            if (count($parts) < 2) continue;

            // The last part is the category name, preceding parts are parents
            $childName = array_pop($parts);
            $parentSlug = Str::slug(implode('-', $parts));

            if (isset($parentMap[$parentSlug])) {
                if (! $dryRun) {
                    DB::table('product_categories')->where('id', $cat->id)->update([
                        'parent_id' => $parentMap[$parentSlug],
                        'name' => $childName,
                        'slug' => Str::slug($childName),
                        'updated_at' => now(),
                    ]);
                }
                $hierarchies++;
            }
        }

        // Add SEO for cleaned categories
        if (! $dryRun) {
            $this->addSeo();
        }

        $this->info("Fixed: $fixed | Merged duplicates: $deleted | Hierarchies: $hierarchies");
        return 0;
    }

    private function cleanName(string $name): string
    {
        // Remove pipe-separated duplications: "Tools / Test Equipment | Tools / Test Equipment / Logic Analyzers"
        // becomes "Tools / Test Equipment / Logic Analyzers"
        if (str_contains($name, '|')) {
            $parts = array_map('trim', explode('|', $name));
            // Use the longest/most specific path
            usort($parts, fn($a, $b) => substr_count($b, '/') - substr_count($a, '/'));
            $name = $parts[0];
        }

        // Remove trailing separators and clean up: "3D printing / Filaments / ABS Filaments -"
        $name = rtrim($name, ' -/|');

        // Fix double slashes and spaces around slashes
        $name = preg_replace('/\s*\/\s*/', ' / ', $name);

        return trim($name);
    }

    private function addSeo(): void
    {
        $cats = DB::table('product_categories')->whereNull('parent_id')->where('created_at', '>=', '2026-07-18')->get();
        foreach ($cats as $cat) {
            DB::table('product_categories')->where('id', $cat->id)->update([
                'description' => "Browse {$cat->name} — electronic components, parts and engineering supplies on NeoGiga.",
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }
    }
}
