<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 2: Source Management - catalog_source_webhooks table
     * Configures webhook endpoints for real-time updates from sources
     */
    public function up(): void
    {
        Schema::create('catalog_source_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_source_id')->constrained('catalog_sources')->cascadeOnDelete();
            $table->string('webhook_url'); // NeoGiga endpoint to receive webhooks
            $table->string('external_webhook_url')->nullable(); // Source's webhook registration URL
            $table->enum('event_type', ['product_update', 'price_change', 'stock_change', 'lifecycle_change', 'full_sync']);
            $table->json('event_filters')->nullable(); // Filter criteria for events
            $table->string('secret_token')->nullable(); // For HMAC signature verification
            $table->boolean('active')->default(true);
            $table->timestamp('last_received_at')->nullable();
            $table->unsignedBigInteger('total_received')->default(0);
            $table->unsignedBigInteger('successful_processed')->default(0);
            $table->unsignedBigInteger('failed_processed')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
            
            $table->index(['catalog_source_id', 'event_type', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_source_webhooks');
    }
};
