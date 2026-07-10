"""Non-destructive PostgreSQL schema creation."""

from __future__ import annotations


SCHEMA_SQL = """
CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE IF NOT EXISTS manufacturers (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL,
  normalized_name TEXT NOT NULL UNIQUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS categories (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL,
  normalized_name TEXT NOT NULL,
  parent_id UUID NULL REFERENCES categories(id),
  path TEXT NOT NULL,
  source_category_id TEXT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE(parent_id, normalized_name)
);

CREATE TABLE IF NOT EXISTS parts (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  mpn TEXT NOT NULL,
  normalized_mpn TEXT NOT NULL,
  manufacturer_id UUID NOT NULL REFERENCES manufacturers(id),
  category_id UUID NULL REFERENCES categories(id),
  description TEXT NULL,
  package TEXT NULL,
  datasheet_url TEXT NULL,
  attributes JSONB NOT NULL DEFAULT '{}',
  source TEXT NOT NULL DEFAULT 'jlcpcb_parts_database',
  source_part_id TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE(manufacturer_id, mpn),
  UNIQUE(source, source_part_id)
);

CREATE TABLE IF NOT EXISTS part_offers (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  part_id UUID NOT NULL REFERENCES parts(id) ON DELETE CASCADE,
  distributor TEXT NOT NULL,
  sku TEXT NOT NULL,
  price_breaks JSONB NOT NULL DEFAULT '[]',
  stock BIGINT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  fetched_at TIMESTAMPTZ NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE(distributor, sku)
);

CREATE TABLE IF NOT EXISTS etl_import_batches (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  source TEXT NOT NULL,
  source_url TEXT NULL,
  source_checksum TEXT NULL,
  source_schema JSONB NULL,
  status TEXT NOT NULL,
  started_at TIMESTAMPTZ NOT NULL,
  finished_at TIMESTAMPTZ NULL,
  rows_read BIGINT NOT NULL DEFAULT 0,
  rows_loaded BIGINT NOT NULL DEFAULT 0,
  rows_skipped BIGINT NOT NULL DEFAULT 0,
  errors JSONB NOT NULL DEFAULT '[]'
);

CREATE TABLE IF NOT EXISTS etl_import_errors (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  batch_id UUID NOT NULL REFERENCES etl_import_batches(id),
  source_part_id TEXT NULL,
  reason TEXT NOT NULL,
  source_record JSONB NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_parts_normalized_mpn ON parts(normalized_mpn);
CREATE INDEX IF NOT EXISTS idx_parts_manufacturer_id ON parts(manufacturer_id);
CREATE INDEX IF NOT EXISTS idx_parts_category_id ON parts(category_id);
CREATE INDEX IF NOT EXISTS idx_parts_source_part_id ON parts(source_part_id);
CREATE INDEX IF NOT EXISTS idx_part_offers_part_id ON part_offers(part_id);
CREATE INDEX IF NOT EXISTS idx_part_offers_stock ON part_offers(stock);
CREATE INDEX IF NOT EXISTS idx_categories_path ON categories(path);
CREATE INDEX IF NOT EXISTS idx_parts_attributes_gin ON parts USING GIN(attributes);
"""


def create_schema(conn) -> None:
    with conn.cursor() as cur:
        cur.execute(SCHEMA_SQL)
    conn.commit()
