import sqlite3

import pytest

from tools.jlcpcb_etl.schema_inspector import SourceMapping
from tools.jlcpcb_etl.sqlite_reader import stream_source_rows


MAPPING = SourceMapping(
    parts_table="components",
    source_id="id",
    mpn="mpn",
    manufacturer="manufacturer",
    category="category",
    description=None,
    package=None,
    datasheet_url=None,
    sku=None,
    stock=None,
    price_breaks=None,
)


def test_stream_source_rows_uses_keyset_cursor(tmp_path):
    path = tmp_path / "parts.sqlite3"
    conn = sqlite3.connect(path)
    conn.execute("CREATE TABLE components (id TEXT PRIMARY KEY, mpn TEXT, manufacturer TEXT, category TEXT)")
    conn.executemany(
        "INSERT INTO components VALUES (?, ?, 'Maker', 'Category')",
        [("C00001", "A"), ("C00002", "B"), ("C00003", "C")],
    )
    conn.commit()
    conn.close()

    rows = list(stream_source_rows(path, MAPPING, after_source_id="C00001", limit=2))

    assert [row["id"] for row in rows] == ["C00002", "C00003"]


def test_keyset_and_offset_cannot_be_combined(tmp_path):
    path = tmp_path / "parts.sqlite3"

    with pytest.raises(ValueError, match="mutually exclusive"):
        list(stream_source_rows(path, MAPPING, offset=1, after_source_id="C00001"))
