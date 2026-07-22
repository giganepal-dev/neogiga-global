<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseZone;
use App\Models\Warehouse\WarehouseAisle;
use App\Models\Warehouse\WarehouseRack;
use App\Models\Warehouse\WarehouseShelf;
use App\Models\Warehouse\WarehouseBin;
use App\Models\Warehouse\InventoryBatch;
use App\Models\Warehouse\InventorySerial;
use App\Models\Warehouse\StockCount;
use App\Models\Warehouse\StockCountItem;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductWarehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2WarehouseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'code' => 'WH-TEST',
            'type' => 'main',
            'is_active' => true,
        ]);
    }

    public function test_warehouse_zone_creation(): void
    {
        $zone = WarehouseZone::create([
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Main Storage Zone',
            'code' => 'ZONE-001',
            'type' => 'storage',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('warehouse_zones', [
            'warehouse_id' => $this->warehouse->id,
            'code' => 'ZONE-001',
            'type' => 'storage',
        ]);

        $this->assertEquals('Main Storage Zone', $zone->name);
        $this->assertTrue($zone->is_active);
    }

    public function test_warehouse_location_hierarchy(): void
    {
        $zone = WarehouseZone::create([
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Storage Zone',
            'code' => 'Z-STO-001',
            'type' => 'storage',
        ]);

        $aisle = WarehouseAisle::create([
            'zone_id' => $zone->id,
            'name' => 'Aisle A',
            'code' => 'A-AA-01',
            'sequence' => 1,
        ]);

        $rack = WarehouseRack::create([
            'aisle_id' => $aisle->id,
            'name' => 'Rack 1',
            'code' => 'R-R1-01',
            'sequence' => 1,
            'levels' => 4,
        ]);

        $shelf = WarehouseShelf::create([
            'rack_id' => $rack->id,
            'name' => 'Shelf B',
            'code' => 'S-B-1',
            'level_number' => 2,
        ]);

        $bin = WarehouseBin::create([
            'shelf_id' => $shelf->id,
            'name' => 'Bin 05',
            'code' => 'B-05',
            'type' => 'small_parts',
            'sequence' => 5,
        ]);

        // Test relationships
        $this->assertEquals($zone->id, $bin->shelf->rack->aisle->zone_id);
        $this->assertEquals('storage', $bin->shelf->rack->aisle->zone->type);
        
        // Test full location path
        $this->assertStringContainsString($this->warehouse->name, $bin->full_location_path);
        $this->assertStringContainsString($zone->name, $bin->full_location_path);
        $this->assertStringContainsString($aisle->name, $bin->full_location_path);
        $this->assertStringContainsString($rack->name, $bin->full_location_path);
        $this->assertStringContainsString($shelf->name, $bin->full_location_path);
        $this->assertStringContainsString($bin->name, $bin->full_location_path);
    }

    public function test_inventory_batch_tracking(): void
    {
        $product = Product::factory()->create();

        $batch = InventoryBatch::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'batch_number' => 'BATCH-2026-001',
            'lot_number' => 'LOT-ABC123',
            'manufacturing_date' => now()->subMonths(2),
            'expiry_date' => now()->addMonths(10),
            'date_code' => '2401',
            'country_of_origin' => 'JP',
            'quantity_received' => 1000,
            'quantity_available' => 850,
            'quantity_reserved' => 100,
            'quantity_sold' => 50,
            'unit_cost' => 12.50,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $this->assertEquals('BATCH-2026-001', $batch->batch_number);
        $this->assertFalse($batch->isExpired());
        $this->assertTrue($batch->isExpiringSoon(365));
        $this->assertEquals(245, $batch->daysUntilExpiry());
        $this->assertEquals(10625.00, $batch->value); // 850 * 12.50
    }

    public function test_inventory_serial_tracking(): void
    {
        $product = Product::factory()->create();

        $serial = InventorySerial::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'SN-2026-ABC-001234',
            'manufacturer_serial' => 'MFG-SN-XYZ789',
            'manufacturing_date' => now()->subMonths(3),
            'warranty_start_date' => now()->subMonths(2),
            'warranty_end_date' => now()->addMonths(22),
            'warranty_months' => 24,
            'warranty_provider' => 'Manufacturer',
            'status' => 'in_stock',
        ]);

        $this->assertEquals('SN-2026-ABC-001234', $serial->serial_number);
        $this->assertTrue($serial->isInStock());
        $this->assertTrue($serial->isUnderWarranty());
        $this->assertEquals(660, $serial->warrantyDaysRemaining());
        $this->assertFalse($serial->isWarrantyExpired());
    }

    public function test_serial_status_transitions(): void
    {
        $product = Product::factory()->create();

        $serial = InventorySerial::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'SN-TEST-001',
            'status' => 'in_stock',
        ]);

        // Transition to reserved
        $serial->update(['status' => 'reserved']);
        $this->assertEquals('reserved', $serial->status);
        $this->assertFalse($serial->isInStock());

        // Transition to sold
        $serial->update(['status' => 'sold']);
        $this->assertTrue($serial->isSold());
    }

    public function test_batch_expiry_detection(): void
    {
        $product = Product::factory()->create();

        // Expired batch
        $expiredBatch = InventoryBatch::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'batch_number' => 'BATCH-EXPIRED',
            'expiry_date' => now()->subDays(10),
            'status' => 'active',
        ]);

        // Expiring soon batch
        $expiringBatch = InventoryBatch::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'batch_number' => 'BATCH-EXPIRING',
            'expiry_date' => now()->addDays(15),
            'status' => 'active',
        ]);

        // Valid batch
        $validBatch = InventoryBatch::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'batch_number' => 'BATCH-VALID',
            'expiry_date' => now()->addMonths(12),
            'status' => 'active',
        ]);

        $this->assertTrue($expiredBatch->isExpired());
        $this->assertTrue($expiringBatch->isExpiringSoon(30));
        $this->assertFalse($validBatch->isExpiringSoon(30));
    }

    public function test_stock_count_creation(): void
    {
        $stockCount = StockCount::create([
            'warehouse_id' => $this->warehouse->id,
            'conducted_by' => $this->adminUser->id,
            'reference_number' => 'SC-SCH-202607-0001',
            'type' => 'scheduled',
            'status' => 'draft',
        ]);

        $this->assertEquals('scheduled', $stockCount->type);
        $this->assertEquals('draft', $stockCount->status);
        $this->assertFalse($stockCount->isCompleted());
        $this->assertFalse($stockCount->isInProgress());
    }

    public function test_stock_count_lifecycle(): void
    {
        $stockCount = StockCount::create([
            'warehouse_id' => $this->warehouse->id,
            'conducted_by' => $this->adminUser->id,
            'reference_number' => 'SC-SCH-202607-0002',
            'type' => 'cycle',
            'status' => 'draft',
        ]);

        // Start the count
        $stockCount->start();
        $this->assertTrue($stockCount->isInProgress());
        $this->assertNotNull($stockCount->started_at);

        // Complete the count
        $stockCount->complete();
        $this->assertTrue($stockCount->isCompleted());
        $this->assertNotNull($stockCount->completed_at);
    }

    public function test_stock_count_item_variance(): void
    {
        $product = Product::factory()->create();
        
        $stockCount = StockCount::create([
            'warehouse_id' => $this->warehouse->id,
            'conducted_by' => $this->adminUser->id,
            'reference_number' => 'SC-TEST-001',
            'type' => 'spot',
            'status' => 'in_progress',
        ]);

        $item = StockCountItem::create([
            'stock_count_id' => $stockCount->id,
            'product_id' => $product->id,
            'system_quantity' => 100,
            'counted_quantity' => 95,
            'variance_quantity' => -5,
            'unit_cost' => 10.00,
            'variance_value' => -50.00,
            'variance_reason' => 'not_found',
        ]);

        $this->assertTrue($item->hasVariance());
        $this->assertEquals(-5, $item->variance_quantity);
        $this->assertEquals(-50.00, $item->variance_value);
        $this->assertEquals('not_found', $item->variance_reason);
    }

    public function test_bin_type_filtering(): void
    {
        $zone = WarehouseZone::create([
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Mixed Zone',
            'code' => 'Z-MIX',
            'type' => 'storage',
        ]);

        $aisle = WarehouseAisle::create([
            'zone_id' => $zone->id,
            'name' => 'Aisle 1',
            'code' => 'A-01',
        ]);

        $rack = WarehouseRack::create([
            'aisle_id' => $aisle->id,
            'name' => 'Rack 1',
            'code' => 'R-01',
        ]);

        $shelf = WarehouseShelf::create([
            'rack_id' => $rack->id,
            'name' => 'Shelf 1',
            'code' => 'S-01',
        ]);

        // Create different bin types
        $standardBin = WarehouseBin::create([
            'shelf_id' => $shelf->id,
            'name' => 'Standard',
            'code' => 'B-STD',
            'type' => 'standard',
        ]);

        $smallPartsBin = WarehouseBin::create([
            'shelf_id' => $shelf->id,
            'name' => 'Small Parts',
            'code' => 'B-SML',
            'type' => 'small_parts',
        ]);

        $palletBin = WarehouseBin::create([
            'shelf_id' => $shelf->id,
            'name' => 'Pallet',
            'code' => 'B-PAL',
            'type' => 'pallet',
        ]);

        // Test scope filtering
        $smallBins = WarehouseBin::scopeByType(WarehouseBin::query(), 'small_parts')->get();
        $this->assertEquals(1, $smallBins->count());
        $this->assertEquals('small_parts', $smallBins->first()->type);
    }

    public function test_warehouse_bin_capacity(): void
    {
        $zone = WarehouseZone::create([
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Capacity Zone',
            'code' => 'Z-CAP',
        ]);

        $aisle = WarehouseAisle::create(['zone_id' => $zone->id, 'name' => 'A1', 'code' => 'A-C1']);
        $rack = WarehouseRack::create(['aisle_id' => $aisle->id, 'name' => 'R1', 'code' => 'R-C1']);
        $shelf = WarehouseShelf::create(['rack_id' => $rack->id, 'name' => 'S1', 'code' => 'S-C1']);

        $bin = WarehouseBin::create([
            'shelf_id' => $shelf->id,
            'name' => 'Limited Bin',
            'code' => 'B-LIM',
            'max_items' => 10,
            'max_weight_kg' => 50.00,
        ]);

        $this->assertEquals(10, $bin->max_items);
        $this->assertEquals(50.00, $bin->max_weight_kg);
    }

    public function test_product_bin_assignment(): void
    {
        $product = Product::factory()->create();
        
        $zone = WarehouseZone::create(['warehouse_id' => $this->warehouse->id, 'name' => 'Zone', 'code' => 'Z-PRD']);
        $aisle = WarehouseAisle::create(['zone_id' => $zone->id, 'name' => 'A1', 'code' => 'A-P1']);
        $rack = WarehouseRack::create(['aisle_id' => $aisle->id, 'name' => 'R1', 'code' => 'R-P1']);
        $shelf = WarehouseShelf::create(['rack_id' => $rack->id, 'name' => 'S1', 'code' => 'S-P1']);
        $bin = WarehouseBin::create(['shelf_id' => $shelf->id, 'name' => 'B1', 'code' => 'B-P1']);

        $productWarehouse = ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'bin_id' => $bin->id,
            'available_stock' => 100,
        ]);

        $this->assertEquals($bin->id, $productWarehouse->bin_id);
        $this->assertEquals(100, $productWarehouse->available_stock);
    }

    public function test_batch_quantity_calculations(): void
    {
        $product = Product::factory()->create();

        $batch = InventoryBatch::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'batch_number' => 'BATCH-CALC',
            'quantity_received' => 1000,
            'quantity_available' => 500,
            'quantity_reserved' => 200,
            'quantity_sold' => 250,
            'quantity_returned' => 30,
            'quantity_damaged' => 20,
        ]);

        $totalQuantity = $batch->quantity_available + 
                        $batch->quantity_reserved + 
                        $batch->quantity_sold + 
                        $batch->quantity_returned + 
                        $batch->quantity_damaged;

        $this->assertEquals(1000, $totalQuantity);
        $this->assertEquals($batch->quantity_received, $totalQuantity);
    }

    public function test_warehouse_activity_logging(): void
    {
        $zone = WarehouseZone::create([
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Activity Zone',
            'code' => 'Z-ACT',
        ]);

        // Log activity
        \DB::table('warehouse_activity_logs')->insert([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->adminUser->id,
            'activity_type' => 'bin_created',
            'description' => 'New bin created in zone',
            'entity_type' => WarehouseBin::class,
            'entity_id' => 1,
            'location_path' => "{$this->warehouse->name} > {$zone->name}",
            'new_values' => json_encode(['code' => 'B-NEW']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('warehouse_activity_logs', [
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->adminUser->id,
            'activity_type' => 'bin_created',
        ]);
    }

    public function test_scope_queries(): void
    {
        // Create multiple zones
        WarehouseZone::create(['warehouse_id' => $this->warehouse->id, 'name' => 'Active Zone', 'code' => 'Z-ACT', 'is_active' => true]);
        WarehouseZone::create(['warehouse_id' => $this->warehouse->id, 'name' => 'Inactive Zone', 'code' => 'Z-INA', 'is_active' => false]);

        $activeZones = WarehouseZone::scopeActive(WarehouseZone::query())->get();
        $this->assertEquals(1, $activeZones->count());
        $this->assertEquals('Active Zone', $activeZones->first()->name);

        $forWarehouse = WarehouseZone::scopeForWarehouse(WarehouseZone::query(), $this->warehouse->id)->get();
        $this->assertEquals(2, $forWarehouse->count());
    }

    public function test_formatted_bin_code(): void
    {
        $zone = WarehouseZone::create(['warehouse_id' => $this->warehouse->id, 'name' => 'Test', 'code' => 'Z-TST']);
        $aisle = WarehouseAisle::create(['zone_id' => $zone->id, 'name' => 'A1', 'code' => 'A-T1']);
        $rack = WarehouseRack::create(['aisle_id' => $aisle->id, 'name' => 'R1', 'code' => 'R-T1']);
        $shelf = WarehouseShelf::create(['rack_id' => $rack->id, 'name' => 'S1', 'code' => 'S-T1']);
        $bin = WarehouseBin::create(['shelf_id' => $shelf->id, 'name' => 'B1', 'code' => 'B-T1']);

        $formattedCode = $bin->formatted_code;
        
        $this->assertStringContainsString('Z-TST', $formattedCode);
        $this->assertStringContainsString('A-T1', $formattedCode);
        $this->assertStringContainsString('R-T1', $formattedCode);
        $this->assertStringContainsString('S-T1', $formattedCode);
        $this->assertStringContainsString('B-T1', $formattedCode);
    }
}
