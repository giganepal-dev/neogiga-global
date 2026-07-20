<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RFQ supplier bidding — assign sellers to RFQs, collect bids, compare, award.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Which sellers are invited to bid on an RFQ
        Schema::create('rfq_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfq_requests')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('status')->default('invited'); // invited, viewed, declined, bid_submitted
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('deadline_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->unique(['rfq_id', 'vendor_id']);
        });

        // Seller bids on RFQ items
        Schema::create('rfq_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfq_requests')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('rfq_assignments')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('status')->default('submitted'); // draft, submitted, withdrawn, awarded, rejected
            $table->text('cover_note')->nullable();        // seller's proposal summary
            $table->string('currency')->default('USD');
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->string('valid_until')->nullable();      // bid expiry
            $table->json('terms')->nullable();              // incoterms, payment terms, warranty
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        // Per-line-item bid pricing
        Schema::create('rfq_bid_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained('rfq_bids')->cascadeOnDelete();
            $table->foreignId('rfq_item_id')->constrained('rfq_items')->cascadeOnDelete();
            $table->decimal('unit_price', 14, 4);
            $table->decimal('quantity', 14, 2);
            $table->decimal('total_price', 14, 4);
            $table->string('stock_status')->default('available'); // available, backorder, substitute
            $table->string('substitute_mpn')->nullable();
            $table->text('item_notes')->nullable();
            $table->timestamps();
        });

        // Admin award decisions (full or partial)
        Schema::create('rfq_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfq_requests')->cascadeOnDelete();
            $table->foreignId('bid_id')->constrained('rfq_bids')->cascadeOnDelete();
            $table->foreignId('awarded_by')->nullable()->constrained('users');
            $table->string('status')->default('awarded'); // awarded, accepted, rejected, converted
            $table->text('admin_notes')->nullable();
            $table->timestamp('awarded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_awards');
        Schema::dropIfExists('rfq_bid_items');
        Schema::dropIfExists('rfq_bids');
        Schema::dropIfExists('rfq_assignments');
    }
};
