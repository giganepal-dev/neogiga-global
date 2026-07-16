<?php

namespace Tests\Feature;

use App\Models\CommerceAi\CommerceAiBomRequest;
use App\Models\CommerceAi\CommerceAiBomResult;
use App\Models\CommerceAi\CommerceAiRecommendationItem;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommerceAiBomCartApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureSchema();
    }

    public function test_owned_bom_adds_only_purchasable_items_and_replay_is_idempotent(): void
    {
        [, $token] = $this->apiUser('ai-cart-owner@example.test', ['cart.manage']);
        $marketplaceId = $this->marketplaceId();
        $available = $this->product('Available controller', 'NG-AI-AVAILABLE', 19.99, $marketplaceId, 10);
        $unavailable = $this->product('Unavailable sensor', 'NG-AI-UNAVAILABLE', 7.50, $marketplaceId, 0);
        $bom = $this->bomForTokenUser($token, [
            ['product_id' => $available->id, 'name' => $available->name, 'quantity' => 2],
            ['product_id' => $unavailable->id, 'name' => $unavailable->name, 'quantity' => 1],
            ['product_id' => null, 'name' => 'Generic enclosure', 'quantity' => 1],
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/ai/add-bom-to-cart', [
                'bom_result_id' => $bom->id,
                'marketplace_id' => $marketplaceId,
            ])
            ->assertCreated()
            ->assertJsonPath('data.added_count', 1)
            ->assertJsonPath('data.skipped_count', 2)
            ->assertJsonPath('data.cart.item_count', 2)
            ->assertJsonPath('data.disclaimer', 'Advisory only. Cart contents are not an order, payment, or stock reservation.');

        $this->withToken($token)
            ->postJson('/api/v1/cart/add-bom', [
                'bom_result_id' => $bom->id,
                'marketplace_id' => $marketplaceId,
            ])
            ->assertOk()
            ->assertJsonPath('data.added_count', 0)
            ->assertJsonPath('data.already_added_count', 1)
            ->assertJsonPath('data.cart.item_count', 2);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $available->id,
            'quantity' => 2,
            'unit_price' => 19.99,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/ai/add-bom-to-cart', [
                'bom_result_id' => $bom->id,
                'marketplace_id' => $this->marketplaceId(),
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Your active cart belongs to another marketplace. Complete or clear it before changing marketplace.');
    }

    public function test_bom_cart_is_scoped_to_its_owner_and_requires_cart_permission(): void
    {
        [, $ownerToken] = $this->apiUser('ai-cart-owner-2@example.test', ['cart.manage']);
        [, $otherToken] = $this->apiUser('ai-cart-other@example.test', ['cart.manage']);
        [, $noPermissionToken] = $this->apiUser('ai-cart-no-permission@example.test');
        $marketplaceId = $this->marketplaceId();
        $product = $this->product('Owned BOM product', 'NG-AI-OWNED', 12.50, $marketplaceId, 10);
        $bom = $this->bomForTokenUser($ownerToken, [
            ['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1],
        ]);

        $this->withToken($otherToken)
            ->postJson('/api/v1/ai/add-bom-to-cart', ['bom_result_id' => $bom->id])
            ->assertNotFound();

        $this->withToken($noPermissionToken)
            ->postJson('/api/v1/ai/add-bom-to-cart', ['bom_result_id' => $bom->id])
            ->assertForbidden();
    }

    private function apiUser(string $email, array $permissions = []): array
    {
        $token = bin2hex(random_bytes(32));
        $role = Role::create([
            'name' => 'ai-cart-'.str_replace(['@', '.'], '-', $email),
            'display_name' => 'AI Cart Test',
            'permissions' => $permissions,
            'is_active' => true,
        ]);
        $user = User::forceCreate([
            'name' => 'AI Cart Customer',
            'email' => $email,
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'api_token_hash' => hash('sha256', $token),
        ]);

        return [$user, $token];
    }

    private function marketplaceId(): int
    {
        $suffix = substr(uniqid(), -8);
        do {
            $iso3 = strtoupper(substr(bin2hex(random_bytes(4)), 0, 3));
            $iso2 = substr($iso3, 0, 2);
        } while (DB::table('countries')->where('iso_code_2', $iso2)->orWhere('iso_code_3', $iso3)->exists());
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'AI Cart Country '.$suffix,
            'iso_code_2' => $iso2,
            'iso_code_3' => $iso3,
            'currency_code' => $iso3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $currency = Currency::create([
            'name' => 'US Dollar '.$suffix,
            'code' => $iso3,
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
        ]);

        return Marketplace::create([
            'name' => 'AI Cart Marketplace '.$suffix,
            'code' => 'aic-'.$suffix,
            'country_id' => $countryId,
            'currency_id' => $currency->id,
            'timezone' => 'UTC',
            'locale' => 'en',
            'is_active' => true,
        ])->id;
    }

    private function product(string $name, string $sku, float $price, int $marketplaceId, int $stock): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => strtolower($sku),
            'sku' => $sku,
            'type' => 'simple',
            'status' => 'approved',
            'base_price' => $price,
            'track_inventory' => true,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'marketplace_id' => $marketplaceId,
            'name' => 'AI Cart Warehouse '.$sku,
            'code' => 'W-'.$sku,
            'address_line1' => 'AI Cart Test Address',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_stocks')->insert([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'marketplace_id' => $marketplaceId,
            'sku' => $sku,
            'quantity_available' => $stock,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $product;
    }

    private function bomForTokenUser(string $token, array $items): CommerceAiBomResult
    {
        $userId = User::query()->where('api_token_hash', hash('sha256', $token))->value('id');
        $request = CommerceAiBomRequest::create([
            'user_id' => $userId,
            'prompt' => 'Test BOM',
            'intent' => 'test',
            'status' => 'completed',
        ]);
        $result = CommerceAiBomResult::create([
            'commerce_ai_bom_request_id' => $request->id,
            'title' => 'Test BOM',
            'payload' => ['source_notes' => 'Test fixture'],
        ]);

        foreach ($items as $item) {
            CommerceAiRecommendationItem::create($item + [
                'commerce_ai_bom_result_id' => $result->id,
                'availability_status' => 'catalog_match_stock_not_verified',
            ]);
        }

        return $result;
    }

    private function ensureSchema(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->json('permissions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', fn (Blueprint $table) => $table->unsignedBigInteger('role_id')->nullable()->index());
        }
        if (! Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 3)->unique();
                $table->string('symbol')->nullable();
                $table->unsignedTinyInteger('decimal_places')->default(2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('marketplaces')) {
            Schema::create('marketplaces', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->unsignedBigInteger('currency_id')->nullable();
                $table->string('timezone')->default('UTC');
                $table->string('locale')->default('en');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('sku')->unique();
                $table->string('type')->default('simple');
                $table->string('status')->default('draft');
                $table->decimal('base_price', 12, 2)->default(0);
                $table->decimal('sale_price', 12, 2)->nullable();
                $table->boolean('track_inventory')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('product_variants')) {
            Schema::create('product_variants', fn (Blueprint $table) => $table->id());
        }
        if (! Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('marketplace_id')->nullable();
                $table->string('name');
                $table->string('code')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('inventory_stocks')) {
            Schema::create('inventory_stocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->unsignedBigInteger('marketplace_id')->nullable();
                $table->string('sku');
                $table->integer('quantity_available')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('marketplace_product_prices')) {
            Schema::create('marketplace_product_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('marketplace_id');
                $table->decimal('base_price', 15, 4);
                $table->decimal('sale_price', 15, 4)->nullable();
                $table->string('currency_code', 3);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('marketplace_id')->nullable();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('discount_total', 12, 2)->default(0);
                $table->decimal('shipping_total', 12, 2)->default(0);
                $table->decimal('grand_total', 12, 2)->default(0);
                $table->integer('item_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cart_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('variant_id')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
        foreach ([
            'commerce_ai_sessions' => fn (Blueprint $table) => $table->string('session_key')->unique(),
            'commerce_ai_messages' => fn (Blueprint $table) => $table->text('message'),
            'commerce_ai_bom_requests' => fn (Blueprint $table) => [$table->unsignedBigInteger('user_id')->nullable(), $table->text('prompt'), $table->string('intent')->nullable(), $table->string('status')->default('completed')],
            'commerce_ai_bom_results' => fn (Blueprint $table) => [$table->unsignedBigInteger('commerce_ai_bom_request_id'), $table->string('title'), $table->json('payload')],
            'commerce_ai_recommendation_items' => fn (Blueprint $table) => [$table->unsignedBigInteger('commerce_ai_bom_result_id')->nullable(), $table->unsignedBigInteger('product_id')->nullable(), $table->string('name'), $table->decimal('quantity', 12, 3)->default(1), $table->string('availability_status')->default('catalog_match_not_verified')],
        ] as $name => $columns) {
            if (! Schema::hasTable($name)) {
                Schema::create($name, function (Blueprint $table) use ($columns) {
                    $table->id();
                    $columns($table);
                    $table->timestamps();
                });
            }
        }
    }
}
