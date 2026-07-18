<?php

namespace App\Console\Commands;

use App\Models\Marketplace\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class NormalizeCatalogTaxonomyCommand extends Command
{
    protected $signature = 'catalog:normalize-taxonomy
        {--apply : Persist high-confidence NeoGiga taxonomy assignments}
        {--yes : Explicitly confirm the reviewed plan}
        {--limit=0 : Maximum Needs Review products to inspect, 0 means all}
        {--expected-plan-hash= : Exact hash printed by the corresponding dry run}
        {--backup-reference= : Verified backup directory required for apply}';

    protected $description = 'Map source-backed imported categories into the NeoGiga taxonomy without guessing unresolved products';

    private const RULES = [
        'microcontroller' => 'microcontrollers', 'mcu/mpu/soc' => 'microcontrollers',
        'rf and wireless' => 'rf-semiconductors', 'rf ' => 'rf-semiconductors',
        'opto' => 'optoelectronics', 'led indication' => 'optoelectronics',
        'logic' => 'logic-ics', 'memory' => 'memory',
        'operational amplifier' => 'analog-ics', 'opamp' => 'analog-ics', 'comparator' => 'analog-ics',
        'adc/dac' => 'analog-ics', 'power management' => 'analog-ics',
        'resistor' => 'resistors', 'capacitor' => 'capacitors',
        'inductor' => 'inductors', 'coil' => 'inductors', 'choke' => 'inductors',
        'crystal' => 'crystals-oscillators', 'oscillator' => 'crystals-oscillators', 'resonator' => 'crystals-oscillators',
        'connector' => 'connectors', 'header' => 'connectors', 'terminal block' => 'connectors',
        'switch' => 'switches-relays', 'relay' => 'switches-relays',
        'fuse' => 'fuses-protection', 'circuit protection' => 'fuses-protection', 'esd' => 'fuses-protection', 'tvs' => 'fuses-protection',
        'diode' => 'discrete-semiconductors', 'transistor' => 'discrete-semiconductors', 'mosfet' => 'discrete-semiconductors', 'thyristor' => 'discrete-semiconductors',
        'temperature' => 'temperature-humidity', 'thermistor' => 'temperature-humidity', 'hall sensor' => 'motion-imu', 'sensor' => 'sensors',
        'motor driver' => 'motor-drivers', 'motor' => 'dc-gear-motors', 'battery' => 'battery-management-systems',
    ];

    public function handle(): int
    {
        try {
            $plan = $this->plan((int) $this->option('limit'));
            $this->line(json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            if (! $this->option('apply')) {
                $this->info('Dry run only: no category or product row was changed.');
                return self::SUCCESS;
            }
            if (! $this->option('yes')) {
                throw new RuntimeException('--yes is required with --apply.');
            }
            if (! is_dir((string) $this->option('backup-reference'))) {
                throw new RuntimeException('A verified --backup-reference directory is required.');
            }
            if (! hash_equals($plan['plan_hash'], (string) $this->option('expected-plan-hash'))) {
                throw new RuntimeException('The category plan changed after dry run. Refusing to apply a stale plan.');
            }

            foreach ($plan['assignments'] as $slug => $productIds) {
                foreach (array_chunk($productIds, 2_000) as $ids) {
                    DB::table('products')->whereIn('id', $ids)->update([
                        'category_id' => $plan['categories'][$slug]['id'],
                        'updated_at' => now(),
                    ]);
                }
            }

            $this->info('Applied '.$plan['summary']['auto_assignable'].' high-confidence NeoGiga taxonomy assignments.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }

    /** @return array<string, mixed> */
    private function plan(int $limit): array
    {
        $reviewId = DB::table('product_categories')->where('slug', '205-needs-review')->value('id');
        if (! $reviewId) {
            throw new RuntimeException('The imported Needs Review category is required for this operation.');
        }

        $categories = DB::table('product_categories')->whereIn('slug', array_values(self::RULES))->get(['id', 'slug', 'name'])->keyBy('slug');
        if ($categories->count() !== count(array_unique(self::RULES))) {
            throw new RuntimeException('The NeoGiga taxonomy seed must run before normalization.');
        }

        $assignments = [];
        $unresolved = 0;
        $digest = hash_init('sha256');
        $query = Product::query()->where('category_id', $reviewId)->orderBy('id');
        if ($limit > 0) {
            $query->limit($limit);
        }
        $query->select(['id', 'attributes'])->chunkById(1_000, function ($products) use (&$assignments, &$unresolved, $digest): void {
            foreach ($products as $product) {
                $slug = $this->resolveSlug($product->attributes);
                hash_update($digest, $product->id.'|'.($slug ?? 'unresolved')."\n");
                if (! $slug) {
                    $unresolved++;
                } else {
                    $assignments[$slug][] = $product->id;
                }
            }
        });

        ksort($assignments);
        $summary = [
            'review_products_scanned' => array_sum(array_map('count', $assignments)) + $unresolved,
            'auto_assignable' => array_sum(array_map('count', $assignments)),
            'unresolved' => $unresolved,
            'by_target' => array_map('count', $assignments),
        ];
        $stable = ['version' => 'neogiga-taxonomy-normalization-v1', 'summary' => $summary, 'data_digest' => hash_final($digest)];

        return $stable + [
            'categories' => $categories->map(fn ($category) => ['id' => (int) $category->id, 'name' => $category->name])->all(),
            'assignments' => $assignments,
            'plan_hash' => hash('sha256', json_encode($stable, JSON_THROW_ON_ERROR)),
            'dry_run' => true,
            'generated_at' => now()->toIso8601String(),
            'disclaimer' => 'Only source-category values matching explicit NeoGiga taxonomy rules are assigned automatically. Records without dependable source category data remain in review.',
        ];
    }

    private function resolveSlug(mixed $attributes): ?string
    {
        $attributes = is_array($attributes) ? $attributes : [];
        $raw = data_get($attributes, 'raw.extra');
        $payload = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
        if (! is_array($payload)) {
            return null;
        }
        $value = Str::lower(trim(implode(' ', array_filter([
            data_get($payload, 'category.name1'), data_get($payload, 'category.name2'),
        ], 'is_string'))));
        if ($value === '') {
            return null;
        }
        foreach (self::RULES as $needle => $slug) {
            if (str_contains($value, $needle)) {
                return $slug;
            }
        }
        return null;
    }
}
