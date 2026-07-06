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
        Schema::create('rfid_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('rfid_tag')->index();
            $table->string('card_type')->nullable();
            $table->string('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->dateTime('scan_time');
            $table->string('location')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index(['device_id', 'scan_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_logs');
    }
};
