<?php

namespace App\Services\CatalogImport\Ti;

use App\Models\Manufacturer;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductSeoMeta;
use App\Services\Catalog\CategoryResolutionService;
use App\Services\Seo\CatalogSeoTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Imports a TI parametric export as a canonical, quote-only NeoGiga catalog.
 *
 * Source prices and seeded availability remain provenance, never commercial
 * offers or verified warehouse stock.
 */
class TiParametricCatalogImporter
{
    private const SOURCE_NAME = 'Texas Instruments parametric export';

    /**
     * The supplied export has a 38-column header but 46-column rows. Keep the
     * documented row layout here rather than shifting values by position.
     */
    private const LEGACY_ROW_COLUMNS = [
        'sku', 'mpn', 'name', 'slug', 'brand', 'manufacturer_line', 'main_category', 'category', 'subcategory',
        'short_description', 'description', 'specifications_json', 'rating', 'operating_temperature_range_c',
        'temperature_min_c', 'temperature_max_c', 'functional_safety_category', 'package_type', 'pin_count',
        'package_area_mm2', 'package_size_mm', 'manufacturer_price_usd', 'manufacturer_currency', 'sale_price_usd',
        'sale_price_inr', 'sale_price_npr_ex_vat', 'sale_price_npr_inc_vat', 'manufacturer_status', 'publish_status', 'is_published',
        'datasheet_pdf_url', 'datasheet_html_url', 'manufacturer_product_url', 'image_url', 'image_source_page',
        'image_fetch_mode', 'image_filename', 'image_status', 'seo_title', 'seo_description', 'seo_keywords',
        'source_name', 'source_file', 'source_export_date', 'stock_enabled', 'data_quality_note',
    ];

    public function __construct(
        private readonly CatalogSeoTemplateService $seo,
        private readonly CategoryResolutionService $categories,
    ) {}

    /** @param array{dry_run?:bool,limit?:int,publish?:bool} $options */
    public function import(string $file, array $options = []): array
    {
        if (! is_file($file) || ! is_readable($file)) {
            throw new \InvalidArgumentException("TI source file is not readable: {$file}");
        }

        $dryRun = (bool) ($options['dry_run'] ?? false);
        $publish = (bool) ($options['publish'] ?? false);
        $limit = max(0, (int) ($options['limit'] ?? 0));
        $sourceChecksum = hash_file('sha256', $file);
        $sourceFile = basename($file);
        $stats = [
            'seen' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped_existing_curated' => 0,
            'skipped_invalid' => 0,
            'specifications_written' => 0,
            'seo_written' => 0,
            'images_not_imported' => 0,
            'prices_not_published' => 0,
            'inventory_not_imported' => 0,
            'errors' => [],
        ];

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Unable to open TI source file: {$file}");
        }

