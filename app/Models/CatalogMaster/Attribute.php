<?php

namespace App\Models\CatalogMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attributes';

    protected $fillable = [
        'code',
        'name',
        'description',
        'data_type',
        'default_unit_id',
        'unit_family',
        'is_filterable',
        'is_comparable',
        'is_searchable',
        'is_required',
        'visible_on_product_page',
        'allow_range_values',
        'sort_order',
        'display_group',
        'validation_rules',
        'extra_config',
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
        'is_comparable' => 'boolean',
        'is_searchable' => 'boolean',
        'is_required' => 'boolean',
        'visible_on_product_page' => 'boolean',
        'allow_range_values' => 'boolean',
        'sort_order' => 'integer',
        'validation_rules' => 'array',
        'extra_config' => 'array',
    ];

    // Data type constants
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_DECIMAL = 'decimal';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_OPTION = 'option';
    public const TYPE_MULTI_OPTION = 'multi_option';
    public const TYPE_RANGE = 'range';
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';

    public function defaultUnit(): BelongsTo
    {
        return $this->belongsTo(AttributeUnit::class, 'default_unit_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }

    public function groupMembers(): HasMany
    {
        return $this->hasMany(AttributeGroupMember::class);
    }

    public function groups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(AttributeGroup::class, 'attribute_group_members')
            ->withPivot('position')
            ->orderBy('pivot_position');
    }

    public function categoryAttributes(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class);
    }

    public function externalMappings(): HasMany
    {
        return $this->hasMany(ExternalAttributeMapping::class);
    }

    public function productValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }

    /**
     * Generate code from name
     */
    public static function generateCode(string $name): string
    {
        return str()->snake(strtolower($name));
    }

    /**
     * Check if attribute accepts numeric values
     */
    public function isNumeric(): bool
    {
        return in_array($this->data_type, [self::TYPE_INTEGER, self::TYPE_DECIMAL, self::TYPE_RANGE]);
    }

    /**
     * Check if attribute has predefined options
     */
    public function hasOptions(): bool
    {
        return in_array($this->data_type, [self::TYPE_OPTION, self::TYPE_MULTI_OPTION]);
    }
}
