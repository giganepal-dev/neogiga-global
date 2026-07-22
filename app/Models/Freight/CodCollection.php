<?php

namespace App\Models\Freight;

use App\Models\Dispatch\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodCollection extends Model
{
    protected $fillable = [
        'driver_id',
        'proof_of_delivery_id',
        'amount',
        'currency',
        'collection_date',
        'status',
        'reconciled_date',
        'reconciled_by',
        'deposited_date',
        'deposit_reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'collection_date' => 'date',
        'reconciled_date' => 'date',
        'deposited_date' => 'date',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function proofOfDelivery(): BelongsTo
    {
        return $this->belongsTo(ProofOfDelivery::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReconciled($query)
    {
        return $query->where('status', 'reconciled');
    }

    public function scopeDeposited($query)
    {
        return $query->where('status', 'deposited');
    }
}
