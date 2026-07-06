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
        Schema::create('gps_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('altitude', 10, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable()->comment('km/h');
            $table->decimal('heading', 5, 2)->nullable()->comment('degrees');
            $table->integer('satellites')->nullable();
            $table->string('accuracy')->nullable();
            $table->dateTime('gps_time');
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index(['device_id', 'gps_time']);
            // Note: spatialIndex removed for SQLite compatibility. Enable for MySQL/PostgreSQL in production.
            // $table->spatialIndex('point');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_logs');
    }
};
