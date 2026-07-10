<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('category_lms_links')) {
            return;
        }

        Schema::create('category_lms_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->unsignedBigInteger('lms_course_id')->nullable()->index();
            $table->unsignedBigInteger('lms_project_id')->nullable()->index();
            $table->string('title');
            $table->string('relation_type')->default('topic')->index();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_lms_links');
    }
};
