<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_accounts')) {
            Schema::table('b2b_accounts', function (Blueprint $table) {
                foreach (['document_company_reg', 'document_tax_certificate', 'document_institutional_id'] as $column) {
                    if (! Schema::hasColumn('b2b_accounts', $column)) {
                        $table->string($column)->nullable()->after('pan_vat_number');
                    }
                }
            });
        }

        if (Schema::hasTable('b2b_quotations')) {
            Schema::table('b2b_quotations', function (Blueprint $table) {
                if (! Schema::hasColumn('b2b_quotations', 'payment_status')) {
                    $table->string('payment_status')->default('locked')->index()->after('status');
                }
                if (! Schema::hasColumn('b2b_quotations', 'order_id')) {
                    $table->unsignedBigInteger('order_id')->nullable()->index()->after('payment_status');
                }
                if (! Schema::hasColumn('b2b_quotations', 'sent_at')) {
                    $table->timestamp('sent_at')->nullable()->after('accepted_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_accounts')) {
            Schema::table('b2b_accounts', function (Blueprint $table) {
                foreach (['document_company_reg', 'document_tax_certificate', 'document_institutional_id'] as $column) {
                    if (Schema::hasColumn('b2b_accounts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('b2b_quotations')) {
            Schema::table('b2b_quotations', function (Blueprint $table) {
                foreach (['payment_status', 'order_id', 'sent_at'] as $column) {
                    if (Schema::hasColumn('b2b_quotations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
