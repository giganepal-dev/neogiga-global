<?php

namespace App\Models\Pricing;

use App\Models\BaseModel;
use App\Models\Marketplace\Country;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportDutyRule extends BaseModel
{
    protected $table = 'import_duty_rules';

    protected $fillable = [
        'country_id', 'category_id', 'hs_code_pattern', 'rate', 'calculation_method',
        'min_value', 'max_value', 'is_active', 'notes', 'metadata'
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Product\ProductCategory::class, 'category_id');
    }
}
