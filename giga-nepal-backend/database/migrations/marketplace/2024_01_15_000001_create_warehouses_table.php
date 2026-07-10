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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('region'); // 'Middle East', 'South Asia', etc.
            $table->string('country');
            $table->string('city');
            $table->string('address');
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('currency_code', 3)->default('USD');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->json('contact_info')->nullable(); // phone, email, manager name
            $table->json('operating_hours')->nullable();
            $table->integer('capacity_units')->default(0);
            $table->integer('current_stock_units')->default(0);
            $table->boolean('is_distribution_center')->default(false);
            $table->boolean('is_fulfillment_center')->default(false);
            $table->boolean('allows_cross_border')->default(true);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('region');
            $table->index('country');
            $table->index('city');
            $table->index('status');
            $table->index('is_distribution_center');
            
            // Foreign keys (optional, can be added later if needed)
            // $table->foreign('created_by')->references('id')->on('users');
            // $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
