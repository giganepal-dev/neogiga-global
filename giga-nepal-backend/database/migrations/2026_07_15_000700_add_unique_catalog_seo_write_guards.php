<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PRODUCT_INDEX = 'product_seo_meta_product_scope_unique';

    private const VERSION_INDEX = 'catalog_seo_versions_scope_version_unique';

    public function up(): void
    {
        $this->assertRequiredSchema();
        $needsProductIndex = ! Schema::hasIndex('product_seo_meta', self::PRODUCT_INDEX);
        $needsVersionIndex = ! Schema::hasIndex('catalog_seo_versions', self::VERSION_INDEX);

        if ($needsProductIndex) {
            $this->assertNoProductSeoDuplicates();
        }
        if ($needsVersionIndex) {
            $this->assertNoVersionDuplicates();
        }

        if ($needsProductIndex) {
            $this->createProductSeoUniqueIndex();
        }
        if ($needsVersionIndex) {
            $this->createVersionUniqueIndex();
        }
    }

    private function assertRequiredSchema(): void
    {
        $required = [
            'product_seo_meta' => ['product_id'],
            'catalog_seo_versions' => ['entity_type', 'entity_id', 'marketplace_id', 'locale', 'version'],
        ];
        foreach ($required as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Cannot add SEO uniqueness guards because {$table} is missing.");
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException("Cannot add SEO uniqueness guards because {$table}.{$column} is missing.");
                }
            }
        }
    }

    private function assertNoProductSeoDuplicates(): void
    {
        $duplicateExists = DB::table('product_seo_meta')
            ->select('product_id')
            ->groupBy('product_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();
        if ($duplicateExists) {
            throw new RuntimeException('Refusing to add the product SEO uniqueness guard: duplicate product_id groups require a reviewed, backed-up remediation first.');
        }
    }

    private function assertNoVersionDuplicates(): void
    {
        $duplicateExists = DB::table('catalog_seo_versions')
            ->select(['entity_type', 'entity_id', 'marketplace_id', 'locale', 'version'])
            ->groupBy(['entity_type', 'entity_id', 'marketplace_id', 'locale', 'version'])
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();
        if ($duplicateExists) {
            throw new RuntimeException('Refusing to add the SEO-version uniqueness guard: duplicate scope/version groups require a reviewed, backed-up remediation first.');
        }
    }

    private function createProductSeoUniqueIndex(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => DB::statement('CREATE UNIQUE INDEX '.self::PRODUCT_INDEX.' ON product_seo_meta (product_id) NULLS NOT DISTINCT'),
            'sqlite' => DB::statement('CREATE UNIQUE INDEX '.self::PRODUCT_INDEX.' ON product_seo_meta (COALESCE(product_id, -1))'),
            default => throw new RuntimeException('The SEO uniqueness migration supports PostgreSQL and SQLite only; no weaker nullable uniqueness fallback was applied.'),
        };
    }

    private function createVersionUniqueIndex(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => DB::statement('CREATE UNIQUE INDEX '.self::VERSION_INDEX.' ON catalog_seo_versions (entity_type, entity_id, marketplace_id, locale, version) NULLS NOT DISTINCT'),
            'sqlite' => DB::statement('CREATE UNIQUE INDEX '.self::VERSION_INDEX.' ON catalog_seo_versions (entity_type, entity_id, COALESCE(marketplace_id, -1), locale, version)'),
            default => throw new RuntimeException('The SEO-version uniqueness migration supports PostgreSQL and SQLite only; no weaker nullable uniqueness fallback was applied.'),
        };
    }

    public function down(): void
    {
        // Upgrade-only safety migration: retain write guards and never expose duplicate-write races again.
    }
};
