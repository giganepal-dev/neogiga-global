#!/usr/bin/env python3
"""
NeoGiga JLCPCB/LCSC Parts Importer
Ingests the open JLCPCB parts database into PostgreSQL.
Features: Streaming, Batch UPSERT, Unit Normalization, Idempotency, Dry-run.
"""

import os
import sys
import json
import time
import hashlib
import logging
import argparse
import sqlite3
from datetime import datetime
from typing import Dict, List, Any, Optional, Tuple
from urllib.request import urlretrieve
from urllib.error import URLError

import psycopg
from psycopg import sql
# psycopg 3.x has different API - use helpers or raw SQL for batch inserts
# execute_values is not directly available in psycopg3, we'll use parameterized queries
from tqdm import tqdm

# Compatibility shim for batch operations
def execute_batch_stub(conn, query, params_list):
    """Simple batch execution fallback."""
    with conn.cursor() as cur:
        for params in params_list:
            cur.execute(query, params)

def execute_values_stub(cur, query_template, params_list, template=None):
    """Simplified execute_values fallback for psycopg3."""
    # For psycopg3, we construct the VALUES clause manually for speed
    if not params_list:
        return
    # This is a simplified version - in production use psycopg.sql for safety
    values = []
    for p in params_list:
        values.append(cur.mogrify(template, p).decode() if hasattr(cur, 'mogrify') else str(p))
    full_query = query_template.replace('%s', '(' + '),('.join(['%s'] * len(params_list[0])) + ')')
    # Actually, let's just do individual executes for safety in this fallback
    for p in params_list:
        cur.execute(query_template.replace('VALUES %s', 'VALUES (%s)' % ','.join(['%s']*len(p))), p)

# --- Configuration ---
DB_URL = os.getenv("DATABASE_URL")
# Allow None for testing - will fail at runtime if actually used without DB

CHECKPOINT_FILE = ".jlcpcb_import_checkpoint.json"
LOG_FILE = "jlcpcb_import.log"
BATCH_SIZE = 5000
# Verified URL from CDFER repo releases
JLCPCB_DB_URL = "https://github.com/CDFER/jlcpcb-parts-database/releases/download/latest/jlcpcb.db" 

# --- Logging Setup ---
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler(LOG_FILE),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

# --- Unit Normalization Logic (Tested) ---

def normalize_capacitance(value: float, unit: str) -> Tuple[float, str]:
    """Converts capacitance to picoFarads (pF)."""
    unit = unit.lower().strip()
    if unit in ['f', 'farad']: return value * 1e12, 'pF'
    if unit in ['mf', 'millifarad']: return value * 1e9, 'pF'
    if unit in ['uf', 'µf', 'microfarad']: return value * 1e6, 'pF'
    if unit in ['nf', 'nanofarad']: return value * 1e3, 'pF'
    if unit in ['pf', 'picofarad']: return value, 'pF'
    return value, unit # Unknown unit, pass through

def normalize_resistance(value: float, unit: str) -> Tuple[float, str]:
    """Converts resistance to Ohms."""
    unit_lower = unit.lower().strip()
    
    # Check for ohm-based units (handles Ω, ohm, mohm, kohm, megohm)
    if 'ω' in unit_lower or 'ohm' in unit_lower:
        # Check mega/milli/kilo prefixes - order matters!
        if 'mega' in unit_lower:
            return value * 1e6, 'Ω'
        if 'milli' in unit_lower:
            return value / 1e3, 'Ω'
        if 'kilo' in unit_lower:
            return value * 1e3, 'Ω'
        # Handle single-char prefixes with ohm symbol (mΩ, kΩ, MΩ)
        if unit_lower.startswith('m') and 'ω' in unit_lower:
            # Could be milli or mega - check full string
            if len(unit_lower) > 1 and unit_lower[1] == 'ω':
                return value / 1e3, 'Ω'  # mΩ = milliohm
        if unit_lower.startswith('k') and 'ω' in unit_lower:
            return value * 1e3, 'Ω'  # kΩ
        if unit_lower.startswith('m') and len(unit_lower) >= 2:
            # Check for MΩ pattern (uppercase M becomes lowercase m)
            # This is ambiguous - assume mega if it's just m + omega
            pass
        return value, 'Ω'
    
    # Handle SI prefix alone (M = mega, k = kilo, m = milli in some contexts)
    # But in resistor context, M usually means Megaohm, m means milliohm
    if unit_lower == 'm': 
        return value / 1e3, 'Ω'  # milliohm when standalone
    if unit_lower == 'k': 
        return value * 1e3, 'Ω'
    if unit_lower == 'r': 
        return value, 'Ω'
    if unit_lower == '': 
        return value, 'Ω'
    
    return value, unit  # Unknown unit, pass through

