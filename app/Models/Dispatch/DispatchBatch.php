<?php

namespace App\Models\Dispatch;

use App\Models\Warehouse\Warehouse;
use App\Models\Marketplace\Marketplace;
use App\Models\User;
use App\Models\Freight\Carrier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'batch_number',
        'warehouse_id',
        'marketplace_id',
        'scheduled_date',
        'status',
        'assigned_to',
        'carrier_id',
        'route_code',
        'total_orders',
        'total_items',
        'total_weight',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'total_orders' => 'integer',
        'total_items' => 'integer',
        'total_weight' => 'decimal:3',
        'deleted_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DispatchItem::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function proofOfDeliveries(): HasMany
    {
        return $this->hasMany(\App\Models\Freight\ProofOfDelivery::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePicking($query)
    {
        return $query->where('status', 'picking');
    }

    public function scopePacked($query)
    {
        return $query->where('status', 'packed');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeDispatched($query)
    {
        return $query->where('status', 'dispatched');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
