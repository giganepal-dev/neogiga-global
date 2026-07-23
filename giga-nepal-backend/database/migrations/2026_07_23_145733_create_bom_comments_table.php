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
        Schema::create('bom_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bom_import_line_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->string('comment_type', 50)->default('general');
            $table->boolean('is_internal')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('bom_import_id');
            $table->index('bom_import_line_id');
            $table->index('user_id');
            $table->index('comment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_comments');
    }
};
