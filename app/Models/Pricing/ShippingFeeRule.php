<?php

namespace App\Models\Pricing;

use App\Models\BaseModel;
use App\Models\Marketplace\Country;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingFeeRule extends BaseModel
{
    protected $table = 'shipping_fee_rules';

    protected $fillable = [
        'country_id', 'region_id', 'delivery_zone_id', 'weight_min', 'weight_max',
        'price_min', 'price_max', 'base_fee', 'per_kg_fee', 'percentage_fee',
        'free_shipping_threshold', 'is_active', 'carrier_name', 'estimated_days', 'metadata'
    ];

    protected $casts = [
        'weight_min' => 'decimal:2',
        'weight_max' => 'decimal:2',
        'price_min' => 'decimal:2',
        'price_max' => 'decimal:2',
        'base_fee' => 'decimal:2',
        'per_kg_fee' => 'decimal:2',
        'percentage_fee' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_active' => 'boolean',
        'estimated_days' => 'integer',
        'metadata' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region()
    {
        return $this->belongsTo(\App\Models\Marketplace\Region::class);
    }

    public function deliveryZone()
    {
        return $this->belongsTo(\App\Models\Marketplace\DeliveryZone::class);
    }
}
