<?php

namespace Tests\Unit;

use App\Models\Manufacturer;
use App\Services\Manufacturer\ManufacturerInventoryService;
use Tests\TestCase;

class ManufacturerInventoryServiceTest extends TestCase
{
    public function test_global_summary_returns_zeroed_structure_when_table_missing(): void
    {
        $service = app(ManufacturerInventoryService::class);
        $manufacturer = new Manufacturer(['id' => 1]);

        $summary = $service->globalSummary($manufacturer);

        $this->assertSame(0, $summary['sku_count']);
        $this->assertSame(0.0, $summary['quantity_on_hand']);
        $this->assertSame(0.0, $summary['quantity_available']);
    }
}
