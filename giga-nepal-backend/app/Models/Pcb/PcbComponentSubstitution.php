<?php

namespace App\Models\Pcb;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcbComponentSubstitution extends Model
{
    protected $fillable = [
        'component_match_id', 'original_product_id',
        'substitute_product_id', 'substitution_type',
        'justification', 'requires_approval',
        'approved', 'approved_by_id', 'approved_at',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function componentMatch(): BelongsTo
    {
        return $this->belongsTo(PcbComponentMatch::class, 'component_match_id');
    }

    public function originalProduct(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'original_product_id');
    }

    public function substituteProduct(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'substitute_product_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'approved_by_id');
    }
}
