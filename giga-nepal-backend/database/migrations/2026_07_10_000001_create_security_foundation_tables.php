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
        Schema::create('device_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_fingerprint', 255)->index();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('device_type', 50)->default('desktop'); // desktop, mobile, tablet
            $table->json('location_data')->nullable(); // country, city, lat, lng
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['device_fingerprint', 'is_active']);
        });

        Schema::create('login_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->enum('login_status', ['success', 'failed'])->default('success');
            $table->string('failure_reason')->nullable(); // invalid_password, account_locked, 2fa_failed, etc.
            $table->json('location_data')->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['login_status', 'created_at']);
        });

        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('alert_type'); // suspicious_login, brute_force, unusual_location, etc.
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index(['severity', 'is_read']);
        });

        // Add 2FA and account status columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(false)->after('password');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            
            $table->enum('account_status', ['active', 'suspended', 'deleted'])->default('active')->after('email_verified_at');
            $table->timestamp('suspended_at')->nullable()->after('account_status');
            $table->text('suspension_reason')->nullable()->after('suspended_at');
            $table->softDeletes()->after('suspension_reason');
            
            $table->index('account_status');
            $table->index('two_factor_enabled');
        });

        // Add failed login tracking to users
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('remember_token');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
        Schema::dropIfExists('login_history');
        Schema::dropIfExists('device_sessions');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'account_status',
                'suspended_at',
                'suspension_reason',
                'failed_login_attempts',
                'locked_until',
            ]);
        });
    }
};
