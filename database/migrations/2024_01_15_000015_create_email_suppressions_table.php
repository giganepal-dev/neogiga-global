<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('email_normalized')->index();
            $table->enum('type', ['bounce', 'complaint', 'unsubscribe', 'manual'])->default('unsubscribe');
            $table->enum('status', ['bounced', 'complained', 'unsubscribed', 'suppressed'])->index();
            $table->string('reason')->nullable();
            $table->string('provider_event_id')->nullable()->unique();
            $table->foreignId('email_subscriber_id')->nullable()->constrained('email_subscribers')->onDelete('set null');
            $table->foreignId('email_campaign_id')->nullable()->constrained('email_campaigns')->onDelete('set null');
            $table->string('source')->default('system'); // system, webhook, admin, user
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['email_normalized', 'status']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
    }
};
