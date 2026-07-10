<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redirect rule definitions. Storing a rule here does NOT make it fire —
 * execution additionally requires marketplaces.redirect_enabled = true for
 * the owning marketplace (defaults false everywhere). This lets rules be
 * authored and reviewed before anyone flips the live switch.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketplace_redirect_rules')) {
            return;
        }

        Schema::create('marketplace_redirect_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->string('from_pattern');
            $table->string('to_pattern');
            $table->string('redirect_type', 20)->default('temporary'); // temporary|permanent
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->index(['marketplace_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_redirect_rules');
    }
};
