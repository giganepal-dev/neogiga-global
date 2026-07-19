<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NormalizeCatalogBrandsCommand extends Command
{
    protected $signature = 'catalog:normalize-brands
        {--apply : Persist only the reviewed, high-confidence brand merges}
        {--yes : Explicitly confirm the reviewed plan}
        {--expected-plan-hash= : Exact hash printed by the corresponding dry run}
        {--backup-reference= : Verified backup directory required for apply}';

    protected $description = 'Consolidate known catalog brand aliases into international canonical names without deleting source records';

    /**
     * These are reviewed display aliases, not fuzzy-match rules. Every source
     * slug must resolve to an existing catalog record before it can be merged.
     *
     * @var array<string, string>
     */
    private const MERGES = [
        'st-microelectronics' => 'stmicroelectronics',
        'liteon' => 'lite-on',
        'ta-i-tech' => 'tai-tech',
        'alps-alpine' => 'alpsalpine',
        'aipulnion' => 'aipulnion-guangzhou-aipu-elec-tech',
        'aishi' => 'aishi-aihua-group',
        'world-semi' => 'worldsemi',
        'macom' => 'ma-com',
        'awinic' => 'awinic-shanghai-awinic-tech',
        'crosschip' => 'cross-chip',
        'magntek' => 'magn-tek',
        'tracopower' => 'traco-power',
        'apmemory' => 'ap-memory',
        'littelfuse-inc' => 'littelfuse',
        'texas-instrument' => 'texas-instruments',
        'texas-i' => 'texas-instruments',
        'texas' => 'texas-instruments',
        'texas-instruements' => 'texas-instruments',
        'texas-instrumental' => 'texas-instruments',
        'analog-devices-inc-maxim-integrated' => 'analog-devices',
        'analog-devices-maxim-integrated' => 'analog-devices',
        'maxim-analog-device' => 'analog-devices',
        'analog' => 'analog-devices',
        'nxp-semiconductor' => 'nxp-semiconductors',
        'bosch-sensortech' => 'bosch-sensortec',
        'infineon-technologies-ag' => 'infineon-technologies',
        'monolithic-power-systems-mps' => 'monolithic-power-systems',
        'quectell' => 'quectel',
        'quetell' => 'quectel',
        'mcc-micro-commercial-components' => 'micro-commercial-components',
        'micro-commercial-components-mcc' => 'micro-commercial-components',
        'global-connector-technology-gct' => 'global-connector-technology',
        'wurth-electronic' => 'wurth-elektronik',
        'wuerth-electronic' => 'wurth-elektronik',
        'wurth-elektronics' => 'wurth-elektronik',
        'te-connectivity-amp' => 'te-connectivity',
        'amp-te-connectivity' => 'te-connectivity',
        'te-connectivity-amp-brand' => 'te-connectivity',
        'microchip-technology-atmel' => 'microchip-technology',
        'nordic-semiconductor-asa' => 'nordic-semiconductor',
        'sensirion-ag' => 'sensirion',
        'acconeer-ab' => 'acconeer',
        'diotec-semiconductor-ag' => 'diotec-semiconductor',
    ];

    /** @var array<string, string> */
    private const DISPLAY_NAMES = [
        'stmicroelectronics' => 'STMicroelectronics',
        'lite-on' => 'Lite-On',
        'tai-tech' => 'TAI-TECH',
        'alpsalpine' => 'ALPS ALPINE',
        'aipulnion-guangzhou-aipu-elec-tech' => 'AIPULNION',
        'aishi-aihua-group' => 'AISHI',
        'worldsemi' => 'Worldsemi',
        'ma-com' => 'MACOM',
        'awinic-shanghai-awinic-tech' => 'AWINIC',
        'cross-chip' => 'CrossChip',
        'magn-tek' => 'MagnTek',
        'traco-power' => 'TRACO POWER',
        'ap-memory' => 'AP Memory',
        'littelfuse' => 'Littelfuse',
        'texas-instruments' => 'Texas Instruments',
        'analog-devices' => 'Analog Devices',
        'nxp-semiconductors' => 'NXP Semiconductors',
        'bosch-sensortec' => 'Bosch Sensortec',
        'infineon-technologies' => 'Infineon Technologies',
        'monolithic-power-systems' => 'Monolithic Power Systems',
        'quectel' => 'Quectel',
        'micro-commercial-components' => 'Micro Commercial Components',
        'global-connector-technology' => 'Global Connector Technology',
        'wurth-elektronik' => 'Wurth Elektronik',
        'te-connectivity' => 'TE Connectivity',
        'microchip-technology' => 'Microchip Technology',
        'nordic-semiconductor' => 'Nordic Semiconductor',
        'sensirion' => 'Sensirion',
        'acconeer' => 'Acconeer',
        'diotec-semiconductor' => 'DIOTEC Semiconductor',
        'setsafe-setfuse' => 'SETsafe / SETfuse',
    ];

    public function handle(): int
    {
        try {
            $plan = $this->plan();
            $this->line(json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            if (! $this->option('apply')) {
                $this->info('Dry run only: no brand, manufacturer, or product row was changed.');

                return self::SUCCESS;
            }

            if (! $this->option('yes')) {
                throw new RuntimeException('--yes is required with --apply.');
            }
            if (! is_dir((string) $this->option('backup-reference'))) {
                throw new RuntimeException('A verified --backup-reference directory is required.');
            }
            if (! hash_equals($plan['plan_hash'], (string) $this->option('expected-plan-hash'))) {
                throw new RuntimeException('The brand plan changed after dry run. Refusing to apply a stale plan.');
            }

            DB::transaction(function () use ($plan): void {
                foreach ($plan['merges'] as $merge) {
                    $productUpdates = [
                        'brand_id' => $merge['target_brand_id'],
                        'updated_at' => now(),
                    ];
                    if ($merge['target_manufacturer_id']) {
                        $productUpdates['manufacturer_id'] = $merge['target_manufacturer_id'];
                    }
                    DB::table('products')
                        ->where('brand_id', $merge['source_brand_id'])
                        ->update($productUpdates);

                    if ($merge['source_manufacturer_id'] && $merge['target_manufacturer_id']) {
                        $aliasKey = [
                            'manufacturer_id' => $merge['target_manufacturer_id'],
                            'normalized_alias' => $this->normalizedAlias($merge['source_name']),
                        ];
                        $aliasValues = [
                            'alias' => $merge['source_name'],
                            'source_name' => 'catalog_brand_normalization',
                            'source_url' => null,
                            'confidence_score' => 100,
                            'updated_at' => now(),
                        ];
                        if (DB::table('manufacturer_aliases')->where($aliasKey)->exists()) {
                            DB::table('manufacturer_aliases')->where($aliasKey)->update($aliasValues);
                        } else {
                            DB::table('manufacturer_aliases')->insert($aliasKey + $aliasValues + ['created_at' => now()]);
                        }

                        DB::table('manufacturers')
                            ->where('id', $merge['source_manufacturer_id'])
                            ->update(['is_active' => false, 'updated_at' => now()]);
                    }

                    DB::table('product_brands')
                        ->where('id', $merge['source_brand_id'])
                        ->update([
                            'is_active' => false,
                            'is_featured' => false,
                            'updated_at' => now(),
                        ]);
                }

                foreach ($plan['display_corrections'] as $correction) {
                    DB::table('product_brands')
                        ->where('id', $correction['brand_id'])
                        ->update(['name' => $correction['name'], 'updated_at' => now()]);

                    if ($correction['manufacturer_id']) {
                        DB::table('manufacturers')
                            ->where('id', $correction['manufacturer_id'])
                            ->update(['name' => $correction['name'], 'updated_at' => now()]);
                    }
                }
            });

            Cache::increment('catalog:brand-version');
            Cache::increment('seo:sitemap-version');

            $this->info('Applied '.$plan['summary']['brands_merged'].' source-brand merges and '.$plan['summary']['display_corrections'].' canonical display-name corrections.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /** @return array<string, mixed> */
    private function plan(): array
    {
        $slugs = array_values(array_unique(array_merge(
            array_keys(self::MERGES),
            array_values(self::MERGES),
            array_keys(self::DISPLAY_NAMES),
        )));
        $brands = DB::table('product_brands')
            ->whereIn('slug', $slugs)
            ->get(['id', 'name', 'slug', 'manufacturer_id'])
            ->keyBy('slug');

        $missing = array_values(array_diff($slugs, $brands->keys()->all()));
        if ($missing) {
            throw new RuntimeException('Expected brand records are missing: '.implode(', ', $missing));
        }

        $merges = [];
        foreach (self::MERGES as $sourceSlug => $targetSlug) {
            $source = $brands[$sourceSlug];
            $target = $brands[$targetSlug];
            if ($source->id === $target->id) {
                throw new RuntimeException("Brand merge source {$sourceSlug} resolves to its own target.");
            }

            $merges[] = [
                'source_brand_id' => $source->id,
                'source_name' => $source->name,
                'source_slug' => $sourceSlug,
                'source_manufacturer_id' => $source->manufacturer_id,
                'target_brand_id' => $target->id,
                'target_name' => self::DISPLAY_NAMES[$targetSlug] ?? $target->name,
                'target_slug' => $targetSlug,
                'target_manufacturer_id' => $target->manufacturer_id,
                'products_to_relink' => DB::table('products')->where('brand_id', $source->id)->count(),
            ];
        }

        $displayCorrections = [];
        foreach (self::DISPLAY_NAMES as $slug => $name) {
            $brand = $brands[$slug];
            if ($brand->name !== $name) {
                $displayCorrections[] = [
                    'brand_id' => $brand->id,
                    'slug' => $slug,
                    'from' => $brand->name,
                    'name' => $name,
                    'manufacturer_id' => $brand->manufacturer_id,
                ];
            }
        }

        $summary = [
            'brands_merged' => count($merges),
            'products_to_relink' => array_sum(array_column($merges, 'products_to_relink')),
            'display_corrections' => count($displayCorrections),
        ];
        $stable = [
            'version' => 'neogiga-catalog-brand-normalization-v1',
            'summary' => $summary,
            'merges' => $merges,
            'display_corrections' => $displayCorrections,
        ];

        return $stable + ['plan_hash' => hash('sha256', json_encode($stable, JSON_THROW_ON_ERROR))];
    }

    private function normalizedAlias(string $value): string
    {
        return strtoupper((string) preg_replace('/[^a-z0-9]+/i', '', $value));
    }
}
