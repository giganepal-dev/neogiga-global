<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Index product specifications and datasheets into ai_embeddings.
 *
 * Builds a searchable text blob per product (name, specs, brand, MPN, category)
 * and writes it to the ai_embeddings table for vector/semantic search.
 *
 * ponytail: text-only embeddings. Vector embeddings (pgvector) deferred until
 * the model provider is configured and the operator opts in.
 */
class IndexProductEmbeddings extends Command
{
    protected $signature = 'ai:index-products
                            {--limit=1000 : Products per batch}
                            {--offset=0 : Start from product ID}
                            {--reset : Clear existing embeddings first}';

    protected $description = 'Index product specs and datasheets into ai_embeddings for Commerce AI search.';

    public function handle(): int
    {
        if (! DB::getSchemaBuilder()->hasTable('ai_embeddings')) {
            $this->error('ai_embeddings table does not exist. Run migrations first.');
            return 1;
        }

        if ($this->option('reset')) {
            DB::table('ai_embeddings')->truncate();
            $this->info('Cleared existing embeddings.');
        }

        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $indexed = 0;
        $skipped = 0;

        $this->info("Indexing products (limit={$limit}, offset={$offset})...");
        $bar = $this->output->createProgressBar();

        DB::table('products')
            ->whereIn('status', ['active', 'approved'])
            ->where('visibility_status', 'public')
            ->skip($offset)
            ->take($limit)
            ->orderBy('id')
            ->each(function ($product) use (&$indexed, &$skipped, $bar) {
                try {
                    $text = $this->buildTextBlob($product);
                } catch (\Exception $e) {
                    $this->warn("  Skipping product #{$product->id}: {$e->getMessage()}");
                    $skipped++;
                    $bar->advance();
                    return;
                }
                if (empty(trim($text))) {
                    $skipped++;
                    $bar->advance();
                    return;
                }

                $existing = DB::table('ai_embeddings')
                    ->where('source_type', 'product')
                    ->where('source_id', $product->id)
                    ->first();

                if ($existing) {
                    DB::table('ai_embeddings')->where('id', $existing->id)->update([
                        'embedding_metadata' => json_encode(['text' => $text, 'hash' => md5($text), 'indexed_at' => now()->toIso8601String()]),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('ai_embeddings')->insert([
                        'uuid' => (string) Str::uuid(),
                        'provider' => 'local',
                        'model' => 'text-index',
                        'source_type' => 'product',
                        'source_id' => $product->id,
                        'embedding_metadata' => json_encode(['text' => $text, 'hash' => md5($text), 'indexed_at' => now()->toIso8601String()]),
                        'permission_scope' => 'public',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $indexed++;
                $bar->advance();
            });

        $bar->finish();
        $this->newLine();
        $this->info("Done. Indexed: {$indexed}, Skipped: {$skipped}");

        return 0;
    }

    private function buildTextBlob(object $product): string
    {
        $parts = [];

        // Name + identifiers
        $parts[] = $product->name ?? '';
        if (! empty($product->sku)) {
            $parts[] = "SKU: {$product->sku}";
        }
        if (! empty($product->mpn)) {
            $parts[] = "MPN: {$product->mpn}";
        }

        // Brand + manufacturer
        $brand = DB::table('product_brands')->where('id', $product->brand_id)->value('name');
        if ($brand) {
            $parts[] = "Brand: {$brand}";
        }
        $mfr = DB::table('manufacturers')->where('id', $product->manufacturer_id)->value('name');
        if ($mfr) {
            $parts[] = "Manufacturer: {$mfr}";
        }

        // Category path
        $cat = DB::table('product_categories')->where('id', $product->category_id)->first();
        if ($cat) {
            $parts[] = "Category: {$cat->name}";
        }

        // Specifications
        $specs = DB::table('product_specs')
            ->where('product_id', $product->id)
            ->select('name', 'value')
            ->get();
        foreach ($specs as $spec) {
            if (! empty($spec->value)) {
                $parts[] = "{$spec->name}: {$spec->value}";
            }
        }

        // Datasheet text (if table has extracted_text column)
        if (DB::getSchemaBuilder()->hasColumn('product_datasheets', 'extracted_text')) {
            $datasheet = DB::table('product_datasheets')
                ->where('product_id', $product->id)
                ->orderByDesc('created_at')
                ->value('extracted_text');
            if ($datasheet) {
                $parts[] = mb_substr($datasheet, 0, 5000);
            }
        }

        // Description
        if (! empty($product->description)) {
            $parts[] = strip_tags((string) $product->description);
        }

        return implode("\n", array_filter($parts));
    }
}
