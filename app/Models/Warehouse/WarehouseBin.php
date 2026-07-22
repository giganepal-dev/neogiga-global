<?php

namespace App\Models\Warehouse;

use App\Models\Marketplace\ProductWarehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseBin extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'warehouse_bins';

    protected $fillable = [
        'shelf_id',
        'name',
        'code',
        'sequence',
        'type',
        'capacity_volume_m3',
        'max_weight_kg',
        'max_items',
        'is_active',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'capacity_volume_m3' => 'decimal:4',
        'max_weight_kg' => 'decimal:2',
        'max_items' => 'integer',
        'is_active' => 'boolean',
    ];

    const TYPE_STANDARD = 'standard';
    const TYPE_SMALL_PARTS = 'small_parts';
    const TYPE_PALLET = 'pallet';
    const TYPE_BULK = 'bulk';
    const TYPE_COLD = 'cold';
    const TYPE_HAZMAT = 'hazmat';

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(WarehouseShelf::class, 'shelf_id');
    }

    public function productWarehouses(): HasMany
    {
        return $this->hasMany(ProductWarehouse::class, 'bin_id');
    }

    public function stockCountItems(): HasMany
    {
        return $this->hasMany(StockCountItem::class, 'bin_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForShelf($query, $shelfId)
    {
        return $query->where('shelf_id', $shelfId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getFullLocationPathAttribute(): string
    {
        return "{$this->shelf->rack->aisle->zone->warehouse->name} > {$this->shelf->rack->aisle->zone->name} > {$this->shelf->rack->aisle->name} > {$this->shelf->rack->name} > {$this->shelf->name} > {$this->name}";
    }

    public function getFormattedCodeAttribute(): string
    {
        return sprintf(
            '%s-%s-%s-%s-%s',
            $this->shelf->rack->aisle->zone->code,
            $this->shelf->rack->aisle->code,
            $this->shelf->rack->code,
            $this->shelf->code,
            $this->code
        );
    }
}
