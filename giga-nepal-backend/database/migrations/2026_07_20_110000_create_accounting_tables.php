<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();        // e.g. 1000, 4000, 5000
            $table->string('name');                       // e.g. "Sales Revenue — Nepal"
            $table->string('type');                       // asset, liability, equity, revenue, expense
            $table->string('normal_balance')->default('debit'); // debit or credit
            $table->boolean('is_active')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces');
            $table->string('currency_code', 3)->default('USD');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number')->unique();   // JE-000001
            $table->date('entry_date');
            $table->string('reference_type')->nullable(); // pos_sale, order, refund, settlement, inventory
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');   // draft, posted, voided
            $table->foreignId('posted_by')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accounting_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('accounting_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->decimal('debit', 14, 4)->default(0);
            $table->decimal('credit', 14, 4)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 14, 6)->default(1);
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces');
            $table->text('description')->nullable();
            $table->timestamps();

            // Enforce single-sided entry per line
            $table->index(['entry_id', 'account_id']);
        });

        // Seed default chart of accounts
        $this->seedDefaultAccounts();
    }

    private function seedDefaultAccounts(): void
    {
        $accounts = [
            // Assets (1000–1999)
            ['code' => '1000', 'name' => 'Cash on Hand', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1010', 'name' => 'Cash — Bank', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1300', 'name' => 'Store Credit — Customer', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1400', 'name' => 'Payment Gateway Clearing', 'type' => 'asset', 'normal_balance' => 'debit'],

            // Liabilities (2000–2999)
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2100', 'name' => 'Tax Payable — Sales/VAT/GST', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2200', 'name' => 'Tax Payable — Income', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2300', 'name' => 'Seller Settlements Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2400', 'name' => 'Distributor Commissions Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2500', 'name' => 'Reseller Commissions Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2600', 'name' => 'Customer Deposits / Advances', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2700', 'name' => 'Gift Card Liability', 'type' => 'liability', 'normal_balance' => 'credit'],

            // Equity (3000–3999)
            ['code' => '3000', 'name' => 'Owner Equity', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3200', 'name' => 'Currency Translation Reserve', 'type' => 'equity', 'normal_balance' => 'credit'],

            // Revenue (4000–4999)
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue', 'normal_balance' => 'credit'],
            ['code' => '4100', 'name' => 'Shipping Revenue', 'type' => 'revenue', 'normal_balance' => 'credit'],
            ['code' => '4200', 'name' => 'Service Revenue', 'type' => 'revenue', 'normal_balance' => 'credit'],
            ['code' => '4300', 'name' => 'LMS Course Revenue', 'type' => 'revenue', 'normal_balance' => 'credit'],

            // Cost of Goods (5000–5999)
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5100', 'name' => 'Shipping Expense', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5200', 'name' => 'Payment Processing Fees', 'type' => 'expense', 'normal_balance' => 'debit'],

            // Expenses (6000–7999)
            ['code' => '6000', 'name' => 'Marketing Expense', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '6100', 'name' => 'Seller Commissions Expense', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '6200', 'name' => 'Affiliate Commissions Expense', 'type' => 'expense', 'normal_balance' => 'debit'],

            // Contra / Other (8000+)
            ['code' => '8000', 'name' => 'Sales Discounts', 'type' => 'revenue', 'normal_balance' => 'debit'],
            ['code' => '8100', 'name' => 'Sales Returns', 'type' => 'revenue', 'normal_balance' => 'debit'],
        ];

        $now = now();
        foreach ($accounts as $acct) {
            DB::table('chart_of_accounts')->insert(array_merge($acct, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entry_lines');
        Schema::dropIfExists('accounting_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};
