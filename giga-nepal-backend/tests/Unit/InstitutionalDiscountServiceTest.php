<?php

namespace Tests\Unit;

use App\Models\B2B\B2BAccount;
use App\Services\B2B\InstitutionalDiscountService;
use Tests\TestCase;

class InstitutionalDiscountServiceTest extends TestCase
{
    public function test_applies_configured_government_discount(): void
    {
        $account = new B2BAccount(['type' => 'government']);
        $result = app(InstitutionalDiscountService::class)->applyDiscount(100, $account);

        $expectedPercent = (float) config('b2b_institutional.discounts.government', 12);
        $this->assertSame($expectedPercent, $result['discount_percent']);
        $this->assertEqualsWithDelta(100 * (1 - $expectedPercent / 100), $result['unit_price'], 0.0001);
    }
}
