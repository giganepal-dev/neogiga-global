<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_payment_processing()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Mock Stripe API
        $this->mockStripePaymentIntent();

        $response = $this->postJson('/api/v1/payments/process', [
            'order_id' => 1,
            'payment_method' => 'stripe',
            'payment_method_id' => 'pm_test_123'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_paypal_payment_processing()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Mock PayPal API
        $this->mockPayPalPayment();

        $response = $this->postJson('/api/v1/payments/process', [
            'order_id' => 1,
            'payment_method' => 'paypal'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_payment_refund()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/payments/refund', [
            'transaction_id' => 'pi_test_123',
            'amount' => 99.99,
            'gateway' => 'stripe'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    protected function mockStripePaymentIntent()
    {
        // Mock implementation for testing
    }

    protected function mockPayPalPayment()
    {
        // Mock implementation for testing
    }
}
