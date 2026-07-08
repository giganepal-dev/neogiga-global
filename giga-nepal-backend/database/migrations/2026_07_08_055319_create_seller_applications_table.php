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
        // GUARD: prod already has seller_applications (created 2026-07-07 by
        // create_vendor_seller_phase_b_tables with a different schema, owned by
        // the live Api\Onboarding module). This duplicate from PR#2 must never
        // clobber or crash a deploy — skip when the table exists.
        if (Schema::hasTable('seller_applications')) {
            return;
        }

        Schema::create('seller_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name');
            $table->string('business_type')->nullable(); // Manufacturer, Distributor, Retailer, Brand Owner
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone');
            $table->string('country');
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('business_address')->nullable();
            $table->string('pan_number')->nullable(); // For Nepal/India
            $table->string('vat_number')->nullable();
            $table->string('company_registration_number')->nullable();
            $table->string('website_url')->nullable();
            $table->text('product_categories')->nullable(); // JSON: categories they want to sell
            $table->text('brand_names')->nullable(); // JSON: brands they represent
            $table->integer('estimated_monthly_volume')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('document_pan')->nullable(); // Path to uploaded PAN certificate
            $table->string('document_company_reg')->nullable(); // Path to company registration
            $table->string('document_tax_certificate')->nullable(); // Tax/VAT certificate
            $table->string('document_identity')->nullable(); // Citizenship/Passport
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('email');
            $table->index('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_applications');
    }
};
