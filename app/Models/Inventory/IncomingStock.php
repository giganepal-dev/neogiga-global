<?php

namespace App\Models\Inventory;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingStock extends BaseModel
{
    protected $table = 'incoming_stocks';

    protected $fillable = [
        'stock_id', 'warehouse_id', 'product_id', 'vendor_id',
        'quantity', 'expected_date', 'received_date', 'status',
        'purchase_order_reference', 'supplier_invoice', 'notes', 'metadata'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expected_date' => 'date',
        'received_date' => 'datetime',
        'metadata' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    public function stock(): BelongsTo
    {
        return $this->belongsTo(InventoryStock::class, 'stock_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product\Product::class);
    }

    public function vendor()
    {
        return $this->belongsTo(\App\Models\Vendor\Vendor::class);
    }
}
