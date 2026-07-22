<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4: Advanced Purchasing & Supplier Portal
     */
    public function up(): void
    {
        // Purchase Requisitions
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('requisition_number')->unique()->index();
            $table->enum('status', ['draft', 'submitted', 'pending_approval', 'approved', 'rejected', 'converted_to_po', 'cancelled'])->default('draft');
            $table->text('justification')->nullable();
            $table->date('required_by_date')->nullable();
            $table->decimal('estimated_total', 15, 4)->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['marketplace_id', 'status']);
            $table->index(['warehouse_id', 'status']);
            $table->index(['requested_by', 'status']);
        });

        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('manufacturer_name')->nullable();
            $table->string('mpn')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('quantity_requested', 12, 4)->default(1);
            $table->string('unit')->default('piece');
            $table->decimal('estimated_unit_cost', 15, 4)->default(0);
            $table->decimal('estimated_total', 15, 4)->default(0);
            $table->text('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_quantity')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('quantity_approved', 12, 4)->nullable();
            $table->timestamps();
            
            $table->index(['requisition_id', 'product_id']);
        });

        // Supplier RFQs
        Schema::create('supplier_rfqs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('purchase_requisition_id')->nullable()->constrained('purchase_requisitions')->nullOnDelete();
            $table->string('rfq_number')->unique()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'sent', 'responses_received', 'evaluating', 'awarded', 'closed', 'cancelled'])->default('draft');
            $table->date('submission_deadline')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->string('delivery_location')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['marketplace_id', 'status']);
            $table->index(['created_by', 'status']);
        });

        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('supplier_rfqs')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->string('manufacturer_name')->nullable();
            $table->string('mpn')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit')->default('piece');
            $table->text('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['rfq_id', 'product_id']);
        });

        Schema::create('rfq_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('supplier_rfqs')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->enum('status', ['invited', 'viewed', 'responded', 'declined', 'awarded', 'not_awarded'])->default('invited');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->text('supplier_notes')->nullable();
            $table->timestamps();
            
            $table->unique(['rfq_id', 'supplier_id']);
            $table->index(['rfq_id', 'status']);
        });

        // Supplier Quotations
        Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('rfq_id')->constrained('supplier_rfqs')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('quotation_number')->index();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->enum('status', ['submitted', 'under_review', 'accepted', 'rejected', 'expired', 'converted_to_po'])->default('submitted');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->integer('lead_time_days')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('delivery_terms')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            
            $table->index(['rfq_id', 'status']);
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('supplier_quotations')->cascadeOnDelete();
            $table->foreignId('rfq_item_id')->nullable()->constrained('rfq_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->string('manufacturer_name')->nullable();
            $table->string('mpn')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit')->default('piece');
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->integer('lead_time_days')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['quotation_id', 'product_id']);
        });

        // Enhanced Purchase Orders (add fields to existing)
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('purchase_requisition_id')->nullable()->after('id')->constrained('purchase_requisitions')->nullOnDelete();
            $table->foreignId('supplier_quotation_id')->nullable()->after('purchase_requisition_id')->constrained('supplier_quotations')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('warehouse_id')->constrained('departments')->nullOnDelete();
            $table->string('internal_reference')->nullable()->after('order_number');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->decimal('discount_amount', 15, 4)->default(0)->after('tax_amount');
            $table->decimal('shipping_cost', 15, 4)->default(0)->after('discount_amount');
            $table->decimal('other_charges', 15, 4)->default(0)->after('shipping_cost');
            $table->string('payment_terms')->nullable()->after('other_charges');
            $table->string('delivery_terms')->nullable()->after('payment_terms');
            $table->date('expected_delivery_date')->nullable()->after('delivery_terms');
            $table->text('notes')->nullable()->after('expected_delivery_date');
            
            $table->index(['purchase_requisition_id', 'status']);
            $table->index(['supplier_quotation_id', 'status']);
        });

        // Goods Receipt Notes
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('freight_shipment_id')->nullable()->constrained('freight_shipments')->nullOnDelete();
            $table->string('grn_number')->unique()->index();
            $table->date('receipt_date');
            $table->enum('status', ['draft', 'received', 'quality_check_pending', 'quality_check_passed', 'quality_check_failed', 'partially_received', 'completed', 'cancelled'])->default('received');
            $table->string('supplier_invoice_number')->nullable();
            $table->date('supplier_invoice_date')->nullable();
            $table->string('delivery_note_number')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->foreignId('received_by')->constrained('users')->nullOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspection_completed_at')->nullable();
            $table->enum('quality_status', ['pending', 'passed', 'failed', 'partial'])->default('pending');
            $table->text('quality_notes')->nullable();
            $table->text('damage_notes')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            
            $table->index(['purchase_order_id', 'status']);
            $table->index(['warehouse_id', 'receipt_date']);
            $table->index(['supplier_id', 'receipt_date']);
        });

        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('warehouse_bin_id')->nullable()->constrained('warehouse_bins')->nullOnDelete();
            $table->decimal('quantity_ordered', 12, 4)->default(1);
            $table->decimal('quantity_received', 12, 4)->default(0);
            $table->decimal('quantity_accepted', 12, 4)->default(0);
            $table->decimal('quantity_rejected', 12, 4)->default(0);
            $table->decimal('quantity_damaged', 12, 4)->default(0);
            $table->string('reject_reason')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_numbers')->nullable(); // JSON array of serials
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->timestamps();
            
            $table->index(['grn_id', 'product_id']);
            $table->index(['product_id', 'batch_number']);
        });

        // Supplier Invoices
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('invoice_number')->index();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('shipping_cost', 15, 4)->default(0);
            $table->decimal('other_charges', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->decimal('amount_paid', 15, 4)->default(0);
            $table->decimal('balance_due', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['draft', 'pending', 'partially_paid', 'paid', 'overdue', 'cancelled', 'disputed'])->default('pending');
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('entered_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['supplier_id', 'status']);
            $table->index(['invoice_date', 'status']);
            $table->index(['due_date', 'status']);
        });

        Schema::create('supplier_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained('supplier_invoices')->cascadeOnDelete();
            $table->foreignId('grn_item_id')->nullable()->constrained('grn_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('product_name');
            $table->string('mpn')->nullable();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit')->default('piece');
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            $table->timestamps();
        });

        // Supplier Performance Tracking
        Schema::create('supplier_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete();
            $table->date('evaluation_date');
            $table->integer('on_time_delivery_score')->default(0); // 0-100
            $table->integer('quality_score')->default(0); // 0-100
            $table->integer('pricing_score')->default(0); // 0-100
            $table->integer('communication_score')->default(0); // 0-100
            $table->integer('documentation_score')->default(0); // 0-100
            $table->decimal('defect_rate', 5, 4)->default(0);
            $table->integer('average_lead_time_days')->default(0);
            $table->text('comments')->nullable();
            $table->foreignId('evaluated_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['supplier_id', 'evaluation_date']);
        });

        // Supplier Documents
        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('document_type')->index(); // tax_cert, business_license, iso_cert, etc.
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_mime_type');
            $table->integer('file_size_bytes');
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('document_number')->nullable();
            $table->enum('status', ['active', 'expired', 'pending_review', 'rejected'])->default('pending_review');
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index(['supplier_id', 'document_type']);
            $table->index(['supplier_id', 'status']);
        });

        // Purchase Returns
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('supplier_invoice_id')->nullable()->constrained('supplier_invoices')->nullOnDelete();
            $table->string('return_number')->unique()->index();
            $table->date('return_date');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'shipped', 'received_by_supplier', 'credit_issued', 'rejected', 'cancelled'])->default('draft');
            $table->text('return_reason');
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('shipped_at')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamps();
            
            $table->index(['supplier_id', 'status']);
            $table->index(['goods_receipt_id', 'status']);
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('grn_item_id')->constrained('grn_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity_returned', 12, 4)->default(1);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->text('reason')->nullable();
            $table->text('condition')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('supplier_documents');
        Schema::dropIfExists('supplier_performance_logs');
        Schema::dropIfExists('supplier_invoice_items');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('goods_receipts');
        
        // Remove added columns from purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['purchase_requisition_id']);
            $table->dropForeign(['supplier_quotation_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'purchase_requisition_id', 'supplier_quotation_id', 'department_id',
                'internal_reference', 'approval_status', 'approved_by', 'approved_at',
                'discount_amount', 'shipping_cost', 'other_charges', 'payment_terms',
                'delivery_terms', 'expected_delivery_date', 'notes'
            ]);
        });
        
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('supplier_quotations');
        Schema::dropIfExists('rfq_suppliers');
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('supplier_rfqs');
        Schema::dropIfExists('requisition_items');
        Schema::dropIfExists('purchase_requisitions');
    }
};
