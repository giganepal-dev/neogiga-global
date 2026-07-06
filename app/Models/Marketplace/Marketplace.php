<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Marketplace extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'country_id',
        'currency_id',
        'is_active',
        'is_global',
        'default_language',
        'timezone',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_global' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(MarketplaceDomain::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(MarketplaceSetting::class);
    }
}
