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
        Schema::create('firmware_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->foreignId('firmware_id')->constrained('firmwares');
            $table->string('previous_version')->nullable();
            $table->string('target_version');
            $table->string('status')->default('pending')->comment('pending, downloading, installing, success, failed, rolled_back');
            $table->text('error_message')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->boolean('is_rollback')->default(false);
            $table->foreignId('initiated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['device_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firmware_updates');
    }
};
