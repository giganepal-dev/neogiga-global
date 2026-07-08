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
        Schema::create('distributor_territories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_application_id')->constrained()->onDelete('cascade');
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->foreignId('province_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('district_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('territory_name');
            $table->string('territory_type')->default('exclusive'); // exclusive, non-exclusive, regional
            $table->string('status')->default('active'); // active, inactive, pending_approval
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('territory_description')->nullable();
            $table->json('coverage_areas')->nullable(); // Additional coverage details
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            $table->unique(['distributor_application_id', 'country_id', 'province_id', 'district_id', 'city_id']);
            $table->index(['country_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_territories');
    }
};
