<?php

namespace App\Models\Inventory;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorInventory extends BaseModel
{
    protected $table = 'vendor_inventory';

    protected $fillable = [
        'vendor_id', 'product_id', 'warehouse_id',
        'sku_vendor', 'quantity_available', 'quantity_reserved',
        'is_active', 'last_synced_at', 'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity_available' => 'integer',
        'quantity_reserved' => 'integer',
        'last_synced_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function vendor()
    {
        return $this->belongsTo(\App\Models\Vendor\Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product\Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
