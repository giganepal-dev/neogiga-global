<?php

namespace Tests\Unit;

use App\Models\Distributor\Distributor;
use App\Services\Distributor\DistributorCommissionService;
use Tests\TestCase;

class DistributorCommissionServiceTest extends TestCase
{
    public function test_summary_returns_zeroed_structure_when_tables_missing(): void
    {
        $service = new DistributorCommissionService;
        $distributor = new Distributor(['id' => 1]);

        $summary = $service->summary($distributor);

        $this->assertSame(0.0, $summary['pending']);
        $this->assertSame(0.0, $summary['approved']);
        $this->assertSame(0.0, $summary['paid']);
        $this->assertSame(0.0, $summary['total_earned']);
        $this->assertSame('USD', $summary['currency_code']);
    }
}
