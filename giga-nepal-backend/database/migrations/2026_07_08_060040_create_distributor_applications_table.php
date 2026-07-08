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
        // GUARD: prod already has distributor_applications (owned by the live
        // Api\Onboarding module, different schema). This duplicate from PR#2
        // must never clobber or crash a deploy — skip when the table exists.
        if (Schema::hasTable('distributor_applications')) {
            return;
        }

        Schema::create('distributor_applications', function (Blueprint $table) {
            $table->id();
            
            // Applicant Information
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('company_name')->nullable();
            $table->string('company_registration_number')->nullable();
            $table->string('pan_number')->nullable(); // For Nepal/India tax
            
            // Business Details
            $table->string('business_type')->default('individual'); // individual, company, partnership
            $table->text('business_description')->nullable();
            $table->string('website_url')->nullable();
            $table->integer('years_in_business')->default(0);
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->integer('employee_count')->default(0);
            
            // Territory & Coverage
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('province_id')->nullable()->constrained('provinces');
            $table->foreignId('district_id')->nullable()->constrained('districts');
            $table->string('city')->nullable();
            $table->text('coverage_areas')->nullable(); // JSON: areas they can cover
            $table->boolean('exclusive_territory_requested')->default(false);
            
            // Product Categories Interest
            $table->json('interested_categories')->nullable(); // Category IDs
            $table->text('product_experience')->nullable();
            
            // Infrastructure
            $table->boolean('has_warehouse')->default(false);
            $table->integer('warehouse_count')->default(0);
            $table->integer('warehouse_sqft')->nullable();
            $table->boolean('has_showroom')->default(false);
            $table->integer('showroom_count')->default(0);
            $table->boolean('has_sales_team')->default(false);
            $table->integer('sales_team_size')->default(0);
            $table->boolean('has_service_center')->default(false);
            $table->text('logistics_capability')->nullable();
            
            // Financial Capacity
            $table->decimal('initial_investment_capacity', 15, 2)->nullable();
            $table->decimal('monthly_purchase_capacity', 15, 2)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            
            // References & Experience
            $table->text('brand_references')->nullable(); // Previous brands distributed
            $table->text('major_clients')->nullable();
            $table->text('competitive_advantage')->nullable();
            
            // Documents (file paths)
            $table->string('company_registration_doc')->nullable();
            $table->string('pan_certificate_doc')->nullable();
            $table->string('citizenship_doc')->nullable();
            $table->string('tax_clearance_doc')->nullable();
            $table->string('warehouse_proof_doc')->nullable();
            $table->string('bank_statement_doc')->nullable();
            $table->string('business_plan_doc')->nullable();
            $table->string('other_documents')->nullable();
            
            // NeoGiga Specific
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces');
            $table->string('referral_source')->nullable(); // How they heard about NeoGiga
            $table->string('referred_by')->nullable(); // User ID or name
            $table->text('why_neogiga')->nullable();
            $table->text('additional_comments')->nullable();
            
            // Application Status
            $table->string('status')->default('pending'); // pending, under_review, approved, rejected, withdrawn
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // Commission & Terms
            $table->decimal('commission_rate', 5, 2)->nullable(); // Percentage
            $table->integer('payment_terms_days')->default(30);
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->timestamp('agreement_signed_at')->nullable();
            $table->string('agreement_document')->nullable();
            
            // Tracking
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->text('withdrawal_reason')->nullable();
            
            $table->index(['status', 'country_id']);
            $table->index(['email', 'phone']);
            $table->index(['marketplace_id', 'status']);
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
