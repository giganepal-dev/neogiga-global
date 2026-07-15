<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->enum('tier', ['tier_1', 'tier_2', 'tier_3'])->default('tier_1');
                $table->text('description')->nullable();
                $table->string('website_url')->nullable();
                $table->string('api_endpoint')->nullable();
                $table->json('api_credentials')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('country')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_featured')->default(false);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('tier');
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
