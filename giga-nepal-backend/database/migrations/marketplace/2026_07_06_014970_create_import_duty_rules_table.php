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
        Schema::create('import_duty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hs_code')->nullable();
            $table->json('category_ids')->nullable();
            $table->decimal('duty_rate', 5, 2);
            $table->string('duty_type')->default('percentage'); // percentage, fixed
            $table->decimal('fixed_amount', 15, 4)->nullable();
            $table->string('origin_country')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_id']);
            $table->index(['marketplace_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_duty_rules');
    }
};
