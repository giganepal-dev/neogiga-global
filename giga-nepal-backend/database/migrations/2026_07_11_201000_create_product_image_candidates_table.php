<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_image_candidates')) {
            return;
        }

        Schema::create('product_image_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('candidate_url');
            $table->text('source_page_url')->nullable();
            $table->string('source_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('mpn')->nullable()->index();
            $table->string('discovered_by')->default('product-images:discover-candidates');
            $table->string('rights_status')->default('pending_review')->index();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('evidence')->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'candidate_url']);
            $table->index(['product_id', 'rights_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_image_candidates');
    }
};
