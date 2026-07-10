<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('low_stock_alerts')) {
            Schema::create('low_stock_alerts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('inventory_stock_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('vendor_id')->nullable()->index();
                $table->unsignedBigInteger('marketplace_id')->nullable()->index();
                $table->decimal('available_quantity', 15, 3)->default(0);
                $table->decimal('threshold', 15, 3)->default(0);
                $table->string('status', 40)->default('open')->index();
                $table->timestamps();
            });
        }

        Schema::table('low_stock_alerts', function (Blueprint $table) {
            if (! Schema::hasColumn('low_stock_alerts', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('product_id')->index();
            }
            if (! Schema::hasColumn('low_stock_alerts', 'severity')) {
                $table->string('severity', 40)->default('warning')->after('status')->index();
            }
            if (! Schema::hasColumn('low_stock_alerts', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('severity')->index();
            }
            if (! Schema::hasColumn('low_stock_alerts', 'action_note')) {
                $table->text('action_note')->nullable()->after('assigned_to');
            }
            if (! Schema::hasColumn('low_stock_alerts', 'acknowledged_by')) {
                $table->unsignedBigInteger('acknowledged_by')->nullable()->after('action_note')->index();
            }
            if (! Schema::hasColumn('low_stock_alerts', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('acknowledged_by');
            }
            if (! Schema::hasColumn('low_stock_alerts', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('acknowledged_at')->index();
            }
            if (! Schema::hasColumn('low_stock_alerts', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            }
            if (! Schema::hasColumn('low_stock_alerts', 'metadata')) {
                $table->json('metadata')->nullable()->after('resolved_at');
            }
        });

        if (! Schema::hasTable('stock_alert_actions')) {
            Schema::create('stock_alert_actions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('low_stock_alert_id')->index();
                $table->string('action', 80)->index();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alert_actions');
    }
};
