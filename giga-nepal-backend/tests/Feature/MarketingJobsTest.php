<?php

namespace Tests\Feature;

use App\Jobs\Marketing\CalculateTopSearchTermsJob;
use App\Jobs\Marketing\CalculateTrendingCategoriesJob;
use App\Jobs\Marketing\CalculateTrendingProductsJob;
use App\Jobs\Marketing\DetectAbandonedCartsJob;
use App\Jobs\Marketing\GenerateRegionalSalesReportJob;
use App\Jobs\Marketing\RefreshCustomerSegmentJob;
use App\Jobs\Marketing\SendAbandonedCartReminderJob;
use App\Jobs\Marketing\SendTransactionalEmailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_analytics_jobs_write_first_party_aggregates(): void
    {
        $categoryId = DB::table('product_categories')->insertGetId([
            'name' => 'Microcontrollers',
            'slug' => 'microcontrollers-'.Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'name' => 'ESP32 DevKit',
            'slug' => 'esp32-devkit-'.Str::random(6),
            'sku' => 'NG-TEST-'.Str::random(8),
            'category_id' => $categoryId,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_views')->insert([
            ['product_id' => $productId, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $productId, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('add_to_cart_events')->insert([
            ['product_id' => $productId, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('product_searches')->insert([
            ['query' => ' ESP32 ', 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['query' => 'esp32', 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        (new CalculateTrendingProductsJob())->handle();
        (new CalculateTrendingCategoriesJob())->handle();
        (new CalculateTopSearchTermsJob())->handle();

        $this->assertDatabaseHas('trending_products', ['product_id' => $productId]);
        $this->assertDatabaseHas('trending_categories', ['category_id' => $categoryId]);
        $this->assertDatabaseHas('top_search_terms', ['term' => 'esp32', 'search_count' => 2]);
    }

    public function test_abandoned_cart_job_captures_cart_and_queues_reminder_email(): void
    {
        $user = User::create([
            'name' => 'Cart Owner',
            'email' => 'cart-owner@example.test',
            'password' => Hash::make('password123'),
        ]);
        $productId = DB::table('products')->insertGetId([
            'name' => 'Cart Product',
            'slug' => 'cart-product-'.Str::random(6),
            'sku' => 'NG-CART-'.Str::random(8),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cartId = DB::table('carts')->insertGetId([
            'user_id' => $user->id,
            'currency_code' => 'USD',
            'grand_total' => 25,
            'item_count' => 1,
            'is_active' => true,
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(3),
        ]);
        DB::table('cart_items')->insert([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => 2,
            'unit_price' => 12.5,
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(3),
        ]);

        (new DetectAbandonedCartsJob(['inactive_minutes' => 60]))->handle();

        $abandoned = DB::table('abandoned_carts')->where('cart_id', $cartId)->first();
        $this->assertNotNull($abandoned);
        $this->assertSame(1, DB::table('abandoned_cart_items')->where('abandoned_cart_id', $abandoned->id)->count());

        app(SendAbandonedCartReminderJob::class, ['payload' => ['abandoned_cart_id' => $abandoned->id]])
            ->handle(app(\App\Services\Marketing\EmailQueueService::class));

        $this->assertDatabaseHas('abandoned_cart_reminders', [
            'abandoned_cart_id' => $abandoned->id,
            'status' => 'email_queued',
        ]);
        $this->assertDatabaseHas('email_messages', [
            'to_email' => 'cart-owner@example.test',
            'message_type' => 'abandoned_cart',
            'status' => 'queued',
        ]);
    }

    public function test_segment_regional_sales_and_transactional_email_jobs_run(): void
    {
        $customerId = DB::table('customer_profiles')->insertGetId([
            'email' => 'segment@example.test',
            'customer_type' => 'b2b',
            'lifecycle_stage' => 'lead',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $segmentId = DB::table('customer_segments')->insertGetId([
            'name' => 'B2B Leads',
            'slug' => 'b2b-leads-'.Str::random(6),
            'rules' => json_encode(['customer_type' => 'b2b']),
            'type' => 'dynamic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new RefreshCustomerSegmentJob(['segment_id' => $segmentId]))
            ->handle(app(\App\Services\Marketing\CustomerSegmentationService::class));

        $this->assertDatabaseHas('customer_segment_members', [
            'customer_segment_id' => $segmentId,
            'customer_profile_id' => $customerId,
        ]);

        $currencyId = DB::table('currencies')->insertGetId([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'exchange_rate' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Test Country',
            'iso_code_2' => 'TC',
            'iso_code_3' => 'TST',
            'currency_code' => 'USD',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $marketplaceId = DB::table('marketplaces')->insertGetId([
            'name' => 'Test Market',
            'code' => 'TEST-'.Str::random(6),
            'country_id' => $countryId,
            'currency_id' => $currencyId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('orders')->insert([
            'order_number' => 'ORD-'.Str::random(8),
            'marketplace_id' => $marketplaceId,
            'status' => 'confirmed',
            'grand_total' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new GenerateRegionalSalesReportJob())->handle();
        $this->assertSame('99.00', (string) DB::table('regional_sales_reports')->value('amount'));

        app(SendTransactionalEmailJob::class, ['payload' => [
            'to_email' => 'notify@example.test',
            'subject' => 'NeoGiga test',
            'text_body' => 'Queued by job test.',
        ]])->handle(
            app(\App\Services\Marketing\EmailProviderManager::class),
            app(\App\Services\Marketing\EmailQueueService::class),
        );

        $this->assertDatabaseHas('email_messages', [
            'to_email' => 'notify@example.test',
            'status' => 'test_queued',
        ]);
    }
}
