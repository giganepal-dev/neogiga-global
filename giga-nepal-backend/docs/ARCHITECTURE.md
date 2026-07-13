# NeoGiga Architecture

## Core model

NeoGiga is a Laravel multi-marketplace commerce platform with a single canonical global catalog. A product is identified by `manufacturer + normalized MPN`. Marketplace-specific state is modeled separately so products are not copied per country or storefront.

## Application layers

| Layer | Responsibility |
| --- | --- |
| Public storefront | Locale-prefixed catalog, category, product, AI, seller, RFQ, and BOM user interfaces. |
| Marketplace overlays | Regional price, inventory, currency, visibility, tax, delivery, seller, and checkout-readiness rules. |
| Canonical catalog | Manufacturers, categories, products, specifications, assets, source provenance, and normalized identity. |
| Admin workspace | Catalog review, supplier ingestion, marketplaces, regional controls, product/user management, and operational audit workflows. |
| BOM domain | A shared `BomImportService` parses, normalizes, matches, and persists BOM imports for APIs and the SSR uploader. |
| PCB domain | `pcb.neogiga.com` hosts project, file, Gerber, BOM/CPL, and quote lifecycle workflows. |
| Ingestion/media | Supplier staging/review and guarded local-media import preserve raw/source values, checksums, confidence, timestamps, and license notes. |

## Request routing

- Public routes are normally locale-prefixed, for example `/en/products/{slug}` and `/en/bom-imports`.
- The root BOM route redirects to the localized uploader.
- The admin workspace is isolated under the admin host/path.
- PCB requests are routed to the PCB workspace host and lifecycle controllers.

## Data and safety constraints

- Never duplicate a canonical product merely to represent a regional offer.
- Never overwrite manually curated catalog content from an import.
- Imported datasets and assets retain source name, URL/file/page reference, timestamps, raw values, normalized values, confidence, and license notes.
- Destructive operations require authorization, validation, auditability, and a recoverable deployment/data backup.
- AI recommendations must display source notes, confidence level, last-updated information, and an advisory-only disclaimer.

## Recent architecture changes

The `pcb-usable-portal` branch added regional-commerce readiness boundaries, supplier ingestion/staging, catalog provenance, PCB lifecycle tables, and a public SSR BOM entry point over the pre-existing BOM service. It also formalized guarded catalog-media import behavior. These changes are documented here because they affect deployment sequencing and operational ownership.
