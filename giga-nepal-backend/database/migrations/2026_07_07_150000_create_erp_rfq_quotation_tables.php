<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP B2B: RFQ (request for quote) -> Quotation flow (NEOGIGA_ERP_ADAPTATION_COMMAND).
 * Additive; references users/products/marketplaces read-only via nullable soft links.
 * Quotation totals are computed server-side (never client-trusted).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_requests', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable()->index();
            $table->string('contact_phone')->nullable();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('open')->index(); // open|quoted|accepted|closed|cancelled
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_request_id')->constrained('rfq_requests')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->decimal('quantity', 15, 3)->default(1);
            $table->decimal('target_price', 18, 4)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('rfq_request_id')->nullable()->constrained('rfq_requests')->nullOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();   // customer the quote is for
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('draft')->index();          // draft|sent|accepted|rejected|expired
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('shipping_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->decimal('quantity', 15, 3)->default(1);
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0); // server-computed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('rfq_requests');
    }
};
