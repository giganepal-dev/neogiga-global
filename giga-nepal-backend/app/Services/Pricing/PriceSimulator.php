<?php

namespace App\Services\Pricing;

/**
 * Read-only price simulator (see PRICE_SIMULATOR_GUIDE.md). Delegates to
 * PricingRuleResolver and returns the full breakdown + rule trace. It performs
 * NO writes — simulation must never mutate prices; persisting a simulated
 * price is a separate, explicit, approved action elsewhere.
 */
class PriceSimulator
{
    public function __construct(private readonly PricingRuleResolver $resolver)
    {
    }

    /**
     * @return array{cost_basis:float,currency:string,final_price:float,gross_margin_percent:float,blocked:bool,block_reasons:array,applied_rule_ids:array,trace:array}
     */
    public function simulate(PricingContext $ctx): array
    {
        $result = $this->resolver->price($ctx);
        $result['simulated'] = true;
        $result['persisted'] = false;

        return $result;
    }
}
