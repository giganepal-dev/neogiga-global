<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lms_lesson_files')) {
            return;
        }

        Schema::create('lms_lesson_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lms_lesson_id')->constrained('lms_lessons')->cascadeOnDelete();
            $table->unsignedBigInteger('admin_media_asset_id')->nullable()->index();
            $table->string('title');
            $table->string('file_url')->nullable();
            $table->string('file_type')->default('resource')->index();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('is_downloadable')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_lesson_files');
    }
};
