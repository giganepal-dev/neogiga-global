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
        Schema::create('distributor_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_application_id')->constrained()->onDelete('cascade');
            $table->foreignId('distributor_territory_id')->nullable()->constrained()->onDelete('set null');
            $table->string('lead_name');
            $table->string('lead_email')->nullable();
            $table->string('lead_phone');
            $table->string('company_name')->nullable();
            $table->string('designation')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('province_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('district_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('set null');
            
            // Lead details
            $table->text('requirements')->nullable();
            $table->json('interested_products')->nullable(); // Array of product IDs or categories
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->string('lead_source')->default('direct'); // direct, referral, website, event, social_media
            $table->string('lead_status')->default('new'); // new, contacted, qualified, proposal_sent, negotiated, won, lost, on_hold
            $table->integer('priority')->default(0); // 0-10 scale
            $table->timestamp('expected_closure_date')->nullable();
            
            // Communication tracking
            $table->integer('contact_attempts')->default(0);
            $table->timestamp('last_contacted_at')->nullable();
            $table->text('last_communication_notes')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            
            // Conversion tracking
            $table->foreignId('converted_customer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('converted_order_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('converted_at')->nullable();
            $table->text('lost_reason')->nullable();
            
            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            
            $table->index(['distributor_application_id', 'lead_status']);
            $table->index(['lead_status', 'priority']);
            $table->index('next_follow_up_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_leads');
    }
};
