<?php

namespace App\Models\Freight;

use App\Models\Order;
use App\Models\Dispatch\DispatchBatch;
use App\Models\Dispatch\Driver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProofOfDelivery extends Model
{
    protected $fillable = [
        'order_id',
        'dispatch_batch_id',
        'driver_id',
        'status',
        'delivered_at',
        'recipient_name',
        'recipient_signature',
        'photos',
        'otp_verified',
        'failure_reason',
        'delivery_notes',
        'cod_amount',
        'cod_collected',
        'cod_collected_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'photos' => 'array',
        'otp_verified' => 'boolean',
        'cod_collected' => 'boolean',
        'cod_collected_at' => 'datetime',
        'cod_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function dispatchBatch(): BelongsTo
    {
        return $this->belongsTo(DispatchBatch::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function codCollection(): HasMany
    {
        return $this->hasMany(CodCollection::class);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeWithCod($query)
    {
        return $query->where('cod_amount', '>', 0);
    }

    public function scopeCodCollected($query)
    {
        return $query->where('cod_collected', true);
    }

    public function scopeCodPending($query)
    {
        return $query->where('cod_collected', false)->where('cod_amount', '>', 0);
    }
}
