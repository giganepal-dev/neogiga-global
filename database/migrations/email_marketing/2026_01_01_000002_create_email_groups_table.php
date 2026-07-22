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
        Schema::create('email_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->index();
            $table->string('description')->nullable();
            $table->string('group_type', 30)->default('custom')->index(); // country, custom, segment, dynamic
            $table->string('country_code', 2)->nullable()->index();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_primary')->default(false);
            $table->string('default_language', 10)->default('en');
            $table->string('default_currency', 3)->default('USD');
            $table->foreignId('sender_identity_id')->nullable();
            $table->string('email_provider', 30)->nullable();
            $table->text('physical_address')->nullable();
            $table->text('unsubscribe_footer')->nullable();
            $table->integer('daily_send_limit')->default(10000);
            $table->integer('hourly_send_limit')->default(1000);
            $table->integer('per_second_rate')->default(10);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['group_type', 'is_active']);
            $table->index(['country_code', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_groups');
    }
};
