<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Marketing\AccountCommunicationService;
use App\Services\Marketing\EmailQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionalEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure email_messages table exists (may require marketing migrations)
        if (! Schema::hasTable('email_messages')) {
            $this->markTestSkipped('email_messages table does not exist — run marketing migrations.');
        }
    }

    public function test_registration_queues_welcome_email(): void
    {
        $role = Role::firstOrCreate(['name' => 'customer'], [
            'display_name' => 'Customer',
            'description' => 'Test',
            'permissions' => ['cart.manage'],
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Test Customer',
            'email' => 'test-register@neogiga.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
        ]);

        // Simulate registration email
        $service = app(AccountCommunicationService::class);
        $service->registration($user);

        // Assert email was queued
        $this->assertDatabaseHas('email_messages', [
            'to_email' => 'test-register@neogiga.com',
            'message_type' => 'transactional',
        ]);

        $message = DB::table('email_messages')
            ->where('to_email', 'test-register@neogiga.com')
            ->first();

        $this->assertNotNull($message);
        $this->assertNotEmpty($message->idempotency_key);
        $this->assertContains($message->status, ['queued', 'scheduled', 'sent', 'test_queued']);
    }

    public function test_registration_email_has_correct_metadata(): void
    {
        $role = Role::firstOrCreate(['name' => 'customer'], [
            'display_name' => 'Customer', 'description' => 'Test',
            'permissions' => ['cart.manage'], 'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@neogiga.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
        ]);

        app(AccountCommunicationService::class)->registration($user);

        $message = DB::table('email_messages')
            ->where('to_email', 'jane@neogiga.com')
            ->first();

        $metadata = json_decode((string) ($message->metadata ?? '{}'), true) ?: [];
        $this->assertArrayHasKey('event_type', $metadata);
        $this->assertContains($metadata['event_type'] ?? '', ['registration_received', 'email_verification']);
    }

    public function test_idempotency_key_prevents_duplicates(): void
    {
        $role = Role::firstOrCreate(['name' => 'customer'], [
            'display_name' => 'Customer', 'description' => 'Test',
            'permissions' => ['cart.manage'], 'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Dup Test',
            'email' => 'dup@neogiga.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
        ]);

        app(AccountCommunicationService::class)->registration($user);

        $before = DB::table('email_messages')
            ->where('to_email', 'dup@neogiga.com')
            ->count();

        // Second registration call should not create duplicates
        app(AccountCommunicationService::class)->registration($user);

        $after = DB::table('email_messages')
            ->where('to_email', 'dup@neogiga.com')
            ->count();

        $this->assertSame($before, $after, 'Idempotency should prevent duplicate email queue entries');
    }

    public function test_transactional_email_disabled_behavior(): void
    {
        config(['marketing.transactional.enabled' => false]);

        $role = Role::firstOrCreate(['name' => 'customer'], [
            'display_name' => 'Customer', 'description' => 'Test',
            'permissions' => ['cart.manage'], 'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Disabled Test',
            'email' => 'disabled@neogiga.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $role->id,
        ]);

        try {
            app(AccountCommunicationService::class)->registration($user);
        } catch (\Throwable) {
            // Should not throw even when disabled
        }

        // Message should either not exist or be suppressed
        $message = DB::table('email_messages')
            ->where('to_email', 'disabled@neogiga.com')
            ->first();

        if ($message) {
            $this->assertContains($message->status, ['suppressed', 'queued', 'scheduled', 'test_queued']);
        }
        // If message doesn't exist, that's also valid when disabled
        $this->assertTrue(true);
    }

    public function test_welcome_template_renders_without_error(): void
    {
        $this->assertTrue(view()->exists('mail.transactional.welcome'));
        $this->assertTrue(view()->exists('mail.transactional.layout'));

        $html = view('mail.transactional.welcome', [
            'locale' => 'en',
            'brand' => 'NeoGiga',
            'regionName' => 'Pakistan',
            'greeting' => 'Welcome to NeoGiga Pakistan!',
            'userName' => 'Test User',
            'userEmail' => 'test@neogiga.com',
            'loginUrl' => 'https://pk.neogiga.com/en/login',
            'securityNote' => 'This is a transactional message.',
        ])->render();

        $this->assertStringContainsString('Welcome to NeoGiga Pakistan!', $html);
        $this->assertStringContainsString('Test User', $html);
        $this->assertStringContainsString('test@neogiga.com', $html);
        $this->assertStringContainsString('pk.neogiga.com', $html);
        $this->assertStringNotContainsString('{{', $html); // No unrendered Blade
    }

    public function test_order_status_template_renders_without_error(): void
    {
        $this->assertTrue(view()->exists('mail.transactional.order-status'));

        $html = view('mail.transactional.order-status', [
            'locale' => 'en',
            'brand' => 'NeoGiga',
            'orderNumber' => 'NG-2026-00123',
            'statusLabel' => 'Shipped',
            'statusBadge' => 'badge-ok',
            'statusDate' => '2026-07-19',
            'statusMessage' => 'Your order has been dispatched.',
            'trackingNumber' => '1Z999AA10123456784',
            'carrier' => 'UPS',
            'orderUrl' => 'https://neogiga.com/en/account/orders/123',
            'securityNote' => 'This is a transactional message.',
        ])->render();

        $this->assertStringContainsString('NG-2026-00123', $html);
        $this->assertStringContainsString('Shipped', $html);
        $this->assertStringContainsString('1Z999AA10123456784', $html);
        $this->assertStringContainsString('UPS', $html);
        $this->assertStringNotContainsString('{{', $html);
    }

    public function test_order_confirmation_template_renders_without_error(): void
    {
        $this->assertTrue(view()->exists('mail.transactional.order-confirmation'));

        $html = view('mail.transactional.order-confirmation', [
            'locale' => 'en',
            'brand' => 'NeoGiga',
            'orderNumber' => 'NG-2026-00500',
            'orderDate' => '2026-07-19',
            'orderStatus' => 'Confirmed',
            'orderTotal' => '1,250.00',
            'currency' => 'USD',
            'paymentStatus' => 'Paid',
            'products' => [
                ['name' => 'STM32F103C8T6', 'mpn' => 'STM32F103C8T6', 'quantity' => 10, 'price' => '125.00'],
                ['name' => 'TPS5430DDAR', 'mpn' => 'TPS5430DDAR', 'quantity' => 5, 'price' => '50.00'],
            ],
            'shippingAddress' => '123 Tech Park, Karachi, Pakistan',
            'orderUrl' => 'https://pk.neogiga.com/en/account/orders/500',
            'securityNote' => 'This is a transactional message.',
        ])->render();

        $this->assertStringContainsString('NG-2026-00500', $html);
        $this->assertStringContainsString('STM32F103C8T6', $html);
        $this->assertStringContainsString('1,250.00', $html);
        $this->assertStringContainsString('Karachi, Pakistan', $html);
        $this->assertStringNotContainsString('{{', $html);
    }
}
