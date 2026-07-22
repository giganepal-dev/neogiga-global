<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBarcode;
use App\Models\Marketplace\ProductVariant;
use App\Models\Marketplace\ProductWarehouse;
use App\Models\Marketplace\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for complete barcode system
 */
class CompleteBarcodeSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'mpn' => 'TEST-MPN-001',
        ]);
    }

    public function test_can_create_code128_barcode(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/barcode', [
                'product_id' => $this->product->id,
                'barcode_value' => 'TEST-BARCODE-123',
                'barcode_type' => 'code128',
                'source' => 'internal',
                'is_primary' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Barcode created successfully',
            ])
            ->assertJsonPath('barcode.barcode_value', 'TEST-BARCODE-123')
            ->assertJsonPath('barcode.barcode_type', 'code128')
            ->assertJsonPath('barcode.is_primary', true);

        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $this->product->id,
            'barcode_value' => 'TEST-BARCODE-123',
            'barcode_type' => 'code128',
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    public function test_cannot_create_duplicate_barcode(): void
    {
        // Create first barcode
        ProductBarcode::create([
            'product_id' => $this->product->id,
            'barcode_value' => 'DUPLICATE-BARCODE',
            'barcode_type' => 'code128',
            'is_active' => true,
        ]);

        // Try to create duplicate
        $response = $this->actingAs($this->user)
            ->postJson('/api/barcode', [
                'product_id' => $this->product->id,
                'barcode_value' => 'DUPLICATE-BARCODE',
                'barcode_type' => 'code128',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_scan_barcode_and_get_product(): void
    {
        $barcode = ProductBarcode::create([
            'product_id' => $this->product->id,
            'barcode_value' => 'SCAN-TEST-123',
            'barcode_type' => 'code128',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/barcode/scan', [
            'barcode_value' => 'SCAN-TEST-123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'barcode' => [
                    'value' => 'SCAN-TEST-123',
                ],
                'product' => [
                    'id' => $this->product->id,
                    'name' => 'Test Product',
                    'sku' => 'TEST-SKU-001',
                ],
            ]);

        // Verify scan was logged
        $this->assertDatabaseHas('product_barcode_scan_logs', [
            'barcode_value' => 'SCAN-TEST-123',
            'was_successful' => true,
        ]);
    }

    public function test_failed_scan_returns_404(): void
    {
        $response = $this->postJson('/api/barcode/scan', [
            'barcode_value' => 'NON-EXISTENT-BARCODE',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Barcode not found',
            ]);

        // Verify failed scan was logged
        $this->assertDatabaseHas('product_barcode_scan_logs', [
            'barcode_value' => 'NON-EXISTENT-BARCODE',
            'was_successful' => false,
            'failure_reason' => 'Barcode not found',
        ]);
    }

    public function test_can_generate_barcode_svg(): void
    {
        $barcode = ProductBarcode::create([
            'product_id' => $this->product->id,
            'barcode_value' => 'SVG-TEST-123',
            'barcode_type' => 'code128',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/barcode/{$barcode->id}/generate");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'barcode_id' => $barcode->id,
            ])
            ->assertJsonPath('barcode_value', 'SVG-TEST-123');

        $this->assertStringContainsString('<svg', $response->json('svg'));
    }

    public function test_can_create_ean13_barcode_with_check_digit(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/barcode', [
                'product_id' => $this->product->id,
                'barcode_value' => '123456789012', // 12 digits, check digit will be calculated
                'barcode_type' => 'ean13',
                'source' => 'manufacturer',
            ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('product_barcodes', [
            'barcode_value' => '123456789012',
            'barcode_type' => 'ean13',
        ]);

        $barcode = ProductBarcode::where('barcode_value', '123456789012')->first();
        $this->assertNotNull($barcode->check_digit);
    }

    public function test_ean13_validation_rejects_invalid_length(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/barcode', [
                'product_id' => $this->product->id,
                'barcode_value' => '12345', // Too short
                'barcode_type' => 'ean13',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_import_multiple_barcodes(): void
    {
        $barcodes = [
            [
                'product_id' => $this->product->id,
                'barcode_value' => 'IMPORT-001',
                'barcode_type' => 'code128',
            ],
            [
                'product_id' => $this->product->id,
                'barcode_value' => 'IMPORT-002',
                'barcode_type' => 'code128',
            ],
            [
                'product_id' => $this->product->id,
                'barcode_value' => 'IMPORT-003',
                'barcode_type' => 'ean13',
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/barcode/import', [
                'barcodes' => $barcodes,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'results' => [
                    'total' => 3,
                    'success' => 3,
                    'failed' => 0,
                    'duplicates' => 0,
                ],
            ]);

        $this->assertEquals(3, ProductBarcode::count());
    }

    public function test_import_handles_duplicates_gracefully(): void
    {
        // Create one barcode first
        ProductBarcode::create([
            'product_id' => $this->product->id,
            'barcode_value' => 'DUP-IMPORT-001',
            'barcode_type' => 'code128',
            'is_active' => true,
        ]);

        $barcodes = [
            [
                'product_id' => $this->product->id,
                'barcode_value' => 'DUP-IMPORT-001', // Duplicate
                'barcode_type' => 'code128',
            ],
            [
                'product_id' => $this->product->id,
                'barcode_value' => 'NEW-IMPORT-001', // New
                'barcode_type' => 'code128',
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/barcode/import', [
                'barcodes' => $barcodes,
            ]);

        $response->assertStatus(207) // Multi-status
            ->assertJson([
                'success' => false,
                'results' => [
                    'total' => 2,
                    'success' => 1,
                    'duplicates' => 1,
                ],
            ]);
    }

    public function test_can_deactivate_barcode(): void
    {
        $barcode = ProductBarcode::create([
            'product_id' => $this->product->id,
            'barcode_value' => 'DEACTIVATE-TEST',
            'barcode_type' => 'code128',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/barcode/{$barcode->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Barcode deactivated successfully',
            ]);

        $this->assertDatabaseHas('product_barcodes', [
            'id' => $barcode->id,
            'is_active' => false,
        ]);
    }

    public function test_primary_barcode_updates_product_field(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/barcode', [
                'product_id' => $this->product->id,
                'barcode_value' => 'PRIMARY-TEST',
                'barcode_type' => 'code128',
                'is_primary' => true,
            ]);

        $this->product->refresh();
        
        $this->assertEquals('PRIMARY-TEST', $this->product->barcode_primary);
    }

    public function test_can_get_scan_logs(): void
    {
        // Create some scan logs
        ProductBarcodeScanLog::create([
            'barcode_value' => 'LOG-TEST-001',
            'was_successful' => true,
            'user_id' => $this->user->id,
        ]);

        ProductBarcodeScanLog::create([
            'barcode_value' => 'LOG-TEST-002',
            'was_successful' => false,
            'failure_reason' => 'Test failure',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/barcode/scan-logs');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'logs.data');
    }

    public function test_can_filter_scan_logs_by_success(): void
    {
        ProductBarcodeScanLog::create([
            'barcode_value' => 'SUCCESS-LOG',
            'was_successful' => true,
        ]);

        ProductBarcodeScanLog::create([
            'barcode_value' => 'FAILED-LOG',
            'was_successful' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/barcode/scan-logs?successful_only=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'logs.data')
            ->assertJsonPath('logs.data.0.barcode_value', 'SUCCESS-LOG');
    }

    public function test_can_create_barcode_for_variant(): void
    {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/barcode', [
                'product_id' => $this->product->id,
                'variant_id' => $variant->id,
                'barcode_value' => 'VARIANT-BARCODE',
                'barcode_type' => 'code128',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $this->product->id,
            'product_variant_id' => $variant->id,
            'barcode_value' => 'VARIANT-BARCODE',
        ]);
    }

    public function test_can_create_barcode_for_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();
        
        $productWarehouse = ProductWarehouse::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $warehouse->id,
            'quantity_available' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/barcode', [
                'product_id' => $this->product->id,
                'warehouse_id' => $productWarehouse->id,
                'barcode_value' => 'WAREHOUSE-BARCODE',
                'barcode_type' => 'code128',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $this->product->id,
            'product_warehouse_id' => $productWarehouse->id,
            'barcode_value' => 'WAREHOUSE-BARCODE',
        ]);
    }
}
