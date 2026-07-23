<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerInventoryMovement extends Model
{
    protected $table = 'seller_inventory_movements';

    protected $fillable = [
        'vendor_id',
        'product_id',
        'variant_id',
        'warehouse_id',
        'seller_offer_id',
        'order_id',
        'order_item_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'available_before',
        'available_after',
        'reserved_before',
        'reserved_after',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'quantity_change' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'available_before' => 'integer',
        'available_after' => 'integer',
        'reserved_before' => 'integer',
        'reserved_after' => 'integer',
    ];

    const TYPE_OPENING_BALANCE = 'opening_balance';
    const TYPE_PURCHASE_RECEIPT = 'purchase_receipt';
    const TYPE_MANUAL_INCREASE = 'manual_increase';
    const TYPE_MANUAL_DECREASE = 'manual_decrease';
    const TYPE_RESERVATION = 'reservation';
    const TYPE_RESERVATION_RELEASE = 'reservation_release';
    const TYPE_FULFILLMENT = 'fulfillment';
    const TYPE_RETURN = 'return';
    const TYPE_DAMAGE = 'damage';
    const TYPE_QUARANTINE = 'quarantine';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_CORRECTION = 'correction';

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function sellerOffer(): BelongsTo
    {
        return $this->belongsTo(SellerOffer::class, 'seller_offer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function reference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }
        return $this->reference_type::find($this->reference_id);
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }
}
