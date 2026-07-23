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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('body');
            $table->string('type')->default('text'); // text, file, image, system
            $table->json('attachments')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->json('reactions')->nullable(); // [{user_id, emoji}]
            $table->foreignId('parent_id')->nullable()->constrained('chat_messages')->onDelete('cascade');
            $table->integer('depth')->default(0);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['is_read', 'created_at']);
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
