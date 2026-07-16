<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_brands')) {
            Schema::table('product_brands', function (Blueprint $table) {
                $columns = [
                    'logo_original_url' => fn () => $table->text('logo_original_url')->nullable(),
                    'logo_source_domain' => fn () => $table->string('logo_source_domain', 190)->nullable(),
                    'logo_source_type' => fn () => $table->string('logo_source_type', 40)->nullable(),
                    'logo_verified' => fn () => $table->boolean('logo_verified')->default(false),
                    'logo_verified_at' => fn () => $table->timestamp('logo_verified_at')->nullable(),
                    'logo_verified_by' => fn () => $table->foreignId('logo_verified_by')->nullable()->constrained('users')->nullOnDelete(),
                    'logo_alt_text' => fn () => $table->string('logo_alt_text', 500)->nullable(),
                    'logo_width' => fn () => $table->unsignedInteger('logo_width')->nullable(),
                    'logo_height' => fn () => $table->unsignedInteger('logo_height')->nullable(),
                    'logo_mime_type' => fn () => $table->string('logo_mime_type', 100)->nullable(),
                    'logo_sha256' => fn () => $table->char('logo_sha256', 64)->nullable(),
                    'logo_background_type' => fn () => $table->string('logo_background_type', 20)->default('unknown'),
                    'logo_status' => fn () => $table->string('logo_status', 30)->default('pending'),
                    'logo_review_note' => fn () => $table->text('logo_review_note')->nullable(),
                    'logo_confidence' => fn () => $table->decimal('logo_confidence', 4, 3)->nullable(),
                    'logo_metadata' => fn () => $table->json('logo_metadata')->nullable(),
                ];

                foreach ($columns as $column => $definition) {
                    if (! Schema::hasColumn('product_brands', $column)) {
                        $definition();
                    }
                }
            });
        }

        if (! Schema::hasTable('brand_logo_histories')) {
            Schema::create('brand_logo_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brand_id')->constrained('product_brands')->cascadeOnDelete();
                $table->string('action', 40);
                $table->string('storage_disk', 80)->nullable();
                $table->text('logo_path')->nullable();
                $table->text('original_url')->nullable();
                $table->string('source_domain', 190)->nullable();
                $table->string('source_type', 40)->nullable();
                $table->decimal('confidence', 4, 3)->nullable();
                $table->string('status', 30)->default('pending');
                $table->text('review_note')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['brand_id', 'status']);
            });
        }

        if (! Schema::hasTable('brand_aliases')) {
            Schema::create('brand_aliases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brand_id')->constrained('product_brands')->cascadeOnDelete();
                $table->string('alias', 190);
                $table->string('normalized_alias', 190)->unique();
                $table->string('source', 80)->default('admin');
                $table->decimal('confidence', 4, 3)->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Governance data is retained intentionally. Removing it would lose
        // provenance for already-reviewed official assets.
    }
};
