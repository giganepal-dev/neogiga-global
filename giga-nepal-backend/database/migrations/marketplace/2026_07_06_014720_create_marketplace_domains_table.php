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
        Schema::create('marketplace_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('ssl_certificate_path')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->json('redirect_rules')->nullable();
            $table->timestamps();
            
            $table->index('domain');
            $table->index('marketplace_id');
            $table->index(['marketplace_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_domains');
    }
};
