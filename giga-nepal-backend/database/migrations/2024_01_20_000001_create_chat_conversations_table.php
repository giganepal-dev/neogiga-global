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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('subject')->nullable();
            $table->string('type')->default('direct'); // direct, group, support, sales
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_private')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('active'); // active, resolved, closed
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('category')->nullable(); // support, sales, technical, billing, general
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('chat_conversations')->onDelete('cascade');
            $table->integer('message_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->foreignId('last_message_id')->nullable()->constrained('chat_messages')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
