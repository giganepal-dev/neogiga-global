# NeoGiga JLCPCB/LCSC Parts ETL

Standalone Python ETL for importing the open CDFER JLCPCB/LCSC in-stock SQLite database into PostgreSQL.

The downloader reads the current repository README from `https://github.com/CDFER/jlcpcb-parts-database` and discovers the official GitHub Pages SQLite URL at runtime. It does not scrape protected JLCPCB/LCSC pages.

## Prerequisites

- Python 3.11+
- PostgreSQL with `pgcrypto` available
- Enough local disk for the SQLite database, currently about 1GB before reports/logs
- `DATABASE_URL` set in the environment for imports

## Install

```bash
python -m venv .venv
source .venv/bin/activate
pip install -r tools/jlcpcb_etl/requirements.txt
```

Windows:

```bat
.venv\Scripts\activate
pip install -r tools\jlcpcb_etl\requirements.txt
```

## Test

```bash
pytest tools/jlcpcb_etl/tests -v
```

## Dry Run

```bash
python -m tools.jlcpcb_etl.cli --dry-run --limit 1000
```

Use an existing SQLite download to avoid re-downloading the large file:

```bash
python -m tools.jlcpcb_etl.cli --dry-run --sqlite-file /path/to/jlcpcb-components.sqlite3 --limit 1000
```

## Schema Inspection

```bash
python -m tools.jlcpcb_etl.cli --inspect-only --sqlite-file /path/to/jlcpcb-components.sqlite3
```

The default source-field mapping for the CDFER SQLite schema is stored in
`tools/jlcpcb_etl/mappings/source_fields.yaml`. The inspector uses this mapping
when the configured table/columns are present and falls back to inference for
future source schema changes.

Reports are written to:

- `tools/jlcpcb_etl/output/sqlite_schema_report.json`
- `tools/jlcpcb_etl/output/validation_report.json`
- `tools/jlcpcb_etl/output/validation_report.md`
- `tools/jlcpcb_etl/output/jlcpcb_etl.jsonl`

## Import

`DATABASE_URL` is read only from the environment. The tool refuses writes unless `--yes` is provided.

```bash
export DATABASE_URL='postgresql://user:password@host:5432/database'
python -m tools.jlcpcb_etl.cli --limit 1000 --yes
```

Resume:

```bash
python -m tools.jlcpcb_etl.cli --resume --yes
```

Reset checkpoint:

```bash
python -m tools.jlcpcb_etl.cli --reset-checkpoint --dry-run --limit 100
```

Full import command, only after dry-run validation and explicit approval:

```bash
python -m tools.jlcpcb_etl.cli --yes
```

## Source Attribution

Source repository: `https://github.com/CDFER/jlcpcb-parts-database`

The repository README states the database is generated from the open `yaqwsx/jlcparts` data and published by GitHub Actions to GitHub Pages. The repository license is MIT. Keep upstream license and attribution notes with any redistributed derivative dataset.

## Troubleshooting

- If download times out, use `--sqlite-file` with a manually downloaded database.
- If schema inspection cannot infer required fields, inspect `output/sqlite_schema_report.json` and add source-field mapping support before import.
- If PostgreSQL connection fails, verify `DATABASE_URL`; credentials are never logged.
- If checkpoint resume fails, the source checksum changed. Start a new import or reset the checkpoint after reviewing the new source.
