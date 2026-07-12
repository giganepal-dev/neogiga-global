<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_type'); // e.g., 'adafruit', 'waveshare', 'okystar'
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->integer('created_items')->default(0);
            $table->integer('updated_items')->default(0);
            $table->integer('failed_items')->default(0);
            $table->text('error_message')->nullable();
            $table->json('options')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['job_type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
