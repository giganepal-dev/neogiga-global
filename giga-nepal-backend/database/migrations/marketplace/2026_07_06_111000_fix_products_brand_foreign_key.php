<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite' || !$this->usesLegacyBrandsForeignKey()) {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement(<<<'SQL'
CREATE TABLE products_phase1_fix (
    id integer primary key autoincrement not null,
    vendor_id integer null,
    brand_id integer null,
    category_id integer null,
    name varchar not null,
    slug varchar not null,
    sku varchar not null,
    mpn varchar null,
    short_description text null,
    description text null,
    type varchar check ("type" in ('simple', 'variable', 'bundle', 'kit', 'service', 'digital')) not null default 'simple',
    status varchar check ("status" in ('draft', 'pending', 'approved', 'rejected', 'archived')) not null default 'draft',
    base_price numeric not null default '0',
    cost_price numeric null,
    sale_price numeric null,
    sale_start_date date null,
    sale_end_date date null,
    tax_class_id integer null,
    is_taxable tinyint(1) not null default '1',
    track_inventory tinyint(1) not null default '1',
    stock_quantity integer not null default '0',
    low_stock_threshold integer not null default '5',
    is_featured tinyint(1) not null default '0',
    is_virtual tinyint(1) not null default '0',
    is_downloadable tinyint(1) not null default '0',
    download_url varchar null,
    download_limit integer null,
    download_expiry_days integer null,
    weight numeric null,
    length numeric null,
    width numeric null,
    height numeric null,
    weight_unit varchar not null default 'kg',
    dimension_unit varchar not null default 'cm',
    marketplace_visibility text null,
    attributes text null,
    metadata text null,
    seo_meta text null,
    created_by integer null,
    approved_by integer null,
    approved_at datetime null,
    rejection_reason text null,
    created_at datetime null,
    updated_at datetime null,
    foreign key(vendor_id) references vendors(id) on delete set null,
    foreign key(brand_id) references product_brands(id) on delete set null,
    foreign key(category_id) references product_categories(id) on delete set null,
    foreign key(created_by) references users(id) on delete set null,
    foreign key(approved_by) references users(id) on delete set null
)
SQL);

        DB::statement(<<<'SQL'
INSERT INTO products_phase1_fix (
    id, vendor_id, brand_id, category_id, name, slug, sku, mpn, short_description,
    description, type, status, base_price, cost_price, sale_price, sale_start_date,
    sale_end_date, tax_class_id, is_taxable, track_inventory, stock_quantity,
    low_stock_threshold, is_featured, is_virtual, is_downloadable, download_url,
    download_limit, download_expiry_days, weight, length, width, height,
    weight_unit, dimension_unit, marketplace_visibility, attributes, metadata,
    seo_meta, created_by, approved_by, approved_at, rejection_reason, created_at, updated_at
)
SELECT
    id, vendor_id, brand_id, category_id, name, slug, sku, mpn, short_description,
    description, type, status, base_price, cost_price, sale_price, sale_start_date,
    sale_end_date, tax_class_id, is_taxable, track_inventory, stock_quantity,
    low_stock_threshold, is_featured, is_virtual, is_downloadable, download_url,
    download_limit, download_expiry_days, weight, length, width, height,
    weight_unit, dimension_unit, marketplace_visibility, attributes, metadata,
    seo_meta, created_by, approved_by, approved_at, rejection_reason, created_at, updated_at
FROM products
SQL);

        Schema::drop('products');
        DB::statement('ALTER TABLE products_phase1_fix RENAME TO products');

        DB::statement('CREATE UNIQUE INDEX products_slug_unique ON products (slug)');
        DB::statement('CREATE UNIQUE INDEX products_sku_unique ON products (sku)');
        DB::statement('CREATE INDEX products_vendor_id_index ON products (vendor_id)');
        DB::statement('CREATE INDEX products_category_id_index ON products (category_id)');
        DB::statement('CREATE INDEX products_brand_id_index ON products (brand_id)');
        DB::statement('CREATE INDEX products_status_index ON products (status)');
        DB::statement('CREATE INDEX products_is_featured_status_index ON products (is_featured, status)');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        //
    }

    private function usesLegacyBrandsForeignKey(): bool
    {
        $foreignKeys = DB::select("PRAGMA foreign_key_list('products')");

        foreach ($foreignKeys as $foreignKey) {
            if (($foreignKey->from ?? null) === 'brand_id' && ($foreignKey->table ?? null) === 'brands') {
                return true;
            }
        }

        return false;
    }
};
