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
        Schema::create('sensor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('sensor_type')->comment('temperature, humidity, battery, fuel, door, motion, etc.');
            $table->string('sensor_id')->nullable();
            $table->decimal('value', 12, 4);
            $table->string('unit')->nullable();
            $table->string('status')->nullable()->comment('normal, warning, critical');
            $table->json('raw_data')->nullable();
            $table->dateTime('reading_time');
            $table->timestamps();
            
            $table->index(['device_id', 'sensor_type', 'reading_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_logs');
    }
};
