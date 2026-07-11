<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $missing = collect([
            'order_id', 'submitted_at', 'quoted_at', 'quote_valid_until',
            'customer_approved_at', 'customer_rejected_at', 'customer_notes',
        ])->reject(fn (string $column) => Schema::hasColumn('pcb_quote_configurations', $column))->all();

        if (! $missing) {
            return;
        }

        Schema::table('pcb_quote_configurations', function (Blueprint $table) use ($missing) {
            if (in_array('order_id', $missing, true)) {
                $table->foreignId('order_id')->nullable()->after('project_id')->constrained('orders')->nullOnDelete();
            }
            if (in_array('submitted_at', $missing, true)) {
                $table->timestamp('submitted_at')->nullable();
            }
            if (in_array('quoted_at', $missing, true)) {
                $table->timestamp('quoted_at')->nullable();
            }
            if (in_array('quote_valid_until', $missing, true)) {
                $table->date('quote_valid_until')->nullable();
            }
            if (in_array('customer_approved_at', $missing, true)) {
                $table->timestamp('customer_approved_at')->nullable();
            }
            if (in_array('customer_rejected_at', $missing, true)) {
                $table->timestamp('customer_rejected_at')->nullable();
            }
            if (in_array('customer_notes', $missing, true)) {
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
