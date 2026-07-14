<?php

namespace App\Services\Seo;

use App\Models\Manufacturer;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\ProductSeoMeta;
use App\Services\Marketplace\MarketplaceUrlGenerator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CatalogSeoTemplateService
{
    public const TEMPLATE_VERSION = 'approved-marketplace-patterns-2026-07-v1';

    public const DISCLAIMER = 'Advisory only';

    public function __construct(private readonly MarketplaceUrlGenerator $urls) {}

    /** @return array<string, mixed> */
    public function product(Product $product, ?Marketplace $marketplace, string $locale = 'en'): array
    {
        $name = $this->clean($product->name ?? 'Product');
        $context = $this->context($marketplace);
        $title = "Buy {$name} on NeoGiga {$context['name']} | {$context['site_suffix']}";

        if ($context['kind'] === 'global') {
            $description = "Buy {$name} on NeoGiga Engineering Marketplace. Low MOQ, Quality Products, B2B Sourcing from Regional Warehouse.";
        } else {
            $description = "Buy {$name} on NeoGiga {$context['name']} Engineering Marketplace. Low MOQ, Quality Products, B2B Sourcing from {$context['fulfilment']}.";
        }

        $indexability = $this->productIndexability($product, $marketplace);

        return $this->payload(
            $title,
            $description,
            $this->canonical($marketplace, $locale, 'products', (string) ($product->slug ?? '')),
            $indexability,
            $context,
        );
    }

    /** @return array<string, mixed> */
    public function category(ProductCategory $category, ?Marketplace $marketplace, string $locale = 'en'): array
    {
        $name = $this->clean($category->name ?? 'Category');
        $context = $this->context($marketplace);
        $title = "Buy {$name} on NeoGiga {$context['name']} | {$context['site_suffix']}";

        if ($context['kind'] === 'global') {
            $description = "Buy {$name} on NeoGiga Engineering Marketplace. Explore Quality Products, Low MOQ and B2B Sourcing from Regional Warehouse.";
        } else {
            $description = "Buy {$name} on NeoGiga {$context['name']} Engineering Marketplace. Explore Quality Products, Low MOQ and B2B Sourcing from {$context['fulfilment']}.";
        }

        $indexability = $this->categoryIndexability($category, $marketplace);

        return $this->payload(
            $title,
            $description,
            $this->canonical($marketplace, $locale, 'categories', (string) ($category->slug ?? '')),
            $indexability,
            $context,
        );
    }

    /** @return array<string, mixed> */
    public function brand(ProductBrand $brand, ?Marketplace $marketplace, string $locale = 'en'): array
    {
        $name = $this->clean($brand->name ?? 'Brand');
        $context = $this->context($marketplace);
        $title = "Buy {$name} Products on NeoGiga {$context['name']} | {$context['site_suffix']}";
        $description = "Buy {$name} products on NeoGiga {$context['name']} Engineering Marketplace. Explore technical products, Low MOQ, RFQ and B2B sourcing from {$context['fulfilment']}.";
        $indexability = $this->brandIndexability($brand, $marketplace);

        return $this->payload(
            $title,
            $description,
            $this->canonical($marketplace, $locale, 'brand', (string) ($brand->slug ?? '')),
            $indexability,
            $context,
        );
    }

    /** @return array<string, mixed> */
    public function manufacturer(Manufacturer $manufacturer, ?Marketplace $marketplace, string $locale = 'en'): array
    {
        $name = $this->clean($manufacturer->name ?? 'Manufacturer');
        $context = $this->context($marketplace);
        $title = "Buy {$name} Parts on NeoGiga {$context['name']} | {$context['site_suffix']}";
        $description = "Find {$name} manufacturer parts on NeoGiga {$context['name']} Engineering Marketplace with MPN search, RFQ, technical references and B2B sourcing from {$context['fulfilment']}.";
        $active = (bool) ($manufacturer->is_active ?? true);
        $indexability = $this->marketplaceBlocked($marketplace)
            ?: ($active
                ? ['robots' => 'index,follow', 'reason' => 'The manufacturer is active and has a canonical identity.']
                : ['robots' => 'noindex,nofollow', 'reason' => 'The manufacturer is inactive.']);

        return $this->payload(
            $title,
            $description,
            $this->canonical($marketplace, $locale, 'manufacturer', (string) ($manufacturer->slug ?? '')),
            $indexability,
            $context,
        );
    }

    /** @return array<string, mixed> */
    public function activeProduct(Product $product, ?Marketplace $marketplace, string $locale = 'en'): array
    {
        $generated = $this->product($product, $marketplace, $locale);
        $row = $product->relationLoaded('seoMeta') ? $product->seoMeta : ProductSeoMeta::where('product_id', $product->id)->first();
        if (! $row || ! $this->isManualProductRow($row)) {
            return $generated + ['active_source' => 'generated'];
        }

        return array_merge($generated, [
            'title' => $row->meta_title ?: $row->title ?: $generated['title'],
            'description' => $row->meta_description ?: $generated['description'],
            'canonical' => $row->canonical_url ?: $generated['canonical'],
            'robots' => $row->robots ?: $generated['robots'],
            'robots_reason' => $row->robots_reason ?: 'Manual SEO override preserved.',
            'active_source' => 'manual',
            'confidence_level' => $row->confidence_level ?: 'manual_admin_override',
            'last_updated' => $row->updated_at?->toIso8601String() ?: now()->toIso8601String(),
        ]);
    }

    /** @return array<string, mixed> */
    public function activeCategory(ProductCategory $category, ?Marketplace $marketplace, string $locale = 'en'): array
    {
        $generated = $this->category($category, $marketplace, $locale);
        $meta = is_array($category->seo_meta) ? $category->seo_meta : [];
        if (! $this->isManualArray($meta)) {
            return $generated + ['active_source' => 'generated'];
        }

        return array_merge($generated, [
            'title' => $meta['title'] ?? $generated['title'],
            'description' => $meta['description'] ?? $generated['description'],
            'canonical' => $meta['canonical_url'] ?? $generated['canonical'],
            'robots' => $meta['robots'] ?? $generated['robots'],
            'robots_reason' => $meta['robots_reason'] ?? 'Manual category SEO override preserved.',
            'active_source' => 'manual',
            'confidence_level' => $meta['confidence_level'] ?? 'manual_admin_override',
            'last_updated' => $meta['last_updated'] ?? $category->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ]);
    }

    /** @return array<string, mixed> */
    public function activeBrand(ProductBrand $brand, ?Marketplace $marketplace, string $locale = 'en'): array
    {
        $generated = $this->brand($brand, $marketplace, $locale);
        $meta = is_array($brand->seo_meta) ? $brand->seo_meta : [];
        $hasExplicitOverride = trim((string) ($brand->seo_title ?? '')) !== ''
            || trim((string) ($brand->seo_description ?? '')) !== ''
            || trim((string) ($brand->canonical_url ?? '')) !== ''
            || $this->isManualArray($meta);
        if (! $hasExplicitOverride) {
            return $generated + ['active_source' => 'generated'];
        }

        return array_merge($generated, [
            'title' => $brand->seo_title ?: ($meta['title'] ?? $generated['title']),
            'description' => $brand->seo_description ?: ($meta['description'] ?? $generated['description']),
            'canonical' => $brand->canonical_url ?: ($meta['canonical_url'] ?? $generated['canonical']),
            'robots' => $meta['robots'] ?? $generated['robots'],
            'robots_reason' => $meta['robots_reason'] ?? 'Manual brand SEO override preserved.',
            'active_source' => 'manual',
            'confidence_level' => $meta['confidence_level'] ?? 'manual_admin_override',
            'last_updated' => $meta['last_updated'] ?? $brand->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ]);
    }

    public function isManualProductRow(ProductSeoMeta $row): bool
    {
        $metadata = is_array($row->metadata ?? null)
            ? $row->metadata
            : (json_decode((string) ($row->metadata ?? ''), true) ?: []);
        $source = strtolower((string) ($metadata['source'] ?? ''));

        return (bool) ($row->is_manual_override ?? false)
            || (bool) ($row->is_locked ?? false)
            || ($row->active_source ?? null) === 'manual'
            || ($metadata['saved_via'] ?? null) === 'admin.web'
            || (($row->confidence_level ?? null) === 'manual' && $source === '')
            || ($source !== '' && ! in_array($source, ['neogiga_seo_generator', 'catalog_seo_template'], true));
    }

    public function isManualCategory(ProductCategory $category): bool
    {
        return $this->isManualArray(is_array($category->seo_meta) ? $category->seo_meta : []);
    }

    /** @param array<string, mixed> $payload */
    public function recordVersion(string $entityType, int $entityId, array $payload, string $changeType, ?int $changedBy = null, ?int $marketplaceId = null, string $locale = 'en'): void
    {
        if (! DB::getSchemaBuilder()->hasTable('catalog_seo_versions')) {
            return;
        }

        $version = (int) DB::table('catalog_seo_versions')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('marketplace_id', $marketplaceId)
            ->max('version') + 1;

        DB::table('catalog_seo_versions')->insert([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'marketplace_id' => $marketplaceId,
            'locale' => $locale,
            'version' => $version,
            'active_source' => $payload['active_source'] ?? 'generated',
            'title' => $payload['title'] ?? null,
            'description' => $payload['description'] ?? null,
            'canonical_url' => $payload['canonical'] ?? null,
            'robots' => $payload['robots'] ?? null,
            'robots_reason' => $payload['robots_reason'] ?? null,
            'template_version' => $payload['template_version'] ?? self::TEMPLATE_VERSION,
            'change_type' => $changeType,
            'changed_by' => $changedBy,
            'source_notes' => $payload['source_notes'] ?? 'Generated from configured marketplace and catalog identity fields.',
            'confidence_level' => $payload['confidence_level'] ?? 'generated_from_catalog_fields',
            'last_updated' => now(),
            'advisory_disclaimer' => self::DISCLAIMER,
            'snapshot' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function invalidate(string $entityType, int $entityId): void
    {
        Cache::forever("seo:catalog-version:{$entityType}:{$entityId}", (string) now()->getTimestampMs());
        Cache::forever('seo:sitemap-version', (string) now()->getTimestampMs());
    }

    /** @return array<string, mixed> */
    public function rollbackVersion(string $entityType, int $entityId, int $versionRecordId, ?int $changedBy = null): array
    {
        if (! in_array($entityType, ['product', 'category'], true)) {
            throw new RuntimeException('Only product and category SEO versions can be restored.');
        }

        $target = DB::table('catalog_seo_versions')
            ->where('id', $versionRecordId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();
        if (! $target) {
            throw new RuntimeException('The requested SEO version does not belong to this catalog record.');
        }

        $snapshot = json_decode((string) ($target->snapshot ?? ''), true) ?: [];
        $restored = array_merge($snapshot, [
            'title' => $snapshot['title'] ?? $target->title,
            'description' => $snapshot['description'] ?? $target->description,
            'canonical' => $snapshot['canonical'] ?? $target->canonical_url,
            'robots' => $snapshot['robots'] ?? $target->robots,
            'robots_reason' => $snapshot['robots_reason'] ?? $target->robots_reason,
            'template_version' => $snapshot['template_version'] ?? $target->template_version,
            'active_source' => $snapshot['active_source'] ?? $target->active_source,
            'source_notes' => 'Restored from append-only SEO version #'.(int) $target->version.'.',
            'confidence_level' => $snapshot['confidence_level'] ?? $target->confidence_level ?? 'restored_version',
            'last_updated' => now()->toIso8601String(),
            'advisory_disclaimer' => self::DISCLAIMER,
        ]);

        DB::transaction(function () use ($entityType, $entityId, $target, $restored, $changedBy) {
            if ($entityType === 'product') {
                $product = Product::findOrFail($entityId);
                $record = ProductSeoMeta::firstOrNew(['product_id' => $entityId]);
                $metadata = is_array($record->metadata) ? $record->metadata : [];
                $this->recordVersion('product', $entityId, [
                    'title' => $record->meta_title ?: $record->title,
                    'description' => $record->meta_description,
                    'canonical' => $record->canonical_url,
                    'robots' => $record->robots,
                    'robots_reason' => $record->robots_reason,
                    'template_version' => $record->template_version,
                    'active_source' => $record->active_source ?: 'generated',
                    'source_notes' => 'Automatic safety snapshot created immediately before rollback.',
                    'confidence_level' => $record->confidence_level ?: 'existing_value',
                ], 'pre_rollback_snapshot', $changedBy, $target->marketplace_id, $target->locale ?: 'en');

                $isManual = ($restored['active_source'] ?? null) === 'manual';
                $record->fill([
                    'title' => $restored['title'],
                    'meta_title' => $restored['title'],
                    'meta_description' => $restored['description'],
                    'canonical_url' => $restored['canonical'],
                    'robots' => $restored['robots'] ?: 'noindex,follow',
                    'robots_reason' => $restored['robots_reason'],
                    'template_version' => $restored['template_version'],
                    'active_source' => $isManual ? 'manual' : 'generated',
                    'is_manual_override' => $isManual,
                    'modified_by' => $changedBy,
                    'confidence_level' => $restored['confidence_level'],
                    'metadata' => array_merge($metadata, [
                        'source' => 'seo_version_rollback',
                        'source_notes' => $restored['source_notes'],
                        'confidence_level' => $restored['confidence_level'],
                        'last_updated' => $restored['last_updated'],
                        'advisory_disclaimer' => self::DISCLAIMER,
                    ]),
                ]);
                $record->save();
                $product->update(['seo_meta' => array_merge(is_array($product->seo_meta) ? $product->seo_meta : [], [
                    'title' => $restored['title'],
                    'description' => $restored['description'],
                    'canonical_url' => $restored['canonical'],
                    'robots' => $restored['robots'],
                    'robots_reason' => $restored['robots_reason'],
                    'manual_override' => $isManual,
                    'active_source' => $isManual ? 'manual' : 'generated',
                    'source' => 'seo_version_rollback',
                    'last_updated' => $restored['last_updated'],
                ])]);
            } else {
                $category = ProductCategory::findOrFail($entityId);
                $current = is_array($category->seo_meta) ? $category->seo_meta : [];
                $this->recordVersion('category', $entityId, [
                    'title' => $current['title'] ?? null,
                    'description' => $current['description'] ?? null,
                    'canonical' => $current['canonical_url'] ?? null,
                    'robots' => $current['robots'] ?? null,
                    'robots_reason' => $current['robots_reason'] ?? null,
                    'template_version' => $current['template_version'] ?? null,
                    'active_source' => $current['active_source'] ?? 'generated',
                    'source_notes' => 'Automatic safety snapshot created immediately before rollback.',
                    'confidence_level' => $current['confidence_level'] ?? 'existing_value',
                ], 'pre_rollback_snapshot', $changedBy, $target->marketplace_id, $target->locale ?: 'en');

                $isManual = ($restored['active_source'] ?? null) === 'manual';
                $category->update(['seo_meta' => array_merge($current, [
                    'title' => $restored['title'],
                    'description' => $restored['description'],
                    'canonical_url' => $restored['canonical'],
                    'robots' => $restored['robots'],
                    'robots_reason' => $restored['robots_reason'],
                    'template_version' => $restored['template_version'],
                    'active_source' => $isManual ? 'manual' : 'generated',
                    'manual_override' => $isManual,
                    'source' => 'seo_version_rollback',
                    'source_notes' => $restored['source_notes'],
                    'confidence_level' => $restored['confidence_level'],
                    'last_updated' => $restored['last_updated'],
                    'advisory_disclaimer' => self::DISCLAIMER,
                ])]);
            }

            $this->recordVersion($entityType, $entityId, $restored, 'rollback', $changedBy, $target->marketplace_id, $target->locale ?: 'en');
            $this->invalidate($entityType, $entityId);
        }, 3);

        return $restored;
    }

    /** @return array{robots:string,reason:string} */
    private function productIndexability(Product $product, ?Marketplace $marketplace): array
    {
        if ($blocked = $this->marketplaceBlocked($marketplace)) {
            return $blocked;
        }

        if (! in_array((string) ($product->status ?? ''), ['active', 'approved', 'published'], true)) {
            return ['robots' => 'noindex,nofollow', 'reason' => 'The product is not published.'];
        }

        if (isset($product->visibility_status) && ! in_array($product->visibility_status, ['public', 'marketplace_only', 'quote_only'], true)) {
            return ['robots' => 'noindex,nofollow', 'reason' => 'The product visibility status is private or blocked.'];
        }

        $complete = trim((string) ($product->name ?? '')) !== ''
            && trim((string) ($product->slug ?? '')) !== ''
            && (! empty($product->category_id) || ! empty($product->mpn))
            && trim(strip_tags((string) (($product->short_description ?? '') ?: ($product->description ?? '')))) !== '';

        return $complete
            ? ['robots' => 'index,follow', 'reason' => 'Published canonical product with sufficient identity and content.']
            : ['robots' => 'noindex,follow', 'reason' => 'The public product is incomplete and remains discoverable without being indexed.'];
    }

    /** @return array{robots:string,reason:string} */
    private function categoryIndexability(ProductCategory $category, ?Marketplace $marketplace): array
    {
        if ($blocked = $this->marketplaceBlocked($marketplace)) {
            return $blocked;
        }
        if (! (bool) ($category->is_active ?? false)) {
            return ['robots' => 'noindex,nofollow', 'reason' => 'The category is inactive.'];
        }

        $complete = trim((string) ($category->name ?? '')) !== '' && trim((string) ($category->slug ?? '')) !== '';

        return $complete
            ? ['robots' => 'index,follow', 'reason' => 'Active canonical category with a complete identity.']
            : ['robots' => 'noindex,follow', 'reason' => 'The public category is incomplete.'];
    }

    /** @return array{robots:string,reason:string} */
    private function brandIndexability(ProductBrand $brand, ?Marketplace $marketplace): array
    {
        if ($blocked = $this->marketplaceBlocked($marketplace)) {
            return $blocked;
        }
        if (! (bool) ($brand->is_active ?? false) || ! (bool) ($brand->landing_page_enabled ?? true)) {
            return ['robots' => 'noindex,nofollow', 'reason' => 'The brand is inactive or its landing page is disabled.'];
        }

        return trim((string) ($brand->slug ?? '')) !== ''
            ? ['robots' => 'index,follow', 'reason' => 'Active canonical brand page; product availability is not an eligibility requirement.']
            : ['robots' => 'noindex,follow', 'reason' => 'The brand does not yet have a complete canonical identity.'];
    }

    /** @return array{robots:string,reason:string}|null */
    private function marketplaceBlocked(?Marketplace $marketplace): ?array
    {
        if (! $marketplace) {
            return null;
        }

        return ($marketplace->is_active && $marketplace->is_visible && $marketplace->indexable)
            ? null
            : ['robots' => 'noindex,nofollow', 'reason' => 'The selected marketplace is private, disabled, staging, or explicitly non-indexable.'];
    }

    /** @return array<string, mixed> */
    private function context(?Marketplace $marketplace): array
    {
        $code = strtoupper((string) ($marketplace?->code ?? 'GLOBAL'));
        $countryCode = strtoupper((string) ($marketplace?->country?->iso_code_2 ?? $marketplace?->country_iso2 ?? ''));
        $kind = $code === 'GLOBAL' ? 'global' : (($code === 'NEPAL' || $countryCode === 'NP') ? 'nepal' : 'regional');
        $country = $this->clean($marketplace?->seo_marketplace_name ?: $marketplace?->country?->name ?: $marketplace?->regional_brand_name ?: $marketplace?->name ?: 'Global');
        $name = $kind === 'global' ? 'Global' : ($kind === 'nepal' ? 'Nepal' : Str::of($country)->replaceStart('NeoGiga ', '')->toString());
        $hasLocalWarehouse = (bool) ($marketplace?->has_local_warehouse ?? false) || (bool) ($marketplace?->local_warehouse_support ?? false);

        if ($kind === 'global') {
            $fulfilment = 'Regional Warehouse';
        } elseif ($marketplace?->seo_fulfilment_phrase) {
            $fulfilment = $this->clean($marketplace->seo_fulfilment_phrase);
        } elseif ($hasLocalWarehouse) {
            $fulfilment = $this->clean($marketplace?->warehouse_display_name ?: ($kind === 'nepal' ? 'Nepal Warehouse' : $name.' Warehouse'));
        } else {
            $fulfilment = $kind === 'nepal' ? 'Regional Warehouse serving Nepal' : 'Regional Fulfilment Network';
        }

        return [
            'kind' => $kind,
            'name' => $name,
            'fulfilment' => $fulfilment,
            'site_suffix' => $this->clean($marketplace?->seo_site_suffix ?: 'NeoGiga Engineering Marketplace'),
        ];
    }

    /** @return array<string, mixed> */
    private function payload(string $title, string $description, string $canonical, array $indexability, array $context): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $indexability['robots'],
            'robots_reason' => $indexability['reason'],
            'template_version' => self::TEMPLATE_VERSION,
            'marketplace_name' => $context['name'],
            'fulfilment_phrase' => $context['fulfilment'],
            'source_notes' => 'Generated from configured marketplace and canonical catalog fields.',
            'confidence_level' => 'generated_from_catalog_fields',
            'last_updated' => now()->toIso8601String(),
            'advisory_disclaimer' => self::DISCLAIMER,
        ];
    }

    private function canonical(?Marketplace $marketplace, string $locale, string $collection, string $slug): string
    {
        $locale = preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $locale) ? $locale : 'en';
        $path = '/'.$locale.'/'.$collection.'/'.rawurlencode($slug);

        return $marketplace ? $this->urls->forMarketplace($marketplace, $path) : 'https://neogiga.com'.$path;
    }

    /** @param array<string, mixed> $meta */
    private function isManualArray(array $meta): bool
    {
        $source = strtolower((string) ($meta['source'] ?? ''));

        return (bool) ($meta['manual_override'] ?? false)
            || (bool) ($meta['locked'] ?? false)
            || ($meta['active_source'] ?? null) === 'manual'
            || ($source !== '' && ! in_array($source, ['neogiga_seo_generator', 'catalog_seo_template'], true));
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '');
    }
}
