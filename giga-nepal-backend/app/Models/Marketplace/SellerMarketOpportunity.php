<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class SellerMarketOpportunity extends Model
{
    protected $fillable = [
        'mpn', 'product_name', 'brand', 'category', 'demand_score', 'search_volume',
        'search_growth', 'order_count', 'rfq_count', 'bom_occurrence', 'current_supply',
        'regional_demand', 'opportunity_reason', 'marketplace_id', 'is_active',
    ];

    protected $casts = [
        'demand_score' => 'decimal:2', 'search_growth' => 'decimal:2',
        'regional_demand' => 'array', 'is_active' => 'boolean',
        'search_volume' => 'integer', 'order_count' => 'integer',
        'rfq_count' => 'integer', 'bom_occurrence' => 'integer', 'current_supply' => 'integer',
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeTopOpportunities($q, int $limit = 20) {
        return $q->orderByDesc('demand_score')->limit($limit);
    }
    public function scopeOfMarketplace($q, string $marketplace) {
        return $q->where('marketplace_id', $marketplace);
    }
}
