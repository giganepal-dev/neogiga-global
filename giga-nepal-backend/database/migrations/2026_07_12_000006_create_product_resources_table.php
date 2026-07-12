<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            // Resource type: datasheet, manual, cad_model, library, example_code, etc.
            $table->enum('type', [
                'datasheet',
                'manual',
                'cad_3d_model',
                'arduino_library',
                'platformio_library',
                'circuitpython_library',
                'example_code',
                'github_example',
                'documentation_link',
                'video_tutorial',
                'pinout_diagram',
                'wiring_diagram',
                'certification',
                'other'
            ])->default('other');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('external_url')->nullable();
            $table->string('github_repo')->nullable();
            $table->string('language')->default('en');
            $table->string('version')->nullable();
            $table->boolean('is_downloadable')->default(true);
            $table->integer('download_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'type']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_resources');
    }
};
