<?php

namespace App\Models\Pricing;

use App\Models\Marketplace\Marketplace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingRule extends Model
{
    protected $fillable = [
        'rule_set_id', 'name', 'code',
        'owner_type', 'owner_id', 'marketplace_id',
        'scope_type', 'scope_product_id', 'scope_category_id', 'scope_brand_id',
        'scope_manufacturer_id', 'scope_seller_id', 'scope_warehouse_id',
        'scope_country_id', 'scope_region_id', 'scope_city', 'scope_postal_group',
        'customer_segment', 'min_quantity', 'max_quantity',
        'cost_basis', 'action_type', 'action_value', 'action_currency',
        'priority', 'condition_operator', 'stackable', 'stop_processing',
        'starts_at', 'ends_at', 'timezone',
        'active', 'approval_status', 'version', 'reason', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'action_value' => 'decimal:6',
        'priority' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'stackable' => 'boolean',
        'stop_processing' => 'boolean',
        'active' => 'boolean',
        'version' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PricingRuleCondition::class);
    }
}
