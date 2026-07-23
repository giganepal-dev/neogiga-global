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
        Schema::table('bom_import_lines', function (Blueprint $table) {
            $table->json('suggestions')->nullable()->after('candidates');
            $table->string('normalized_mpn', 200)->nullable()->after('suggestions');
            $table->json('normalization_warnings')->nullable()->after('normalized_mpn');
            $table->json('metadata')->nullable()->after('normalization_warnings');
            $table->string('package_type', 50)->nullable()->after('metadata');
            $table->string('lifecycle_status', 50)->nullable()->after('package_type');
            $table->decimal('unit_price', 12, 4)->nullable()->after('lifecycle_status');
            $table->decimal('extended_price', 14, 4)->nullable()->after('unit_price');
            $table->integer('lead_time_days')->nullable()->after('extended_price');
            $table->string('risk_level', 20)->nullable()->after('lead_time_days');
            $table->boolean('is_alternative')->default(false)->after('risk_level');
            $table->integer('alternative_for_line_no')->nullable()->after('is_alternative');
            $table->boolean('customer_supplied')->default(false)->after('alternative_for_line_no');
            $table->boolean('requires_review')->default(false)->after('customer_supplied');
            $table->text('reviewer_notes')->nullable()->after('requires_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_import_lines', function (Blueprint $table) {
            $table->dropColumn([
                'suggestions',
                'normalized_mpn',
                'normalization_warnings',
                'metadata',
                'package_type',
                'lifecycle_status',
                'unit_price',
                'extended_price',
                'lead_time_days',
                'risk_level',
                'is_alternative',
                'alternative_for_line_no',
                'customer_supplied',
                'requires_review',
                'reviewer_notes',
            ]);
        });
    }
};
