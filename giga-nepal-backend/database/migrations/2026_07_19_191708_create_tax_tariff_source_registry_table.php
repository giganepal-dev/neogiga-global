<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tax_tariff_source_registry')) {
            return;
        }

        Schema::create('tax_tariff_source_registry', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 3);
            $table->unsignedBigInteger('marketplace_id')->nullable();
            $table->string('source_name');
            $table->string('source_type', 40); // official_tax_authority, official_customs, wto, wits, manual_verified
            $table->string('official_domain')->nullable();
            $table->text('data_endpoint')->nullable();
            $table->string('file_format', 20)->nullable(); // json, xml, csv, xlsx, pdf
            $table->string('authentication_type', 20)->nullable(); // none, api_key, oauth2, basic
            $table->string('update_frequency', 20)->nullable(); // daily, weekly, monthly, quarterly, annually, manual
            $table->text('license_or_usage_notes')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('active')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_successful_import_at')->nullable();
            $table->string('last_source_version')->nullable();
            $table->date('last_source_date')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['country_code', 'source_type']);
            $table->index(['active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_tariff_source_registry');
    }
};
