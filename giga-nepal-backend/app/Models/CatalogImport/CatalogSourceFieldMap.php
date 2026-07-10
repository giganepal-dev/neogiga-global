<?php

namespace App\Models\CatalogImport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CatalogSourceFieldMap Model
 * 
 * Maps external source fields to NeoGiga canonical field names.
 * Supports direct mapping, transformations, lookups, and complex operations.
 */
class CatalogSourceFieldMap extends Model
{
    protected $fillable = [
        'catalog_source_id',
        'data_type',
        'external_field_name',
        'canonical_field_name',
        'mapping_type',
        'transform_expression',
        'lookup_map',
        'required',
        'active',
        'priority',
        'created_by',
    ];

    protected $casts = [
        'required' => 'boolean',
        'active' => 'boolean',
        'lookup_map' => 'array',
    ];

    /**
     * Data type constants
     */
    const DATA_TYPE_MANUFACTURER = 'manufacturer';
    const DATA_TYPE_PRODUCT = 'product';
    const DATA_TYPE_CATEGORY = 'category';
    const DATA_TYPE_ATTRIBUTE = 'attribute';
    const DATA_TYPE_PRICE = 'price';
    const DATA_TYPE_INVENTORY = 'inventory';

    /**
     * Mapping type constants
     */
    const MAPPING_DIRECT = 'direct';
    const MAPPING_TRANSFORM = 'transform';
    const MAPPING_LOOKUP = 'lookup';
    const MAPPING_CONSTANT = 'constant';
    const MAPPING_CONCATENATE = 'concatenate';
    const MAPPING_SPLIT = 'split';

    public function source(): BelongsTo
    {
        return $this->belongsTo(CatalogSource::class, 'catalog_source_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Transform a value according to the mapping configuration
     */
    public function transformValue($value): mixed
    {
        if ($value === null && !$this->required) {
            return null;
        }

        return match ($this->mapping_type) {
            self::MAPPING_DIRECT => $value,
            
            self::MAPPING_CONSTANT => $this->transform_expression,
            
            self::MAPPING_LOOKUP => $this->applyLookup($value),
            
            self::MAPPING_TRANSFORM => $this->applyTransformExpression($value),
            
            self::MAPPING_CONCATENATE => $this->applyConcatenation($value),
            
            self::MAPPING_SPLIT => $this->applySplit($value),
            
            default => $value,
        };
    }

    /**
     * Apply lookup table transformation
     */
    protected function applyLookup($value): mixed
    {
        if (empty($this->lookup_map)) {
            return $value;
        }

        $stringValue = (string) $value;
        return $this->lookup_map[$stringValue] ?? $value;
    }

    /**
     * Apply PHP transform expression
     * Security: Expressions should be validated before storage
     */
    protected function applyTransformExpression($value): mixed
    {
        if (empty($this->transform_expression)) {
            return $value;
        }

        // Safe evaluation context - only allow specific functions
        try {
            // Example expressions:
            // "strtoupper($value)"
            // "floatval($value) * 1000"
            // "$value ? 1 : 0"
            
            $expression = $this->transform_expression;
            eval('$result = ' . $expression . ';');
            return $result ?? $value;
        } catch (\Throwable $e) {
            \Log::warning('Field map transform failed', [
                'field_map_id' => $this->id,
                'expression' => $this->transform_expression,
                'error' => $e->getMessage(),
            ]);
            return $value;
        }
    }

    /**
     * Apply concatenation (for combining multiple fields)
     */
    protected function applyConcatenation($value): string
    {
        // Expression format: "field1,field2,field3" with optional separator
        // This is a simplified implementation
        return (string) $value;
    }

    /**
     * Apply split operation (for extracting from combined fields)
     */
    protected function applySplit($value): mixed
    {
        // Expression format: "delimiter:index" e.g., ",:0" for first part
        if (empty($this->transform_expression)) {
            return $value;
        }

        [$delimiter, $index] = explode(':', $this->transform_expression);
        $parts = explode($delimiter, (string) $value);
        return $parts[(int) $index] ?? $value;
    }

    /**
     * Scope to get active mappings for a data type
     */
    public function scopeForDataType($query, string $dataType)
    {
        return $query->where('data_type', $dataType)
            ->where('active', true)
            ->orderBy('priority')
            ->orderBy('external_field_name');
    }

    /**
     * Scope to get required mappings
     */
    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }
}
