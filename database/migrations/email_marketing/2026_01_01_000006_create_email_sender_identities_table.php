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
        Schema::create('email_sender_identities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->index();
            $table->string('sender_email')->index();
            $table->string('sender_name')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->string('provider', 30)->default('smtp'); // resend, ses, smtp
            $table->string('verification_status', 20)->default('pending'); // pending, verified, failed
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('country_code', 2)->nullable()->index();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['provider', 'is_active']);
            $table->index(['country_code', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_sender_identities');
    }
};
