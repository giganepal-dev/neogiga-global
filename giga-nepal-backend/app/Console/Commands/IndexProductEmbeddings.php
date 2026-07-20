<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
                $text = $this->buildTextBlob($product);
                if (empty(trim($text))) {
                    $skipped++;
                    $bar->advance();
                    return;
                }

                DB::table('ai_embeddings')->updateOrInsert(
                    ['source_type' => 'product', 'source_id' => $product->id],
                    [
                        'content_text' => $text,
                        'content_hash' => md5($text),
                        'status' => 'indexed',
                        'indexed_at' => now(),
                        'updated_at' => now(),
                        'created_at' => DB::raw('COALESCE((SELECT created_at FROM ai_embeddings WHERE source_type=\'product\' AND source_id=' . $product->id . '), NOW())'),
                    ],
                );

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
        $specs = DB::table('product_specifications')
            ->where('product_id', $product->id)
            ->pluck('value', 'name');
        foreach ($specs as $name => $value) {
            if (! empty($value)) {
                $parts[] = "{$name}: {$value}";
            }
        }

        // Datasheet text (if available)
        $datasheet = DB::table('product_datasheets')
            ->where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->value('extracted_text');
        if ($datasheet) {
            $parts[] = mb_substr($datasheet, 0, 5000); // first 5K chars
        }

        // Description
        if (! empty($product->description)) {
            $parts[] = strip_tags((string) $product->description);
        }

        return implode("\n", array_filter($parts));
    }
}