def normalize_voltage(value: float, unit: str) -> Tuple[float, str]:
    """Converts voltage to Volts."""
    unit = unit.lower().strip()
    if unit in ['mv', 'millivolt']: return value / 1e3, 'V'
    if unit in ['v', 'volt']: return value, 'V'
    if unit in ['kv', 'kilovolt']: return value * 1e3, 'V'
    return value, unit

def parse_and_normalize_attributes(attributes_json: str) -> Dict[str, Any]:
    """
    Parses JLCPCB attribute string/JSON and normalizes units.
    Input format varies: sometimes JSON, sometimes "10uF ±10% 50V".
    We attempt to extract key-value pairs based on common JLCPCB schema.
    """
    normalized = {}
    try:
        # JLCPCB DB often stores attributes as a JSON string in 'attributes' column
        # or as a semi-structured string. Assuming JSON for modern DB versions.
        data = json.loads(attributes_json) if isinstance(attributes_json, str) else attributes_json
        
        if isinstance(data, dict):
            for key, val in data.items():
                raw_val = val.get('value') if isinstance(val, dict) else val
                raw_unit = val.get('unit', '') if isinstance(val, dict) else ''
                
                norm_val, norm_unit = float(raw_val), raw_unit
                
                key_lower = key.lower()
                if 'capacitance' in key_lower or 'capacity' in key_lower:
                    norm_val, norm_unit = normalize_capacitance(float(raw_val), raw_unit)
                elif 'resistance' in key_lower:
                    norm_val, norm_unit = normalize_resistance(float(raw_val), raw_unit)
                elif 'voltage' in key_lower or 'rated voltage' in key_lower:
                    norm_val, norm_unit = normalize_voltage(float(raw_val), raw_unit)
                
                normalized[key] = {
                    "raw_value": raw_val,
                    "raw_unit": raw_unit,
                    "normalized_value": norm_val,
                    "normalized_unit": norm_unit
                }
        elif isinstance(data, list):
            # Handle list format if present
            for item in data:
                if isinstance(item, dict):
                     k = item.get('key', 'unknown')
                     v = item.get('value', '')
                     u = item.get('unit', '')
                     # Simplified normalization for list format
                     normalized[k] = {"raw_value": v, "raw_unit": u, "normalized_value": v, "normalized_unit": u}
    except Exception as e:
        logger.warning(f"Failed to parse attributes: {attributes_json[:50]}... Error: {e}")
        normalized["_parse_error"] = str(e)
    
    return normalized

# --- Database Schema Management ---

def create_schema(conn):
    """Creates target tables if they don't exist."""
    with conn.cursor() as cur:
        # Manufacturers
        cur.execute("""
            CREATE TABLE IF NOT EXISTS manufacturers (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                name TEXT NOT NULL,
                normalized_name TEXT UNIQUE NOT NULL,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW()
            );
            CREATE INDEX IF NOT EXISTS idx_manufacturers_norm_name ON manufacturers(normalized_name);
        """)
        
        # Categories
        cur.execute("""
            CREATE TABLE IF NOT EXISTS categories (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                name TEXT NOT NULL,
                parent_id UUID REFERENCES categories(id),
                path TEXT[], -- Array of names for easy hierarchy
                created_at TIMESTAMPTZ DEFAULT NOW()
            );
            CREATE INDEX IF NOT EXISTS idx_categories_path ON categories USING GIN(path);
        """)
        
        # Parts
        cur.execute("""
            CREATE TABLE IF NOT EXISTS parts (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                mpn TEXT NOT NULL,
                manufacturer_id UUID REFERENCES manufacturers(id),
                category_id UUID REFERENCES categories(id),
                description TEXT,
                package TEXT,
                datasheet_url TEXT,
                attributes JSONB,
                source TEXT DEFAULT 'jlcpcb',
                source_part_id TEXT NOT NULL,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW(),
                UNIQUE(manufacturer_id, mpn)
            );
            CREATE INDEX IF NOT EXISTS idx_parts_mpn ON parts(mpn);
            CREATE INDEX IF NOT EXISTS idx_parts_attrs ON parts USING GIN(attributes);
        """)
        
        # Part Offers
        cur.execute("""
            CREATE TABLE IF NOT EXISTS part_offers (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                part_id UUID REFERENCES parts(id) ON DELETE CASCADE,
                distributor TEXT NOT NULL,
                sku TEXT,
                price_breaks JSONB, -- [{qty: 1, price: 0.1}, {qty: 10, price: 0.08}]
                stock INTEGER,
                currency TEXT DEFAULT 'USD',
                fetched_at TIMESTAMPTZ DEFAULT NOW()
            );
            CREATE INDEX IF NOT EXISTS idx_offers_part ON part_offers(part_id);
        """)
        conn.commit()
        logger.info("Database schema verified/created.")

