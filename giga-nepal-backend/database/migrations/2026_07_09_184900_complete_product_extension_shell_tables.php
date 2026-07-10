<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_documents')) {
            Schema::table('product_documents', function (Blueprint $table) {
                if (! Schema::hasColumn('product_documents', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable()->index();
                }
                if (! Schema::hasColumn('product_documents', 'title')) {
                    $table->string('title', 180)->nullable();
                }
                if (! Schema::hasColumn('product_documents', 'document_type')) {
                    $table->string('document_type', 80)->nullable()->index();
                }
                if (! Schema::hasColumn('product_documents', 'source_url')) {
                    $table->string('source_url', 500)->nullable();
                }
                if (! Schema::hasColumn('product_documents', 'file_path')) {
                    $table->string('file_path', 500)->nullable();
                }
                if (! Schema::hasColumn('product_documents', 'mime_type')) {
                    $table->string('mime_type', 120)->nullable();
                }
                if (! Schema::hasColumn('product_documents', 'file_size')) {
                    $table->unsignedBigInteger('file_size')->nullable();
                }
                if (! Schema::hasColumn('product_documents', 'status')) {
                    $table->string('status', 40)->default('pending')->index();
                }
                if (! Schema::hasColumn('product_documents', 'uploaded_by')) {
                    $table->unsignedBigInteger('uploaded_by')->nullable()->index();
                }
                if (! Schema::hasColumn('product_documents', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable();
                }
                if (! Schema::hasColumn('product_documents', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable();
                }
                if (! Schema::hasColumn('product_documents', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(100);
                }
                if (! Schema::hasColumn('product_documents', 'is_public')) {
                    $table->boolean('is_public')->default(true)->index();
                }
                if (! Schema::hasColumn('product_documents', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
            });
        }

        if (Schema::hasTable('product_related_items')) {
            Schema::table('product_related_items', function (Blueprint $table) {
                if (! Schema::hasColumn('product_related_items', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable()->index();
                }
                if (! Schema::hasColumn('product_related_items', 'related_product_id')) {
                    $table->unsignedBigInteger('related_product_id')->nullable()->index();
                }
                if (! Schema::hasColumn('product_related_items', 'type')) {
                    $table->string('type', 60)->default('related')->index();
                }
                if (! Schema::hasColumn('product_related_items', 'reason')) {
                    $table->string('reason', 500)->nullable();
                }
                if (! Schema::hasColumn('product_related_items', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(100)->index();
                }
                if (! Schema::hasColumn('product_related_items', 'is_active')) {
                    $table->boolean('is_active')->default(true)->index();
                }
                if (! Schema::hasColumn('product_related_items', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
                if (! Schema::hasColumn('product_related_items', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
            });
        }

        if (Schema::hasTable('product_compatibility')) {
            Schema::table('product_compatibility', function (Blueprint $table) {
                if (! Schema::hasColumn('product_compatibility', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable()->index();
                }
                if (! Schema::hasColumn('product_compatibility', 'compatible_product_id')) {
                    $table->unsignedBigInteger('compatible_product_id')->nullable()->index();
                }
                if (! Schema::hasColumn('product_compatibility', 'compatibility_type')) {
                    $table->string('compatibility_type', 80)->default('compatible')->index();
                }
                if (! Schema::hasColumn('product_compatibility', 'notes')) {
                    $table->string('notes', 500)->nullable();
                }
                if (! Schema::hasColumn('product_compatibility', 'requirements')) {
                    $table->json('requirements')->nullable();
                }
                if (! Schema::hasColumn('product_compatibility', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(100)->index();
                }
                if (! Schema::hasColumn('product_compatibility', 'is_active')) {
                    $table->boolean('is_active')->default(true)->index();
                }
                if (! Schema::hasColumn('product_compatibility', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        $this->dropIfPresent('product_documents', [
            'product_id', 'title', 'document_type', 'source_url', 'file_path',
            'mime_type', 'file_size', 'status', 'uploaded_by', 'approved_by',
            'approved_at', 'sort_order', 'is_public', 'metadata',
        ]);

        $this->dropIfPresent('product_related_items', [
            'product_id', 'related_product_id', 'type', 'reason', 'sort_order',
            'is_active', 'created_by', 'metadata',
        ]);

        $this->dropIfPresent('product_compatibility', [
            'product_id', 'compatible_product_id', 'compatibility_type', 'notes',
            'requirements', 'sort_order', 'is_active', 'metadata',
        ]);
    }

    private function dropIfPresent(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $existing = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn($tableName, $column)
        ));

        if ($existing === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
