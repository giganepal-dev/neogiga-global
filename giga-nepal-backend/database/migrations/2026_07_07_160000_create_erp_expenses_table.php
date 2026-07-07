<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERP expenses (NEOGIGA_ERP_ADAPTATION_COMMAND). Additive; nullable soft link
 * to suppliers/marketplaces/users. Amounts recorded as-is (server-validated).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->string('category')->index();          // e.g. logistics, utilities, salary, marketing
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('recorded')->index(); // recorded|approved|paid
            $table->date('expense_date')->index();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['category', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
