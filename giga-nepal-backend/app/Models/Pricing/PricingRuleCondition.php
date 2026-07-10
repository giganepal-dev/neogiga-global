<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;

class PricingRuleCondition extends Model
{
    protected $fillable = ['pricing_rule_id', 'field', 'operator', 'value'];
}
