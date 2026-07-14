# ElecForest Import Implementation

Generated: 2026-07-14 (Asia/Kathmandu)

## Safety baseline

- Framework: Laravel 12.63 / PHP 8.5.1
- Local configured database: SQLite with database queue
- Pre-migration backup: `storage/backups/neogiga-before-elecforest-20260714-094817.sqlite`
- Backup SHA-256: `6c0b1814e24d86fa892ab505c4df8e185c1fb159c4b2cada3baf278075f11b86`
- Verified post-import backup: `storage/backups/neogiga-after-elecforest-verified-20260714-134739.sqlite`
- Post-import backup SHA-256: `fe5b90cc33ddd6d6371938a96e183e7397e6215056127433d5acdd4bcf9dc5f2`
- Source SHA-256: `14a04e1001d9d02e787150c33ff3c6970677ed0332b0448475fce6f44b26409c`
- Existing uncommitted work was preserved; no reset, destructive migration or data replacement was performed.

## Components

The integration adds a streaming parser, validator, identity resolver, product/category/specification/content/SEO mappers, media downloader, importer orchestration, queued product/media/derivative/search jobs, ten Artisan commands, an admin monitoring controller and an `Admin > Catalog Imports > ElecForest` page using the existing admin theme.

Identity resolution order is verified manufacturer + MPN, source-specific supplier SKU, exact source URL, verified barcode identifiers, then an approved cross-reference. Similar names never merge. Numeric duplicate SKUs are normalized as strings and treated as ambiguous. NeoGiga SKUs use `NG-EF-{SUPPLIER-SKU}` or a short SHA-256 source hash, with a source hash suffix on collision.

## Media security

Downloads require HTTPS and an exact configured host allowlist. Redirect destinations are revalidated; credentials/custom ports, private/reserved IP addresses, oversized responses, invalid MIME/signatures, malicious SVG and out-of-range dimensions are rejected. Content is streamed with a byte cap, named by SHA-256 and deduplicated. Media remains inactive with pending-rights attribution until approved.

The scraper included repeated shipping, payment and storefront assets. Exact known basenames are retained in raw provenance but classified `ignored_non_product_asset` without network access. Product candidates are separated from WebP/AVIF derivative work so downloads and CPU conversion can be drained independently.

## Commands

- `catalog:import-elecforest`
- `catalog:elecforest-audit`
- `catalog:elecforest-status`
- `catalog:elecforest-resume`
- `catalog:elecforest-retry`
- `catalog:elecforest-map-categories`
- `catalog:elecforest-download-images`
- `catalog:elecforest-generate-seo`
- `catalog:elecforest-validate`
- `catalog:elecforest-publish-qualified`

The main command supports bounded dry-run, synchronous and queued execution plus resume/retry, only-new/update-existing, content/SEO/media controls, regional context and strict draft/publication options.

## Publication policy

All imported records are `draft`, hidden and `noindex,nofollow`. Source prices never produce Offer schema. Publication requires verified taxonomy, manufacturer, complete content and SEO, a rights-approved active local image and no open review task. `--force` is explicit and is never used by the execution workflow.
