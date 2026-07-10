<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportDutyRule extends Model
{
    protected $fillable = [
        'country_id',
        'marketplace_id',
        'hs_code',
        'category_ids',
        'duty_rate',
        'duty_type',
        'fixed_amount',
        'origin_country',
        'effective_from',
        'effective_until',
        'is_active',
    ];

    protected $casts = [
        'category_ids' => 'array',
        'duty_rate' => 'decimal:2',
        'fixed_amount' => 'decimal:4',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
