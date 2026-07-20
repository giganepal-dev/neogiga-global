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
        $this->get('/reseller/login')->assertOk()->assertSee('Reseller sign in');
        $this->get('/manufacturer/login')->assertOk()->assertSee('Manufacturer sign in');
        $this->get('/b2b/login')->assertOk()->assertSee('Business sign in');
        $this->get('/distributor/login')->assertOk()->assertSee('Distributor sign in');
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
        $this->get('/distributor/products')->assertOk();
        $this->get('/distributor/orders')->assertOk();
        $this->get('/distributor/profile')->assertOk();
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
