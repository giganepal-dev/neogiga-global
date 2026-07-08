<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorCommission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distributor_application_id',
        'order_id',
        'customer_id',
        'order_amount',
        'commission_rate',
        'commission_amount',
        'currency',
        'status',
        'approved_at',
        'approved_by',
        'paid_at',
        'paid_by',
        'payment_reference',
        'payment_method',
        'payment_account_number',
        'payment_account_holder_name',
        'commission_period_start',
        'commission_period_end',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'order_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'commission_period_start' => 'date',
        'commission_period_end' => 'date',
    ];

    public function distributorApplication(): BelongsTo
    {
        return $this->belongsTo(DistributorApplication::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function approve(User $admin): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);

        return true;
    }

    public function markAsPaid(User $admin, string $paymentReference, ?string $paymentMethod = null): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => $admin->id,
            'payment_reference' => $paymentReference,
            'payment_method' => $paymentMethod ?? $this->payment_method,
        ]);

        return true;
    }

    public function cancel(string $reason): bool
    {
        if (!in_array($this->status, ['pending', 'approved'])) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'rejection_reason' => $reason,
        ]);

        return true;
    }
}
