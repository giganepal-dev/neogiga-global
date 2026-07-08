<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorCustomer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distributor_application_id',
        'customer_id',
        'distributor_territory_id',
        'relationship_type',
        'assigned_at',
        'assigned_by',
        'is_within_territory',
        'territory_notes',
        'total_sales',
        'total_orders',
        'commission_earned',
        'pending_commission',
        'status',
        'last_order_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'is_within_territory' => 'boolean',
        'total_sales' => 'decimal:2',
        'total_orders' => 'integer',
        'commission_earned' => 'decimal:2',
        'pending_commission' => 'decimal:2',
        'last_order_at' => 'datetime',
    ];

    public function distributorApplication(): BelongsTo
    {
        return $this->belongsTo(DistributorApplication::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function distributorTerritory(): BelongsTo
    {
        return $this->belongsTo(DistributorTerritory::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function addSale(float $amount): void
    {
        $this->increment('total_sales', $amount);
        $this->increment('total_orders');
        $this->update([
            'last_order_at' => now(),
        ]);
    }

    public function addCommission(float $amount): void
    {
        $this->increment('pending_commission', $amount);
    }

    public function payCommission(float $amount): void
    {
        $this->decrement('pending_commission', $amount);
        $this->increment('commission_earned', $amount);
    }
}
