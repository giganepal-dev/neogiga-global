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
        // Region-wise stock visibility rules
        Schema::create('region_stock_visibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('distributor_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('visibility_scope', ['public', 'registered', 'seller_only', 'distributor_only', 'territory_specific']);
            $table->boolean('is_visible')->default(true);
            $table->integer('priority')->default(0); // Higher priority rules override lower
            $table->timestamp('visible_from')->nullable();
            $table->timestamp('visible_until')->nullable();
            $table->json('conditions')->nullable(); // Advanced visibility conditions
            $table->timestamps();
            
            $table->index(['stock_id', 'visibility_scope']);
            $table->index(['country_id', 'marketplace_id', 'visibility_scope']);
            $table->index(['distributor_id', 'visibility_scope']);
        });

        // Territory-based stock allocation
        Schema::create('territory_stock_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('territory_id')->constrained()->cascadeOnDelete();
            $table->integer('allocated_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('sold_quantity')->default(0);
            $table->decimal('min_order_quantity', 10, 2)->default(1);
            $table->decimal('max_order_quantity', 10, 2)->nullable();
            $table->boolean('is_exclusive')->default(false);
            $table->timestamp('allocation_start')->nullable();
            $table->timestamp('allocation_end')->nullable();
            $table->timestamps();
            
            $table->unique(['stock_id', 'distributor_id', 'territory_id']);
            $table->index(['distributor_id', 'territory_id']);
        });

        // Low stock alerts configuration
        Schema::create('low_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Alert recipient
            $table->integer('threshold_quantity');
            $table->enum('alert_type', ['email', 'sms', 'whatsapp', 'push', 'dashboard']);
            $table->boolean('is_active')->default(true);
            $table->boolean('has_been_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index(['stock_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });

        // Stock reservation system
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cart_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'expired']);
            $table->timestamp('expires_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['stock_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
        });

        // Inventory movement audit trail
        Schema::create('inventory_movement_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_id')->constrained('inventory_movements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // created, adjusted, transferred, sold, returned
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['movement_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movement_audits');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('low_stock_alerts');
        Schema::dropIfExists('territory_stock_allocations');
        Schema::dropIfExists('region_stock_visibilities');
    }
};
