<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerShipment extends Model
{
    protected $table = 'seller_shipments';

    protected $fillable = [
        'vendor_id',
        'vendor_order_id',
        'tracking_number',
        'carrier_name',
        'carrier_service',
        'shipping_method',
        'weight_value',
        'weight_unit',
        'length_value',
        'width_value',
        'height_value',
        'dimension_unit',
        'packages',
        'commercial_invoice_path',
        'packing_list_path',
        'certificate_of_origin_path',
        'customs_documents',
        'shipped_at',
        'estimated_delivery_at',
        'delivered_at',
        'returned_at',
        'status',
        'delivery_notes',
        'failure_reason',
        'tracking_events',
        'is_partial',
        'parent_shipment_id',
    ];

    protected $casts = [
        'packages' => 'array',
        'customs_documents' => 'array',
        'tracking_events' => 'array',
        'is_partial' => 'boolean',
        'weight_value' => 'decimal:2',
        'length_value' => 'decimal:2',
        'width_value' => 'decimal:2',
        'height_value' => 'decimal:2',
        'shipped_at' => 'datetime',
        'estimated_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_RETURNED = 'returned';
    const STATUS_FAILED = 'failed';

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class, 'vendor_order_id');
    }

    public function parentShipment(): BelongsTo
    {
        return $this->belongsTo(SellerShipment::class, 'parent_shipment_id');
    }

    public function childShipments(): HasMany
    {
        return $this->hasMany(SellerShipment::class, 'parent_shipment_id');
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWithTracking($query)
    {
        return $query->whereNotNull('tracking_number');
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isInTransit(): bool
    {
        return in_array($this->status, [self::STATUS_PICKED_UP, self::STATUS_IN_TRANSIT, self::STATUS_OUT_FOR_DELIVERY]);
    }

    public function updateTrackingEvents(array $events): void
    {
        $this->update([
            'tracking_events' => $events,
            'updated_at' => now(),
        ]);
    }

    public function markAsShipped(string $trackingNumber, ?string $carrierName = null): void
    {
        $this->update([
            'tracking_number' => $trackingNumber,
            'carrier_name' => $carrierName ?? $this->carrier_name,
            'status' => self::STATUS_PICKED_UP,
            'shipped_at' => now(),
        ]);
    }

    public function markAsDelivered(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
            'delivery_notes' => $notes,
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }
}
