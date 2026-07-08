<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorLead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distributor_application_id',
        'distributor_territory_id',
        'lead_name',
        'lead_email',
        'lead_phone',
        'company_name',
        'designation',
        'address',
        'country_id',
        'province_id',
        'district_id',
        'city_id',
        'requirements',
        'interested_products',
        'estimated_value',
        'lead_source',
        'lead_status',
        'priority',
        'expected_closure_date',
        'contact_attempts',
        'last_contacted_at',
        'last_communication_notes',
        'next_follow_up_at',
        'converted_customer_id',
        'converted_order_id',
        'converted_at',
        'lost_reason',
        'assigned_to',
    ];

    protected $casts = [
        'interested_products' => 'array',
        'estimated_value' => 'decimal:2',
        'priority' => 'integer',
        'contact_attempts' => 'integer',
        'expected_closure_date' => 'datetime',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function distributorApplication(): BelongsTo
    {
        return $this->belongsTo(DistributorApplication::class);
    }

    public function distributorTerritory(): BelongsTo
    {
        return $this->belongsTo(DistributorTerritory::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_customer_id');
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DistributorActivity::class);
    }

    public function scopeNew($query)
    {
        return $query->where('lead_status', 'new');
    }

    public function scopeContacted($query)
    {
        return $query->where('lead_status', 'contacted');
    }

    public function scopeQualified($query)
    {
        return $query->where('lead_status', 'qualified');
    }

    public function scopeWon($query)
    {
        return $query->where('lead_status', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('lead_status', 'lost');
    }

    public function scopeFollowUpDue($query)
    {
        return $query->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now());
    }

    public function markAsContacted(): bool
    {
        $this->update([
            'lead_status' => 'contacted',
            'contact_attempts' => $this->contact_attempts + 1,
            'last_contacted_at' => now(),
        ]);
        return true;
    }

    public function convertToCustomer(User $customer, ?Order $order = null): bool
    {
        $this->update([
            'lead_status' => 'won',
            'converted_customer_id' => $customer->id,
            'converted_order_id' => $order?->id,
            'converted_at' => now(),
        ]);
        return true;
    }

    public function markAsLost(string $reason): bool
    {
        $this->update([
            'lead_status' => 'lost',
            'lost_reason' => $reason,
        ]);
        return true;
    }
}
