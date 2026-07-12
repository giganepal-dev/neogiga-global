<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_ai_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            // AI-generated content
            $table->text('ai_summary')->nullable();
            $table->json('ai_bom_suggestions')->nullable();
            $table->json('ai_compatible_alternatives')->nullable();
            $table->json('ai_cross_sell_recommendations')->nullable();
            $table->json('ai_project_ideas')->nullable();
            
            // Engineering assistant data
            $table->json('ai_pinout_diagrams')->nullable();
            $table->json('ai_wiring_examples')->nullable();
            $table->text('ai_engineering_notes')->nullable();
            
            // Datasheet Q&A
            $table->json('ai_datasheet_qa')->nullable();
            
            // Metadata
            $table->string('ai_model_version')->nullable();
            $table->timestamp('ai_generated_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();

            $table->index('ai_generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ai_features');
    }
};
