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
        Schema::create('email_group_subscriber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('email_subscribers')->cascadeOnDelete()->index();
            $table->foreignId('group_id')->constrained('email_groups')->cascadeOnDelete()->index();
            $table->string('assignment_source', 50)->default('manual'); // manual, import, auto, rule
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subscriber_id', 'group_id']);
            $table->index(['is_primary', 'assigned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_group_subscriber');
    }
};
