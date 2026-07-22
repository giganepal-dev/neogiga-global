<?php

namespace App\Models\Warehouse;

use App\Models\Marketplace\Warehouse as BaseWarehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Warehouse Zone Model
 * 
 * Represents a zone within a warehouse (e.g., Receiving, Storage, Picking, Packing, Dispatch)
 */
class WarehouseZone extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_zones';

    protected $fillable = [
        'warehouse_id',
        'name',
        'code',
        'zone_type',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Zone types available
     */
    public const TYPE_RECEIVING = 'receiving';
    public const TYPE_STORAGE = 'storage';
    public const TYPE_PICKING = 'picking';
    public const TYPE_PACKING = 'packing';
    public const TYPE_DISPATCH = 'dispatch';
    public const TYPE_QUARANTINE = 'quarantine';
    public const TYPE_DAMAGED = 'damaged';

    public static function getZoneTypes(): array
    {
        return [
            self::TYPE_RECEIVING => 'Receiving Area',
            self::TYPE_STORAGE => 'Storage Zone',
            self::TYPE_PICKING => 'Picking Zone',
            self::TYPE_PACKING => 'Packing Zone',
            self::TYPE_DISPATCH => 'Dispatch Zone',
            self::TYPE_QUARANTINE => 'Quarantine Area',
            self::TYPE_DAMAGED => 'Damaged Goods Area',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(BaseWarehouse::class, 'warehouse_id');
    }

    public function aisles(): HasMany
    {
        return $this->hasMany(WarehouseAisle::class, 'warehouse_zone_id');
    }

    public function activeAisles(): HasMany
    {
        return $this->hasMany(WarehouseAisle::class, 'warehouse_zone_id')->where('is_active', true);
    }

    /**
     * Get full location path
     */
    public function getFullLocationAttribute(): string
    {
        return "{$this->warehouse->name} > {$this->name}";
    }

    /**
     * Scope to filter by zone type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('zone_type', $type);
    }

    /**
     * Scope for active zones only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
