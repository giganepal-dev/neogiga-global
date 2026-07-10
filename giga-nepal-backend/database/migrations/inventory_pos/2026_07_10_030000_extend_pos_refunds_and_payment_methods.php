<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_refunds')) {
            if (! Schema::hasColumn('pos_refunds', 'pos_sale_id')) {
                Schema::table('pos_refunds', fn (Blueprint $table) => $table->foreignId('pos_sale_id')->nullable()->constrained('pos_sales')->nullOnDelete());
            }
            if (! Schema::hasColumn('pos_refunds', 'amount')) {
                Schema::table('pos_refunds', fn (Blueprint $table) => $table->decimal('amount', 15, 4)->default(0));
            }
            if (! Schema::hasColumn('pos_refunds', 'currency_code')) {
                Schema::table('pos_refunds', fn (Blueprint $table) => $table->string('currency_code', 3)->default('USD'));
            }
            if (! Schema::hasColumn('pos_refunds', 'refund_method')) {
                Schema::table('pos_refunds', fn (Blueprint $table) => $table->string('refund_method')->default('cash'));
            }
            if (! Schema::hasColumn('pos_refunds', 'reason')) {
                Schema::table('pos_refunds', fn (Blueprint $table) => $table->text('reason')->nullable());
            }
            if (! Schema::hasColumn('pos_refunds', 'status')) {
                Schema::table('pos_refunds', fn (Blueprint $table) => $table->string('status')->default('recorded')->index());
            }
            if (! Schema::hasColumn('pos_refunds', 'processed_by')) {
                Schema::table('pos_refunds', fn (Blueprint $table) => $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete());
            }
            if (! Schema::hasColumn('pos_refunds', 'processed_at')) {
                Schema::table('pos_refunds', fn (Blueprint $table) => $table->timestamp('processed_at')->nullable()->index());
            }
            if (! Schema::hasColumn('pos_refunds', 'metadata')) {
                Schema::table('pos_refunds', fn (Blueprint $table) => $table->json('metadata')->nullable());
            }
        }

        if (! Schema::hasTable('pos_payment_methods')) {
            Schema::create('pos_payment_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('type')->default('cash')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('requires_reference')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_payment_methods');

        if (Schema::hasTable('pos_refunds')) {
            Schema::table('pos_refunds', function (Blueprint $table) {
                foreach (['metadata', 'processed_at', 'processed_by', 'status', 'reason', 'refund_method', 'currency_code', 'amount', 'pos_sale_id'] as $column) {
                    if (Schema::hasColumn('pos_refunds', $column)) {
                        if ($column === 'processed_by') {
                            $table->dropConstrainedForeignId($column);
                        } elseif ($column === 'pos_sale_id') {
                            $table->dropConstrainedForeignId($column);
                        } else {
                            $table->dropColumn($column);
                        }
                    }
                }
            });
        }
    }
};
