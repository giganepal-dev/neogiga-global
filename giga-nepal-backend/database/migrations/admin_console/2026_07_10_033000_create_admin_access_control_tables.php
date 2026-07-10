<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->string('group')->default('admin')->index();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['role_id', 'permission_id']);
            });
        }

        if (! Schema::hasTable('user_country_access')) {
            Schema::create('user_country_access', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'country_id']);
            });
        }

        if (! Schema::hasTable('user_seller_access')) {
            Schema::create('user_seller_access', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('access_level')->default('manager')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'vendor_id']);
            });
        }

        if (! Schema::hasTable('admin_invitations')) {
            Schema::create('admin_invitations', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->index();
                $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
                $table->string('token')->unique();
                $table->string('status')->default('pending')->index();
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('accepted_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_invitations');
        Schema::dropIfExists('user_seller_access');
        Schema::dropIfExists('user_country_access');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
