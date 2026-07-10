<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 2: Source Management - catalog_sources table
     */
    public function up(): void
    {
        Schema::create('catalog_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('source_type', ['api', 'csv', 'xml', 'json', 'sftp', 'manual']);
            $table->string('provider_name')->nullable();
            $table->string('base_url')->nullable();
            $table->string('documentation_url')->nullable();
            $table->enum('authentication_type', ['none', 'api_key', 'oauth2', 'basic', 'token', 'certificate'])->default('none');
            $table->string('country', 2)->nullable();
            $table->string('default_currency', 3)->default('USD');
            $table->boolean('active')->default(true);
            $table->unsignedTinyInteger('priority')->default(10);
            $table->unsignedInteger('rate_limit_per_minute')->nullable();
            $table->json('allowed_data_types')->nullable()->comment('["products", "manufacturers", "categories", "pricing", "inventory"]');
            $table->text('license_notes')->nullable();
            $table->boolean('attribution_required')->default(false);
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['source_type', 'active']);
            $table->index('provider_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_sources');
    }
};
