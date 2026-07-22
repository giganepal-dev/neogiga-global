<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Marketplace\InventoryStock;

/**
 * Warehouse Bin Model
 * 
 * Represents the smallest storage unit in a warehouse (a specific bin location)
 */
class WarehouseBin extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_bins';

    protected $fillable = [
        'warehouse_shelf_id',
        'name',
        'code',
        'bin_type',
        'bin_number',
        'max_weight_kg',
        'volume_liters',
        'is_active',
        'is_locked',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'metadata' => 'array',
        'bin_number' => 'integer',
        'sort_order' => 'integer',
        'max_weight_kg' => 'decimal:2',
        'volume_liters' => 'decimal:2',
    ];

    /**
     * Bin types available
     */
    public const TYPE_STANDARD = 'standard';
    public const TYPE_DRAWER = 'drawer';
    public const TYPE_BULK = 'bulk';
    public const TYPE_COLD_STORAGE = 'cold_storage';
    public const TYPE_HAZARDOUS = 'hazardous';
    public const TYPE_HIGH_SECURITY = 'high_security';

    public static function getBinTypes(): array
    {
        return [
            self::TYPE_STANDARD => 'Standard Bin',
            self::TYPE_DRAWER => 'Drawer',
            self::TYPE_BULK => 'Bulk Storage',
            self::TYPE_COLD_STORAGE => 'Cold Storage',
            self::TYPE_HAZARDOUS => 'Hazardous Materials',
            self::TYPE_HIGH_SECURITY => 'High Security',
        ];
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(WarehouseShelf::class, 'warehouse_shelf_id');
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class, 'warehouse_bin_id');
    }

    public function stockCounts(): HasMany
    {
        return $this->hasMany(\App\Models\Inventory\StockCountItem::class, 'warehouse_bin_id');
    }

    /**
     * Get full location path
     */
    public function getFullLocationAttribute(): string
    {
        $levelNumber = $this->shelf?->level_number ?? '?';
        $rackNumber = $this->shelf?->rack?->rack_number ?? '?';
        $aisleName = $this->shelf?->rack?->aisle?->aisle_number ?? '?';
        $zoneName = $this->shelf?->rack?->aisle?->zone?->name ?? 'Unknown Zone';
        $warehouseName = $this->shelf?->rack?->aisle?->zone?->warehouse?->name ?? 'Unknown Warehouse';
        return "{$warehouseName} > {$zoneName} > A{$aisleName} > R{$rackNumber} > L{$levelNumber} > Bin {$this->bin_number}";
    }

    /**
     * Get location code for quick reference
     */
    public function getLocationCodeAttribute(): string
    {
        return sprintf(
            '%s-%s-%s-%s-%s',
            $this->shelf?->rack?->aisle?->zone?->code ?? 'UNK',
            $this->shelf?->rack?->aisle?->code ?? 'UNK',
            $this->shelf?->rack?->code ?? 'UNK',
            str_pad($this->shelf?->level_number ?? 0, 2, '0', STR_PAD_LEFT),
            str_pad($this->bin_number ?? 0, 3, '0', STR_PAD_LEFT)
        );
    }

    /**
     * Scope for active bins only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for locked bins only
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope to filter by bin type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('bin_type', $type);
    }

    /**
     * Scope to order by bin number
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('bin_number');
    }

    /**
     * Check if bin is available for new stock
     */
    public function isAvailable(): bool
    {
        return $this->is_active && !$this->is_locked;
    }
}
