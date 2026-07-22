<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 5: Complete Accounting System & Financial Reporting
     */
    public function up(): void
    {
        // Chart of Accounts
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('parent_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('account_code')->unique()->index();
            $table->string('account_name');
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('account_subtype', [
                'current_asset', 'fixed_asset', 'other_asset',
                'current_liability', 'long_term_liability', 'other_liability',
                'owner_equity', 'retained_earnings',
                'operating_revenue', 'other_revenue',
                'cost_of_goods_sold', 'operating_expense', 'other_expense'
            ])->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_account')->default(false);
            $table->boolean('allow_manual_posting')->default(true);
            $table->string('currency', 3)->default('USD');
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['account_type', 'account_subtype']);
            $table->index(['marketplace_id', 'is_active']);
        });

        // Fiscal Years & Periods
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('year_name');
            $table->integer('year_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'open', 'locked', 'closed'])->default('draft');
            $table->boolean('is_current')->default(false);
            $table->foreignId('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['marketplace_id', 'status']);
            $table->index(['year_number', 'status']);
        });

        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->string('period_name');
            $table->integer('period_number'); // 1-12 for monthly
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'locked', 'closed'])->default('open');
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['fiscal_year_id', 'period_number']);
            $table->index(['fiscal_year_id', 'status']);
        });

        // Journal Entries
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('entry_number')->unique()->index();
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->nullOnDelete();
            $table->date('entry_date');
            $table->enum('entry_type', [
                'sales', 'purchase', 'payment', 'receipt', 'journal', 
                'adjustment', 'reversal', 'opening', 'closing', 'transfer'
            ])->default('journal');
            $table->string('reference_type')->nullable(); // pos_sale, purchase_order, invoice, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->string('source')->default('manual'); // manual, pos, purchasing, freight, etc.
            $table->decimal('total_debit', 15, 4)->default(0);
            $table->decimal('total_credit', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->boolean('is_balanced')->default(true);
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('reversed_by_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['entry_date', 'entry_type']);
            $table->index(['accounting_period_id', 'is_posted']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['marketplace_id', 'entry_date']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->enum('direction', ['debit', 'credit']);
            $table->decimal('amount', 15, 4)->default(0);
            $table->decimal('base_amount', 15, 4)->default(0); // In base currency
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['journal_entry_id', 'account_id']);
            $table->index(['account_id', 'direction']);
            $table->index(['customer_id', 'direction']);
            $table->index(['supplier_id', 'direction']);
        });

        // Cost Centers & Projects
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('parent_cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->string('code')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['department', 'branch', 'warehouse', 'project', 'other'])->default('department');
            $table->boolean('is_active')->default(true);
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['marketplace_id', 'is_active']);
            $table->index(['type', 'is_active']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('project_code')->unique()->index();
            $table->string('project_name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
            $table->decimal('budget_amount', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['marketplace_id', 'status']);
        });

        // Customer Ledger (Sub-ledger for AR)
        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('journal_line_id')->constrained('journal_lines')->cascadeOnDelete();
            $table->string('reference_type')->index(); // invoice, payment, credit_note, etc.
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_number');
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->enum('transaction_type', ['invoice', 'payment', 'credit_note', 'debit_note', 'adjustment', 'write_off']);
            $table->decimal('debit_amount', 15, 4)->default(0);
            $table->decimal('credit_amount', 15, 4)->default(0);
            $table->decimal('balance', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['customer_id', 'transaction_date']);
            $table->index(['customer_id', 'balance']);
            $table->index(['reference_type', 'reference_id']);
        });

        // Supplier Ledger (Sub-ledger for AP)
        Schema::create('supplier_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('journal_line_id')->constrained('journal_lines')->cascadeOnDelete();
            $table->string('reference_type')->index(); // invoice, payment, debit_note, etc.
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_number');
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->enum('transaction_type', ['invoice', 'payment', 'credit_note', 'debit_note', 'adjustment', 'write_off']);
            $table->decimal('debit_amount', 15, 4)->default(0);
            $table->decimal('credit_amount', 15, 4)->default(0);
            $table->decimal('balance', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['supplier_id', 'transaction_date']);
            $table->index(['supplier_id', 'balance']);
            $table->index(['reference_type', 'reference_id']);
        });

        // Payment Allocations (for linking payments to invoices)
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->enum('allocation_type', ['customer', 'supplier'])->default('customer');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('payment_journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->string('invoice_reference_type')->index(); // pos_sale, supplier_invoice
            $table->unsignedBigInteger('invoice_reference_id');
            $table->string('invoice_number');
            $table->date('allocation_date');
            $table->decimal('allocated_amount', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['allocation_type', 'customer_id']);
            $table->index(['allocation_type', 'supplier_id']);
            $table->index(['invoice_reference_type', 'invoice_reference_id']);
        });

        // Tax Records
        Schema::create('tax_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('tax_class_id')->nullable()->constrained('tax_classes')->nullOnDelete();
            $table->string('tax_type')->index(); // vat, gst, sales_tax, withholding, etc.
            $table->string('tax_name');
            $table->enum('tax_direction', ['output', 'input', 'withholding'])->default('output');
            $table->decimal('taxable_amount', 15, 4)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('tax_period_start');
            $table->date('tax_period_end');
            $table->boolean('is_paid')->default(false);
            $table->date('paid_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['marketplace_id', 'tax_type', 'tax_direction']);
            $table->index(['tax_period_start', 'tax_period_end']);
        });

        // Bank Accounts
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number')->index();
            $table->string('routing_number')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('iban')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['marketplace_id', 'is_active']);
            $table->index(['account_id', 'is_active']);
        });

        // Bank Reconciliation
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('statement_date');
            $table->date('statement_start_date');
            $table->date('statement_end_date');
            $table->decimal('statement_opening_balance', 15, 4)->default(0);
            $table->decimal('statement_closing_balance', 15, 4)->default(0);
            $table->decimal('system_closing_balance', 15, 4)->default(0);
            $table->decimal('reconciled_difference', 15, 4)->default(0);
            $table->enum('status', ['in_progress', 'reconciled', 'cancelled'])->default('in_progress');
            $table->text('notes')->nullable();
            $table->foreignId('prepared_by')->constrained('users')->nullOnDelete();
            $table->timestamp('prepared_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index(['bank_account_id', 'statement_date']);
            $table->index(['status', 'statement_date']);
        });

        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('journal_line_id')->constrained('journal_lines')->cascadeOnDelete();
            $table->string('reference_number');
            $table->date('transaction_date');
            $table->text('description');
            $table->decimal('amount', 15, 4)->default(0);
            $table->enum('direction', ['debit', 'credit']);
            $table->boolean('is_cleared')->default(false);
            $table->timestamp('cleared_at')->nullable();
            $table->foreignId('cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['bank_reconciliation_id', 'is_cleared']);
        });

        // Accounting Mappings (for automatic journal creation)
        Schema::create('accounting_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('mapping_type')->index(); // product_category, warehouse, tax_type, payment_method, etc.
            $table->unsignedBigInteger('mapping_reference_id')->nullable();
            $table->string('mapping_reference_type')->nullable();
            $table->foreignId('debit_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('credit_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->text('conditions')->nullable(); // JSON conditions for when this mapping applies
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['mapping_type', 'is_active']);
            $table->index(['marketplace_id', 'mapping_type']);
        });

        // Financial Report Templates
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('report_name');
            $table->enum('report_type', ['balance_sheet', 'profit_loss', 'cash_flow', 'trial_balance', 'custom'])->default('custom');
            $table->json('template_config'); // Row/column definitions, formulas, etc.
            $table->boolean('is_system_template')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['marketplace_id', 'report_type']);
        });

        // Audit Trail for Accounting
        Schema::create('accounting_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('auditable_type')->index();
            $table->unsignedBigInteger('auditable_id');
            $table->string('action')->index(); // created, updated, posted, reversed, deleted
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->foreignId('user_id')->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_audit_logs');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('accounting_mappings');
        Schema::dropIfExists('bank_reconciliation_items');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('tax_records');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('supplier_ledger_entries');
        Schema::dropIfExists('customer_ledger_entries');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('chart_of_accounts');
    }
};
