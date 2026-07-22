<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Portal finalization — the four newer role portals (reseller, manufacturer,
 * b2b, distributor) mirror the seller portal's session login: the login page
 * renders, guests bounce to login, a login without a linked entity is
 * rejected and logged out, and a linked login lands on a working dashboard
 * plus every page the sidebar links to.
 */
class PortalLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_pages_render(): void
    {
        foreach ([
            '/reseller/login' => 'Reseller sign in',
            '/manufacturer/login' => 'Manufacturer sign in',
            '/b2b/login' => 'Business sign in',
            '/distributor/login' => 'Distributor sign in',
        ] as $path => $heading) {
            $this->get($path)
                ->assertOk()
                ->assertSee($heading)
                ->assertHeader('X-Page-Cache', 'BYPASS')
                ->assertHeader('Cache-Control', 'no-cache, private');
        }
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['reseller', 'manufacturer', 'b2b', 'distributor'] as $portal) {
            $this->get("/{$portal}")->assertRedirect("/{$portal}/login");
            $this->get("/{$portal}/products")->assertRedirect("/{$portal}/login");
        }
    }

    public function test_user_without_linked_entity_is_rejected(): void
    {
        foreach (['reseller', 'manufacturer', 'b2b', 'distributor'] as $portal) {
            $user = $this->user("nolink-{$portal}@example.com");
            $this->post("/{$portal}/login", ['email' => $user->email, 'password' => 'secret'])
                ->assertRedirect()->assertSessionHasErrors(['email']);
            $this->assertGuest();
        }
    }

    public function test_reseller_logs_in_and_portal_pages_work(): void
    {
        $user = $this->user('reseller@example.com');
        DB::table('resellers')->insert([
            'user_id' => $user->id, 'company_name' => 'Kathmandu Circuits',
            'status' => 'active', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post('/reseller/login', ['email' => $user->email, 'password' => 'secret'])
            ->assertRedirect('/reseller');
        $this->get('/reseller')->assertOk()->assertSee('Kathmandu Circuits');
        $this->get('/reseller/products')->assertOk();
        $this->get('/reseller/orders')->assertOk();
        $this->get('/reseller/profile')->assertOk();
    }

    public function test_manufacturer_logs_in_and_portal_pages_work(): void
    {
        $user = $this->user('mfr@example.com');
        DB::table('manufacturers')->insert([
            'user_id' => $user->id, 'name' => 'Shenzhen Semis', 'slug' => 'shenzhen-semis',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post('/manufacturer/login', ['email' => $user->email, 'password' => 'secret'])
            ->assertRedirect('/manufacturer');
        $this->get('/manufacturer')->assertOk()->assertSee('Shenzhen Semis');
        $this->get('/manufacturer/products')->assertOk();
        $this->get('/manufacturer/profile')->assertOk();
    }

    public function test_b2b_user_logs_in_and_portal_pages_work(): void
    {
        $user = $this->user('buyer@example.com');
        $accountId = DB::table('b2b_accounts')->insertGetId([
            'name' => 'Everest Labs', 'slug' => 'everest-labs', 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('b2b_account_users')->insert([
            'b2b_account_id' => $accountId, 'user_id' => $user->id, 'name' => 'Buyer',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post('/b2b/login', ['email' => $user->email, 'password' => 'secret'])
            ->assertRedirect('/b2b');
        $this->get('/b2b')->assertOk()->assertSee('Everest Labs');
        $this->get('/b2b/products')->assertOk();
        $this->get('/b2b/orders')->assertOk();
        $this->get('/b2b/rfqs')->assertOk();
    }

    public function test_distributor_logs_in_and_portal_pages_work(): void
    {
        $user = $this->user('dist@example.com');
        DB::table('distributors')->insert([
            'user_id' => $user->id, 'name' => 'Terai Distribution', 'slug' => 'terai-distribution',
            'email' => 'ops@terai.example.com', 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post('/distributor/login', ['email' => $user->email, 'password' => 'secret'])
            ->assertRedirect('/distributor');
        $this->get('/distributor')->assertOk()->assertSee('Terai Distribution');
        foreach (['products', 'orders', 'territory-stock', 'territories', 'commissions', 'payouts', 'downlines', 'leads', 'support', 'messages', 'profile'] as $page) {
            $this->get('/distributor/'.$page)->assertOk();
        }
    }

    public function test_distributor_dashboard_summaries_are_distributor_scoped(): void
    {
        $user = $this->user('scope-dist@example.com');
        $other = $this->user('scope-other@example.com');
        $distributorId = DB::table('distributors')->insertGetId([
            'user_id' => $user->id, 'name' => 'Scoped Distribution', 'slug' => 'scoped-distribution',
            'email' => 'scope@dist.example.com', 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherId = DB::table('distributors')->insertGetId([
            'user_id' => $other->id, 'name' => 'Other Distribution', 'slug' => 'other-distribution',
            'email' => 'other@dist.example.com', 'status' => 'approved',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([[$distributorId, 'OWN-ORDER', 'Own Lead'], [$otherId, 'OTHER-ORDER', 'Other Private Lead']] as [$ownerId, $reference, $leadName]) {
            DB::table('distributor_orders')->insert([
                'distributor_id' => $ownerId, 'order_reference' => $reference,
                'status' => 'pending', 'currency_code' => 'USD',
                'gross_amount' => 100, 'commission_amount' => 5,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('distributor_leads')->insert([
                'distributor_id' => $ownerId, 'name' => $leadName, 'status' => 'new',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->get('/distributor');
        $response->assertOk()->assertSee('OWN-ORDER')->assertSee('Own Lead');
        $response->assertDontSee('OTHER-ORDER')->assertDontSee('Other Private Lead');
        $response->assertViewHas('overview', fn ($overview) => $overview['orders'] === 1 && $overview['leads'] === 1);
    }

    private function user(string $email): User
    {
        $role = Role::firstOrCreate(['name' => 'portal-user'], ['display_name' => 'Portal User', 'is_active' => true]);

        return User::create([
            'name' => ucfirst(strtok($email, '@')), 'email' => $email,
            'password' => bcrypt('secret'), 'role_id' => $role->id,
        ]);
    }
}
