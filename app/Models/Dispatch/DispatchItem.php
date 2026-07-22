<?php

namespace App\Models\Dispatch;

use App\Models\Marketplace\Product;
use App\Models\Warehouse\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchItem extends Model
{
    protected $fillable = [
        'dispatch_batch_id',
        'order_id',
        'product_id',
        'warehouse_id',
        'bin_id',
        'quantity',
        'status',
        'picked_by',
        'picked_at',
        'packed_by',
        'packed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'picked_at' => 'datetime',
        'packed_at' => 'datetime',
    ];

    public function dispatchBatch(): BelongsTo
    {
        return $this->belongsTo(DispatchBatch::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function bin(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse\WarehouseBin::class, 'bin_id');
    }

    public function pickedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    public function packedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePicked($query)
    {
        return $query->where('status', 'picked');
    }

    public function scopePacked($query)
    {
        return $query->where('status', 'packed');
    }

    public function scopeDispatched($query)
    {
        return $query->where('status', 'dispatched');
    }
}
