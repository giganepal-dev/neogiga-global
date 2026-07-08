<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionStockVisibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'country_id',
        'marketplace_id',
        'province_id',
        'city_id',
        'distributor_id',
        'visibility_scope',
        'is_visible',
        'priority',
        'visible_from',
        'visible_until',
        'conditions',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'conditions' => 'array',
        'visible_from' => 'datetime',
        'visible_until' => 'datetime',
    ];

    public const SCOPE_PUBLIC = 'public';
    public const SCOPE_REGISTERED = 'registered';
    public const SCOPE_SELLER_ONLY = 'seller_only';
    public const SCOPE_DISTRIBUTOR_ONLY = 'distributor_only';
    public const SCOPE_TERRITORY_SPECIFIC = 'territory_specific';

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }
}
