<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Account\PartnerApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnifiedAccountHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_pages_require_authentication(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->get('/account/orders')->assertRedirect('/login');
        $this->get('/account/applications')->assertRedirect('/login');
    }

    public function test_dashboard_and_lists_only_show_records_owned_by_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        DB::table('orders')->insert([
            $this->order('NG-OWNER-001', $owner->id),
            $this->order('NG-OTHER-001', $other->id),
        ]);

        $this->actingAs($owner)->get('/account')
            ->assertOk()->assertSee('account-hub.css')->assertSee('NG-OWNER-001')->assertDontSee('NG-OTHER-001');
        $this->actingAs($owner)->get('/account/orders')
            ->assertOk()->assertSee('NG-OWNER-001')->assertDontSee('NG-OTHER-001');
    }

    public function test_customer_can_submit_partner_application_with_private_document(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['home_marketplace_id' => null]);

        $response = $this->actingAs($user)->post('/account/applications', [
            'role_key' => 'manufacturer', 'company_name' => 'Neo Devices', 'legal_name' => 'Neo Devices Pvt Ltd',
            'contact_phone' => '+9779800000000', 'territory' => 'South Asia',
            'business_description' => 'We manufacture verified embedded control modules.',
            'documents' => [UploadedFile::fake()->create('registration.pdf', 120, 'application/pdf')],
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('account_applications', ['user_id' => $user->id, 'role_key' => 'manufacturer', 'status' => 'submitted']);
        $document = DB::table('account_application_documents')->first();
        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->storage_path);
    }

    public function test_unapproved_role_cannot_be_selected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/account/role', ['role_key' => 'seller'])->assertForbidden();
    }

    public function test_all_customer_account_sections_render_without_placeholder_errors(): void
    {
        $user = User::factory()->create();
        foreach (['orders', 'rfqs', 'quotations', 'bom', 'saved', 'notifications', 'support', 'payments', 'profile', 'security', 'addresses', 'applications'] as $section) {
            $this->actingAs($user)->get('/account/'.$section)->assertOk();
        }
    }

    public function test_approval_provisions_each_supported_partner_context_and_role_entitlement(): void
    {
        $reviewer = User::factory()->create();
        foreach (['institution', 'reseller', 'seller', 'regional_distributor', 'global_distributor', 'manufacturer', 'brand_owner', 'warehouse_partner'] as $index => $role) {
            $user = User::factory()->create();
            $application = DB::table('account_applications')->insertGetId([
                'application_number' => 'NG-TEST-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'user_id' => $user->id, 'role_key' => $role, 'status' => 'submitted',
                'company_name' => 'Partner '.$index, 'legal_name' => 'Partner '.$index.' Ltd',
                'contact_phone' => '+97798000000'.$index, 'territory' => 'Test territory',
                'business_description' => 'Test capability statement', 'submitted_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            app(PartnerApplicationService::class)->approve($application, $reviewer->id);
            $this->assertDatabaseHas('account_applications', ['id' => $application, 'status' => 'approved']);
            $this->assertDatabaseHas('user_account_roles', ['user_id' => $user->id, 'role_key' => $role, 'status' => 'approved']);
        }
    }

    private function order(string $number, int $userId): array
    {
        return [
            'order_number' => $number, 'user_id' => $userId, 'status' => 'pending', 'currency_code' => 'USD',
            'subtotal' => 10, 'tax_total' => 0, 'discount_total' => 0, 'shipping_total' => 0,
            'grand_total' => 10, 'amount_paid' => 0, 'amount_due' => 10, 'payment_status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ];
    }
}
