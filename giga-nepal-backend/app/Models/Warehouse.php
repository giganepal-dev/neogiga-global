<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'code',
        'region',
        'country',
        'city',
        'address',
        'postal_code',
        'latitude',
        'longitude',
        'timezone',
        'currency_code',
        'status',
        'contact_info',
        'operating_hours',
        'capacity_units',
        'current_stock_units',
        'is_distribution_center',
        'is_fulfillment_center',
        'allows_cross_border',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'contact_info' => 'array',
        'operating_hours' => 'array',
        'capacity_units' => 'integer',
        'current_stock_units' => 'integer',
        'is_distribution_center' => 'boolean',
        'is_fulfillment_center' => 'boolean',
        'allows_cross_border' => 'boolean',
    ];

    /**
     * Get the products in this warehouse
     */
    public function products(): HasMany
    {
        return $this->hasMany(WarehouseProduct::class);
    }

    /**
     * Get outbound shipments from this warehouse
     */
    public function outboundShipments(): HasMany
    {
        return $this->hasMany(WarehouseShipment::class, 'from_warehouse_id');
    }

    /**
     * Get inbound shipments to this warehouse
     */
    public function inboundShipments(): HasMany
    {
        return $this->hasMany(WarehouseShipment::class, 'to_warehouse_id');
    }

    /**
     * Scope for distribution centers
     */
    public function scopeDistributionCenters($query)
    {
        return $query->where('is_distribution_center', true);
    }

    /**
     * Scope for active warehouses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for Middle East region
     */
    public function scopeMiddleEast($query)
    {
        return $query->where('region', 'Middle East');
    }

    /**
     * Check if warehouse is in UAE
     */
    public function isUAE(): bool
    {
        return $this->country === 'United Arab Emirates' || $this->country === 'UAE';
    }

    /**
     * Get available capacity
     */
    public function getAvailableCapacityAttribute(): int
    {
        return max(0, $this->capacity_units - $this->current_stock_units);
    }

    /**
     * Check if warehouse can accept cross-border shipments
     */
    public function canAcceptCrossBorder(): bool
    {
        return $this->allows_cross_border && $this->status === 'active';
    }
}
