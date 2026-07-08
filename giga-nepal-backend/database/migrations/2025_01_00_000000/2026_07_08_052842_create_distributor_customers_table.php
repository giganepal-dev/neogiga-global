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
        Schema::create('distributor_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_application_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('distributor_territory_id')->nullable()->constrained()->onDelete('set null');
            
            // Relationship tracking
            $table->string('relationship_type')->default('direct'); // direct, referred, inherited
            $table->timestamp('assigned_at');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Territory validation
            $table->boolean('is_within_territory')->default(true);
            $table->text('territory_notes')->nullable();
            
            // Sales tracking
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->decimal('commission_earned', 15, 2)->default(0);
            $table->decimal('pending_commission', 15, 2)->default(0);
            
            // Status
            $table->string('status')->default('active'); // active, inactive, blocked
            $table->timestamp('last_order_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->unique(['distributor_application_id', 'customer_id']);
            $table->index(['distributor_application_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_customers');
    }
};