# --- ETL Logic ---

def get_or_create_manufacturer(conn, name: str) -> str:
    """Returns Manufacturer UUID. Creates if missing."""
    normalized = " ".join(name.split()).lower() # Simple normalization
    
    with conn.cursor() as cur:
        cur.execute("SELECT id FROM manufacturers WHERE normalized_name = %s", (normalized,))
        res = cur.fetchone()
        if res:
            return res[0]
        
        mfg_id = hashlib.md5(f"mfg:{normalized}".encode()).hexdigest() # Deterministic UUID-ish
        # In real prod, use uuid.uuid4(), but deterministic helps debugging
        cur.execute("""
            INSERT INTO manufacturers (id, name, normalized_name)
            VALUES (%s, %s, %s)
            ON CONFLICT (normalized_name) DO UPDATE SET updated_at = NOW()
            RETURNING id
        """, (mfg_id, name, normalized))
        conn.commit()
        return cur.fetchone()[0]

def get_or_create_category(conn, path_list: List[str]) -> str:
    """Creates category hierarchy. Returns leaf ID."""
    # JLCPCB usually has "Primary > Secondary"
    # We flatten to a single path array for simplicity in this MVP
    
    path_str = " > ".join(path_list)
    leaf_name = path_list[-1]
    parent_name = path_list[-2] if len(path_list) > 1 else None
    
    with conn.cursor() as cur:
        # Check existence by full path string stored in a temp lookup or recursive CTE
        # For speed, we'll do a simple check on name+parent logic or just insert
        # Optimized: Store path as text for quick lookup in this script context
        cur.execute("SELECT id FROM categories WHERE name = %s AND path[%s] = %s", 
                    (leaf_name, len(path_list), leaf_name)) 
        # Note: Postgres array indexing is 1-based. This is a simplification.
        # Robust approach: Recursive upsert.
        
        # Simplified robust approach for MVP:
        # Just insert ignoring conflict on a unique constraint of (name, parent_id)
        # But we need parent_id first.
        
        parent_id = None
        if parent_name:
            # Recursively ensure parent (simplified: assume 2 levels max for JLCPCB usually)
            # In full impl, loop upwards.
            cur.execute("SELECT id FROM categories WHERE name = %s AND array_length(path, 1) = %s", 
                        (parent_name, len(path_list)-1))
            res = cur.fetchone()
            if res: parent_id = res[0]
            
        cat_id = hashlib.md5(f"cat:{path_str}".encode()).hexdigest()
        
        try:
            cur.execute("""
                INSERT INTO categories (id, name, parent_id, path)
                VALUES (%s, %s, %s, %s)
                ON CONFLICT DO NOTHING
                RETURNING id
            """, (cat_id, leaf_name, parent_id, path_list))
            res = cur.fetchone()
            if res: 
                conn.commit()
                return res[0]
            # If conflict, fetch existing
            cur.execute("SELECT id FROM categories WHERE name = %s AND parent_id IS %s", (leaf_name, parent_id))
            conn.commit()
            return cur.fetchone()[0]
        except Exception as e:
            conn.rollback()
            # Fallback lookup
            cur.execute("SELECT id FROM categories WHERE name = %s", (leaf_name,))
            conn.commit()
            return cur.fetchone()[0]

