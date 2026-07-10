<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only status audit trail for the EXISTING RFQ module (rfq_requests /
 * rfq_items, live since 2026-07-07). Deliberately NOT new `rfqs`/`rfq_lines`
 * tables — that would duplicate the live module.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rfq_status_histories')) {
            return;
        }

        Schema::create('rfq_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_request_id')->constrained('rfq_requests')->cascadeOnDelete();
            $table->string('previous_status')->nullable();
            $table->string('status'); // open|quoted|accepted|closed|cancelled
            $table->string('notes', 1000)->nullable();
            $table->unsignedBigInteger('changed_by_user_id')->nullable()->index();
            $table->timestamps();
            $table->index(['rfq_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_status_histories');
    }
};
