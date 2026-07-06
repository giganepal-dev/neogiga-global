<?php

namespace App\Models\Pricing;

use App\Models\BaseModel;
use App\Models\Marketplace\Country;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRule extends BaseModel
{
    protected $table = 'tax_rules';

    protected $fillable = [
        'country_id', 'region_id', 'name', 'rate', 'type',
        'is_compound', 'priority', 'is_active', 'applies_to', 'metadata'
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'applies_to' => 'array',
        'metadata' => 'array',
    ];

    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region()
    {
        return $this->belongsTo(\App\Models\Marketplace\Region::class);
    }
}
