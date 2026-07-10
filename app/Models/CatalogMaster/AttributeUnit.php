<?php

namespace App\Models\CatalogMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeUnit extends Model
{
    use HasFactory;

    protected $table = 'attribute_units';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'unit_family',
        'is_base_unit',
        'conversion_factor',
        'conversion_offset',
        'description',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'conversion_factor' => 'decimal:10',
        'conversion_offset' => 'decimal:10',
    ];

    // Common unit families
    public const FAMILY_VOLTAGE = 'voltage';
    public const FAMILY_CURRENT = 'current';
    public const FAMILY_RESISTANCE = 'resistance';
    public const FAMILY_CAPACITANCE = 'capacitance';
    public const FAMILY_INDUCTANCE = 'inductance';
    public const FAMILY_TEMPERATURE = 'temperature';
    public const FAMILY_FREQUENCY = 'frequency';
    public const FAMILY_LENGTH = 'length';
    public const FAMILY_MASS = 'mass';

    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(AttributeUnitConversion::class, 'from_unit_id');
    }

    public function conversionsTo(): HasMany
    {
        return $this->hasMany(AttributeUnitConversion::class, 'to_unit_id');
    }

    public function productValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_unit_id');
    }

    public function scopeFamily($query, string $family)
    {
        return $query->where('unit_family', $family);
    }

    public function scopeBaseUnits($query)
    {
        return $query->where('is_base_unit', true);
    }

    /**
     * Convert a value from this unit to the base unit
     */
    public function convertToBase(float $value): float
    {
        return ($value * (float) $this->conversion_factor) + (float) $this->conversion_offset;
    }

    /**
     * Convert a value from base unit to this unit
     */
    public function convertFromBase(float $baseValue): float
    {
        return ($baseValue - (float) $this->conversion_offset) / (float) $this->conversion_factor;
    }

    /**
     * Convert a value from this unit to another unit
     */
    public function convertTo(float $value, AttributeUnit $targetUnit): float
    {
        if ($this->unit_family !== $targetUnit->unit_family) {
            throw new \InvalidArgumentException("Cannot convert between different unit families");
        }

        $baseValue = $this->convertToBase($value);
        return $targetUnit->convertFromBase($baseValue);
    }
}
