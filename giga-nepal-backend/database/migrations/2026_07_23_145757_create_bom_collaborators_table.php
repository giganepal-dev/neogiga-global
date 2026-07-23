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
        Schema::create('bom_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50)->default('viewer');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('status', 50)->default('pending');
            $table->json('permissions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['bom_import_id', 'user_id']);
            $table->index('user_id');
            $table->index('role');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_collaborators');
    }
};
