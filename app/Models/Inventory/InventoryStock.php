<?php

namespace App\Models\Inventory;

use App\Models\BaseModel;
use App\Models\Product\Product;
use App\Models\Marketplace\Marketplace;
use App\Models\Vendor\Vendor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStock extends BaseModel
{
    protected $table = 'inventory_stocks';

    protected $fillable = [
        'product_id', 'variant_id', 'warehouse_id', 'marketplace_id', 'vendor_id',
        'sku_global', 'sku_regional', 'sku_vendor',
        'quantity_on_hand', 'quantity_available', 'quantity_reserved',
        'quantity_damaged', 'quantity_incoming', 'reorder_point', 'reorder_quantity',
        'bin_location', 'batch_number', 'serial_number', 'expiry_date',
        'last_counted_at', 'metadata'
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'quantity_available' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_damaged' => 'integer',
        'quantity_incoming' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'expiry_date' => 'date',
        'last_counted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(\App\Models\Product\ProductVariant::class, 'variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'stock_id');
    }

    public function reservedStocks(): HasMany
    {
        return $this->hasMany(ReservedStock::class, 'stock_id');
    }
}
