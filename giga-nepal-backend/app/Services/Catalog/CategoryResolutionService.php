<?php

namespace App\Services\Catalog;

use App\Models\Marketplace\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Resolves supplier taxonomy to the canonical NeoGiga tree. Import callers are
 * deliberately unable to create root categories through this service.
 */
class CategoryResolutionService
{
    /** @var array<int, ProductCategory>|null */
    private ?array $categories = null;

    /** @return array{parent_category_id:?int,category_id:?int,confidence:float,matched_by:string,requires_review:bool,reasons:list<string>,category_name:?string,path:list<string>,source_key:string} */
    public function resolve(?string $rawCategory, array $context = []): array
    {
        $rawCategory = trim((string) $rawCategory);
        $sourceKey = (string) ($context['source_key'] ?? hash('sha256', mb_strtolower($rawCategory.'|'.(string) ($context['manufacturer_category'] ?? ''))));
        $base = $this->result(null, 0, 'unresolved', true, ['No canonical category matched.'], $sourceKey);

        if ($rawCategory === '') {
            return $this->sendToManualReview($base, $context + ['source_key' => $sourceKey]);
        }

        if ($mapped = $this->resolveByExistingMappings($sourceKey, $context)) {
            return $this->resolved($mapped, 1.0, 'existing_mapping', ['Approved supplier mapping reused.'], $sourceKey);
        }

        if (str_contains($rawCategory, ',')) {
            return $this->sendToManualReview($this->result(null, 0.2, 'ambiguous_supplier_path', true, ['Supplier category contains multiple competing classifications.'], $sourceKey), $context + ['source_key' => $sourceKey]);
        }

        if ($category = $this->findExistingCategory($rawCategory)) {
            return $this->resolved($category, 0.99, 'exact_name_or_slug', ['Exact normalized category name or slug matched.'], $sourceKey);
        }

        if ($category = $this->resolveBySynonym($rawCategory)) {
            return $this->resolved($category, 0.95, 'synonym', ['Approved category synonym matched.'], $sourceKey);
        }

        foreach (array_filter([(string) ($context['manufacturer_category'] ?? ''), (string) ($context['product_family'] ?? '')]) as $candidate) {
            if ($category = $this->findExistingCategory($candidate)) {
                return $this->resolved($category, 0.85, 'manufacturer_or_family_taxonomy', ['Manufacturer or product-family taxonomy matched an existing category.'], $sourceKey);
            }
        }

        return $this->sendToManualReview($base, $context + ['source_key' => $sourceKey, 'source_category_name' => $rawCategory]);
    }

