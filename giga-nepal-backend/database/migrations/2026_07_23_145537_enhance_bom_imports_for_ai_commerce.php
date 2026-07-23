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
        Schema::table('bom_imports', function (Blueprint $table) {
            $table->string('original_filename')->nullable()->after('name');
            $table->text('raw_content')->nullable()->after('source_format');
            $table->json('metadata')->nullable()->after('meta');
            $table->timestamp('processed_at')->nullable()->after('metadata');
            $table->integer('processing_duration_ms')->nullable()->after('processed_at');
            $table->string('error_message')->nullable()->after('processing_duration_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_imports', function (Blueprint $table) {
            $table->dropColumn([
                'original_filename',
                'raw_content',
                'metadata',
                'processed_at',
                'processing_duration_ms',
                'error_message',
            ]);
        });
    }
};
