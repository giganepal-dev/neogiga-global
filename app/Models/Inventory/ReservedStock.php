<?php

namespace App\Models\Inventory;

use App\Models\BaseModel;
use App\Models\Cart\CartItem;
use App\Models\Order\OrderItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservedStock extends BaseModel
{
    protected $table = 'reserved_stocks';

    protected $fillable = [
        'stock_id', 'cart_item_id', 'order_item_id',
        'quantity', 'reserved_until', 'reason', 'metadata'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_until' => 'datetime',
        'metadata' => 'array',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(InventoryStock::class, 'stock_id');
    }

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
