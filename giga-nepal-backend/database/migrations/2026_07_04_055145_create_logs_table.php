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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('log_type')->comment('boot, network, api_sync, error, firmware, system');
            $table->string('level')->default('info')->comment('debug, info, warning, error, critical');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('source')->nullable()->comment('Device, Server, API');
            $table->timestamps();
            
            $table->index(['device_id', 'log_type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
