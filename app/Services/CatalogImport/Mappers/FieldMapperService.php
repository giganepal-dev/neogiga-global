<?php

namespace NeoGiga\CatalogImport\Services\Mapping;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NeoGiga\CatalogImport\Models\CatalogSourceFieldMap;
use NeoGiga\CatalogImport\Models\Category;
use NeoGiga\CatalogImport\Models\Manufacturer;
use NeoGiga\CatalogImport\Models\Attribute;

class FieldMapperService
{
    /**
     * Resolve manufacturer from import data
     * Priority: External ID > Normalized Name > Alias Match
     */
    public function resolveManufacturer(string $name, ?string $externalId = null, ?int $sourceId = null): ?Manufacturer
    {
        // 1. Try external ID match first (most reliable)
        if ($externalId && $sourceId) {
            $manufacturer = Manufacturer::whereHas('externalIds', function ($q) use ($externalId, $sourceId) {
                $q->where('external_id', $externalId)
                  ->where('catalog_source_id', $sourceId);
            })->first();
            
            if ($manufacturer) {
                return $manufacturer;
            }
        }

        // 2. Try exact name match (normalized)
        $normalizedName = $this->normalizeManufacturerName($name);
        
        $manufacturer = Manufacturer::where('normalized_name', $normalizedName)->first();
        if ($manufacturer) {
            return $manufacturer;
        }

        // 3. Try alias match
        $manufacturer = Manufacturer::whereHas('aliases', function ($q) use ($name) {
            $q->where('alias', $this->normalizeManufacturerName($name));
        })->first();
        
        if ($manufacturer) {
            return $manufacturer;
        }

        // 4. Fuzzy match (optional, returns null if confidence too low)
        return $this->fuzzyMatchManufacturer($name);
    }

    /**
     * Normalize manufacturer name for matching
     */
    public function normalizeManufacturerName(string $name): string
    {
        $normalized = strtolower(trim($name));
        
        // Remove common suffixes
        $suffixes = [' inc', ' ltd', ' llc', ' corp', ' corporation', ' co', ' company'];
        foreach ($suffixes as $suffix) {
            if (Str::endsWith($normalized, $suffix)) {
                $normalized = substr($normalized, 0, strlen($normalized) - strlen($suffix));
            }
        }
        
        // Remove punctuation and extra spaces
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }

    /**
     * Resolve category from import path or name
     */
    public function resolveCategory(array $categoryPath): ?Category
    {
        // Try to find by full path slug
        $slug = implode('/', array_map(function ($part) {
            return Str::slug($part);
        }, $categoryPath));
        
        $category = Category::where('slug', $slug)->first();
        if ($category) {
            return $category;
        }

        // Try to find by leaf category name
        $leafName = end($categoryPath);
        $category = Category::where('name', $leafName)->first();
        
        return $category;
    }

    /**
     * Get or create attribute by name and category
     */
    public function resolveAttribute(string $name, ?int $categoryId = null): ?Attribute
    {
        $normalizedName = Str::slug(strtolower(trim($name)));
        
        // Try code match first
        $attribute = Attribute::where('code', $normalizedName)->first();
        if ($attribute) {
            return $attribute;
        }

        // Try name match
        $attribute = Attribute::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($attribute) {
            return $attribute;
        }

        // If not found, return null for review queue
        return null;
    }

    /**
     * Apply saved field mappings from source configuration
     */
    public function applySourceMapping(int $sourceId, array $rawData): array
    {
        $mappings = CatalogSourceFieldMap::where('catalog_source_id', $sourceId)
            ->where('active', true)
            ->get()
            ->keyBy('source_field');

        $mapped = [];
        foreach ($rawData as $sourceField => $value) {
            if (isset($mappings[$sourceField])) {
                $map = $mappings[$sourceField];
                $targetField = $map->target_field;
                
                // Apply transformation if defined
                if ($map->transformation_rule) {
                    $value = $this->applyTransformation($value, $map->transformation_rule);
                }
                
                $mapped[$targetField] = $value;
            } else {
                $mapped[$sourceField] = $value;
            }
        }

        return $mapped;
    }

    /**
     * Apply transformation rules to field values
     */
    protected function applyTransformation($value, string $rule)
    {
        // Simple rule engine - extend as needed
        switch ($rule) {
            case 'uppercase':
                return strtoupper($value);
            case 'lowercase':
                return strtolower($value);
            case 'trim':
                return trim($value);
            case 'boolean_yes_no':
                return in_array(strtolower($value), ['yes', 'true', '1']);
            case 'decimal_comma_to_dot':
                return str_replace(',', '.', $value);
            default:
                return $value;
        }
    }

    /**
     * Fuzzy manufacturer match with confidence score
     */
    protected function fuzzyMatchManufacturer(string $name): ?Manufacturer
    {
        $normalizedName = $this->normalizeManufacturerName($name);
        $words = explode(' ', $normalizedName);
        
        // Simple Levenshtein-based matching for close names
        $candidates = Manufacturer::query()
            ->where('status', 'active')
            ->limit(5)
            ->get();

        $bestMatch = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $similarity = similar_text($normalizedName, $candidate->normalized_name, $percent);
            
            if ($percent > 85 && $percent > $bestScore) {
                $bestScore = $percent;
                $bestMatch = $candidate;
            }
        }

        // Only return if confidence is high enough
        return $bestScore >= 85 ? $bestMatch : null;
    }

    /**
     * Generate mapping candidates for review queue
     */
    public function generateMappingCandidates(string $sourceValue, string $targetType): array
    {
        $candidates = [];
        
        if ($targetType === 'manufacturer') {
            $normalizedName = $this->normalizeManufacturerName($sourceValue);
            
            $candidates = Manufacturer::query()
                ->where('normalized_name', 'LIKE', "%{$normalizedName}%")
                ->orWhereHas('aliases', function ($q) use ($sourceValue) {
                    $q->where('alias', 'LIKE', "%{$sourceValue}%");
                })
                ->limit(10)
                ->get(['id', 'display_name', 'normalized_name'])
                ->toArray();
        } elseif ($targetType === 'category') {
            $candidates = Category::query()
                ->where('name', 'LIKE', "%{$sourceValue}%")
                ->limit(10)
                ->get(['id', 'name', 'slug'])
                ->toArray();
        }

        return $candidates;
    }
}
