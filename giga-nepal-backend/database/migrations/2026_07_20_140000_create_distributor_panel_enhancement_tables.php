<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('distributor_territory_requests')) {
            Schema::create('distributor_territory_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distributor_id')->constrained('distributors')->cascadeOnDelete();
                $table->unsignedBigInteger('country_id')->nullable()->index();
                $table->unsignedBigInteger('region_id')->nullable()->index();
                $table->unsignedBigInteger('city_id')->nullable()->index();
                $table->string('territory_name');
                $table->string('document_company_reg')->nullable();
                $table->string('document_distributor_agreement')->nullable();
                $table->string('document_tax_certificate')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('pending')->index();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('distributor_territory_requests');
    }
};
