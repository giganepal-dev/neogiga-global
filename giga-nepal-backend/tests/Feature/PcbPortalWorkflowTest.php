<?php

namespace Tests\Feature;

use App\Models\Pcb\PcbProject;
use App\Models\Pcb\PcbQuoteConfiguration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use ZipArchive;

class PcbPortalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pcb_subdomain_serves_dedicated_portal_and_protects_projects(): void
    {
        $this->pcb()->get('/en')
            ->assertOk()
            ->assertSee('Build your next board.')
            ->assertSee('NeoGiga PCB');

        $this->pcb()->get('/en/projects')
            ->assertRedirect('/en/login');

        $this->get('https://neogiga.com/en')
            ->assertOk()
            ->assertDontSee('Build your next board.');
    }

    public function test_visitor_can_create_a_real_pcb_workspace_account(): void
    {
        $response = $this->pcb()->post('/en/register', [
            'name' => 'PCB Engineer',
            'email' => 'new-pcb-user@example.test',
            'password' => 'strong-password-2026',
            'password_confirmation' => 'strong-password-2026',
            'terms' => '1',
        ]);

        $response->assertRedirect('/en/projects');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new-pcb-user@example.test']);
    }

    public function test_customer_can_create_project_upload_safe_gerber_and_submit_quote(): void
    {
        Storage::fake('local');
        $customer = $this->customer();

        $this->actingAs($customer)->pcb()->post('/en/projects', $this->projectPayload())
            ->assertRedirect();

        $project = PcbProject::firstOrFail();
        $this->assertDatabaseHas('pcb_project_members', [
            'project_id' => $project->id,
            'user_id' => $customer->id,
            'role' => 'owner',
        ]);

        $this->actingAs($customer)->pcb()->post('/en/projects/'.$project->id.'/files', [
            'file_type' => 'gerber',
            'file' => $this->gerberUpload(),
        ])->assertRedirect();

        $this->assertDatabaseHas('pcb_files', ['project_id' => $project->id, 'file_type' => 'gerber']);
        $this->assertDatabaseHas('pcb_gerber_analysis_runs', ['project_id' => $project->id, 'status' => 'completed']);

        $this->actingAs($customer)->pcb()->post('/en/projects/'.$project->id.'/quotes', $this->quotePayload())
            ->assertRedirect();

        $this->assertDatabaseHas('pcb_quote_configurations', [
            'project_id' => $project->id,
            'status' => 'submitted',
            'quantity' => 10,
        ]);
        $this->assertDatabaseHas('pcb_projects', ['id' => $project->id, 'status' => 'quote_pending']);
        $this->actingAs($customer)->pcb()->get('/en/projects/'.$project->id)
            ->assertOk()
            ->assertSee('Engineering review in progress.')
            ->assertSee('board-gerbers.zip');
    }

    public function test_unsafe_zip_path_is_rejected_without_storing_file(): void
    {
        Storage::fake('local');
        $customer = $this->customer();
        $project = $this->project($customer);

        $response = $this->actingAs($customer)->pcb()->from('/en/projects/'.$project->id)->post('/en/projects/'.$project->id.'/files', [
            'file_type' => 'gerber',
            'file' => $this->zipUpload(['../private.txt' => 'unsafe']),
        ]);

        $response->assertRedirect('/en/projects/'.$project->id)->assertSessionHasErrors('file');
        $this->assertDatabaseCount('pcb_files', 0);
    }

    public function test_project_files_and_details_are_not_visible_to_another_customer(): void
    {
        $owner = $this->customer('owner@example.test');
        $other = $this->customer('other@example.test');
        $project = $this->project($owner);

        $this->actingAs($other)->pcb()->get('/en/projects/'.$project->id)->assertForbidden();
    }

    public function test_admin_can_issue_quote_and_customer_approval_creates_shared_order(): void
    {
        Storage::fake('local');
        $customer = $this->customer();
        $project = $this->project($customer);

        $this->actingAs($customer)->pcb()->post('/en/projects/'.$project->id.'/files', [
            'file_type' => 'gerber',
            'file' => $this->gerberUpload(),
        ])->assertRedirect();
        $this->actingAs($customer)->pcb()->post('/en/projects/'.$project->id.'/quotes', $this->quotePayload())->assertRedirect();
        $quote = PcbQuoteConfiguration::firstOrFail();

        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/pcb/projects/'.$project->id)
            ->assertOk()
            ->assertSee('Commercial quote')
            ->assertSee('board-gerbers.zip');
        $this->actingAs($admin)->post('/admin/pcb/projects/'.$project->id.'/quotes/'.$quote->id, [
            'setup_charge' => 20,
            'engineering_charge' => 30,
            'fabrication_unit_price' => 5.50,
            'currency' => 'USD',
            'lead_time_days' => 8,
            'quote_valid_until' => now()->addDays(14)->toDateString(),
            'engineering_notes' => 'Reviewed against the submitted board specification and Gerber archive.',
        ])->assertRedirect();

        $quote->refresh();
        $this->assertSame('quoted', $quote->status);
        $this->assertSame('55.00', $quote->total_fabrication_price);
        $this->actingAs($customer)->pcb()->get('/en/projects/'.$project->id)
            ->assertOk()
            ->assertSee('Commercial total')
            ->assertSee('USD 105.00');

        $this->actingAs($customer)->pcb()->post('/en/projects/'.$project->id.'/quotes/'.$quote->id.'/approve', [
            'customer_notes' => 'Proceed with the reviewed specification.',
        ])->assertRedirect();

        $quote->refresh();
        $this->assertSame('approved', $quote->status);
        $this->assertNotNull($quote->order_id);
        $this->assertDatabaseHas('orders', [
            'id' => $quote->order_id,
            'user_id' => $customer->id,
            'grand_total' => 105,
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $quote->order_id,
            'product_sku' => $project->code,
        ]);
        $this->assertDatabaseHas('pcb_projects', ['id' => $project->id, 'status' => 'ordered']);
        $this->actingAs($customer)->pcb()->get('/en/projects/'.$project->id)
            ->assertOk()
            ->assertSee($quote->order->order_number);
    }

    public function test_admin_pcb_queue_is_protected(): void
    {
        $this->get('/admin/pcb')->assertRedirect('/admin/login');
        $this->actingAs($this->customer())->get('/admin/pcb')->assertRedirect('/admin/login');
        $this->actingAs($this->admin())->get('/admin/pcb')->assertOk()->assertSee('PCB project queue');
    }

    public function test_pcb_api_creates_scoped_projects_and_does_not_allow_customer_status_escalation(): void
    {
        $customer = $this->customer();
        $token = 'pcb-api-test-token';
        $customer->forceFill(['api_token_hash' => hash('sha256', $token)])->save();

        $create = $this->withToken($token)->postJson('/api/v1/pcb/projects', [
            'name' => 'API-created controller board',
            'project_type' => 'prototype',
            'target_quantity' => 5,
            'currency' => 'USD',
            'destination_country' => 'Nepal',
        ]);

        $create->assertCreated()->assertJsonPath('data.status', 'draft');
        $project = PcbProject::findOrFail($create->json('data.id'));

        $this->withToken($token)->patchJson('/api/v1/pcb/projects/'.$project->id, [
            'name' => 'Updated API board',
            'status' => 'manufacturing',
        ])->assertOk()->assertJsonPath('data.status', 'draft');

        $this->getJson('/api/v1/pcb/public/capabilities')
            ->assertOk()
            ->assertJsonPath('data.quote_mode', 'manual_engineering_review')
            ->assertJsonPath('data.automatic_pricing_enabled', false);
    }

    private function pcb(): static
    {
        URL::forceRootUrl('https://pcb.neogiga.com');
        URL::forceScheme('https');

        return $this
            ->withHeader('Host', 'pcb.neogiga.com')
            ->withServerVariables([
                'HTTP_HOST' => 'pcb.neogiga.com',
                'SERVER_NAME' => 'pcb.neogiga.com',
                'SERVER_PORT' => 443,
                'HTTPS' => 'on',
            ]);
    }

    private function customer(string $email = 'pcb-customer@example.test'): User
    {
        $role = Role::firstOrCreate(['name' => 'customer'], [
            'display_name' => 'Customer',
            'permissions' => ['pcb.projects.manage'],
            'is_active' => true,
        ]);

        return User::factory()->create(['email' => $email, 'role_id' => $role->id]);
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], [
            'display_name' => 'Super Admin',
            'permissions' => ['*'],
            'is_active' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function project(User $customer): PcbProject
    {
        $this->actingAs($customer)->pcb()->post('/en/projects', $this->projectPayload())->assertRedirect();

        return PcbProject::latest()->firstOrFail();
    }

    private function projectPayload(): array
    {
        return [
            'name' => 'Industrial sensor controller',
            'description' => 'A two-layer controller for environmental sensing.',
            'application_type' => 'Industrial automation',
            'confidentiality' => 'internal',
            'project_type' => 'prototype',
            'target_quantity' => 10,
            'target_budget' => 500,
            'currency' => 'USD',
            'required_date' => now()->addMonth()->toDateString(),
            'destination_country' => 'Nepal',
            'shipping_postal_code' => '44600',
        ];
    }

    private function quotePayload(): array
    {
        return [
            'board_type' => 'double_sided',
            'quantity' => 10,
            'length_mm' => 80,
            'width_mm' => 50,
            'thickness_mm' => 1.6,
            'layer_count' => 2,
            'substrate_material' => 'FR-4',
            'outer_copper_oz' => '1',
            'solder_mask_color' => 'green',
            'silkscreen_color' => 'white',
            'surface_finish' => 'HASL_Lead_Free',
            'via_covering' => 'tented',
            'panelization_type' => 'none',
            'production_speed' => 'standard',
            'aoi_testing' => '1',
            'electrical_test' => '1',
        ];
    }

    private function gerberUpload(): UploadedFile
    {
        return $this->zipUpload([
            'controller.GTL' => 'top copper',
            'controller.GBL' => 'bottom copper',
            'controller.GTS' => 'top mask',
            'controller.GBS' => 'bottom mask',
            'controller.GKO' => 'outline',
            'controller.DRL' => 'drill',
        ]);
    }

    private function zipUpload(array $entries): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'pcb-test-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return new UploadedFile($path, 'board-gerbers.zip', 'application/zip', null, true);
    }
}
