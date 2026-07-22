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
        Schema::create('email_imports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->index();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 20)->default('csv'); // csv, xls, xlsx
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('imported_rows')->default(0);
            $table->integer('updated_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->integer('invalid_email_rows')->default(0);
            $table->integer('missing_email_rows')->default(0);
            $table->integer('suppressed_rows')->default(0);
            $table->integer('unsubscribed_rows')->default(0);
            $table->integer('country_assigned_rows')->default(0);
            $table->integer('unassigned_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->string('status', 20)->default('pending')->index(); // pending, processing, completed, failed, cancelled
            $table->json('column_mapping')->nullable();
            $table->json('import_options')->nullable();
            $table->foreignId('group_id')->nullable()->constrained('email_groups')->nullOnDelete();
            $table->string('subscriber_type', 50)->default('newsletter_subscriber');
            $table->string('source', 50)->default('import');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('email_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('email_imports')->cascadeOnDelete()->index();
            $table->integer('row_number')->index();
            $table->json('raw_data')->nullable();
            $table->json('mapped_data')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending, validated, imported, updated, duplicate, invalid, suppressed, failed
            $table->string('error_message')->nullable();
            $table->foreignId('subscriber_id')->nullable()->constrained('email_subscribers')->nullOnDelete()->index();
            $table->timestamps();

            $table->index(['import_id', 'status']);
        });

        Schema::create('email_import_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->json('column_mapping')->nullable();
            $table->json('default_values')->nullable();
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_import_mappings');
        Schema::dropIfExists('email_import_rows');
        Schema::dropIfExists('email_imports');
    }
};
