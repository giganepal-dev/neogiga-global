import sqlite3

from tools.jlcpcb_etl.schema_inspector import inspect_sqlite_schema


def test_inspector_detects_cdfer_components_schema(tmp_path):
    sqlite_path = tmp_path / "components.sqlite3"
    conn = sqlite3.connect(sqlite_path)
    conn.execute(
        """
        CREATE TABLE components (
          lcsc INTEGER PRIMARY KEY,
          category_id INTEGER,
          mfr TEXT,
          package TEXT,
          manufacturer_id INTEGER,
          description TEXT,
          datasheet TEXT,
          price TEXT,
          stock INTEGER,
          basic INTEGER,
          preferred INTEGER
        )
        """
    )
    conn.execute("CREATE TABLE categories (id INTEGER PRIMARY KEY, category TEXT, subcategory TEXT)")
    conn.execute("CREATE TABLE manufacturers (id INTEGER PRIMARY KEY, name TEXT)")
    conn.execute("CREATE VIRTUAL TABLE components_fts USING fts5(lcsc, mfr, package, description, datasheet)")
    conn.commit()
    conn.close()

    report = inspect_sqlite_schema(sqlite_path)
    mapping = report["detected_mapping"]
    assert mapping["parts_table"] == "components"
    assert mapping["source_id"] == "lcsc"
    assert mapping["mpn"] == "mfr"
    assert mapping["manufacturer"] == "manufacturer_id"
    assert mapping["category"] == "category_id"
    assert mapping["package"] == "package"
    assert mapping["datasheet_url"] == "datasheet"
    assert mapping["stock"] == "stock"
    assert mapping["price_breaks"] == "price"
