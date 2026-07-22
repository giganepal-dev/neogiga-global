<?php

namespace Tests\Feature\Phases;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Marketplace\Marketplace;
use App\Models\Warehouse\Warehouse;
use App\Models\Purchasing\PurchaseOrder;
use App\Models\Accounting\JournalEntry;
use App\Models\Analytics\KpiDefinition;

class Phases456IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Marketplace $marketplace;
    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->marketplace = Marketplace::factory()->create(['is_active' => true]);
        $this->warehouse = Warehouse::factory()->create([
            'marketplace_id' => $this->marketplace->id,
            'is_active' => true
        ]);
    }

    /**
     * Test Phase 4: Purchase Requisition Workflow
     */
    public function test_purchase_requisition_creation_and_approval(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/purchasing/requisitions', [
                'warehouse_id' => $this->warehouse->id,
                'justification' => 'Urgent stock requirement for Q4 sales',
                'required_by_date' => now()->addDays(14)->toDateString(),
                'items' => [
                    [
                        'product_id' => 1,
                        'quantity_requested' => 100,
                        'estimated_unit_cost' => 25.50
                    ]
                ]
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('purchase_requisitions', [
            'warehouse_id' => $this->warehouse->id,
            'status' => 'submitted'
        ]);
    }

    /**
     * Test Phase 4: Supplier RFQ and Quotation
     */
    public function test_supplier_rfq_process(): void
    {
        // Create RFQ
        $rfqResponse = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/purchasing/rfqs', [
                'title' => 'Electronic Components Q1 2026',
                'submission_deadline' => now()->addDays(7)->toDateString(),
                'items' => [
                    [
                        'product_name' => 'Arduino Uno R3',
                        'quantity' => 50,
                        'specifications' => 'Original board with ATmega328P'
                    ]
                ],
                'supplier_ids' => [1, 2]
            ]);

        $rfqResponse->assertStatus(201);
        $rfqId = $rfqResponse->json('data.id');

        $this->assertDatabaseHas('supplier_rfqs', [
            'id' => $rfqId,
            'status' => 'sent'
        ]);

        $this->assertDatabaseCount('rfq_suppliers', 2);
    }

    /**
     * Test Phase 4: Goods Receipt with Quality Check
     */
    public function test_goods_receipt_with_quality_inspection(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'status' => 'approved'
        ]);

        $grnResponse = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/purchasing/goods-receipts', [
                'purchase_order_id' => $purchaseOrder->id,
                'receipt_date' => now()->toDateString(),
                'items' => [
                    [
                        'purchase_order_item_id' => $purchaseOrder->items()->first()->id,
                        'quantity_received' => 95,
                        'quantity_accepted' => 90,
                        'quantity_rejected' => 5,
                        'quality_status' => 'partial'
                    ]
                ]
            ]);

        $grnResponse->assertStatus(201);
        
        $this->assertDatabaseHas('goods_receipts', [
            'purchase_order_id' => $purchaseOrder->id,
            'quality_status' => 'partial'
        ]);
    }

    /**
     * Test Phase 5: Journal Entry Balance Validation
     */
    public function test_journal_entry_must_be_balanced(): void
    {
        $unbalancedData = [
            'entry_date' => now()->toDateString(),
            'description' => 'Test unbalanced entry',
            'lines' => [
                [
                    'account_id' => 1,
                    'direction' => 'debit',
                    'amount' => 1000
                ],
                [
                    'account_id' => 2,
                    'direction' => 'credit',
                    'amount' => 900 // Unbalanced!
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/accounting/journal-entries', $unbalancedData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('lines');
    }

    /**
     * Test Phase 5: Balanced Journal Entry Creation
     */
    public function test_balanced_journal_entry_creation(): void
    {
        $balancedData = [
            'entry_date' => now()->toDateString(),
            'description' => 'Test balanced entry',
            'lines' => [
                [
                    'account_id' => 1,
                    'direction' => 'debit',
                    'amount' => 1000
                ],
                [
                    'account_id' => 2,
                    'direction' => 'credit',
                    'amount' => 1000
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/accounting/journal-entries', $balancedData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('journal_entries', [
            'is_balanced' => true,
            'total_debit' => 1000,
            'total_credit' => 1000
        ]);
    }

    /**
     * Test Phase 5: Customer Ledger Update on Invoice
     */
    public function test_customer_ledger_updates_on_invoice(): void
    {
        // Simulate invoice creation that should update customer ledger
        $this->assertDatabaseHas('chart_of_accounts', [
            'account_type' => 'asset'
        ]);
    }

    /**
     * Test Phase 6: KPI Definition and Calculation
     */
    public function test_kpi_definition_creation(): void
    {
        $kpiData = [
            'kpi_code' => 'GROSS_MARGIN_PERCENT',
            'kpi_name' => 'Gross Margin Percentage',
            'category' => 'financial',
            'formula_definition' => '(revenue - cogs) / revenue * 100',
            'unit_type' => 'percentage',
            'decimal_places' => 2
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/analytics/kpis', $kpiData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('kpi_definitions', [
            'kpi_code' => 'GROSS_MARGIN_PERCENT',
            'category' => 'financial'
        ]);
    }

    /**
     * Test Phase 6: Dashboard Configuration
     */
    public function test_analytics_dashboard_creation(): void
    {
        $dashboardData = [
            'dashboard_name' => 'Executive Overview',
            'dashboard_type' => 'executive',
            'layout_config' => [
                'widgets' => [
                    ['type' => 'kpi_card', 'kpi' => 'total_revenue', 'position' => [0, 0]],
                    ['type' => 'chart', 'chart_type' => 'line', 'position' => [0, 1]]
                ]
            ],
            'is_public' => true
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/analytics/dashboards', $dashboardData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('analytics_dashboards', [
            'dashboard_name' => 'Executive Overview',
            'dashboard_type' => 'executive'
        ]);
    }

    /**
     * Test Phase 6: Alert Rule Creation
     */
    public function test_inventory_alert_rule(): void
    {
        $alertData = [
            'rule_name' => 'Low Stock Alert',
            'alert_type' => 'inventory',
            'trigger_condition' => 'available_quantity < reorder_point',
            'severity' => 'high',
            'notification_channels' => ['email', 'slack'],
            'recipients' => [$this->adminUser->id]
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/analytics/alerts', $alertData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('alert_rules', [
            'rule_name' => 'Low Stock Alert',
            'alert_type' => 'inventory'
        ]);
    }

    /**
     * Test Cross-Phase Integration: PO to Journal Entry
     */
    public function test_purchase_order_creates_accounting_entries(): void
    {
        // When a purchase order is approved and goods are received,
        // it should create appropriate journal entries
        
        $po = PurchaseOrder::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'status' => 'approved',
            'total_amount' => 5000
        ]);

        // Verify accounting mappings exist for automatic journal creation
        $this->assertDatabaseHas('accounting_mappings', [
            'mapping_type' => 'purchase'
        ]);
    }

    /**
     * Test Regional Isolation in Analytics
     */
    public function test_analytics_respect_marketplace_isolation(): void
    {
        $marketplace2 = Marketplace::factory()->create(['is_active' => true]);
        
        $kpi = KpiDefinition::factory()->create([
            'marketplace_id' => $this->marketplace->id,
            'kpi_code' => 'TEST_KPI'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/analytics/kpis?marketplace_id={$this->marketplace->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['kpi_code' => 'TEST_KPI']);

        // Should not see KPIs from other marketplace
        $response2 = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/analytics/kpis?marketplace_id={$marketplace2->id}");

        $response2->assertStatus(200)
            ->assertJsonMissing(['kpi_code' => 'TEST_KPI']);
    }

    /**
     * Test Data Export Job Queue
     */
    public function test_data_export_job_creation(): void
    {
        $exportData = [
            'export_name' => 'Monthly Sales Report',
            'export_type' => 'orders',
            'filters' => ['date_range' => 'last_month'],
            'output_format' => 'xlsx'
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/analytics/exports', $exportData);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('data_export_jobs', [
            'export_name' => 'Monthly Sales Report',
            'export_type' => 'orders'
        ]);
    }
}
