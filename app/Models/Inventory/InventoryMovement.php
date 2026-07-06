<?php

namespace App\Models\Inventory;

use App\Models\BaseModel;
use App\Models\Order\OrderItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends BaseModel
{
    protected $table = 'inventory_movements';

    protected $fillable = [
        'stock_id', 'warehouse_id', 'product_id', 'order_item_id',
        'movement_type', 'quantity', 'quantity_before', 'quantity_after',
        'reference_type', 'reference_id', 'reason', 'notes', 'metadata'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'metadata' => 'array',
    ];

    const TYPE_INCOMING = 'incoming';
    const TYPE_OUTGOING = 'outgoing';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_RESERVATION = 'reservation';
    const TYPE_RELEASE = 'release';
    const TYPE_DAMAGED = 'damaged';
    const TYPE_RETURN = 'return';

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

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
