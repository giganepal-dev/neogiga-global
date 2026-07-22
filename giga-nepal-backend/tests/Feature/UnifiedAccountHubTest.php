<?php

namespace Tests\Feature;

use App\Models\Pcb\PcbProject;
use App\Models\User;
use App\Services\Account\PartnerApplicationService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\TokenMismatchException;
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
        $this->get('/account/pcb')->assertRedirect('/login');
    }

    public function test_expired_logout_submission_recovers_at_login_instead_of_rendering_419(): void
    {
        $request = Request::create('/logout', 'POST');
        $this->app->instance('request', $request);
        $response = app(ExceptionHandler::class)->render($request, new TokenMismatchException('CSRF token mismatch.'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(url('/login'), $response->headers->get('Location'));
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
        foreach (['orders', 'rfqs', 'quotations', 'bom', 'pcb', 'saved', 'notifications', 'support', 'payments', 'profile', 'security', 'addresses', 'applications'] as $section) {
            $this->actingAs($user)->get('/account/'.$section)->assertOk();
        }
    }

    public function test_pcb_dashboard_uses_the_same_owner_and_active_member_visibility_as_the_pcb_workspace(): void
    {
        config()->set('pcb.domain', 'pcb.neogiga.com');
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $owned = PcbProject::create(['user_id' => $customer->id, 'name' => 'Customer PCB controller']);
        $shared = PcbProject::create(['user_id' => $other->id, 'name' => 'Shared PCB sensor']);
        $expired = PcbProject::create(['user_id' => $other->id, 'name' => 'Expired PCB archive']);
        $hidden = PcbProject::create(['user_id' => $other->id, 'name' => 'Private PCB project']);
        $shared->members()->create(['user_id' => $customer->id, 'role' => 'viewer', 'nda_accepted' => true]);
        $expired->members()->create([
            'user_id' => $customer->id, 'role' => 'viewer', 'nda_accepted' => true,
            'access_expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($customer)->get('/account/pcb')
            ->assertOk()
            ->assertSee('PCB project dashboard')
            ->assertSee($owned->name)
            ->assertSee($shared->name)
            ->assertSee('https://pcb.neogiga.com/en/projects/'.$owned->id)
            ->assertDontSee($expired->name)
            ->assertDontSee($hidden->name);

        $this->actingAs($customer)->get('/account')
            ->assertOk()
            ->assertSee('PCB engineering projects')
            ->assertSee($owned->name);
    }

    public function test_owned_commerce_details_are_visible_and_other_accounts_are_hidden(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ownedOrder = DB::table('orders')->insertGetId($this->order('NG-DETAIL-001', $owner->id));
        $otherOrder = DB::table('orders')->insertGetId($this->order('NG-DETAIL-002', $other->id));

        $this->actingAs($owner)->get('/account/orders/'.$ownedOrder)->assertOk()->assertSee('NG-DETAIL-001');
        $this->actingAs($owner)->get('/account/orders/'.$otherOrder)->assertNotFound();
    }

    public function test_customer_can_create_and_reply_to_owned_support_ticket(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/account/support', [
            'subject' => 'Order tracking question', 'message' => 'Please confirm the delivery checkpoint.',
            'category' => 'support', 'priority' => 'medium',
        ]);
        $ticket = DB::table('support_tickets')->where('user_id', $user->id)->first();
        $response->assertRedirect('/account/support/'.$ticket->id);

        $this->actingAs($user)->post('/account/support/'.$ticket->id.'/reply', ['message' => 'Adding the order reference.'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('support_ticket_messages', ['support_ticket_id' => $ticket->id, 'message' => 'Adding the order reference.']);
    }

    public function test_customer_can_update_notification_preferences_with_security_alerts_preserved(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patch('/account/notifications', [
            'preferences' => ['order_updates' => ['email' => '1'], 'security' => []],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id, 'notification_type' => 'order_updates', 'email_enabled' => true, 'push_enabled' => false,
        ]);
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id, 'notification_type' => 'security', 'email_enabled' => true, 'push_enabled' => true, 'is_mandatory' => true,
        ]);
    }

    public function test_customer_can_resubmit_an_owned_application_needing_information(): void
    {
        $user = User::factory()->create();
        $application = DB::table('account_applications')->insertGetId([
            'application_number' => 'NG-RESUBMIT-001', 'user_id' => $user->id, 'role_key' => 'reseller',
            'status' => 'needs_information', 'company_name' => 'Resubmit Company', 'review_notes' => 'Provide territory evidence.',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)->post('/account/applications/'.$application.'/resubmit', [
            'applicant_notes' => 'The requested territory evidence is now attached to our company file.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('account_applications', ['id' => $application, 'status' => 'submitted']);
        $this->assertDatabaseHas('account_application_events', ['account_application_id' => $application, 'event_type' => 'resubmitted']);
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
