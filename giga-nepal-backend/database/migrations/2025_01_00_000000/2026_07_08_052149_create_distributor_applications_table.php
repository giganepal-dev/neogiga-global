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
        Schema::create('distributor_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('company_name');
            $table->string('company_registration_number')->nullable();
            $table->string('pan_number')->nullable(); // For Nepal/India tax
            $table->string('vat_number')->nullable();
            $table->string('contact_person_name');
            $table->string('contact_person_email');
            $table->string('contact_person_phone');
            $table->text('business_address');
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->foreignId('province_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('district_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('postal_code')->nullable();
            
            // Territory preferences
            $table->json('preferred_territories')->nullable(); // Array of territory IDs/regions
            $table->string('territory_type')->default('exclusive'); // exclusive, non-exclusive, regional
            $table->text('business_experience')->nullable();
            $table->integer('years_in_business')->nullable();
            $table->decimal('annual_turnover', 15, 2)->nullable();
            $table->string('currency', 3)->default('NPR');
            
            // Product categories interested in
            $table->json('interested_categories')->nullable();
            
            // Documents
            $table->string('company_registration_document')->nullable();
            $table->string('pan_certificate')->nullable();
            $table->string('vat_certificate')->nullable();
            $table->string('citizenship_certificate')->nullable();
            $table->string('tax_clearance_certificate')->nullable();
            $table->text('additional_documents')->nullable();
            
            // Bank details for commission payouts
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_holder_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('routing_number')->nullable();
            
            // Application status
            $table->string('status')->default('pending'); // pending, under_review, approved, rejected, on_hold
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Commission structure
            $table->decimal('commission_rate', 5, 2)->nullable(); // Percentage
            $table->decimal('minimum_order_value', 15, 2)->nullable();
            $table->decimal('target_monthly_sales', 15, 2)->nullable();
            
            // Tracking
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_active')->default(false);
            
            $table->index(['status', 'country_id']);
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_applications');
    }
};
