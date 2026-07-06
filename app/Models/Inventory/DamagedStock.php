<?php

namespace App\Models\Inventory;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamagedStock extends BaseModel
{
    protected $table = 'damaged_stocks';

    protected $fillable = [
        'stock_id', 'warehouse_id', 'product_id',
        'quantity', 'damage_type', 'reason', 'reported_by',
        'disposal_method', 'disposed_at', 'notes', 'metadata'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'disposed_at' => 'datetime',
        'metadata' => 'array',
    ];

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
}
