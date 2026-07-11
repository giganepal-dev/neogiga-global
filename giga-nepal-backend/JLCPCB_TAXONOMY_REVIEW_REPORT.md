# JLCPCB Taxonomy Review Report

Date: 2026-07-11

Scope: live NeoGiga production pilot rows linked to `source=jlcpcb_parts_database`.

## Done

- Added a read-only taxonomy review gate to `/admin/imports/jlcpcb`.
- The gate summarizes distinct imported brands and categories.
- The gate flags brand names that look like raw supplier strings, parenthesized aliases, generic names, or missing brands.
- The gate flags category labels that are too generic for scale import, including `SMD`, `Leaded`, `Needs Review`, `Unknown`, `Uncategorized`, and `Other`.
- No brand, category, or product data was changed.

## Live Findings

- Top imported brand volume is concentrated in `FH(Guangdong Fenghua Advanced Tech)`, `Infineon Technologies`, `Changjiang Electronics Tech (CJ)`, `UNI-ROYAL(Uniroyal Elec)`, `MDD(Microdiode Electronics)`, and `Samsung Electro-Mechanics`.
- Brand labels containing parenthesized supplier aliases should be normalized before large-scale publication.
- Top category volume is concentrated in `MLCC SMD`, `MOSFETs`, `Chip Resistors`, `Zener Diodes`, and `Crystals`.
- Generic category labels detected in pilot data include `SMD`, `Leaded`, and `Needs Review`; these should be remapped before any 10k import.

## Remaining Gate

- Manually inspect sample imported products in admin.
- Decide canonical brand normalization rules for supplier names with aliases.
- Decide category remapping rules for generic categories before scaling beyond 1,000 rows.

## Rollback

This phase is UI/report-only. Rollback is removing the admin summary section and this report; no production catalog rows were modified.
