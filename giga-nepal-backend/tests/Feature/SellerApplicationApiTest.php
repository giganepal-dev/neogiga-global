<?php

namespace Tests\Feature;

use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerApplicationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // This suite tests PR#2's duplicate seller-application module, which is
        // deliberately UNWIRED: the live /seller-applications URI space belongs
        // to the Api\Onboarding module and the live table uses its schema.
        // Kept as reference alongside the module code (see REFERENCE docs).
        $this->markTestSkipped('Targets the unwired PR#2 duplicate module; live seller applications are served by Api\Onboarding.');
    }

    public function test_public_can_submit_seller_application(): void
    {
        $payload = [
            'business_name' => 'Test Electronics Store',
            'business_type' => 'Retailer',
            'contact_person' => 'John Doe',
            'email' => 'john@teststore.com',
            'phone' => '+977-9841234567',
            'country' => 'Nepal',
            'state' => 'Bagmati',
            'city' => 'Kathmandu',
            'business_address' => 'Test Address',
            'pan_number' => '123456789',
            'product_categories' => ['Electronics', 'Mobile Accessories'],
            'brand_names' => ['Samsung', 'Apple'],
            'estimated_monthly_volume' => 500000,
            'additional_info' => 'Test application',
        ];

        $response = $this->postJson('/api/v1/seller-applications', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.business_name', 'Test Electronics Store');

        $this->assertDatabaseHas('seller_applications', [
            'business_name' => 'Test Electronics Store',
            'email' => 'john@teststore.com',
            'status' => 'pending',
        ]);
    }

    public function test_application_requires_required_fields(): void
    {
        $response = $this->postJson('/api/v1/seller-applications', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['business_name', 'contact_person', 'email', 'phone', 'country']);
    }

    public function test_admin_can_view_all_applications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        SellerApplication::create([
            'business_name' => 'Test Store 1',
            'contact_person' => 'Person 1',
            'email' => 'test1@store.com',
            'phone' => '+977-9841234567',
            'country' => 'Nepal',
            'status' => 'pending',
        ]);

        SellerApplication::create([
            'business_name' => 'Test Store 2',
            'contact_person' => 'Person 2',
            'email' => 'test2@store.com',
            'phone' => '+977-9841234568',
            'country' => 'India',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/seller-applications');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data');
    }

    public function test_admin_can_view_single_application(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $application = SellerApplication::create([
            'business_name' => 'Test Store',
            'contact_person' => 'Test Person',
            'email' => 'test@store.com',
            'phone' => '+977-9841234567',
            'country' => 'Nepal',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/seller-applications/{$application->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $application->id)
            ->assertJsonPath('data.business_name', 'Test Store');
    }

    public function test_admin_can_approve_application(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $application = SellerApplication::create([
            'business_name' => 'Test Store',
            'contact_person' => 'Test Person',
            'email' => 'test@store.com',
            'phone' => '+977-9841234567',
            'country' => 'Nepal',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/seller-applications/{$application->id}", [
                'action' => 'approve',
                'admin_notes' => 'Approved for electronics category',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('seller_applications', [
            'id' => $application->id,
            'status' => 'approved',
            'admin_notes' => 'Approved for electronics category',
        ]);
    }

    public function test_admin_can_reject_application(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $application = SellerApplication::create([
            'business_name' => 'Test Store',
            'contact_person' => 'Test Person',
            'email' => 'test@store.com',
            'phone' => '+977-9841234567',
            'country' => 'Nepal',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/seller-applications/{$application->id}", [
                'action' => 'reject',
                'admin_notes' => 'Incomplete documentation',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('seller_applications', [
            'id' => $application->id,
            'status' => 'rejected',
            'admin_notes' => 'Incomplete documentation',
        ]);
    }

    public function test_admin_can_mark_application_under_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $application = SellerApplication::create([
            'business_name' => 'Test Store',
            'contact_person' => 'Test Person',
            'email' => 'test@store.com',
            'phone' => '+977-9841234567',
            'country' => 'Nepal',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/seller-applications/{$application->id}", [
                'action' => 'under_review',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('seller_applications', [
            'id' => $application->id,
            'status' => 'under_review',
        ]);
    }

    public function test_admin_can_view_stats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        SellerApplication::create([
            'business_name' => 'Pending Store',
            'contact_person' => 'Person',
            'email' => 'pending@store.com',
            'phone' => '+977-9841234567',
            'country' => 'Nepal',
            'status' => 'pending',
        ]);

        SellerApplication::create([
            'business_name' => 'Approved Store',
            'contact_person' => 'Person',
            'email' => 'approved@store.com',
            'phone' => '+977-9841234568',
            'country' => 'Nepal',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/seller-applications/stats');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total' => 2,
                    'pending' => 1,
                    'approved' => 1,
                    'under_review' => 0,
                    'rejected' => 0,
                ],
            ]);
    }

    public function test_unauthorized_user_cannot_view_applications(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/seller-applications');

        $response->assertStatus(403);
    }

    public function test_admin_can_filter_applications_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        SellerApplication::create([
            'business_name' => 'Pending Store',
            'contact_person' => 'Person',
            'email' => 'pending@store.com',
            'phone' => '+977-9841234567',
            'country' => 'Nepal',
            'status' => 'pending',
        ]);

        SellerApplication::create([
            'business_name' => 'Approved Store',
            'contact_person' => 'Person',
            'email' => 'approved@store.com',
            'phone' => '+977-9841234568',
            'country' => 'Nepal',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/seller-applications?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.status', 'pending');
    }

    public function test_admin_can_search_applications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        SellerApplication::create([
            'business_name' => 'Tech Electronics',
            'contact_person' => 'Rajesh Kumar',
            'email' => 'rajesh@tech.com',
            'phone' => '+977-9841234567',
            'country' => 'Nepal',
            'status' => 'pending',
        ]);

        SellerApplication::create([
            'business_name' => 'Solar Solutions',
            'contact_person' => 'Priya Sharma',
            'email' => 'priya@solar.com',
            'phone' => '+977-9841234568',
            'country' => 'India',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/seller-applications?search=rajesh');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.contact_person', 'Rajesh Kumar');
    }
}
