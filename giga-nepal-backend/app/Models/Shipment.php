<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'shipment_number',
        'order_id',
        'marketplace_id',
        'vendor_id',
        'warehouse_id',
        'carrier_name',
        'tracking_number',
        'status',
        'shipped_at',
        'estimated_delivery',
        'delivered_at',
        'returned_at',
        'shipping_address_name',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_region',
        'shipping_postal_code',
        'shipping_country',
        'shipping_phone',
        'shipping_email',
        'weight',
        'weight_unit',
        'dimensions',
        'notes',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'estimated_delivery' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
        'dimensions' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class, 'marketplace_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Vendor::class, 'vendor_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Warehouse::class, 'warehouse_id');
    }

    public function tracking(): HasMany
    {
        return $this->hasMany(ShipmentTracking::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTrackingNumber($query, $trackingNumber)
    {
        return $query->where('tracking_number', $trackingNumber);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }
}