    public function normalizeCategoryName(?string $name): string
    {
        $value = Str::ascii(mb_strtolower(trim((string) $name)));
        $value = str_replace(['&', '+'], [' and ', ' and '], $value);
        $value = preg_replace('/\([^)]*\)/', ' ', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    public function findExistingCategory(?string $name): ?ProductCategory
    {
        $needle = $this->normalizeCategoryName($name);
        if ($needle === '') {
            return null;
        }

        foreach ($this->categories() as $category) {
            if ($this->normalizeCategoryName($category->name) === $needle || $this->normalizeCategoryName($category->slug) === $needle) {
                return $category;
            }
        }

        return null;
    }

    public function findExistingParent(?string $name): ?ProductCategory
    {
        return $this->findExistingCategory($name);
    }

    public function resolveBySynonym(?string $name): ?ProductCategory
    {
        $normalized = $this->normalizeCategoryName($name);
        if ($normalized === '') {
            return null;
        }

        if (Schema::hasTable('category_synonyms')) {
            $categoryId = DB::table('category_synonyms')->where('normalized_synonym', $normalized)->value('category_id');
            if ($categoryId && ($category = $this->categoryById((int) $categoryId))) {
                return $category;
            }
        }

        $slug = config('category_resolution.synonyms.'.$normalized);

        return $slug ? $this->categoryBySlug((string) $slug) : null;
    }

    public function resolveByProductAttributes(array $attributes, array $context = []): ?ProductCategory
    {
        foreach (['category', 'subcategory', 'product_family', 'family', 'type'] as $key) {
            if (($category = $this->findExistingCategory($attributes[$key] ?? null)) || ($category = $this->resolveBySynonym($attributes[$key] ?? null))) {
                return $category;
            }
        }

        return null;
    }

    public function resolveByManufacturerTaxonomy(?string $name): ?ProductCategory
    {
        return $this->findExistingCategory($name) ?: $this->resolveBySynonym($name);
    }

    public function resolveByExistingMappings(string $sourceKey, array $context = []): ?ProductCategory
    {
        if (! Schema::hasTable('supplier_category_mappings') || empty($context['catalog_source_id'])) {
            return null;
        }

        $id = DB::table('supplier_category_mappings')
            ->where('catalog_source_id', (int) $context['catalog_source_id'])
            ->where('source_category_key', $sourceKey)
            ->whereIn('mapping_status', ['approved_manual', 'auto_mapped'])
            ->value('category_id');

        return $id ? $this->categoryById((int) $id) : null;
    }

    /**
     * This is intentionally admin-only. Import and queue callers must not pass
     * actor_type=admin, so they can never create a root or speculative child.
     */
    public function createChildUnderExistingParent(string $name, int $parentId, array $context = []): ProductCategory
    {
        if (($context['actor_type'] ?? null) !== 'admin' || ! ($context['allow_create_child'] ?? false)) {
            throw new \LogicException('Only an explicit admin action may create a category child.');
        }
        $parent = $this->categoryById($parentId);
        if (! $parent) {
            throw new \InvalidArgumentException('Category parent does not exist.');
        }
        if ($existing = $this->findExistingCategory($name)) {
            return $existing;
        }

        $category = ProductCategory::create([
            'parent_id' => $parent->id,
            'name' => trim($name),
            'slug' => $this->availableChildSlug($name),
            'description' => $context['description'] ?? null,
            'is_active' => (bool) ($context['is_active'] ?? false),
            'is_featured' => false,
        ]);
        $this->categories = null;
        $this->auditChildCreation($category, $parent, $context);

        return $category;
    }

    /** @param array<string, mixed> $result @param array<string, mixed> $context @return array{parent_category_id:?int,category_id:?int,confidence:float,matched_by:string,requires_review:bool,reasons:list<string>,category_name:?string,path:list<string>,source_key:string} */
    public function sendToManualReview(array $result, array $context = []): array
    {
        if (! Schema::hasTable('category_import_reviews')) {
            return $result;
        }

        $sourceId = isset($context['catalog_source_id']) ? (int) $context['catalog_source_id'] : null;
        $sourceKey = (string) ($context['source_key'] ?? $result['source_key']);
        $query = DB::table('category_import_reviews')->where('source_key', $sourceKey);
        $sourceId === null ? $query->whereNull('catalog_source_id') : $query->where('catalog_source_id', $sourceId);
        $existing = $query->first();
        $data = [
            'product_id' => $context['product_id'] ?? null,
            'catalog_source_id' => $sourceId,
            'proposed_parent_id' => $result['parent_category_id'],
            'proposed_category_id' => $result['category_id'],
            'import_batch_id' => $context['import_batch_id'] ?? null,
            'source_name' => $context['source_name'] ?? null,
            'source_key' => $sourceKey,
            'source_category_name' => $context['source_category_name'] ?? null,
            'source_category_path' => $context['source_category_path'] ?? null,
            'manufacturer_name' => $context['manufacturer_name'] ?? null,
            'mpn' => $context['mpn'] ?? null,
            'confidence' => $result['confidence'],
            'matched_by' => $result['matched_by'],
            'reasons' => json_encode($result['reasons']),
            'context' => json_encode($context),
            'status' => 'pending_review',
            'updated_at' => now(),
        ];
        if ($existing) {
            DB::table('category_import_reviews')->where('id', $existing->id)->update($data);
        } else {
            DB::table('category_import_reviews')->insert($data + ['created_at' => now()]);
        }

        return $result;
    }

    /** @return array<int, ProductCategory> */
    private function categories(): array
    {
        return $this->categories ??= ProductCategory::query()->orderBy('id')->get()->all();
    }

    private function categoryById(int $id): ?ProductCategory
    {
        foreach ($this->categories() as $category) {
            if ($category->id === $id) {
                return $category;
            }
        }

        return null;
    }

    private function categoryBySlug(string $slug): ?ProductCategory
    {
        foreach ($this->categories() as $category) {
            if ($category->slug === $slug) {
                return $category;
            }
        }

        return null;
    }

    /** @return array{parent_category_id:?int,category_id:?int,confidence:float,matched_by:string,requires_review:bool,reasons:list<string>,category_name:?string,path:list<string>,source_key:string} */
    private function resolved(ProductCategory $category, float $confidence, string $matchedBy, array $reasons, string $sourceKey): array
    {
        return $this->result($category, $confidence, $matchedBy, false, $reasons, $sourceKey);
    }

    /** @return array{parent_category_id:?int,category_id:?int,confidence:float,matched_by:string,requires_review:bool,reasons:list<string>,category_name:?string,path:list<string>,source_key:string} */
    private function result(?ProductCategory $category, float $confidence, string $matchedBy, bool $review, array $reasons, string $sourceKey): array
    {
        return [
            'parent_category_id' => $category?->parent_id,
            'category_id' => $category?->id,
            'confidence' => $confidence,
            'matched_by' => $matchedBy,
            'requires_review' => $review,
            'reasons' => $reasons,
            'category_name' => $category?->name,
            'path' => $category ? $this->path($category) : [],
            'source_key' => $sourceKey,
        ];
    }

    /** @return list<string> */
    private function path(ProductCategory $category): array
    {
        $path = [];
        $node = $category;
        $guard = 0;
        while ($node && $guard++ < 12) {
            array_unshift($path, $node->name);
            $node = $node->parent_id ? $this->categoryById((int) $node->parent_id) : null;
        }

        return $path;
    }

    private function availableChildSlug(string $name): string
    {
        $base = Str::limit(Str::slug($name), 180, '');
        $slug = $base;
        $suffix = 2;
        while ($this->categoryBySlug($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function auditChildCreation(ProductCategory $category, ProductCategory $parent, array $context): void
    {
        if (! Schema::hasTable('category_creation_audits')) {
            return;
        }
        DB::table('category_creation_audits')->insert([
            'category_id' => $category->id,
            'parent_category_id' => $parent->id,
            'product_id' => $context['product_id'] ?? null,
            'import_batch_id' => $context['import_batch_id'] ?? null,
            'source_name' => $context['source_name'] ?? 'admin',
            'source_url' => $context['source_url'] ?? null,
            'source_file' => $context['source_file'] ?? null,
            'source_page_url' => $context['source_page_url'] ?? null,
            'downloaded_at' => $context['downloaded_at'] ?? null,
            'imported_at' => now(),
            'data_year' => $context['data_year'] ?? null,
            'license_note' => $context['license_note'] ?? null,
            'confidence_level' => $context['confidence_level'] ?? 'manual',
            'original_raw_value' => $context['original_raw_value'] ?? $category->name,
            'normalized_value' => $this->normalizeCategoryName($category->name),
            'created_by_type' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
