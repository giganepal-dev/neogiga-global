<?php

namespace App\Models\Distributor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Distributor extends Model
{
    protected $fillable = ['user_id', 'parent_id', 'name', 'slug', 'email', 'phone', 'type', 'status', 'country_id', 'approved_by', 'approved_at', 'rejection_reason', 'metadata'];

    protected $casts = ['approved_at' => 'datetime', 'metadata' => 'array'];

    public function profile(): HasOne
    {
        return $this->hasOne(DistributorProfile::class);
    }

    public function territories(): HasMany
    {
        return $this->hasMany(DistributorTerritory::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(DistributorLead::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(DistributorCommission::class);
    }

    public function territoryRequests(): HasMany
    {
        return $this->hasMany(DistributorTerritoryRequest::class);
    }
}
