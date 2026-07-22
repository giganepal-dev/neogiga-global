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
        Schema::create('email_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->index();
            $table->string('provider_type', 30)->index(); // resend, ses, smtp
            $table->string('scope', 20)->default('global'); // global, region, country, group
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('country_code', 2)->nullable()->index();
            $table->foreignId('group_id')->nullable()->constrained('email_groups')->nullOnDelete();
            $table->integer('priority')->default(100)->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_fallback')->default(false)->index();
            $table->integer('daily_quota')->default(50000);
            $table->integer('hourly_quota')->default(5000);
            $table->integer('per_second_rate')->default(14);
            $table->integer('current_daily_count')->default(0);
            $table->integer('current_hourly_count')->default(0);
            $table->timestamp('quota_reset_at')->nullable();
            $table->string('health_status', 20)->default('healthy'); // healthy, degraded, unhealthy
            $table->timestamp('last_health_check_at')->nullable();
            $table->json('credentials_encrypted')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['provider_type', 'scope', 'is_active']);
            $table->index(['priority', 'is_fallback']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_provider_configs');
    }
};
