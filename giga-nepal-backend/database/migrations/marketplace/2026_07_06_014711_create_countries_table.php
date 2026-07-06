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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso_code_2', 2)->unique();
            $table->string('iso_code_3', 3)->unique();
            $table->string('phone_code')->nullable();
            $table->string('capital')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('region')->nullable();
            $table->string('subregion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_eu')->default(false);
            $table->json('translations')->nullable();
            $table->timestamps();
            
            $table->index('iso_code_2');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
