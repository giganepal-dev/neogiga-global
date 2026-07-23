<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enhance rfq_requests
        Schema::table('rfq_requests', function (Blueprint $table) {
            $table->string('title')->nullable()->after('rfq_number');
            $table->foreignId('bom_import_id')->nullable()->after('b2b_account_id')->constrained('bom_imports')->nullOnDelete();
            $table->string('rfq_type', 50)->default('manual')->after('bom_import_id');
            $table->string('priority', 20)->default('normal')->after('rfq_type');
            $table->timestamp('valid_until')->nullable()->after('priority');
            $table->json('metadata')->nullable()->after('valid_until');
            $table->integer('total_items')->default(0)->after('metadata');
            $table->decimal('estimated_total', 14, 4)->nullable()->after('total_items');
        });

        // Enhance rfq_items
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->string('mpn', 200)->nullable()->after('sku');
            $table->string('manufacturer', 200)->nullable()->after('mpn');
            $table->text('description')->nullable()->after('manufacturer');
            $table->string('currency', 3)->default('USD')->after('target_price');
            $table->string('status', 50)->default('pending')->after('currency');
            $table->json('metadata')->nullable()->after('status');
            $table->foreignId('bom_import_line_id')->nullable()->after('metadata')->constrained('bom_import_lines')->nullOnDelete();
            $table->boolean('accept_alternatives')->default(true)->after('bom_import_line_id');
            $table->string('packaging_requirement', 50)->nullable()->after('accept_alternatives');
            $table->timestamp('required_delivery_date')->nullable()->after('packaging_requirement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfq_items', function (Blueprint $table) {
            $table->dropForeign(['bom_import_line_id']);
            $table->dropColumn([
                'mpn',
                'manufacturer',
                'description',
                'currency',
                'status',
                'metadata',
                'bom_import_line_id',
                'accept_alternatives',
                'packaging_requirement',
                'required_delivery_date',
            ]);
        });

        Schema::table('rfq_requests', function (Blueprint $table) {
            $table->dropForeign(['bom_import_id']);
            $table->dropColumn([
                'title',
                'bom_import_id',
                'rfq_type',
                'priority',
                'valid_until',
                'metadata',
                'total_items',
                'estimated_total',
            ]);
        });
    }
};
