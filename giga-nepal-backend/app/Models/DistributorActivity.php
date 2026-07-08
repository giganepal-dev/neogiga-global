<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distributor_application_id',
        'activity_type',
        'description',
        'customer_id',
        'lead_id',
        'order_id',
        'activity_date',
        'activity_time',
        'location',
        'metadata',
        'potential_value',
        'status',
        'follow_up_date',
        'notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'activity_date' => 'date',
        'follow_up_date' => 'datetime',
        'potential_value' => 'decimal:2',
    ];

    public function distributorApplication(): BelongsTo
    {
        return $this->belongsTo(DistributorApplication::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(DistributorLead::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeFollowUpNeeded($query)
    {
        return $query->where('status', 'follow_up_needed');
    }
}
