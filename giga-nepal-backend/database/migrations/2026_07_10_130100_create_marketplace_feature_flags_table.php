<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketplace_feature_flags')) {
            return;
        }

        Schema::create('marketplace_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->string('flag_key', 80);
            $table->boolean('is_enabled')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unique(['marketplace_id', 'flag_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_feature_flags');
    }
};
