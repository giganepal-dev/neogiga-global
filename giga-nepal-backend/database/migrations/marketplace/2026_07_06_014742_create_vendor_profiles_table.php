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
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->text('about')->nullable();
            $table->string('business_type')->nullable();
            $table->integer('years_in_business')->nullable();
            $table->integer('employee_count')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->json('certifications')->nullable();
            $table->json('specialties')->nullable();
            $table->json('shipping_methods')->nullable();
            $table->json('payment_methods')->nullable();
            $table->string('return_policy')->nullable();
            $table->string('warranty_policy')->nullable();
            $table->integer('response_time_hours')->default(24);
            $table->decimal('rating_average', 3, 2)->default(0.00);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_sales')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('vendor_id');
            $table->index('rating_average');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
