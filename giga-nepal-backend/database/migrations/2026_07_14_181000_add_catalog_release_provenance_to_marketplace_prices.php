<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_product_prices')) {
            return;
        }

        Schema::table('marketplace_product_prices', function (Blueprint $table): void {
            $columns = [
                'supplier_product_offer_id' => fn () => $table->unsignedBigInteger('supplier_product_offer_id')->nullable()->index(),
                'source_name' => fn () => $table->string('source_name')->nullable(),
                'source_url' => fn () => $table->text('source_url')->nullable(),
                'source_file' => fn () => $table->text('source_file')->nullable(),
                'source_page_url' => fn () => $table->text('source_page_url')->nullable(),
                'downloaded_at' => fn () => $table->timestamp('downloaded_at')->nullable(),
                'imported_at' => fn () => $table->timestamp('imported_at')->nullable(),
                'data_year' => fn () => $table->string('data_year')->nullable(),
                'license_note' => fn () => $table->text('license_note')->nullable(),
                'confidence_level' => fn () => $table->string('confidence_level', 120)->nullable(),
                'original_raw_value' => fn () => $table->text('original_raw_value')->nullable(),
                'normalized_value' => fn () => $table->text('normalized_value')->nullable(),
                'pricing_rule' => fn () => $table->string('pricing_rule', 120)->nullable(),
                'source_review_status' => fn () => $table->string('source_review_status', 80)->nullable(),
            ];

            foreach ($columns as $name => $definition) {
                if (! Schema::hasColumn('marketplace_product_prices', $name)) {
                    $definition();
                }
            }
        });
    }

    public function down(): void
    {
        // Upgrade-only provenance: never discard source/audit information automatically.
    }
};
