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
        Schema::create('bom_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bom_import_line_id')->constrained()->cascadeOnDelete();
            $table->integer('risk_score')->default(0);
            $table->string('risk_level', 20)->default('low');
            $table->json('risk_factors')->nullable();
            $table->json('mitigation_suggestions')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('bom_import_id');
            $table->index('bom_import_line_id');
            $table->index('risk_level');
            $table->index('risk_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_risk_scores');
    }
};
