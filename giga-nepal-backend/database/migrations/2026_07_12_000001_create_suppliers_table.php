<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->nullable()->unique();
                $table->string('tier')->default('tier_1')->index();
                $table->text('description')->nullable();
                $table->string('website_url')->nullable();
                $table->string('api_endpoint')->nullable();
                $table->json('api_credentials')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('country')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_featured')->default(false);
                $table->integer('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('suppliers', 'tier')) {
                $table->string('tier')->nullable()->index()->after('slug');
            }
            if (! Schema::hasColumn('suppliers', 'description')) {
                $table->text('description')->nullable()->after('tier');
            }
            if (! Schema::hasColumn('suppliers', 'website_url')) {
                $table->string('website_url')->nullable()->after('description');
            }
            if (! Schema::hasColumn('suppliers', 'api_endpoint')) {
                $table->string('api_endpoint')->nullable()->after('website_url');
            }
            if (! Schema::hasColumn('suppliers', 'api_credentials')) {
                $table->json('api_credentials')->nullable()->after('api_endpoint');
            }
            if (! Schema::hasColumn('suppliers', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('api_credentials');
            }
            if (! Schema::hasColumn('suppliers', 'country')) {
                $table->string('country')->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('suppliers', 'is_active')) {
                $table->boolean('is_active')->default(true)->index()->after('country');
            }
            if (! Schema::hasColumn('suppliers', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('suppliers', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_featured');
            }
            if (! Schema::hasColumn('suppliers', 'metadata')) {
                $table->json('metadata')->nullable()->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        // Do not drop suppliers here. The ERP procurement migration owns the base table
        // in existing installs; this migration only supplies missing importer columns.
    }
};
