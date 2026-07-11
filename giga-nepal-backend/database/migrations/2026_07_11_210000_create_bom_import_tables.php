<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Release A — BOM procurement import.
 *
 * Distinct from the curated `bom_projects` build templates: these tables hold a
 * customer's own uploaded parts list (CSV / pasted text / XLSX), each line matched
 * against the catalog by manufacturer part number, then optionally turned into an RFQ.
 *
 * Placed in the top-level migrations directory (always auto-loaded); the `bom/`
 * subdirectory is NOT registered in AppServiceProvider and would not run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bom_imports')) {
            Schema::create('bom_imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('source_format', 20)->default('paste'); // paste|csv|xlsx
                $table->string('status', 20)->default('parsed')->index(); // parsed|matched|converted
                $table->string('currency', 3)->default('USD');
                $table->unsignedInteger('total_lines')->default(0);
                $table->unsignedInteger('matched_lines')->default(0);
                $table->unsignedInteger('unmatched_lines')->default(0);
                $table->unsignedBigInteger('rfq_request_id')->nullable()->index(); // set when converted
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('bom_import_lines')) {
            Schema::create('bom_import_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bom_import_id')->constrained('bom_imports')->cascadeOnDelete();
                $table->unsignedInteger('line_no')->default(0);
                $table->string('raw_reference')->nullable();  // reference designators, e.g. "R1,R2,R3"
                $table->string('mpn')->nullable()->index();
                $table->string('manufacturer')->nullable();
                $table->string('description')->nullable();
                $table->decimal('quantity', 15, 3)->default(1);
                $table->unsignedBigInteger('matched_product_id')->nullable()->index();
                $table->string('match_status', 20)->default('none')->index(); // exact|multiple|manual|none
                $table->unsignedSmallInteger('match_confidence')->default(0);  // 0-100
                $table->json('candidates')->nullable();       // [{product_id,name,sku,mpn}] when ambiguous
                $table->string('notes')->nullable();
                $table->timestamps();
                $table->index(['bom_import_id', 'line_no']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_import_lines');
        Schema::dropIfExists('bom_imports');
    }
};
