<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_offline_sync_events')) {
            Schema::create('pos_offline_sync_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pos_terminal_id')->nullable()->index();
                $table->unsignedBigInteger('pos_session_id')->nullable()->index();
                $table->string('event_uuid', 120)->nullable()->unique();
                $table->string('event_type', 80)->index();
                $table->string('status', 40)->default('pending')->index();
                $table->unsignedInteger('attempts')->default(0);
                $table->json('payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_offline_sync_events');
    }
};
