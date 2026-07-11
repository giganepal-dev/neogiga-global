<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pcb_quote_configurations')) {
            return;
        }

        Schema::table('pcb_quote_configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('pcb_quote_configurations', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('project_id')->constrained('orders')->nullOnDelete();
            }
            if (! Schema::hasColumn('pcb_quote_configurations', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
            if (! Schema::hasColumn('pcb_quote_configurations', 'quoted_at')) {
                $table->timestamp('quoted_at')->nullable();
            }
            if (! Schema::hasColumn('pcb_quote_configurations', 'quote_valid_until')) {
                $table->date('quote_valid_until')->nullable();
            }
            if (! Schema::hasColumn('pcb_quote_configurations', 'customer_approved_at')) {
                $table->timestamp('customer_approved_at')->nullable();
            }
            if (! Schema::hasColumn('pcb_quote_configurations', 'customer_rejected_at')) {
                $table->timestamp('customer_rejected_at')->nullable();
            }
            if (! Schema::hasColumn('pcb_quote_configurations', 'customer_notes')) {
                $table->text('customer_notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pcb_quote_configurations')) {
            return;
        }

        if (Schema::hasColumn('pcb_quote_configurations', 'order_id')) {
            Schema::table('pcb_quote_configurations', fn (Blueprint $table) => $table->dropConstrainedForeignId('order_id'));
        }

        $columns = array_values(array_filter([
            'submitted_at', 'quoted_at', 'quote_valid_until', 'customer_approved_at',
            'customer_rejected_at', 'customer_notes',
        ], fn (string $column) => Schema::hasColumn('pcb_quote_configurations', $column)));

        if ($columns) {
            Schema::table('pcb_quote_configurations', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
