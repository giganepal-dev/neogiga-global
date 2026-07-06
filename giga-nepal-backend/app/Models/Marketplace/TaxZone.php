<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_id',
        'country_id',
        'region_id',
        'name',
        'code',
        'tax_rate',
        'is_compound',
        'is_inclusive',
        'priority',
        'is_active',
        'rules',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:5',
        'is_compound' => 'boolean',
        'is_inclusive' => 'boolean',
        'is_active' => 'boolean',
        'rules' => 'array',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
