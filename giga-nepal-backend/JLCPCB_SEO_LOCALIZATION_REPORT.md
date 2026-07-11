# JLCPCB SEO Localization Report

Date: 2026-07-11

Scope: source-owned `jlcpcb_parts_database` product, category, and brand SEO metadata.

## Template

Professional localized title examples:

- Global: `Buy Resistors Online | Global Stock & RFQ Sourcing | NeoGiga`
- India: `Buy Resistors Online in India | Local Stock & Fast Dispatch | NeoGiga India`
- Nepal: `Buy Resistors in Nepal | Local Stock & Fast Delivery | GigaNepal`

Product example:

- India: `Buy GZ1608D601TF Online in India | Local Stock & Fast Dispatch | NeoGiga India`
- Nepal: `Buy GZ1608D601TF in Nepal | Local Stock & Fast Delivery | GigaNepal`

## Backfill

- Backup before backfill: `/home/neogiga/backups/neogiga_pre_jlcpcb_seo_backfill_20260711T042124Z.dump`
- Brands updated: 274
- Categories updated: 251
- Products updated: 19,946

## Safety

- Imported source-owned records remain `robots=noindex,nofollow` until review/publication.
- Hidden imported products remain excluded from sitemap and public catalog surfaces.
- Sitemap check after backfill: 25 public product URLs.
- Health, sitemap, and product listing endpoints returned 200 after backfill.

## Notes

- The wording uses `Local Stock & Fast Dispatch` / `Fast Delivery` as a marketplace positioning phrase and avoids unconditional same-day claims.
- Same-day dispatch should only be added later when a product has verified local stock and fulfillment SLA data.
