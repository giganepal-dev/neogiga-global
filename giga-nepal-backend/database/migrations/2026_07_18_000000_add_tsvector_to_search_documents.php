<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tsvector is PostgreSQL-only; SQLite (tests) skips — CatalogSearchService
        // falls back to ILIKE when the column is absent.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Add generated tsvector column for full-text search.
        // Uses 'english' config — ponytail: single language, add per-locale configs if needed.
        DB::statement("ALTER TABLE product_search_documents ADD COLUMN IF NOT EXISTS search_vector tsvector GENERATED ALWAYS AS (to_tsvector('english', coalesce(searchable_text, ''))) STORED");
        DB::statement('CREATE INDEX IF NOT EXISTS product_search_documents_search_vector_idx ON product_search_documents USING gin (search_vector)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS product_search_documents_search_vector_idx');
        DB::statement('ALTER TABLE product_search_documents DROP COLUMN IF EXISTS search_vector');
    }
};
