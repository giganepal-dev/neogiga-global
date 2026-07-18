<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportJlcpcbComponentsCommand extends Command
{
    protected $signature = 'jlcpcb:import-components
                            {--chunk=1000 : Records per batch}
                            {--limit=0 : Max records (0 = all)}
                            {--path= : SQLite database path}';

    protected $description = 'Import JLCPCB components from SQLite into NeoGiga product catalog';

    private \PDO $source;

    public function handle(): int
    {
        $path = $this->option('path') ?: base_path('../jlcpcb-components.sqlite3');
        if (!file_exists($path)) {
            $this->error("Source file not found: {$path}");
            return self::FAILURE;
        }

        $this->source = new \PDO("sqlite:{$path}");
        $this->source->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->importCategories();
        $this->importManufacturers();
        $this->importComponents();

        $this->info('Import complete.');
        return self::SUCCESS;
    }

    private function importCategories(): void
    {
        $this->info('Importing categories...');
        $rows = $this->source->query("SELECT id, category, subcategory FROM categories")->fetchAll(\PDO::FETCH_ASSOC);
        $count = 0;

        $categories = [];
        foreach ($rows as $row) {
            $name = trim($row['category']);
            $sub = trim($row['subcategory']);

            // Parent
            $slug = Str::slug($name);
            if (!isset($categories[$slug])) {
                $categories[$slug] = DB::table('product_categories')->insertGetId([
                    'name' => $name,
                    'slug' => $slug,
                    'parent_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }

            // Subcategory — use parent slug as prefix to avoid duplicates
            $subSlug = Str::slug($sub);
            $fullSlug = $slug.'-'.$subSlug;
            DB::table('product_categories')->updateOrInsert(
                ['slug' => $fullSlug],
                [
                'name' => $sub,
                'slug' => $fullSlug,
                'parent_id' => $categories[$slug],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;
        }

        $this->info("  {$count} categories imported.");
    }

    private function importManufacturers(): void
    {
        $this->info('Importing manufacturers...');
        $rows = $this->source->query("SELECT DISTINCT mfr FROM components WHERE mfr != '' ORDER BY mfr")->fetchAll(\PDO::FETCH_COLUMN);
        $count = 0;

        foreach ($rows as $name) {
            $name = trim($name);
            if (empty($name)) continue;

            DB::table('manufacturers')->updateOrInsert(
                ['name' => $name],
                ['slug' => Str::slug($name), 'created_at' => now(), 'updated_at' => now()]
            );
            $count++;
        }

        $this->info("  {$count} manufacturers imported.");
    }

    private function importComponents(): void
    {
        $chunk = (int) $this->option('chunk');
        $limit = (int) $this->option('limit');
        $total = (int) $this->source->query("SELECT COUNT(*) FROM components")->fetchColumn();
        if ($limit > 0) $total = min($total, $limit);

        $this->info("Importing {$total} components in chunks of {$chunk}...");
        $bar = $this->output->createProgressBar($total);

        // Build manufacturer lookup once
        $mfrMap = DB::table('manufacturers')->pluck('id', 'name')->toArray();
        $catMap = DB::table('product_categories')->pluck('id', 'slug')->toArray();

        $offset = 0;
        $imported = 0;

        while ($offset < $total) {
            $limitClause = $limit > 0 ? "LIMIT {$chunk} OFFSET {$offset}" : "LIMIT {$chunk} OFFSET {$offset}";
            $sql = "SELECT lcsc, category_id, mfr, package, description, stock, price, basic
                    FROM components ORDER BY lcsc {$limitClause}";

            $rows = $this->source->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($rows)) break;

            $inserts = [];
            foreach ($rows as $row) {
                $mpn = 'C'.$row['lcsc'];
                $name = $this->makeName($row);
                $price = $this->extractPrice($row['price']);

                // Find category by JLCPCB ID
                $catRow = $this->source->query("SELECT category, subcategory FROM categories WHERE id = {$row['category_id']}")->fetch(\PDO::FETCH_ASSOC);
                $catSlug = $catRow
                    ? Str::slug($catRow['category']).'-'.Str::slug($catRow['subcategory'])
                    : 'uncategorized';
                $categoryId = $catMap[$catSlug] ?? null;

                $mfrName = trim($row['mfr']);
                $manufacturerId = $mfrMap[$mfrName] ?? null;

                $inserts[] = [
                    'name' => $name,
                    'slug' => Str::slug($name.'-'.$mpn),
                    'mpn' => $mpn,
                    'sku' => 'LCSC-'.$row['lcsc'],
                    'category_id' => $categoryId,
                    'brand_id' => $manufacturerId,
                    'description' => $row['description'],
                    'base_price' => $price,
                    'stock_quantity' => (int) $row['stock'],
                    'type' => 'physical',
                    'status' => 'draft',
                    'marketplace_visibility' => 'global',
                    'attributes' => json_encode([
                        'package' => $row['package'],
                        'lcsc' => $row['lcsc'],
                        'jlcpcb_basic' => (bool) $row['basic'],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Chunked insert
            foreach (array_chunk($inserts, 500) as $batch) {
                DB::table('products')->insert($batch);
                $imported += count($batch);
                $bar->advance(count($batch));
            }

            $offset += count($rows);
            if ($limit > 0 && $offset >= $limit) break;
        }

        $bar->finish();
        $this->newLine();
        $this->info("  {$imported} products imported.");
    }

    private function makeName(array $row): string
    {
        // Try to extract a good name from description
        $desc = trim($row['description']);
        if (strlen($desc) <= 120) return $desc;

        // Truncate and take first meaningful part
        $parts = explode(',', $desc);
        if (count($parts) >= 2 && strlen($parts[0]) >= 5) {
            return trim($parts[0].', '.($parts[1] ?? ''));
        }

        return Str::limit($desc, 120);
    }

    private function extractPrice(string $priceJson): ?float
    {
        $prices = json_decode($priceJson, true);
        if (!is_array($prices) || empty($prices)) return null;

        // Take the lowest qty break price
        $first = reset($prices);
        return is_numeric($first) ? (float) $first : null;
    }
}
