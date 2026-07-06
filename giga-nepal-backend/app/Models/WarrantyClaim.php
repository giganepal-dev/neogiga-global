<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyClaim extends Model
{
    protected $fillable = [
        'claim_number',
        'order_id',
        'product_id',
        'marketplace_id',
        'vendor_id',
        'user_id',
        'status',
        'issue_description',
        'claim_type',
        'resolution',
        'claimed_at',
        'approved_at',
        'received_at',
        'processed_at',
        'resolved_at',
        'rejected_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Product::class, 'product_id');
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Marketplace::class, 'marketplace_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketplace\Vendor::class, 'vendor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
