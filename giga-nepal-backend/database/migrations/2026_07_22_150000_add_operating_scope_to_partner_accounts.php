<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['seller_applications', 'distributor_applications', 'vendors', 'distributors'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'operating_scope')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->string('operating_scope', 20)->default('country')->index();
                });
            }
        }

        $this->ensurePublicSellerApplicationColumns();
        $this->ensurePublicDistributorApplicationColumns();

        if (Schema::hasTable('account_applications')) {
            $needsCountry = ! Schema::hasColumn('account_applications', 'country_id');
            $needsScope = ! Schema::hasColumn('account_applications', 'operating_scope');
            Schema::table('account_applications', function (Blueprint $table) use ($needsCountry, $needsScope): void {
                if ($needsCountry) {
                    $table->unsignedBigInteger('country_id')->nullable()->index();
                }
                if ($needsScope) {
                    $table->string('operating_scope', 20)->default('country')->index();
                }
            });
        }
    }

    public function down(): void
    {
        // Partner scope and compliance history are intentionally retained.
    }

    private function ensurePublicSellerApplicationColumns(): void
    {
        if (! Schema::hasTable('seller_applications')) {
            return;
        }
        $existing = array_flip(Schema::getColumnListing('seller_applications'));
        Schema::table('seller_applications', function (Blueprint $table) use ($existing): void {
            if (! isset($existing['country_id'])) $table->unsignedBigInteger('country_id')->nullable()->index();
            if (! isset($existing['region_id'])) $table->unsignedBigInteger('region_id')->nullable()->index();
            if (! isset($existing['city_id'])) $table->unsignedBigInteger('city_id')->nullable()->index();
            if (! isset($existing['seller_type'])) $table->string('seller_type', 80)->nullable();
            if (! isset($existing['brands_carried'])) $table->json('brands_carried')->nullable();
            if (! isset($existing['has_existing_inventory'])) $table->boolean('has_existing_inventory')->default(false);
            if (! isset($existing['has_physical_store'])) $table->boolean('has_physical_store')->default(false);
            if (! isset($existing['monthly_order_capacity'])) $table->string('monthly_order_capacity', 80)->nullable();
            if (! isset($existing['website'])) $table->string('website')->nullable();
            if (! isset($existing['message'])) $table->text('message')->nullable();
            if (! isset($existing['reviewed_at'])) $table->timestamp('reviewed_at')->nullable();
            if (! isset($existing['source'])) $table->string('source', 80)->default('public_sell_on_neogiga')->index();
        });
    }

    private function ensurePublicDistributorApplicationColumns(): void
    {
        if (! Schema::hasTable('distributor_applications')) {
            return;
        }
        $existing = array_flip(Schema::getColumnListing('distributor_applications'));
        Schema::table('distributor_applications', function (Blueprint $table) use ($existing): void {
            if (! isset($existing['business_name'])) $table->string('business_name')->nullable();
            if (! isset($existing['contact_person'])) $table->string('contact_person')->nullable();
            if (! isset($existing['email'])) $table->string('email')->nullable()->index();
            if (! isset($existing['phone'])) $table->string('phone', 40)->nullable();
            if (! isset($existing['whatsapp'])) $table->string('whatsapp', 40)->nullable();
            if (! isset($existing['region_id'])) $table->unsignedBigInteger('region_id')->nullable()->index();
            if (! isset($existing['distributor_type'])) $table->string('distributor_type', 80)->nullable();
            if (! isset($existing['territory_interest'])) $table->string('territory_interest')->nullable();
            if (! isset($existing['current_business_categories'])) $table->json('current_business_categories')->nullable();
            if (! isset($existing['existing_dealer_network'])) $table->boolean('existing_dealer_network')->default(false);
            if (! isset($existing['warehouse_available'])) $table->boolean('warehouse_available')->default(false);
            if (! isset($existing['monthly_capacity'])) $table->string('monthly_capacity', 80)->nullable();
            if (! isset($existing['message'])) $table->text('message')->nullable();
            if (! isset($existing['source'])) $table->string('source', 80)->default('public_distributor_network')->index();
            if (! isset($existing['created_at'])) $table->timestamp('created_at')->nullable();
            if (! isset($existing['updated_at'])) $table->timestamp('updated_at')->nullable();
        });
    }
};
