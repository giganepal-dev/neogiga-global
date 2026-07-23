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
        Schema::create('ai_workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->string('workflow_name', 100);
            $table->string('version', 50);
            $table->string('status', 50)->default('draft');
            $table->json('config')->nullable();
            $table->json('prompts')->nullable();
            $table->json('parameters')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deprecated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['workflow_name', 'version']);
            $table->index('workflow_name');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_workflow_versions');
    }
};
