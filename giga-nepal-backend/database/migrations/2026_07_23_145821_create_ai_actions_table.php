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
        Schema::create('ai_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type', 100);
            $table->string('action_category', 50)->default('commerce');
            $table->text('user_request')->nullable();
            $table->json('model_interpretation')->nullable();
            $table->json('proposed_action')->nullable();
            $table->string('confirmation_required', 20)->default('no');
            $table->string('user_confirmation', 20)->default('pending');
            $table->json('final_action')->nullable();
            $table->json('result')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('status', 50)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('ai_session_id');
            $table->index('action_type');
            $table->index('action_category');
            $table->index('status');
            $table->index('confirmation_required');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_actions');
    }
};