        try {
            $headers = fgetcsv($handle, escape: '');
            if (! is_array($headers)) {
                throw new \RuntimeException('TI source file has no CSV header.');
            }
            $headers = array_map(fn ($value) => trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)), $headers);
            $required = ['mpn', 'name', 'brand', 'manufacturer_product_url', 'specifications_json'];
            $missing = array_values(array_diff($required, $headers));
            if ($missing !== []) {
                throw new \RuntimeException('TI source file is missing required columns: '.implode(', ', $missing));
            }

            while (($row = fgetcsv($handle, escape: '')) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }
                if ($limit > 0 && $stats['seen'] >= $limit) {
                    break;
                }
                $stats['seen']++;
                $record = $this->record($headers, $row);
                if ($record === null) {
                    $stats['skipped_invalid']++;
                    $this->recordError($stats, $stats['seen'] + 1, 'column_count_mismatch');
                    continue;
                }
                if (! $this->valid($record)) {
                    $stats['skipped_invalid']++;
                    $this->recordError($stats, $stats['seen'] + 1, 'missing_mpn_name_or_product_url');
                    continue;
                }

                if ($dryRun) {
                    $stats['images_not_imported']++;
                    $stats['prices_not_published']++;
                    $stats['inventory_not_imported']++;
                    continue;
                }

                $outcome = DB::transaction(fn (): array => $this->persist($record, $sourceFile, $sourceChecksum, $publish), 3);
                foreach ($outcome as $key => $count) {
                    if (array_key_exists($key, $stats)) {
                        $stats[$key] += $count;
                    }
                }
            }
        } finally {
            fclose($handle);
        }

        return $stats + [
            'source_file' => $sourceFile,
            'source_checksum' => $sourceChecksum,
            'mode' => $dryRun ? 'dry_run' : ($publish ? 'publish_quote_only' : 'draft'),
        ];
    }

    /** @param array<string, string|null> $record @return array<string, int> */
    private function persist(array $record, string $sourceFile, string $sourceChecksum, bool $publish): array
    {
        $mpn = trim((string) $record['mpn']);
        $normalizedMpn = $this->normalizedMpn($mpn);
        $manufacturer = $this->manufacturer($sourceFile, $record);
        $brand = $this->brand($manufacturer);
        $category = $this->category($record);
        $existing = Product::query()
            ->where('normalized_mpn', $normalizedMpn)
            ->where(function ($query) use ($manufacturer) {
                $query->where('manufacturer_id', $manufacturer->id)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('manufacturer_id')
                            ->whereRaw('LOWER(COALESCE(manufacturer_name, \'\')) = ?', ['texas instruments']);
                    });
            })
            ->lockForUpdate()
            ->first();

        if ($existing && ! $this->managedByTi($existing)) {
            return [
                'skipped_existing_curated' => 1,
                'images_not_imported' => 1,
                'prices_not_published' => 1,
                'inventory_not_imported' => 1,
            ];
        }

        $specifications = $this->specifications($record);
        $content = $this->content($record, $specifications);
        $now = now();
        $isNew = ! $existing;
        $product = $existing ?: new Product;
        $values = [
            'name' => $content['name'],
            'slug' => $existing?->slug ?: $this->slug($mpn, $content['name']),
            'sku' => $existing?->sku ?: $this->sku($normalizedMpn),
            'mpn' => $mpn,
            'normalized_mpn' => $normalizedMpn,
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_name' => $manufacturer->name,
            'brand_id' => $brand->id,
            'category_id' => $category['category_id'],
            'short_description' => $content['short_description'],
            'description' => $content['description'],
            'type' => 'simple',
            'status' => $publish && ! $category['requires_review'] ? 'approved' : 'draft',
            'approved_at' => $publish && ! $category['requires_review'] ? ($existing?->approved_at ?: $now) : null,
            'base_price' => 0,
            'cost_price' => null,
            'sale_price' => null,
            'track_inventory' => false,
            'stock_quantity' => 0,
            'lifecycle_status' => strtoupper(trim((string) ($record['manufacturer_status'] ?? ''))),
            'source_name' => self::SOURCE_NAME,
            'source_url' => trim((string) $record['manufacturer_product_url']),
            'source_file' => $sourceFile,
            'source_page_url' => trim((string) $record['manufacturer_product_url']),
            'downloaded_at' => $now,
            'imported_at' => $now,
            'data_year' => '2026',
            'license_note' => 'Official TI product and parametric data. Product images and commercial offers are not imported without separate redistribution and commercial verification.',
            'confidence_level' => 'official_manufacturer_parametric_export',
            'original_raw_value' => json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'normalized_value' => json_encode(['mpn' => $normalizedMpn, 'specifications' => $specifications], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'last_verified_at' => $now,
            'attributes' => [
                'datasheet_url' => trim((string) ($record['datasheet_pdf_url'] ?? '')),
                'datasheet_html_url' => trim((string) ($record['datasheet_html_url'] ?? '')),
                'manufacturer_product_url' => trim((string) $record['manufacturer_product_url']),
                'source_checksum' => $sourceChecksum,
                'image_status' => 'not_imported_rights_not_verified',
                'pricing_status' => 'quote_only_unverified',
                'inventory_status' => 'not_imported_unverified',
            ],
            'metadata' => [
                'catalog_importer' => 'ti_parametric_catalog_v1',
                'category_resolution' => $category,
                'source_notes' => 'Product copy and structured specifications are derived from the supplied official TI parametric export. No TI imagery, seeded availability, or source price is published.',
                'confidence_level' => 'official_manufacturer_parametric_export',
                'last_updated' => $now->toIso8601String(),
                'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
            ],
        ];
        if (Schema::hasColumn('products', 'approval_status')) {
            $values['approval_status'] = $publish && ! $category['requires_review'] ? 'approved' : 'pending_review';
        }
        if (Schema::hasColumn('products', 'visibility_status')) {
            $values['visibility_status'] = 'quote_only';
        }
        $product->forceFill($values);
        $product->save();

        $this->replaceSpecifications($product, $specifications);
        $this->writeSeo($product);

        return [
            $isNew ? 'created' : 'updated' => 1,
            'specifications_written' => count($specifications),
            'seo_written' => 1,
            'images_not_imported' => 1,
            'prices_not_published' => 1,
            'inventory_not_imported' => 1,
        ];
    }

    /** @param array<string, string|null> $record */
    private function manufacturer(string $sourceFile, array $record): Manufacturer
    {
        return Manufacturer::firstOrCreate(['slug' => 'texas-instruments'], [
            'name' => 'Texas Instruments',
            'legal_name' => 'Texas Instruments Incorporated',
            'official_website' => 'https://www.ti.com',
            'source_name' => self::SOURCE_NAME,
            'source_url' => 'https://www.ti.com',
            'source_file' => $sourceFile,
            'source_page_url' => trim((string) $record['manufacturer_product_url']),
            'downloaded_at' => now(),
            'imported_at' => now(),
            'data_year' => '2026',
            'license_note' => 'Official manufacturer parametric export.',
            'confidence_level' => 'official_manufacturer_parametric_export',
            'is_active' => true,
        ]);
    }

    private function brand(Manufacturer $manufacturer): ProductBrand
    {
        return ProductBrand::firstOrCreate(['slug' => 'texas-instruments'], [
            'name' => 'Texas Instruments',
            'manufacturer_id' => $manufacturer->id,
            'website_url' => 'https://www.ti.com',
            'short_description' => 'Texas Instruments semiconductors and engineering components.',
            'is_active' => true,
            'landing_page_enabled' => true,
        ]);
    }

    /** @param array<string, string|null> $record */
    /** @return array{parent_category_id:?int,category_id:?int,confidence:float,matched_by:string,requires_review:bool,reasons:list<string>,category_name:?string,path:list<string>,source_key:string} */
    private function category(array $record): array
    {
        $name = trim((string) ($record['subcategory'] ?: $record['category'] ?: 'Amplifiers'));

        return $this->categories->resolve($name, [
            'source_name' => self::SOURCE_NAME,
            'source_category_name' => $name,
            'source_category_path' => trim((string) ($record['category'] ?? '').' / '.$name, ' /'),
            'manufacturer_name' => 'Texas Instruments',
            'mpn' => $record['mpn'] ?? null,
            'manufacturer_category' => $record['category'] ?? null,
            'product_family' => $record['category'] ?? null,
        ]);
    }

    /** @param array<string, string|null> $record @return list<array{name:string,value:string,unit:?string}> */
    private function specifications(array $record): array
    {
        $decoded = json_decode((string) ($record['specifications_json'] ?? '{}'), true);
        $decoded = is_array($decoded) ? $decoded : [];
        $source = [
            'Manufacturer Part Number' => $record['mpn'] ?? null,
            'Product category' => $record['subcategory'] ?? null,
            'Qualification / rating' => $record['rating'] ?? null,
            'Operating temperature range' => $record['operating_temperature_range_c'] ?? null,
            'Functional safety category' => $record['functional_safety_category'] ?? null,
            'Package type' => $record['package_type'] ?? null,
            'Pin count' => $record['pin_count'] ?? null,
            'Package area' => $record['package_area_mm2'] ?? null,
            'Package size' => $record['package_size_mm'] ?? null,
            'Lifecycle status' => $record['manufacturer_status'] ?? null,
        ];
        foreach ($decoded as $name => $value) {
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }
            $source[$this->label((string) $name)] = (string) $value;
        }

        $specifications = [];
        foreach ($source as $name => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $specifications[] = ['name' => $name, 'value' => $value, 'unit' => $this->unit($name)];
        }

        return array_values(array_unique($specifications, SORT_REGULAR));
    }

    /** @param array<string, string|null> $record @param list<array{name:string,value:string,unit:?string}> $specifications */
    private function content(array $record, array $specifications): array
    {
        $mpn = trim((string) $record['mpn']);
        $category = trim((string) ($record['subcategory'] ?: $record['category'] ?: 'amplifier'));
        $rating = trim((string) ($record['rating'] ?? ''));
        $temperature = trim((string) ($record['operating_temperature_range_c'] ?? ''));
        $package = trim((string) ($record['package_type'] ?? ''));
        $name = Str::limit(trim((string) ($record['name'] ?: "{$mpn} {$category}")), 240, '');
        $facts = array_filter([
            $rating !== '' ? "Qualification: {$rating}" : null,
            $temperature !== '' ? "Operating temperature: {$temperature} °C" : null,
            $package !== '' ? "Package: {$package}" : null,
        ]);
        $short = Str::limit("{$mpn} from Texas Instruments is a {$category}. ".implode('; ', $facts).'.', 420, '');
        $specLines = array_map(static fn (array $spec): string => '- '.$spec['name'].': '.$spec['value'].($spec['unit'] ? ' '.$spec['unit'] : ''), $specifications);
        $description = implode("\n", [
            $name,
            '',
            $short,
            '',
            'NeoGiga technical summary',
            'This listing is based on the supplied official Texas Instruments parametric export. Confirm electrical limits, qualification requirements, package selection, and current orderability against the linked manufacturer documentation before design or procurement.',
            '',
            'Key specifications',
            implode("\n", $specLines),
            '',
            'Documentation and sourcing',
            'Manufacturer documentation is linked on this page. NeoGiga availability is quote-only until an offer, price, and warehouse allocation are independently verified.',
        ]);

        return ['name' => $name, 'short_description' => $short, 'description' => $description];
    }

    /** @param list<array{name:string,value:string,unit:?string}> $specifications */
    private function replaceSpecifications(Product $product, array $specifications): void
    {
        $product->specs()->delete();
        foreach ($specifications as $position => $spec) {
            $values = $spec + ['sort_order' => $position];
            if (Schema::hasColumn('product_specs', 'is_visible')) {
                $values['is_visible'] = true;
            }
            if (Schema::hasColumn('product_specs', 'is_filterable')) {
                $values['is_filterable'] = true;
            }
            $product->specs()->create($values);
        }
    }

    private function writeSeo(Product $product): void
    {
        $marketplace = Marketplace::query()->where('is_default', true)->first();
        $generated = $this->seo->product($product, $marketplace, 'en');
        $row = ProductSeoMeta::query()->firstOrNew(['product_id' => $product->id]);
        if ($row->exists && ($row->is_manual_override || $row->is_locked)) {
            return;
        }
        $values = [
            'title' => $generated['title'],
            'meta_title' => $generated['title'],
            'meta_description' => $generated['description'],
            'canonical_url' => $generated['canonical'],
            'robots' => $generated['robots'],
            'generated_title' => $generated['title'],
            'generated_description' => $generated['description'],
            'generated_canonical_url' => $generated['canonical'],
            'generated_robots' => $generated['robots'],
            'robots_reason' => $generated['robots_reason'],
            'template_version' => $generated['template_version'],
            'active_source' => 'generated',
            'confidence_level' => $generated['confidence_level'],
            'generated_at' => now(),
            'metadata' => [
                'source' => 'ti_parametric_catalog_v1',
                'source_notes' => $generated['source_notes'],
                'confidence_level' => $generated['confidence_level'],
                'last_updated' => now()->toIso8601String(),
                'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
            ],
        ];
        foreach (array_keys($values) as $column) {
            if (! Schema::hasColumn('product_seo_meta', $column)) {
                unset($values[$column]);
            }
        }
        $row->forceFill($values)->save();
        if (Schema::hasTable('catalog_seo_versions')) {
            $this->seo->recordVersion('product', $product->id, $generated + ['active_source' => 'generated'], 'generated', null, $marketplace?->id);
        }
    }

    /** @param array<string, string|null> $record */
    private function valid(array $record): bool
    {
        return trim((string) ($record['mpn'] ?? '')) !== ''
            && trim((string) ($record['name'] ?? '')) !== ''
            && filter_var(trim((string) ($record['manufacturer_product_url'] ?? '')), FILTER_VALIDATE_URL) !== false;
    }

    /** @param list<string> $headers @param list<string|null> $row @return array<string, string|null>|null */
    private function record(array $headers, array $row): ?array
    {
        if (count($row) === count($headers)) {
            $record = array_combine($headers, $row);

            return is_array($record) ? $record : null;
        }

        if (count($headers) === 38 && count($row) === count(self::LEGACY_ROW_COLUMNS)) {
            $record = array_combine(self::LEGACY_ROW_COLUMNS, $row);

            return is_array($record) ? $record : null;
        }

        return null;
    }

    /** @param array<string, mixed> $stats */
    private function recordError(array &$stats, int $line, string $reason): void
    {
        if (count($stats['errors']) < 25) {
            $stats['errors'][] = compact('line', 'reason');
        }
    }

    private function managedByTi(Product $product): bool
    {
        return (string) $product->source_name === self::SOURCE_NAME;
    }

    private function normalizedMpn(string $mpn): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', trim($mpn)));
    }

    private function sku(string $normalizedMpn): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $normalizedMpn) ?: 'PART';

        return 'NG-TI-'.Str::upper(Str::substr($value, 0, 42)).'-'.substr(sha1($normalizedMpn), 0, 8);
    }

    private function slug(string $mpn, string $name): string
    {
        return Str::limit(Str::slug('ti-'.$mpn.'-'.$name), 170, '').'-'.substr(sha1($this->normalizedMpn($mpn)), 0, 8);
    }

    private function label(string $name): string
    {
        return Str::headline(str_replace(['_c', '_mm2', '_mm'], ['', '', ''], $name));
    }

    private function unit(string $name): ?string
    {
        return match ($name) {
            'Operating temperature range' => '°C',
            'Package area' => 'mm²',
            'Package size' => 'mm',
            default => null,
        };
    }
}
