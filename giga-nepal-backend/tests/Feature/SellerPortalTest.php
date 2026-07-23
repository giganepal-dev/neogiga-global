<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Release 2 slice 1 — seller web portal: session login gated on a linked
 * vendor, dashboard over SellerDashboardService, and the seller-isolation
 * invariant (a seller can never see another vendor's products/orders).
 */
class SellerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/seller/login')
            ->assertOk()
            ->assertSee('Seller sign in')
            ->assertHeader('X-Page-Cache', 'BYPASS')
            ->assertHeader('Cache-Control', 'no-cache, private');
    }

    public function test_unauthenticated_portal_access_redirects_to_login(): void
    {
        foreach (['', '/products', '/orders', '/inventory', '/payouts', '/support', '/profile'] as $path) {
            $this->get('/seller'.$path)->assertRedirect('/seller/login');
        }
    }

    public function test_user_without_vendor_cannot_enter(): void
    {
        $user = $this->user('novendor@example.com');

        $this->post('/seller/login', ['email' => $user->email, 'password' => 'secret'])
            ->assertRedirect()->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_vendor_owner_logs_in_and_sees_dashboard(): void
    {
        [$user, $vendorId] = $this->seller('owner@example.com', 'Acme Components');
        $this->product($vendorId, 'Acme Resistor', 'ACME-R1', 'approved');

        $this->post('/seller/login', ['email' => $user->email, 'password' => 'secret'])
            ->assertRedirect('/seller');

        $res = $this->get('/seller');
        $res->assertOk();
        $res->assertSee('Acme Components');
        $res->assertSee('Gross Sales');
        $res->assertSee('Seller Readiness');
        $res->assertViewHas('overview', fn ($overview) => $overview['products']['total_products'] === 1);
        $res->assertViewHas('stats', fn ($s) => $s['product_count'] === 1);
    }

    public function test_seller_operational_pages_and_support_are_vendor_scoped(): void
    {
        [$userA, $vendorA] = $this->seller('ops-a@example.com', 'Operations A');
        [, $vendorB] = $this->seller('ops-b@example.com', 'Operations B');
        DB::table('vendor_support_tickets')->insert([
            'vendor_id' => $vendorB,
            'ticket_number' => 'VST-OTHER',
            'subject' => 'Private ticket for vendor B',
            'category' => 'general',
            'priority' => 'normal',
            'status' => 'open',
            'message' => 'This must remain private.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($userA);
        $this->get('/seller/inventory')->assertOk()->assertSee('Stock positions');
        $this->get('/seller/payouts')->assertOk()->assertSee('Payout history');
        $this->get('/seller/support')->assertOk()->assertDontSee('Private ticket for vendor B');
        $this->post('/seller/support', [
            'subject' => 'Order settlement question',
            'category' => 'payouts',
            'priority' => 'normal',
            'message' => 'Please confirm the settlement schedule.',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('vendor_support_tickets', [
            'vendor_id' => $vendorA,
            'subject' => 'Order settlement question',
        ]);
        $this->assertDatabaseMissing('vendor_support_tickets', [
            'vendor_id' => $vendorB,
            'subject' => 'Order settlement question',
        ]);
    }

    public function test_seller_sees_only_own_products(): void
    {
        [$userA, $vendorA] = $this->seller('a@example.com', 'Vendor A');
        [, $vendorB] = $this->seller('b@example.com', 'Vendor B');
        $this->product($vendorA, 'Alpha Part', 'VA-1', 'approved');
        $this->product($vendorB, 'Bravo Secret Part', 'VB-1', 'approved');

        $this->actingAs($userA);
        $res = $this->get('/seller/products');

        $res->assertOk();
        $res->assertSee('Alpha Part');
        $res->assertDontSee('Bravo Secret Part'); // isolation invariant
    }

    public function test_vendor_staff_member_gets_access_via_vendor_staff_table(): void
    {
        [, $vendorId] = $this->seller('boss@example.com', 'Staffed Vendor');
        $staff = $this->user('staff@example.com');
        if (! Schema::hasTable('vendor_staff')) {
            $this->markTestSkipped('vendor_staff table not present in this schema.');
        }
        DB::table('vendor_staff')->insert([
            'vendor_id' => $vendorId, 'user_id' => $staff->id, 'name' => 'Staff Member',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($staff);
        $this->get('/seller')->assertOk()->assertSee('Staffed Vendor');
    }

    // ---- fixtures ------------------------------------------------------------

    /** @return array{0:User,1:int} */
    private function seller(string $email, string $vendorName): array
    {
        $user = $this->user($email);
        $vendorId = DB::table('vendors')->insertGetId([
            'user_id' => $user->id,
            'name' => $vendorName,
            'slug' => Str::slug($vendorName).'-'.substr(md5($email), 0, 5),
            'email' => 'v-'.$email,
            'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$user, $vendorId];
    }

    private function user(string $email): User
    {
        $role = Role::firstOrCreate(['name' => 'seller'], ['display_name' => 'Seller', 'is_active' => true]);

        return User::create([
            'name' => ucfirst(strtok($email, '@')), 'email' => $email,
            'password' => bcrypt('secret'), 'role_id' => $role->id,
        ]);
    }

    private function product(int $vendorId, string $name, string $sku, string $status): void
    {
        DB::table('products')->insert([
            'name' => $name, 'slug' => Str::slug($sku), 'sku' => $sku,
            'vendor_id' => $vendorId, 'type' => 'simple', 'status' => $status,
            'base_price' => 1.00, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
