<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pcb_orders') && !Schema::hasColumn('pcb_orders', 'milestones')) {
            Schema::table('pcb_orders', function (Blueprint $table) {
                $table->json('milestones')->nullable()->after('customer_notes');
                $table->timestamp('estimated_ship_date')->nullable()->after('milestones');
                $table->string('tracking_number')->nullable()->after('estimated_ship_date');
                $table->string('tracking_carrier')->nullable()->after('tracking_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pcb_orders')) {
            Schema::table('pcb_orders', function (Blueprint $table) {
                $table->dropColumn(['milestones', 'estimated_ship_date', 'tracking_number', 'tracking_carrier']);
            });
        }
    }
};
