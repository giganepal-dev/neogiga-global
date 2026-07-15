<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pcb_quote_configurations')) {
            Schema::table('pcb_quote_configurations', function (Blueprint $table) {
                if (!Schema::hasColumn('pcb_quote_configurations', 'submitted_at')) {
                    $table->timestamp('submitted_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn('pcb_quote_configurations', 'quoted_at')) {
                    $table->timestamp('quoted_at')->nullable()->after('submitted_at');
                }
                if (!Schema::hasColumn('pcb_quote_configurations', 'customer_rejected_at')) {
                    $table->timestamp('customer_rejected_at')->nullable()->after('quoted_at');
                }
                if (!Schema::hasColumn('pcb_quote_configurations', 'customer_notes')) {
                    $table->text('customer_notes')->nullable()->after('customer_rejected_at');
                }
                if (!Schema::hasColumn('pcb_quote_configurations', 'quote_valid_until')) {
                    $table->date('quote_valid_until')->nullable()->after('customer_notes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pcb_quote_configurations')) {
            Schema::table('pcb_quote_configurations', function (Blueprint $table) {
                $columns = ['submitted_at', 'quoted_at', 'customer_rejected_at', 'customer_notes', 'quote_valid_until'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('pcb_quote_configurations', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
