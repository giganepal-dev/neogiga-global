<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pcb_quote_configurations')) return;

        Schema::table('pcb_quote_configurations', function (Blueprint $table) {
            $fields = [
                'assembly_service' => ['enum', ['none', 'smt_top', 'smt_bottom', 'smt_both', 'through_hole', 'mixed'], 'none'],
                'smt_pads_per_board' => ['integer', null, null],
                'through_hole_joints_per_board' => ['integer', null, null],
                'stencil_service' => ['boolean', null, 'false'],
                'stencil_type' => ['string', null, null], // framed, frameless, nano
                'conformal_coating' => ['boolean', null, 'false'],
                'bga_assembly' => ['boolean', null, 'false'],
                'component_sourcing' => ['enum', ['customer_supplied', 'neogiga_sourced', 'mixed'], 'customer_supplied'],
                'assembly_testing' => ['string', null, null], // none, visual, aoi, xray, functional
                'assembly_lead_time_days' => ['integer', null, null],
            ];

            foreach ($fields as $name => [$type, $values, $default]) {
                if (Schema::hasColumn('pcb_quote_configurations', $name)) continue;

                if ($type === 'enum') {
                    $table->enum($name, $values)->default($default)->after('electrical_test_type');
                } elseif ($type === 'boolean') {
                    $table->boolean($name)->default($default === 'true')->after('electrical_test_type');
                } elseif ($type === 'integer') {
                    $table->unsignedInteger($name)->nullable()->after('electrical_test_type');
                } else {
                    $table->string($name)->nullable()->after('electrical_test_type');
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pcb_quote_configurations')) return;

        Schema::table('pcb_quote_configurations', function (Blueprint $table) {
            $cols = ['assembly_service','smt_pads_per_board','through_hole_joints_per_board',
                'stencil_service','stencil_type','conformal_coating','bga_assembly',
                'component_sourcing','assembly_testing','assembly_lead_time_days'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('pcb_quote_configurations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
