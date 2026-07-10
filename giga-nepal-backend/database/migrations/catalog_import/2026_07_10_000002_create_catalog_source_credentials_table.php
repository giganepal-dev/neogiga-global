<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 2: Source Management - catalog_source_credentials table
     * Stores encrypted API credentials securely
     */
    public function up(): void
    {
        Schema::create('catalog_source_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('credential_type'); // api_key, secret, username, password, token, certificate
            $table->string('credential_name'); // human-readable label
            $table->text('encrypted_value'); // AES-256 encrypted credential value
            $table->string('encryption_version')->default('v1');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable(); // additional context like key ID, scope
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_rotated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_rotated_at')->nullable();
            $table->timestamps();
            
            $table->unique(['catalog_source_id', 'credential_type', 'credential_name']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_source_credentials');
    }
};
