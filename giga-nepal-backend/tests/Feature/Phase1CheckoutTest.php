<?php

namespace Tests\Feature;

use App\Models\Payments\PaymentProvider;
use App\Models\Role;
use App\Models\User;
use App\Services\Payments\PaymentMethodPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase1CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_stocked_product_to_cart_and_checkout_pending_order(): void
    {
        $token = bin2hex(random_bytes(32));
        $user = $this->customerUser($token);
        $marketplaceId = $this->marketplaceId();
        $this->enablePaymentProvider('bank_transfer');
        $productId = $this->stockedProductId($marketplaceId);

        $this->withToken($token)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $productId,
                'marketplace_id' => $marketplaceId,
                'quantity' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.grand_total', '39.98');

        $checkoutResponse = $this->withToken($token)
            ->postJson('/api/v1/checkout', [
                'confirm' => true,
                'payment_method' => 'bank_transfer',
                'billing_address' => ['name' => 'Phase One Customer'],
                'shipping_address' => ['name' => 'Phase One Customer'],
            ]);

        $checkoutResponse
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.payments.0.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'id' => $checkoutResponse->json('data.id'),
            'user_id' => $user->id,
            'grand_total' => 39.98,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $checkoutResponse->json('data.id'),
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_checkout_rejects_disabled_or_tampered_payment_method(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->customerUser($token);
        $marketplaceId = $this->marketplaceId();
        $productId = $this->stockedProductId($marketplaceId);

        PaymentProvider::updateOrCreate(
            ['code' => 'bank_transfer'],
            [
                'name' => 'Bank Transfer',
                'is_enabled' => false,
                'is_live' => false,
                'supported_currencies' => null,
                'config' => [],
                'sort_order' => 20,
            ],
        );

        $this->withToken($token)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $productId,
                'marketplace_id' => $marketplaceId,
                'quantity' => 1,
            ])
            ->assertCreated();

        $this->withToken($token)
            ->postJson('/api/v1/checkout', [
                'confirm' => true,
                'payment_method' => 'bank_transfer',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment_method');

        $this->withToken($token)
            ->postJson('/api/v1/checkout', [
                'confirm' => true,
                'payment_method' => 'unsupported_gateway',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment_method');
    }

    public function test_marketplace_registry_overrides_the_global_payment_provider(): void
    {
        $marketplaceId = $this->marketplaceId();
        $this->enablePaymentProvider('bank_transfer');

        PaymentProvider::create([
            'marketplace_id' => $marketplaceId,
            'code' => 'bank_transfer',
            'name' => 'Bank Transfer',
            'is_enabled' => false,
            'is_live' => false,
            'supported_currencies' => null,
            'config' => [],
            'sort_order' => 20,
        ]);

        $methods = app(PaymentMethodPolicyService::class)->allowedMethods($marketplaceId, 'USD');

        $this->assertFalse($methods->pluck('code')->contains('bank_transfer'));
    }

    private function customerUser(string &$token): User
    {
        $role = Role::updateOrCreate(
            ['name' => 'customer'],
            [
                'display_name' => 'Customer',
                'permissions' => ['cart.manage', 'checkout.create', 'orders.view'],
                'is_active' => true,
            ]
        );

        $user = User::create([
            'name' => 'Phase One Checkout',
            'email' => 'phase1-checkout-'.uniqid().'@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        return $user;
    }

    private function enablePaymentProvider(string $code): void
    {
        PaymentProvider::updateOrCreate(
            ['code' => $code],
            [
                'name' => ucwords(str_replace('_', ' ', $code)),
                'is_enabled' => true,
                'is_live' => false,
                'supported_currencies' => null,
                'config' => [],
                'sort_order' => 20,
            ],
        );
    }

    private function marketplaceId(): int
    {
        $suffix = substr(uniqid(), -8);

        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Phase One Country',
            'iso_code_2' => strtoupper(substr($suffix, 0, 2)),
            'iso_code_3' => strtoupper(substr($suffix, 0, 3)),
            'currency_code' => 'USD',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currencyId = DB::table('currencies')->insertGetId([
            'name' => 'US Dollar',
            'code' => strtoupper(substr($suffix, 0, 3)),
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'is_default' => false,
            'exchange_rate' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('marketplaces')->insertGetId([
            'name' => 'Phase One Marketplace',
            'code' => 'p1-'.$suffix,
            'country_id' => $countryId,
            'currency_id' => $currencyId,
            'timezone' => 'UTC',
            'locale' => 'en',
            'is_active' => true,
            'is_default' => false,
            'allow_vendor_registration' => true,
            'require_vendor_approval' => false,
            'tax_rate' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function stockedProductId(int $marketplaceId): int
    {
        $suffix = substr(uniqid(), -8);

        $productId = DB::table('products')->insertGetId([
            'name' => 'Phase One Product',
            'slug' => 'phase-one-product-'.$suffix,
            'sku' => 'P1-'.$suffix,
            'type' => 'simple',
            'status' => 'approved',
            'base_price' => 19.99,
            'track_inventory' => true,
            'stock_quantity' => 10,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $warehouseId = DB::table('warehouses')->insertGetId([
            'marketplace_id' => $marketplaceId,
            'name' => 'Phase One Warehouse',
            'code' => 'P1W-'.$suffix,
            'address_line1' => 'Phase One Street',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_stocks')->insert([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'marketplace_id' => $marketplaceId,
            'sku' => 'P1-'.$suffix,
            'quantity_available' => 10,
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'quantity_incoming' => 0,
            'reorder_point' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $productId;
    }
}
