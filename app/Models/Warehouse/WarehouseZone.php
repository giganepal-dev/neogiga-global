<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseZone extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'warehouse_zones';

    protected $fillable = [
        'warehouse_id',
        'name',
        'code',
        'description',
        'type',
        'temperature_min',
        'temperature_max',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'temperature_min' => 'decimal:2',
        'temperature_max' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    const TYPE_STORAGE = 'storage';
    const TYPE_RECEIVING = 'receiving';
    const TYPE_SHIPPING = 'shipping';
    const TYPE_QUARANTINE = 'quarantine';
    const TYPE_COLD_STORAGE = 'cold_storage';
    const TYPE_HAZMAT = 'hazmat';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function aisles(): HasMany
    {
        return $this->hasMany(WarehouseAisle::class, 'zone_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function getLocationPathAttribute(): string
    {
        return "{$this->warehouse->name} > {$this->name}";
    }
}
