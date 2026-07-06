<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase1AuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_register_and_use_bearer_token(): void
    {
        Role::updateOrCreate(
            ['name' => 'customer'],
            [
                'display_name' => 'Customer',
                'permissions' => ['cart.manage', 'checkout.create', 'orders.view'],
                'is_active' => true,
            ]
        );

        $email = 'phase1-auth-'.uniqid().'@example.test';

        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Phase One Customer',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonPath('data.user.email', $email)
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertNotEmpty(User::where('email', $email)->value('api_token_hash'));

        $this->withToken($registerResponse->json('data.token'))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $email);
    }

    public function test_customer_can_login_and_logout_token(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'customer'],
            [
                'display_name' => 'Customer',
                'permissions' => ['cart.manage', 'checkout.create', 'orders.view'],
                'is_active' => true,
            ]
        );

        $email = 'phase1-login-'.uniqid().'@example.test';
        User::create([
            'name' => 'Phase One Login',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure(['data' => ['token']]);

        $this->withToken($loginResponse->json('data.token'))
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertNull(User::where('email', $email)->value('api_token_hash'));
    }
}
