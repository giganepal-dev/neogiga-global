<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Warehouse Aisle Model
 * 
 * Represents an aisle within a warehouse zone
 */
class WarehouseAisle extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_aisles';

    protected $fillable = [
        'warehouse_zone_id',
        'name',
        'code',
        'aisle_number',
        'length_meters',
        'width_meters',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'aisle_number' => 'integer',
        'sort_order' => 'integer',
        'length_meters' => 'decimal:2',
        'width_meters' => 'decimal:2',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'warehouse_zone_id');
    }

    public function racks(): HasMany
    {
        return $this->hasMany(WarehouseRack::class, 'warehouse_aisle_id');
    }

    public function activeRacks(): HasMany
    {
        return $this->hasMany(WarehouseRack::class, 'warehouse_aisle_id')->where('is_active', true);
    }

    /**
     * Get full location path
     */
    public function getFullLocationAttribute(): string
    {
        $zoneName = $this->zone?->name ?? 'Unknown Zone';
        $warehouseName = $this->zone?->warehouse?->name ?? 'Unknown Warehouse';
        return "{$warehouseName} > {$zoneName} > Aisle {$this->aisle_number}";
    }

    /**
     * Scope for active aisles only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by aisle number
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('aisle_number');
    }
}
