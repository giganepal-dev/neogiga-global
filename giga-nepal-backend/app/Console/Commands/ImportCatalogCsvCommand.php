<?php

namespace App\Console\Commands;

use App\Models\Marketplace\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportCatalogCsvCommand extends Command
{
    protected $signature = 'catalog:import-csv {file : Path to CSV file} {--source= : Source name (sparkfun, adafruit, etc.)} {--dry-run : Preview without inserting}';
    protected $description = 'Import products from a CSV file into the NeoGiga catalog';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! file_exists($file) || ! is_readable($file)) {
            $this->error("File not found or not readable: {$file}");
            return 1;
        }

        $source = $this->option('source') ?: 'csv_import';
        $dryRun = $this->option('dry-run');

        $fh = fopen($file, 'r');
        // Handle BOM
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fh);
        }

        $headers = fgetcsv($fh);
        if (! $headers) {
            $this->error('CSV has no headers');
            fclose($fh);
            return 1;
        }
        $headers = array_map(fn ($h) => trim((string) $h, "\xEF\xBB\xBF \t\n\r\0\x0B"), $headers);

        // Create one import batch for this run
        $batchId = (string) Str::uuid();
        DB::table('catalog_import_batches')->insert([
            'id' => $batchId,
            'source_id' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $imported = $skipped = $errors = 0;
        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($fh)) !== false) {
            $bar->advance();
            if (count($row) < count($headers)) { $row = array_pad($row, count($headers), ''); }
            elseif (count($row) > count($headers)) { $row = array_slice($row, 0, count($headers)); }
            $data = array_combine($headers, $row);

            $name = trim((string) ($data['name'] ?? ''));
            $mpn = trim((string) ($data['mpn'] ?? ''));
            $sku = trim((string) ($data['supplier_sku'] ?? $data['sku'] ?? ''));

            if (empty($name)) {
                $skipped++;
                continue;
            }

            // Skip existing by MPN+source
            $exists = Product::where('mpn', $mpn)
                ->where('source_name', $source)
                ->where('mpn', '!=', '')
                ->exists();

            if ($exists && $mpn !== '') {
                $skipped++;
                continue;
            }

            // Category handling
            $catPath = trim((string) ($data['category'] ?? ''));
            $categoryId = $this->resolveCategory($catPath);

            // Build slug
            $slug = Str::slug($name);
            $baseSlug = $slug;
            $suffix = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . ($suffix++);
            }

            if ($dryRun) {
                $imported++;
                continue;
            }

            DB::beginTransaction();
            try {
                $productId = DB::table('products')->insertGetId([
                    'name' => $name,
                    'slug' => $slug,
                    'sku' => $sku ?: ('NG-' . Str::random(8)),
                    'mpn' => $mpn ?: null,
                    'manufacturer_name' => trim((string) ($data['manufacturer'] ?? '')) ?: null,
                    'short_description' => trim((string) ($data['short_description'] ?? '')) ?: null,
                    'description' => trim((string) ($data['description'] ?? '')) ?: null,
                    'category_id' => $categoryId,
                    'source_name' => $source,
                    'source_url' => trim((string) ($data['source_url'] ?? '')) ?: null,
                    'base_price' => (float) ($data['price'] ?? 0),
                    'status' => 'approved',
                    'approval_status' => 'approved',
                    'visibility_status' => 'marketplace_only',
                    'type' => 'simple',
                    'lifecycle_status' => trim((string) ($data['lifecycle_status'] ?? '')) ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Catalog source
                DB::table('catalog_product_sources')->insert([
                    'product_id' => $productId,
                    'source_id' => 1,
                    'source_url' => trim((string) ($data['source_url'] ?? '')),
                    'source_part_id' => $mpn ?: $sku,
                    'source_payload_hash' => md5($name . ($mpn ?: $sku) . $source),
                    'import_batch_id' => $batchId,
                    'data_quality_score' => '1.00',
                    'review_status' => 'approved',
                    'imported_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
                $imported++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors++;
                if ($errors <= 3) {
                    $this->warn("\n  Error on {$name}: " . $e->getMessage());
                }
            }
        }

        fclose($fh);
        $bar->finish();

        $this->newLine(2);
        $this->info("Source: {$source}");
        $this->info("Imported: {$imported} | Skipped (duplicates): {$skipped} | Errors: {$errors}");

        return 0;
    }

    private function resolveCategory(string $catPath): ?int
    {
        $catPath = trim($catPath);
        if (empty($catPath)) {
            return null;
        }

        // "Home > Components > Cables > Hook Up" → take the last segment
        $segments = array_map('trim', explode('>', $catPath));
        $lastSegment = end($segments);
        if (empty($lastSegment) || strtolower($lastSegment) === 'home') {
            return null;
        }

        $slug = Str::slug($lastSegment);

        // Try exact match first
        $id = DB::table('product_categories')->where('slug', $slug)->value('id');
        if ($id) {
            return (int) $id;
        }

        // Try second-to-last segment
        if (count($segments) >= 2) {
            $parentSegment = trim($segments[count($segments) - 2]);
            $parentSlug = Str::slug($parentSegment);
            $id = DB::table('product_categories')->where('slug', $parentSlug)->value('id');
            if ($id) {
                // Create child category
                return DB::table('product_categories')->insertGetId([
                    'name' => $lastSegment,
                    'slug' => $slug,
                    'parent_id' => (int) $id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Create under "Uncategorized" or root
        return null;
    }

    private function parseJsonArray(string $raw): array
    {
        $raw = trim($raw);
        if (empty($raw) || $raw === '[]') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try comma-separated
        return array_filter(array_map('trim', explode(',', $raw)));
    }
}
