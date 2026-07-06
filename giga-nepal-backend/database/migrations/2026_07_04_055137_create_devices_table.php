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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique()->index(); // Unique device identifier
            $table->string('serial_number')->unique();
            $table->string('mac_address')->nullable();
            $table->string('imei')->nullable();
            $table->foreignId('device_type_id')->constrained();
            $table->string('model')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('hardware_version')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('site_id')->nullable()->constrained();
            $table->string('installation_location')->nullable();
            $table->string('assigned_to_type')->nullable()->comment('vehicle, gate, room');
            $table->string('assigned_to_id')->nullable()->comment('ID of vehicle/gate/room');
            $table->string('sim_number')->nullable();
            $table->foreignId('network_provider_id')->nullable()->constrained();
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->foreignId('installed_by')->nullable()->constrained('users');
            $table->dateTime('installed_at')->nullable();
            $table->foreignId('device_status_id')->default(1)->constrained(); // Default to pending
            $table->dateTime('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['customer_id', 'device_status_id']);
            $table->index(['device_status_id', 'last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
