<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role'); // owner, admin, catalog_manager, inventory_manager, order_manager, logistics_manager, finance_manager, support_agent, viewer
            $table->json('permissions')->nullable(); // custom permissions override
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['vendor_id', 'user_id']);
            $table->index(['vendor_id', 'is_active']);
            $table->index('role');
        });

        Schema::create('vendor_member_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('email');
            $table->string('role');
            $table->string('token', 100)->unique();
            $table->boolean('is_accepted')->default(false);
            $table->boolean('is_expired')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index(['vendor_id', 'is_accepted']);
            $table->index('token');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_member_invitations');
        Schema::dropIfExists('vendor_team_members');
    }
};
