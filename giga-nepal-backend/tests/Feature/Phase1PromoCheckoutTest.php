<?php

namespace Tests\Feature;

use App\Models\Payments\PaymentProvider;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\GiftCard;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase1PromoCheckoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_coupon_and_gift_card_apply_at_checkout(): void
    {
        $token = bin2hex(random_bytes(32));
        $user = $this->customerUser($token);
        $marketplaceId = $this->marketplaceId();
        $this->enablePaymentProvider('bank_transfer');
        $productId = $this->stockedProductId($marketplaceId);

        // 2 x 19.99 = 39.98 subtotal
        $this->withToken($token)->postJson('/api/v1/cart/items', [
            'product_id' => $productId, 'marketplace_id' => $marketplaceId, 'quantity' => 2,
        ])->assertCreated();

        $coupon = Coupon::create(['code' => 'SAVE10-' . uniqid(), 'type' => 'percentage', 'value' => 10, 'is_active' => true, 'scope' => 'cart']);
        $card = GiftCard::create(['code' => 'GC-' . strtoupper(uniqid()), 'initial_balance' => 20, 'current_balance' => 20, 'currency' => 'USD', 'status' => 'active']);
        $card->transactions()->create(['type' => 'issue', 'amount' => 20, 'balance_after' => 20, 'created_at' => now()]);

        // Apply coupon (server computes 10% of 39.98 = 4.00)
        $this->withToken($token)->postJson('/api/v1/cart/apply-coupon', ['code' => $coupon->code])
            ->assertOk()->assertJsonPath('data.discount', fn ($v) => (float) $v === 4.0);

        // Apply gift card
        $this->withToken($token)->postJson('/api/v1/cart/apply-gift-card', ['code' => $card->code])
            ->assertOk()->assertJsonPath('data.balance', fn ($v) => (float) $v === 20.0);

        $res = $this->withToken($token)->postJson('/api/v1/checkout', [
            'confirm' => true, 'payment_method' => 'bank_transfer',
        ])->assertCreated();

        // grand = 39.98 - 4.00 coupon = 35.98; gift card pays 20 -> amount_due 15.98
        $res->assertJsonPath('data.discount_total', '4.00')
            ->assertJsonPath('data.grand_total', '35.98')
            ->assertJsonPath('data.amount_due', '15.98');

        $orderId = $res->json('data.id');
        $this->assertDatabaseHas('coupon_redemptions', ['coupon_id' => $coupon->id, 'order_id' => $orderId, 'discount_amount' => 4.00]);
        $this->assertDatabaseHas('payments', ['order_id' => $orderId, 'payment_method' => 'gift_card', 'amount' => 20.00, 'status' => 'captured']);
        $this->assertDatabaseHas('payments', ['order_id' => $orderId, 'payment_method' => 'bank_transfer', 'amount' => 15.98, 'status' => 'pending']);
        $this->assertEquals(0.0, (float) GiftCard::find($card->id)->current_balance);
        $this->assertEquals(1, (int) Coupon::find($coupon->id)->used_count);
    }

    private function customerUser(string $token): User
    {
        $role = Role::updateOrCreate(['name' => 'customer'], [
            'display_name' => 'Customer',
            'permissions' => ['cart.manage', 'checkout.create', 'orders.view'],
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Promo Customer',
            'email' => 'promo-' . uniqid() . '@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $user->forceFill(['api_token_hash' => hash('sha256', $token)])->save();

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
            'name' => 'Promo Country', 'iso_code_2' => strtoupper(substr($suffix, 0, 2)),
            'iso_code_3' => strtoupper(substr($suffix, 0, 3)), 'currency_code' => 'USD',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $currencyId = DB::table('currencies')->insertGetId([
            'name' => 'US Dollar', 'code' => strtoupper(substr($suffix, 0, 3)), 'symbol' => '$',
            'decimal_places' => 2, 'is_active' => true, 'is_default' => false, 'exchange_rate' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('marketplaces')->insertGetId([
            'name' => 'Promo Marketplace', 'code' => 'promo-' . $suffix, 'country_id' => $countryId,
            'currency_id' => $currencyId, 'timezone' => 'UTC', 'locale' => 'en', 'is_active' => true,
            'is_default' => false, 'allow_vendor_registration' => true, 'require_vendor_approval' => false,
            'tax_rate' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function stockedProductId(int $marketplaceId): int
    {
        $suffix = substr(uniqid(), -8);
        $productId = DB::table('products')->insertGetId([
            'name' => 'Promo Product', 'slug' => 'promo-product-' . $suffix, 'sku' => 'PP-' . $suffix,
            'type' => 'simple', 'status' => 'approved', 'base_price' => 19.99, 'track_inventory' => true,
            'stock_quantity' => 10, 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'marketplace_id' => $marketplaceId, 'name' => 'Promo WH', 'code' => 'PWH-' . $suffix,
            'address_line1' => 'X', 'is_active' => true, 'is_default' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inventory_stocks')->insert([
            'product_id' => $productId, 'warehouse_id' => $warehouseId, 'marketplace_id' => $marketplaceId,
            'sku' => 'PP-' . $suffix, 'quantity_available' => 10, 'quantity_reserved' => 0,
            'quantity_damaged' => 0, 'quantity_incoming' => 0, 'reorder_point' => 2, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $productId;
    }
}
