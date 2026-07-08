<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment provider abstraction + wallet/store-credit + vendor payouts
 * (NEOGIGA_DIGCASH_PAYMENT_ADAPTATION_COMMAND). Additive and non-destructive:
 * this WRAPS the existing `payments`/`refunds` tables (via nullable payment_id
 * on the events table) rather than replacing them — no parallel payment ledger.
 * No live credentials are stored (config json holds only public settings;
 * secrets stay in .env). Wallet + event tables are append-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();             // esewa|khalti|fonepay|stripe|paypal|bank_transfer|cod|wallet
            $table->string('name');
            $table->boolean('is_enabled')->default(false)->index();
            $table->boolean('is_live')->default(false);   // false = sandbox/test
            $table->json('supported_currencies')->nullable();
            $table->json('config')->nullable();           // PUBLIC settings only (no secrets)
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();
        });

        // Audit trail that augments the EXISTING payments table (soft link).
        Schema::create('payment_transaction_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('provider_code')->nullable()->index();
            $table->string('event');                      // initiated|authorized|captured|failed|refunded|webhook
            $table->decimal('amount', 18, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('payload')->nullable();          // sanitized — no secrets
            $table->timestamp('created_at')->nullable();  // append-only
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('active')->index(); // active|frozen
            $table->timestamps();
        });

        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('type');                       // credit|debit|refund|adjust
            $table->decimal('amount', 18, 2);             // signed: +credit / -debit
            $table->decimal('balance_after', 18, 2);
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('reference')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();  // append-only
            $table->index(['wallet_id', 'id']);
        });

        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_number')->unique();
            $table->unsignedBigInteger('vendor_id')->index(); // soft link to vendors
            $table->string('currency', 3)->default('USD');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('status')->default('pending')->index(); // pending|approved|processing|paid|rejected
            $table->string('method')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_payout_id')->constrained('vendor_payouts')->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('description')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payout_items');
        Schema::dropIfExists('vendor_payouts');
        Schema::dropIfExists('wallet_ledger_entries');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('payment_transaction_events');
        Schema::dropIfExists('payment_providers');
    }
};
