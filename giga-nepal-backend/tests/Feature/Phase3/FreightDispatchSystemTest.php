<?php

namespace Tests\Feature\Phase3;

use App\Models\Freight\FreightShipment;
use App\Models\Freight\Carrier;
use App\Models\Dispatch\DispatchBatch;
use App\Models\Dispatch\Driver;
use App\Models\Warehouse\Warehouse;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreightDispatchSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Warehouse $warehouse;
    protected Carrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->carrier = Carrier::factory()->create([
            'name' => 'DHL Express',
            'code' => 'dhl',
            'type' => 'courier',
        ]);
    }

    public function test_create_freight_shipment(): void
    {
        $response = $this->actingAs($this->user, 'api-token')
            ->postJson('/api/v1/freight', [
                'shipment_type' => 'inbound',
                'warehouse_id' => $this->warehouse->id,
                'carrier_id' => $this->carrier->id,
                'awb_number' => 'AWB123456789',
                'origin_country' => 'CN',
                'gross_weight' => 150.5,
                'freight_cost' => 500.00,
                'currency' => 'USD',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('shipment_type', 'inbound')
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('freight_shipments', [
            'shipment_type' => 'inbound',
            'awb_number' => 'AWB123456789',
        ]);
    }

    public function test_create_dispatch_batch(): void
    {
        $order = Order::factory()->create(['status' => 'paid']);

        $response = $this->actingAs($this->user, 'api-token')
            ->postJson('/api/v1/dispatch', [
                'warehouse_id' => $this->warehouse->id,
                'order_ids' => [$order->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('dispatch_batches', [
            'warehouse_id' => $this->warehouse->id,
        ]);
    }

    public function test_list_carriers(): void
    {
        Carrier::factory()->count(3)->create(['is_active' => true]);

        $response = $this->actingAs($this->user, 'api-token')
            ->getJson('/api/v1/freight/carriers');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_driver_deliveries(): void
    {
        $driver = Driver::factory()->create(['status' => 'on_route']);
        
        \App\Models\Freight\ProofOfDelivery::factory()->create([
            'driver_id' => $driver->id,
            'status' => 'in_transit',
        ]);

        $response = $this->actingAs($this->user, 'api-token')
            ->getJson("/api/v1/dispatch/drivers/{$driver->id}/deliveries");

        $response->assertStatus(200);
    }
}
