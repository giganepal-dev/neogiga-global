<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Warehouse Shelf Model
 * 
 * Represents a shelf level on a warehouse rack
 */
class WarehouseShelf extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_shelves';

    protected $fillable = [
        'warehouse_rack_id',
        'name',
        'code',
        'level_number',
        'max_weight_kg',
        'height_cm',
        'depth_cm',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'level_number' => 'integer',
        'sort_order' => 'integer',
        'max_weight_kg' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'depth_cm' => 'decimal:2',
    ];

    public function rack(): BelongsTo
    {
        return $this->belongsTo(WarehouseRack::class, 'warehouse_rack_id');
    }

    public function bins(): HasMany
    {
        return $this->hasMany(WarehouseBin::class, 'warehouse_shelf_id');
    }

    public function activeBins(): HasMany
    {
        return $this->hasMany(WarehouseBin::class, 'warehouse_shelf_id')->where('is_active', true);
    }

    /**
     * Get full location path
     */
    public function getFullLocationAttribute(): string
    {
        $rackNumber = $this->rack?->rack_number ?? '?';
        $aisleName = $this->rack?->aisle?->aisle_number ?? '?';
        $zoneName = $this->rack?->aisle?->zone?->name ?? 'Unknown Zone';
        $warehouseName = $this->rack?->aisle?->zone?->warehouse?->name ?? 'Unknown Warehouse';
        return "{$warehouseName} > {$zoneName} > Aisle {$aisleName} > Rack {$rackNumber} > Level {$this->level_number}";
    }

    /**
     * Scope for active shelves only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by level number
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level_number');
    }
}
