<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pcb_files uses project_id, but Eloquent withCount('files')
        // convention looks for pcb_project_id. Add it as a generated alias.
        if (! Schema::hasColumn('pcb_files', 'pcb_project_id')) {
            Schema::table('pcb_files', function (Blueprint $table) {
                $table->uuid('pcb_project_id')->nullable()->after('project_id');
            });

            // Copy existing values
            DB::statement('UPDATE pcb_files SET pcb_project_id = project_id WHERE pcb_project_id IS NULL');
        }

        if (! Schema::hasColumn('pcb_quote_configurations', 'pcb_project_id')) {
            Schema::table('pcb_quote_configurations', function (Blueprint $table) {
                $table->uuid('pcb_project_id')->nullable()->after('project_id');
            });

            DB::statement('UPDATE pcb_quote_configurations SET pcb_project_id = project_id WHERE pcb_project_id IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('pcb_files', fn (Blueprint $table) => $table->dropColumn('pcb_project_id'));
        Schema::table('pcb_quote_configurations', fn (Blueprint $table) => $table->dropColumn('pcb_project_id'));
    }
};
