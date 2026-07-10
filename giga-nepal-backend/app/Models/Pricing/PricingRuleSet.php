<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingRuleSet extends Model
{
    protected $fillable = [
        'name', 'code', 'marketplace_id', 'owner_type', 'owner_id', 'active', 'description',
    ];

    protected $casts = ['active' => 'boolean'];

    public function rules(): HasMany
    {
        return $this->hasMany(PricingRule::class, 'rule_set_id');
    }
}
