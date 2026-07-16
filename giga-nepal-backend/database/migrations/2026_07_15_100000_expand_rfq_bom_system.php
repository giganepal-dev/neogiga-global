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
        // Enhance existing rfq_requests table
        Schema::table('rfq_requests', function (Blueprint $table) {
            $table->uuid('public_id')->unique()->after('id');
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'product_matching',
                'supplier_inquiry', 'partially_quoted', 'quoted',
                'revision_requested', 'approved', 'rejected',
                'converted_to_order', 'expired', 'cancelled'
            ])->default('draft')->change();
            $table->string('contact_name')->nullable()->change();
            $table->string('company_name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->string('whatsapp')->nullable()->after('phone');
            $table->foreignId('country_id')->nullable()->after('whatsapp')->constrained();
            $table->string('state_province')->nullable()->after('country_id');
            $table->string('city')->nullable()->after('state_province');
            $table->text('billing_address')->nullable()->after('city');
            $table->text('shipping_address')->nullable()->after('billing_address');
            $table->string('tax_vat_number')->nullable()->after('shipping_address');
            $table->string('company_registration_number')->nullable()->after('tax_vat_number');
            $table->string('industry')->nullable()->after('company_registration_number');
            $table->string('project_name')->nullable()->after('industry');
            $table->text('project_description')->nullable()->after('project_name');
            $table->enum('preferred_contact_method', ['email', 'phone', 'whatsapp'])->default('email')->after('project_description');
            $table->date('required_response_date')->nullable()->after('preferred_contact_method');
            $table->text('comments')->nullable()->change();
            $table->foreignId('assigned_salesperson_id')->nullable()->after('comments')->constrained('users');
            $table->foreignId('assigned_sourcing_agent_id')->nullable()->after('assigned_salesperson_id')->constrained('users');
            $table->foreignId('assigned_product_specialist_id')->nullable()->after('assigned_sourcing_agent_id')->constrained('users');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('quoted_at')->nullable()->after('submitted_at');
            $table->timestamp('expires_at')->nullable()->after('quoted_at');
            $table->boolean('allow_alternatives')->default(true)->after('expires_at');
            $table->string('currency', 3)->default('USD')->after('allow_alternatives');
            $table->integer('version')->default(1)->after('currency');
            $table->json('metadata')->nullable()->after('version');
            
            // Indexes for performance
            $table->index('public_id');
            $table->index('status');
            $table->index('submitted_at');
            $table->index(['user_id', 'status']);
        });

        // Enhance rfq_items table
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->string('customer_part_number')->nullable()->after('product_id');
            $table->string('target_unit_price', 20)->nullable()->after('quantity');
            $table->string('currency', 3)->default('USD')->after('target_unit_price');
            $table->date('required_delivery_date')->nullable()->after('currency');
            $table->foreignId('preferred_warehouse_id')->nullable()->after('required_delivery_date')->constrained('warehouses');
            $table->string('preferred_country_of_origin')->nullable()->after('preferred_warehouse_id');
            $table->boolean('accept_alternatives')->default(true)->after('preferred_country_of_origin');
            $table->boolean('exact_match_required')->default(false)->after('accept_alternatives');
            $table->text('technical_notes')->nullable()->after('exact_match_required');
            $table->text('customer_notes')->nullable()->after('technical_notes');
            $table->string('package_type')->nullable()->after('customer_notes');
            $table->string('lifecycle_status')->nullable()->after('package_type');
            $table->json('match_data')->nullable()->after('lifecycle_status');
            
            $table->index(['rfq_request_id', 'status']);
        });

        // Create RFQ versions table for tracking revisions
        Schema::create('rfq_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_request_id')->constrained()->onDelete('cascade');
            $table->integer('version_number');
            $table->json('snapshot_data');
            $table->string('change_summary')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
            
            $table->index(['rfq_request_id', 'version_number']);
        });

        // Create RFQ status history for audit trail
        Schema::create('rfq_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_request_id')->constrained()->onDelete('cascade');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
            
            $table->index('rfq_request_id');
        });

        // Create RFQ assignments table
        Schema::create('rfq_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->enum('role', ['salesperson', 'sourcing_agent', 'product_specialist', 'manager']);
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['rfq_request_id', 'user_id', 'role']);
        });

        // Create RFQ messages table for communication
        Schema::create('rfq_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->enum('sender_type', ['customer', 'admin', 'supplier', 'system']);
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index('rfq_request_id');
        });

        // Create RFQ attachments table
        Schema::create('rfq_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('rfq_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('disk')->default('private');
            $table->string('path');
            $table->string('download_hash')->unique();
            $table->enum('attachment_type', ['datasheet', 'bom', 'drawing', 'specification', 'certificate', 'other']);
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();
            
            $table->index('rfq_request_id');
        });

        // Create BOM uploads table
        Schema::create('bom_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('session_id')->nullable();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('disk')->default('private');
            $table->string('path');
            $table->enum('status', ['pending', 'processing', 'parsed', 'matched', 'ready', 'failed'])->default('pending');
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('invalid_rows')->default(0);
            $table->integer('matched_rows')->default(0);
            $table->integer('unmatched_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->json('column_mapping')->nullable();
            $table->json('parsing_errors')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index('public_id');
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        // Create BOM import rows table
        Schema::create('bom_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_upload_id')->constrained()->onDelete('cascade');
            $table->integer('line_number');
            $table->string('customer_part_number')->nullable();
            $table->string('mpn')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('description')->nullable();
            $table->string('package')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('target_price', 20)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->date('required_date')->nullable();
            $table->boolean('alternative_allowed')->default(true);
            $table->string('reference_designator')->nullable();
            $table->text('notes')->nullable();
            $table->enum('row_status', ['pending', 'valid', 'invalid', 'duplicate', 'matched', 'partial_match', 'unmatched'])->default('pending');
            $table->string('validation_error')->nullable();
            $table->foreignId('matched_product_id')->nullable()->constrained('products');
            $table->float('match_confidence')->default(0);
            $table->string('match_type')->nullable();
            $table->json('match_details')->nullable();
            $table->foreignId('suggested_alternative_id')->nullable()->constrained('products');
            $table->timestamps();
            
            $table->index(['bom_upload_id', 'line_number']);
            $table->index(['bom_upload_id', 'row_status']);
            $table->index('matched_product_id');
        });

        // Create BOM column mappings table for template flexibility
        Schema::create('bom_column_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('column_name');
            $table->string('standard_field');
            $table->json('aliases')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            $table->unique('standard_field');
        });

        // Create BOM matches table for tracking match history
        Schema::create('bom_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_import_row_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->float('confidence_score');
            $table->string('match_algorithm');
            $table->json('match_criteria');
            $table->boolean('is_accepted')->default(false);
            $table->boolean('is_rejected')->default(false);
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index(['bom_import_row_id', 'confidence_score']);
        });

        // Create BOM alternatives table
        Schema::create('bom_alternatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_import_row_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->enum('alternative_type', ['better_performance', 'lower_cost', 'direct_replacement', 'functional_equivalent']);
            $table->json('comparison_data');
            $table->boolean('is_recommended')->default(false);
            $table->timestamps();
            
            $table->index('bom_import_row_id');
        });

        // Create supplier quotes table
        Schema::create('supplier_quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('rfq_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_user_id')->nullable()->constrained('users');
            $table->string('supplier_name')->nullable();
            $table->string('supplier_country')->nullable();
            $table->string('supplier_sku')->nullable();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->string('mpn');
            $table->string('manufacturer')->nullable();
            $table->unsignedInteger('offered_quantity')->default(0);
            $table->decimal('unit_cost', 15, 4);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('moq')->default(1);
            $table->integer('lead_time_days')->nullable();
            $table->string('date_code')->nullable();
            $table->string('packaging')->nullable();
            $table->enum('condition', ['new', 'refurbished', 'used', 'pulls'])->default('new');
            $table->string('warranty')->nullable();
            $table->json('compliance')->nullable();
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->date('quote_validity')->nullable();
            $table->text('supplier_notes')->nullable();
            $table->integer('internal_risk_score')->default(0);
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
            $table->timestamps();
            
            $table->index('public_id');
            $table->index(['rfq_request_id', 'status']);
        });

        // Create supplier quote items table
        Schema::create('supplier_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('rfq_item_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
            
            $table->index('supplier_quote_id');
        });

        // Create customer quotes table
        Schema::create('customer_quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('rfq_request_id')->constrained()->onDelete('cascade');
            $table->integer('quote_number');
            $table->foreignId('created_by_id')->constrained('users');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('margin_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('insurance_cost', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_terms')->nullable();
            $table->string('delivery_terms')->nullable();
            $table->date('validity_date');
            $table->integer('estimated_dispatch_days')->nullable();
            $table->integer('estimated_delivery_days')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained();
            $table->text('commercial_notes')->nullable();
            $table->text('technical_notes')->nullable();
            $table->enum('status', ['draft', 'sent', 'viewed', 'revised', 'accepted', 'rejected', 'expired', 'converted'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            
            $table->index('public_id');
            $table->index(['rfq_request_id', 'status']);
            $table->unique(['rfq_request_id', 'quote_number']);
        });

        // Create customer quote items table
        Schema::create('customer_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('rfq_item_id')->constrained();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->foreignId('supplier_quote_id')->nullable()->constrained('supplier_quotes');
            $table->string('mpn');
            $table->string('manufacturer')->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('total_price', 15, 2);
            $table->integer('lead_time_days')->nullable();
            $table->boolean('is_alternative')->default(false);
            $table->foreignId('original_rfq_item_id')->nullable()->constrained('rfq_items');
            $table->json('pricing_breakdown')->nullable();
            $table->timestamps();
            
            $table->index('customer_quote_id');
        });

        // Create quote versions table
        Schema::create('quote_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_quote_id')->constrained()->onDelete('cascade');
            $table->integer('version_number');
            $table->json('snapshot_data');
            $table->string('change_summary')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
            
            $table->index(['customer_quote_id', 'version_number']);
        });

        // Create quote approvals table
        Schema::create('quote_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->enum('approval_level', ['sales_manager', 'finance', 'regional_admin', 'super_admin']);
            $table->enum('status', ['pending', 'approved', 'rejected']);
            $table->text('comments')->nullable();
            $table->timestamps();
            
            $table->index(['customer_quote_id', 'approval_level']);
        });

        // Create quote activity logs table
        Schema::create('quote_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('activitable');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action');
            $table->json('properties')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['activitable_type', 'activitable_id']);
        });

        // Create product matches table for caching
        Schema::create('product_matches', function (Blueprint $table) {
            $table->id();
            $table->string('query_mpn');
            $table->string('normalized_mpn');
            $table->foreignId('product_id')->constrained();
            $table->float('confidence_score');
            $table->string('match_algorithm');
            $table->integer('hit_count')->default(0);
            $table->timestamp('last_matched_at')->nullable();
            $table->timestamps();
            
            $table->index(['normalized_mpn', 'confidence_score']);
        });

        // Create product aliases table for MPN variations
        Schema::create('product_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('alias_mpn');
            $table->string('source')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by_id')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->unique(['product_id', 'alias_mpn']);
            $table->index('alias_mpn');
        });

        // Create manufacturer aliases table
        Schema::create('manufacturer_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->onDelete('cascade');
            $table->string('alias_name');
            $table->string('source')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            
            $table->unique(['brand_id', 'alias_name']);
            $table->index('alias_name');
        });

        // Create notification logs table
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->string('notification_type');
            $table->string('channel');
            $table->json('payload');
            $table->enum('status', ['pending', 'sent', 'failed', 'retrying'])->default('pending');
            $table->integer('attempt_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['notifiable_type', 'notifiable_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('manufacturer_aliases');
        Schema::dropIfExists('product_aliases');
        Schema::dropIfExists('product_matches');
        Schema::dropIfExists('quote_activity_logs');
        Schema::dropIfExists('quote_approvals');
        Schema::dropIfExists('quote_versions');
        Schema::dropIfExists('customer_quote_items');
        Schema::dropIfExists('customer_quotes');
        Schema::dropIfExists('supplier_quote_items');
        Schema::dropIfExists('supplier_quotes');
        Schema::dropIfExists('bom_alternatives');
        Schema::dropIfExists('bom_matches');
        Schema::dropIfExists('bom_column_mappings');
        Schema::dropIfExists('bom_import_rows');
        Schema::dropIfExists('bom_uploads');
        Schema::dropIfExists('rfq_attachments');
        Schema::dropIfExists('rfq_messages');
        Schema::dropIfExists('rfq_assignments');
        Schema::dropIfExists('rfq_status_history');
        Schema::dropIfExists('rfq_versions');
        
        // Revert rfq_items changes
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->dropColumn([
                'customer_part_number', 'target_unit_price', 'currency',
                'required_delivery_date', 'preferred_warehouse_id',
                'preferred_country_of_origin', 'accept_alternatives',
                'exact_match_required', 'technical_notes', 'customer_notes',
                'package_type', 'lifecycle_status', 'match_data'
            ]);
        });
        
        // Revert rfq_requests changes
        Schema::table('rfq_requests', function (Blueprint $table) {
            $table->dropColumn([
                'public_id', 'whatsapp', 'country_id', 'state_province', 'city',
                'billing_address', 'shipping_address', 'tax_vat_number',
                'company_registration_number', 'industry', 'project_name',
                'project_description', 'preferred_contact_method',
                'required_response_date', 'assigned_salesperson_id',
                'assigned_sourcing_agent_id', 'assigned_product_specialist_id',
                'submitted_at', 'quoted_at', 'expires_at', 'allow_alternatives',
                'currency', 'version', 'metadata'
            ]);
            $table->enum('status', ['pending', 'processed', 'quoted', 'completed'])->default('pending')->change();
            $table->string('contact_name')->change();
            $table->string('company_name')->change();
            $table->string('email')->change();
            $table->string('phone')->change();
            $table->text('comments')->change();
        });
    }
};
