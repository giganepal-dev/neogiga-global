<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Warehouse Rack Model
 * 
 * Represents a rack within a warehouse aisle
 */
class WarehouseRack extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_racks';

    protected $fillable = [
        'warehouse_aisle_id',
        'name',
        'code',
        'rack_number',
        'levels',
        'max_weight_kg',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'rack_number' => 'integer',
        'levels' => 'integer',
        'sort_order' => 'integer',
        'max_weight_kg' => 'decimal:2',
    ];

    public function aisle(): BelongsTo
    {
        return $this->belongsTo(WarehouseAisle::class, 'warehouse_aisle_id');
    }

    public function shelves(): HasMany
    {
        return $this->hasMany(WarehouseShelf::class, 'warehouse_rack_id');
    }

    public function activeShelves(): HasMany
    {
        return $this->hasMany(WarehouseShelf::class, 'warehouse_rack_id')->where('is_active', true);
    }

    /**
     * Get full location path
     */
    public function getFullLocationAttribute(): string
    {
        $aisleName = $this->aisle?->aisle_number ?? '?';
        $zoneName = $this->aisle?->zone?->name ?? 'Unknown Zone';
        $warehouseName = $this->aisle?->zone?->warehouse?->name ?? 'Unknown Warehouse';
        return "{$warehouseName} > {$zoneName} > Aisle {$aisleName} > Rack {$this->rack_number}";
    }

    /**
     * Scope for active racks only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by rack number
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('rack_number');
    }
}
