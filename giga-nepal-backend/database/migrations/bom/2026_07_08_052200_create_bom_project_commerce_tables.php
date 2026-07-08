<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bom_project_categories')) {
            Schema::create('bom_project_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bom_projects')) {
            Schema::create('bom_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bom_project_category_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('country_id')->nullable()->index();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('difficulty')->default('beginner')->index();
                $table->string('estimated_build_time')->nullable();
                $table->text('description')->nullable();
                $table->text('safety_notes')->nullable();
                $table->json('required_tools')->nullable();
                $table->boolean('is_public')->default(false)->index();
                $table->string('status')->default('draft')->index();
                $table->json('seo_meta')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bom_project_items')) {
            Schema::create('bom_project_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bom_project_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->string('name');
                $table->string('required_or_optional')->default('required')->index();
                $table->decimal('quantity', 15, 3)->default(1);
                $table->text('reason')->nullable();
                $table->boolean('substitute_allowed')->default(true);
                $table->integer('priority')->default(100)->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        foreach ([
            'bom_project_tools',
            'bom_project_lms_links',
            'bom_project_code_samples',
            'bom_project_alternatives',
            'bom_project_price_snapshots',
            'bom_project_build_guides',
            'bom_user_builds',
            'bom_user_build_items',
            'bom_cart_conversions',
        ] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table) use ($tableName) {
                    $table->id();
                    $table->foreignId('bom_project_id')->nullable()->constrained('bom_projects')->nullOnDelete();
                    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                    $table->string('name')->nullable();
                    $table->string('status')->default('active')->index();
                    $table->json('payload')->nullable();
                    $table->timestamps();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'bom_cart_conversions',
            'bom_user_build_items',
            'bom_user_builds',
            'bom_project_build_guides',
            'bom_project_price_snapshots',
            'bom_project_alternatives',
            'bom_project_code_samples',
            'bom_project_lms_links',
            'bom_project_tools',
            'bom_project_items',
            'bom_projects',
            'bom_project_categories',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
