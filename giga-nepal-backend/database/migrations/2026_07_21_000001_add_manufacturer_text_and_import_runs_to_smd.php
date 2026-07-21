<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smd_marking_matches', function (Blueprint $table) {
            if (! Schema::hasColumn('smd_marking_matches', 'manufacturer_text')) {
                $table->string('manufacturer_text')->nullable()->after('manufacturer_id');
            }
        });

        if (Schema::hasTable('smd_import_runs')) {
            return;
        }

        Schema::create('smd_import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('yooneed');
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->json('stats')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('smd_marking_matches', function (Blueprint $table) {
            $table->dropColumn('manufacturer_text');
        });

        Schema::dropIfExists('smd_import_runs');
    }
};
