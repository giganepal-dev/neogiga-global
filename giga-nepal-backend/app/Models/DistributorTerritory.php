<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorTerritory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distributor_application_id',
        'country_id',
        'province_id',
        'district_id',
        'city_id',
        'territory_name',
        'territory_type',
        'status',
        'approved_at',
        'approved_by',
        'territory_description',
        'coverage_areas',
        'priority',
    ];

    protected $casts = [
        'coverage_areas' => 'array',
        'approved_at' => 'datetime',
        'priority' => 'integer',
    ];

    public function distributorApplication(): BelongsTo
    {
        return $this->belongsTo(DistributorApplication::class);
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(DistributorLead::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(DistributorCustomer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
