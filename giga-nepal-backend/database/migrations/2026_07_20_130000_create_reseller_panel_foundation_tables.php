<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('resellers') && ! Schema::hasColumn('resellers', 'home_marketplace_id')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->unsignedBigInteger('home_marketplace_id')->nullable()->after('country_id')->index();
                $table->foreign('home_marketplace_id')->references('id')->on('marketplaces')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('reseller_applications')) {
            Schema::create('reseller_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->string('company_name');
                $table->string('contact_person');
                $table->string('email')->index();
                $table->string('phone')->nullable();
                $table->unsignedBigInteger('country_id')->nullable()->index();
                $table->string('registration_number')->nullable();
                $table->string('tax_number')->nullable();
                $table->string('document_company_reg')->nullable();
                $table->string('document_reseller_certificate')->nullable();
                $table->string('document_tax_certificate')->nullable();
                $table->string('document_gst_info')->nullable();
                $table->text('territory_notes')->nullable();
                $table->string('status')->default('pending')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reseller_territories')) {
            Schema::create('reseller_territories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->unsignedBigInteger('country_id')->nullable()->index();
                $table->boolean('is_primary')->default(false)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->string('status')->default('active')->index();
                $table->timestamps();
                $table->unique(['reseller_id', 'marketplace_id']);
            });
        }

        if (! Schema::hasTable('reseller_territory_requests')) {
            Schema::create('reseller_territory_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->unsignedBigInteger('country_id')->nullable()->index();
                $table->string('document_company_reg')->nullable();
                $table->string('document_reseller_certificate')->nullable();
                $table->string('document_tax_certificate')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('pending')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reseller_support_tickets')) {
            Schema::create('reseller_support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('ticket_number')->unique();
                $table->string('subject');
                $table->text('body')->nullable();
                $table->string('status')->default('open')->index();
                $table->string('priority')->default('normal');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reseller_rfq_assignments')) {
            Schema::create('reseller_rfq_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rfq_id')->constrained('rfq_requests')->cascadeOnDelete();
                $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
                $table->string('status')->default('invited')->index();
                $table->timestamp('invited_at')->useCurrent();
                $table->timestamp('deadline_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamps();
                $table->unique(['rfq_id', 'reseller_id']);
            });
        }

        if (! Schema::hasTable('reseller_rfq_bids')) {
            Schema::create('reseller_rfq_bids', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rfq_id')->constrained('rfq_requests')->cascadeOnDelete();
                $table->foreignId('assignment_id')->constrained('reseller_rfq_assignments')->cascadeOnDelete();
                $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
                $table->string('status')->default('submitted')->index();
                $table->text('cover_note')->nullable();
                $table->string('currency')->default('USD');
                $table->unsignedInteger('lead_time_days')->nullable();
                $table->date('valid_until')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reseller_rfq_bid_items')) {
            Schema::create('reseller_rfq_bid_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bid_id')->constrained('reseller_rfq_bids')->cascadeOnDelete();
                $table->foreignId('rfq_item_id')->constrained('rfq_items')->cascadeOnDelete();
                $table->decimal('unit_price', 14, 4);
                $table->decimal('quantity', 14, 2);
                $table->decimal('total_price', 14, 4);
                $table->string('stock_status')->default('available');
                $table->string('substitute_mpn')->nullable();
                $table->text('item_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'reseller_rfq_bid_items',
            'reseller_rfq_bids',
            'reseller_rfq_assignments',
            'reseller_support_tickets',
            'reseller_territory_requests',
            'reseller_territories',
            'reseller_applications',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('resellers') && Schema::hasColumn('resellers', 'home_marketplace_id')) {
            Schema::table('resellers', function (Blueprint $table) {
                $table->dropForeign(['home_marketplace_id']);
                $table->dropColumn('home_marketplace_id');
            });
        }
    }
};