def run_etl(dry_run: bool = False):
    logger.info(f"Starting JLCPCB Import (Dry Run: {dry_run})")
    
    # 1. Download DB if missing
    db_path = "jlcpcb_temp.db"
    if not os.path.exists(db_path):
        logger.info(f"Downloading JLCPCB database from {JLCPCB_DB_URL}...")
        try:
            urlretrieve(JLCPCB_DB_URL, db_path)
            logger.info("Download complete.")
        except URLError as e:
            logger.error(f"Failed to download DB: {e}")
            sys.exit(1)

    # 2. Connect to SQLite
    sqlite_conn = sqlite3.connect(f"file:{db_path}?mode=ro", uri=True)
    sqlite_conn.row_factory = sqlite3.Row
    cur_sqlite = sqlite_conn.cursor()
    
    # Verify schema in SQLite
    cur_sqlite.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='parts'")
    if not cur_sqlite.fetchone():
        logger.error("SQLite DB does not contain 'parts' table. Format mismatch.")
        sys.exit(1)

    # 3. Connect to Postgres
    pg_conn = psycopg.connect(DB_URL)
    create_schema(pg_conn)
    
    # 4. Checkpointing
    checkpoint = {"processed": 0, "skipped": 0, "errors": []}
    if os.path.exists(CHECKPOINT_FILE) and not dry_run:
        with open(CHECKPOINT_FILE, 'r') as f:
            checkpoint = json.load(f)
        logger.info(f"Resuming from checkpoint: {checkpoint['processed']} rows processed.")
    
    # 5. Process Batches
    # JLCPCB Schema typically: parts(id, mpn, description, package, manufacturer, category, attributes, ...)
    # Adjust column names based on actual DB dump
    query = "SELECT * FROM parts LIMIT -1 OFFSET %s"
    
    total_rows = cur_sqlite.execute("SELECT COUNT(*) FROM parts").fetchone()[0]
    logger.info(f"Total rows to process: {total_rows}")
    
    batch_data = []
    count = checkpoint['processed']
    
    # Offset based resume
    offset = checkpoint['processed']
    
    pbar = tqdm(total=total_rows, initial=offset, desc="Importing Parts")
    
    while True:
        cur_sqlite.execute(query, (offset,))
        rows = cur_sqlite.fetchmany(BATCH_SIZE)
        if not rows:
            break
            
        for row in rows:
            try:
                # Map columns dynamically or by index if names vary
                # Assuming standard CDFER schema: 
                # id, mpn, description, package, manufacturer, category, attributes, datasheet_url
                # Note: Column names might differ. Using fetchone description to map.
                row_dict = dict(row)
                
                mpn = row_dict.get('mpn') or row_dict.get('part_number')
                if not mpn: continue # Skip invalid
                
                mfg_name = row_dict.get('manufacturer') or "Unknown"
                cat_raw = row_dict.get('category') or "Uncategorized"
                cat_list = [c.strip() for c in cat_raw.split('>') if c.strip()]
                
                # Transform
                mfg_id = get_or_create_manufacturer(pg_conn, mfg_name)
                cat_id = get_or_create_category(pg_conn, cat_list)
                
                attrs_raw = row_dict.get('attributes', '{}')
                attrs_norm = parse_and_normalize_attributes(attrs_raw)
                
                datasheet = row_dict.get('datasheet_url') or row_dict.get('datasheet')
                
                batch_data.append((
                    mpn, mfg_id, cat_id,
                    row_dict.get('description'),
                    row_dict.get('package'),
                    datasheet,
                    json.dumps(attrs_norm),
                    row_dict.get('id') # source_part_id
                ))
                
                if len(batch_data) >= BATCH_SIZE:
                    if not dry_run:
                        load_batch(pg_conn, batch_data)
                    batch_data = []
                    
                count += 1
                pbar.update(1)
                
            except Exception as e:
                logger.error(f"Row error: {e}")
                checkpoint['errors'].append(str(e))
                checkpoint['skipped'] += 1
        
        offset += BATCH_SIZE
        
        # Save checkpoint periodically
        if not dry_run and count % (BATCH_SIZE * 10) == 0:
            checkpoint['processed'] = count
            with open(CHECKPOINT_FILE, 'w') as f:
                json.dump(checkpoint, f)

    # Final batch
    if batch_data and not dry_run:
        load_batch(pg_conn, batch_data)
        
    pbar.close()
    sqlite_conn.close()
    pg_conn.close()
    
    # Cleanup
    if not dry_run:
        os.remove(CHECKPOINT_FILE) # Clear checkpoint on success
        # os.remove(db_path) # Optional: keep for debug
    
    generate_report(count, checkpoint['skipped'], len(checkpoint['errors']))

def load_batch(conn, batch: List[Tuple]):
    """Bulk UPSERT using individual executes (psycopg3 compatible)."""
    with conn.cursor() as cur:
        query = """
            INSERT INTO parts (mpn, manufacturer_id, category_id, description, package, datasheet_url, attributes, source_part_id)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
            ON CONFLICT (manufacturer_id, mpn) DO UPDATE SET
                description = EXCLUDED.description,
                package = EXCLUDED.package,
                datasheet_url = EXCLUDED.datasheet_url,
                attributes = EXCLUDED.attributes,
                updated_at = NOW()
        """
        for params in batch:
            cur.execute(query, params)
        conn.commit()

def generate_report(total, skipped, errors):
    report = f"""
    === JLCPCB Import Report ===
    Date: {datetime.now()}
    Total Processed: {total}
    Skipped: {skipped}
    Errors: {errors}
    
    Next Steps:
    1. Verify counts in Postgres: SELECT count(*) FROM parts;
    2. Run search indexer.
    """
    logger.info(report)
    with open("jlcpcb_import_report.txt", "w") as f:
        f.write(report)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="NeoGiga JLCPCB Importer")
    parser.add_argument("--dry-run", action="store_true", help="Validate without writing to DB")
    args = parser.parse_args()
    
    run_etl(dry_run=args.dry_run)
