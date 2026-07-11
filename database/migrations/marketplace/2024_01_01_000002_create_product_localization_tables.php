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
        Schema::create('product_localizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id'); // References products table
            $table->string('country_code', 2);
            $table->foreign('country_code')->references('iso_code')->on('countries');
            $table->string('locale', 10)->default('en');
            $table->string('name', 255)->nullable(); // Localized product name
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('slug', 255)->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('images')->nullable(); // Localized image URLs
            $table->json('datasheets')->nullable(); // Localized datasheet URLs
            $table->string('warranty_info')->nullable();
            $table->json('country_restrictions')->nullable(); // Restricted states/provinces
            $table->boolean('is_available')->default(true);
            $table->timestamp('availability_starts_at')->nullable();
            $table->timestamp('availability_ends_at')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'country_code', 'locale']);
            $table->index(['country_code', 'is_available']);
            $table->index('product_id');
        });

        Schema::create('category_localizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id'); // References categories table
            $table->string('country_code', 2);
            $table->foreign('country_code')->references('iso_code')->on('countries');
            $table->string('locale', 10)->default('en');
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('slug', 255)->nullable();
            $table->json('og_tags')->nullable();
            $table->json('schema_markup')->nullable();
            $table->boolean('is_published')->default(false);
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['category_id', 'country_code', 'locale']);
            $table->index(['country_code', 'is_published']);
        });

        Schema::create('brand_localizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('brand_id'); // References brands table
            $table->string('country_code', 2);
            $table->foreign('country_code')->references('iso_code')->on('countries');
            $table->string('locale', 10)->default('en');
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('slug', 255)->nullable();
            $table->json('og_tags')->nullable();
            $table->boolean('is_published')->default(false);
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'country_code', 'locale']);
            $table->index(['country_code', 'is_published']);
        });

        Schema::create('manufacturer_localizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('manufacturer_id'); // References manufacturers table
            $table->string('country_code', 2);
            $table->foreign('country_code')->references('iso_code')->on('countries');
            $table->string('locale', 10)->default('en');
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('slug', 255)->nullable();
            $table->json('og_tags')->nullable();
            $table->boolean('is_published')->default(false);
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['manufacturer_id', 'country_code', 'locale']);
            $table->index(['country_code', 'is_published']);
        });

        Schema::create('product_marketplace_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id'); // References products table
            $table->uuid('marketplace_id')->nullable();
            $table->foreign('marketplace_id')->references('id')->on('marketplaces')->onDelete('cascade');
            $table->uuid('warehouse_id')->nullable();
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->decimal('base_cost_usd', 12, 4);
            $table->decimal('exchange_rate', 12, 6)->default(1.0);
            $table->decimal('price_in_local_currency', 12, 2);
            $table->decimal('duty_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('marketplace_margin', 12, 2)->default(0);
            $table->decimal('category_margin', 12, 2)->default(0);
            $table->decimal('seller_margin', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2);
            $table->string('currency_code', 3);
            $table->json('price_breaks')->nullable(); // [[qty: 10, price: 9.99], [qty: 100, price: 8.99]]
            $table->integer('moq')->default(1);
            $table->integer('order_multiple')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'marketplace_id', 'warehouse_id']);
            $table->index(['marketplace_id', 'is_active']);
            $table->index(['product_id', 'warehouse_id']);
        });

        Schema::create('inventory_warehouse', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id'); // References products table
            $table->uuid('warehouse_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_incoming')->default(0);
            $table->timestamp('incoming_eta')->nullable();
            $table->decimal('last_cost', 12, 4)->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->uuid('last_counted_by')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'product_id']);
            $table->index(['product_id', 'quantity_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_warehouse');
        Schema::dropIfExists('product_marketplace_prices');
        Schema::dropIfExists('manufacturer_localizations');
        Schema::dropIfExists('brand_localizations');
        Schema::dropIfExists('category_localizations');
        Schema::dropIfExists('product_localizations');
    }
};
