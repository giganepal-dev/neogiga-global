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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('category', 50)->default('general'); // newsletter, product_launch, promotion, etc.
            $table->text('subject')->nullable();
            $table->text('preview_text')->nullable();
            $table->longText('html_content');
            $table->longText('text_content')->nullable();
            $table->json('merge_tags')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('email_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('email_templates')->cascadeOnDelete()->index();
            $table->integer('version_number')->index();
            $table->longText('html_content');
            $table->longText('text_content')->nullable();
            $table->text('changes_summary')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at');

            $table->unique(['template_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_template_versions');
        Schema::dropIfExists('email_templates');
    }
};
