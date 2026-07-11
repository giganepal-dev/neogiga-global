<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->string('iso_code', 2)->primary(); // US, NP, IN, etc.
            $table->string('name', 100);
            $table->string('native_name', 100)->nullable();
            $table->string('phone_code', 10)->nullable();
            $table->string('region', 50)->nullable(); // Asia, Europe, etc.
            $table->string('subregion', 50)->nullable(); // Southern Asia, etc.
            $table->json('languages')->nullable(); // ['ne', 'en']
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_vat')->default(false);
            $table->string('vat_label', 50)->default('VAT');
            $table->decimal('default_vat_rate', 5, 2)->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('region');
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary(); // USD, NPR, INR
            $table->string('name', 100);
            $table->string('symbol', 10);
            $table->integer('precision')->default(2);
            $table->decimal('exchange_rate_to_usd', 12, 6)->default(1.0);
            $table->timestamp('exchange_rate_updated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('marketplaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_code', 2);
            $table->foreign('country_code')->references('iso_code')->on('countries')->onDelete('cascade');
            $table->string('subdomain', 50)->unique(); // np, in, bd, au
            $table->string('name', 100); // NeoGiga Nepal
            $table->string('short_name', 50); // Nepal
            $table->string('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->string('timezone', 50)->default('UTC');
            $table->string('locale', 10)->default('en');
            $table->json('supported_locales')->nullable(); // ['en', 'ne']
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable(); // Theme, feature flags
            $table->timestamps();

            $table->unique(['country_code', 'subdomain']);
            $table->index('is_active');
        });

        Schema::create('marketplace_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marketplace_id');
            $table->foreign('marketplace_id')->references('id')->on('marketplaces')->onDelete('cascade');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, json, boolean
            $table->timestamps();

            $table->unique(['marketplace_id', 'key']);
            $table->index('marketplace_id');
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('country_code', 2);
            $table->foreign('country_code')->references('iso_code')->on('countries');
            $table->string('city', 100);
            $table->string('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->json('shipping_zones')->nullable(); // Served regions
            $table->json('courier_partners')->nullable(); // Available couriers
            $table->integer('lead_time_days')->default(1);
            $table->timestamps();

            $table->index('country_code');
            $table->index('is_active');
            $table->index(['country_code', 'is_active']);
        });

        Schema::create('tax_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_code', 2);
            $table->foreign('country_code')->references('iso_code')->on('countries');
            $table->string('tax_type', 50); // VAT, GST, Sales Tax, Environmental
            $table->string('tax_name', 100);
            $table->decimal('rate', 5, 2);
            $table->boolean('is_compound')->default(false);
            $table->string('applies_to', 20)->default('all'); // all, digital, physical
            $table->json('exempt_categories')->nullable();
            $table->date('effective_from')->default(DB::raw('CURRENT_DATE'));
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_code', 'is_active']);
            $table->index('tax_type');
        });

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marketplace_id')->nullable();
            $table->foreign('marketplace_id')->references('id')->on('marketplaces')->onDelete('cascade');
            $table->string('rule_type', 50); // country_markup, category_markup, brand_markup
            $table->uuid('target_id')->nullable(); // category_id, brand_id, vendor_id
            $table->string('target_type', 50)->nullable(); // category, brand, vendor
            $table->decimal('percentage_markup', 5, 2)->default(0);
            $table->decimal('fixed_markup', 12, 2)->default(0);
            $table->decimal('min_margin', 5, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->date('effective_from')->default(DB::raw('CURRENT_DATE'));
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority applied first
            $table->timestamps();

            $table->index(['marketplace_id', 'is_active']);
            $table->index(['rule_type', 'target_type']);
        });

        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marketplace_id')->nullable();
            $table->foreign('marketplace_id')->references('id')->on('marketplaces')->onDelete('cascade');
            $table->string('provider', 50); // stripe, paypal, esewa, khalti, razorpay
            $table->string('display_name', 100);
            $table->json('supported_countries')->nullable(); // ['NP', 'IN']
            $table->json('supported_currencies')->nullable(); // ['NPR', 'USD']
            $table->json('config')->nullable(); // API keys, secrets (encrypted)
            $table->boolean('is_test_mode')->default(true);
            $table->boolean('is_active')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['marketplace_id', 'is_active']);
            $table->index('provider');
        });

        Schema::create('shipping_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->string('destination_country', 2);
            $table->foreign('destination_country')->references('iso_code')->on('countries');
            $table->string('carrier', 50);
            $table->string('service_level', 50); // Standard, Express, Overnight
            $table->decimal('base_cost', 10, 2)->default(0);
            $table->decimal('cost_per_kg', 10, 2)->default(0);
            $table->decimal('free_shipping_threshold', 10, 2)->nullable();
            $table->integer('min_delivery_days')->default(1);
            $table->integer('max_delivery_days')->default(7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['warehouse_id', 'destination_country']);
            $table->index('is_active');
        });

        Schema::create('localized_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marketplace_id');
            $table->foreign('marketplace_id')->references('id')->on('marketplaces')->onDelete('cascade');
            $table->string('page_key', 100); // homepage_hero, about_us, contact
            $table->string('locale', 10)->default('en');
            $table->string('title', 255)->nullable();
            $table->text('content')->nullable();
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->json('og_tags')->nullable();
            $table->json('schema_markup')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_id', 'page_key', 'locale']);
            $table->index(['marketplace_id', 'is_published']);
        });

        Schema::create('localized_seo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marketplace_id');
            $table->foreign('marketplace_id')->references('id')->on('marketplaces')->onDelete('cascade');
            $table->string('entity_type', 50); // product, category, brand, manufacturer
            $table->uuid('entity_id');
            $table->string('locale', 10)->default('en');
            $table->string('slug', 255);
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('og_tags')->nullable();
            $table->json('twitter_tags')->nullable();
            $table->json('schema_markup')->nullable();
            $table->boolean('is_generated')->default(false);
            $table->timestamps();

            $table->unique(['marketplace_id', 'entity_type', 'entity_id', 'locale']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localized_seo');
        Schema::dropIfExists('localized_pages');
        Schema::dropIfExists('shipping_rules');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('marketplace_settings');
        Schema::dropIfExists('marketplaces');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('countries');
    }
};
