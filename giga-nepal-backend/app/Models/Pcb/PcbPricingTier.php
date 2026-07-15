<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;

class PcbPricingTier extends Model
{
    protected $fillable = [
        'tier_key', 'label', 'min_layers', 'max_layers',
        'board_material', 'min_quantity', 'max_quantity',
        'min_length_mm', 'max_length_mm', 'min_width_mm', 'max_width_mm',
        'base_fabrication_price', 'price_per_sq_cm', 'price_per_layer',
        'engineering_fee', 'setup_fee', 'surcharge_rates',
        'lead_time_days', 'active', 'sort_order',
    ];

    protected $casts = [
        'min_layers' => 'integer',
        'max_layers' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'base_fabrication_price' => 'decimal:2',
        'price_per_sq_cm' => 'decimal:4',
        'price_per_layer' => 'decimal:4',
        'engineering_fee' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'surcharge_rates' => 'array',
        'lead_time_days' => 'integer',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForSpecs($query, int $layers, string $material = 'FR-4', int $quantity = 5, float $lengthMm = 100, float $widthMm = 100)
    {
        return $query->active()
            ->where('min_layers', '<=', $layers)
            ->where('max_layers', '>=', $layers)
            ->where('board_material', $material)
            ->where('min_quantity', '<=', $quantity)
            ->where('max_quantity', '>=', $quantity)
            ->where('min_length_mm', '<=', $lengthMm)
            ->where('max_length_mm', '>=', $lengthMm)
            ->where('min_width_mm', '<=', $widthMm)
            ->where('max_width_mm', '>=', $widthMm)
            ->orderBy('sort_order');
    }
}
