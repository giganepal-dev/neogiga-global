<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;

class MarginFloorRule extends Model
{
    protected $fillable = [
        'marketplace_id', 'scope_type', 'scope_id',
        'min_gross_margin_percent', 'min_net_margin_percent',
        'min_contribution_margin_percent', 'require_approval_below', 'is_active',
    ];

    protected $casts = [
        'min_gross_margin_percent' => 'decimal:2',
        'min_net_margin_percent' => 'decimal:2',
        'min_contribution_margin_percent' => 'decimal:2',
        'require_approval_below' => 'boolean',
        'is_active' => 'boolean',
    ];
}
