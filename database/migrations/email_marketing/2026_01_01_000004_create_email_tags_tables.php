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
        Schema::create('email_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique()->index();
            $table->string('color', 7)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('email_subscriber_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('email_subscribers')->cascadeOnDelete()->index();
            $table->foreignId('tag_id')->constrained('email_tags')->cascadeOnDelete()->index();
            $table->string('source', 50)->default('manual');
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subscriber_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_subscriber_tags');
        Schema::dropIfExists('email_tags');
    }
};
