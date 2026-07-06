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
        Schema::create('device_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('wifi_ssid')->nullable();
            $table->string('wifi_password')->nullable();
            $table->string('api_url')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('school_id')->nullable();
            $table->string('vehicle_id')->nullable();
            $table->string('route_number')->nullable();
            $table->integer('upload_interval')->default(60)->comment('Seconds between uploads');
            $table->boolean('buzzer_enabled')->default(true);
            $table->string('display_message')->nullable();
            $table->boolean('gps_enabled')->default(true);
            $table->string('gsm_apn')->nullable();
            $table->json('sync_settings')->nullable();
            $table->json('custom_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_configs');
    }
};
