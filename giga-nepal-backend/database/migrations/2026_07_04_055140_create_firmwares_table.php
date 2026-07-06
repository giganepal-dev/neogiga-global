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
        Schema::create('firmwares', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->foreignId('device_type_id')->constrained();
            $table->string('model_compatibility')->nullable()->comment('Comma separated models or JSON');
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size')->nullable()->comment('File size in bytes');
            $table->string('checksum')->nullable()->comment('SHA256 checksum');
            $table->text('release_notes')->nullable();
            $table->boolean('is_ota_enabled')->default(true);
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['device_type_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firmwares');
    }
};
