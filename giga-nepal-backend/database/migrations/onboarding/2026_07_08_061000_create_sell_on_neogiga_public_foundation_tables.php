<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seller_applications')) {
            Schema::create('seller_applications', function (Blueprint $table) {
                $table->id();
                $table->string('business_name');
                $table->string('contact_person');
                $table->string('email')->index();
                $table->string('phone', 40);
                $table->string('whatsapp', 40)->nullable();
                $table->unsignedBigInteger('country_id')->nullable()->index();
                $table->unsignedBigInteger('region_id')->nullable()->index();
                $table->unsignedBigInteger('city_id')->nullable()->index();
                $table->string('business_type', 80);
                $table->string('seller_type', 80);
                $table->json('product_categories')->nullable();
                $table->json('brands_carried')->nullable();
                $table->boolean('has_existing_inventory')->default(false);
                $table->boolean('has_physical_store')->default(false);
                $table->string('monthly_order_capacity', 80)->nullable();
                $table->string('website')->nullable();
                $table->text('message')->nullable();
                $table->string('status', 40)->default('pending')->index();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->string('source', 80)->default('public_sell_on_neogiga')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('distributor_applications')) {
            Schema::create('distributor_applications', function (Blueprint $table) {
                $table->id();
                $table->string('business_name');
                $table->string('contact_person');
                $table->string('email')->index();
                $table->string('phone', 40);
                $table->string('whatsapp', 40)->nullable();
                $table->unsignedBigInteger('country_id')->nullable()->index();
                $table->unsignedBigInteger('region_id')->nullable()->index();
                $table->unsignedBigInteger('city_id')->nullable()->index();
                $table->string('distributor_type', 80);
                $table->string('territory_interest')->nullable();
                $table->json('current_business_categories')->nullable();
                $table->boolean('existing_dealer_network')->default(false);
                $table->boolean('warehouse_available')->default(false);
                $table->string('monthly_capacity', 80)->nullable();
                $table->text('message')->nullable();
                $table->string('status', 40)->default('pending')->index();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->string('source', 80)->default('public_distributor_network')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commerce_ai_sessions')) {
            Schema::create('commerce_ai_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('session_key')->unique();
                $table->string('intent', 80)->nullable()->index();
                $table->string('status', 40)->default('active')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commerce_ai_messages')) {
            Schema::create('commerce_ai_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('commerce_ai_session_id')->nullable()->constrained('commerce_ai_sessions')->nullOnDelete();
                $table->string('role', 40);
                $table->text('message');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commerce_ai_bom_requests')) {
            Schema::create('commerce_ai_bom_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('commerce_ai_session_id')->nullable()->constrained('commerce_ai_sessions')->nullOnDelete();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->text('prompt');
                $table->string('intent', 80)->nullable()->index();
                $table->string('status', 40)->default('completed')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commerce_ai_bom_results')) {
            Schema::create('commerce_ai_bom_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('commerce_ai_bom_request_id')->constrained('commerce_ai_bom_requests')->cascadeOnDelete();
                $table->string('title');
                $table->string('estimated_total')->nullable();
                $table->json('payload');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commerce_ai_recommendation_items')) {
            Schema::create('commerce_ai_recommendation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('commerce_ai_bom_result_id')->nullable()->constrained('commerce_ai_bom_results')->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('name');
                $table->decimal('quantity', 12, 3)->default(1);
                $table->text('reason')->nullable();
                $table->string('availability_status', 80)->default('catalog_match_not_verified');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'commerce_ai_recommendation_items',
            'commerce_ai_bom_results',
            'commerce_ai_bom_requests',
            'commerce_ai_messages',
            'commerce_ai_sessions',
            'distributor_applications',
            'seller_applications',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
