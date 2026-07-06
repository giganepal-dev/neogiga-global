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
        Schema::create('vendor_payout_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type'); // bank_transfer, paypal, stripe, etc.
            $table->json('details'); // account_number, routing, etc.
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->decimal('minimum_payout', 10, 2)->default(0.00);
            $table->decimal('payout_fee_percent', 5, 2)->default(0.00);
            $table->timestamps();
            
            $table->index('vendor_id');
            $table->index(['vendor_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_payout_methods');
    }
};
