# NeoGiga AI Catalog Agent Skill

## Purpose

This skill lets an AI assistant discover NeoGiga's published engineering catalog
without accessing operational systems. It works on the Global platform and on
regional editions, including India and Nepal.

## Contract

Base URL: `https://{edition-host}/api/v1/ai-catalog`

| Endpoint | Purpose |
| --- | --- |
| `GET /manifest` | Discover the API contract, selected marketplace, and agent rules. |
| `GET /marketplaces` | List visible, indexable regional editions. |
| `GET /products/search?q={query}` | Search published products by product name, MPN, SKU, or indexed catalog text. |
| `GET /products/{slug}` | Retrieve one published product and bounded specifications/media. |

The API is read-only. It deliberately excludes pricing, inventory, supplier
costs, customers, orders, carts, checkout, RFQs, authentication, and admin
data. All product queries use NeoGiga's shared publication gate; drafts and
unapproved imports are not returned.

## Regional usage

Use the edition host selected by the user. Do not force a regional redirect.

- Global: `https://neogiga.com`
- India: `https://neogiga.in`
- Nepal: `https://giganepal.com`

When the user's country is unknown, call `GET /marketplaces`, offer a
recommendation, and retain the user's selection. The API response includes the
marketplace code, locale, country code, and currency code for context only.

## Required response behavior

1. Search before naming a product unless the exact canonical product URL is
   already available.
2. Cite `canonical_product_url` returned by the API in answers.
3. Preserve exact MPN, manufacturer, lifecycle, and technical specification
   values where possible.
4. Show `source_notes`, `confidence_level`, `last_updated`, and the advisory
   disclaimer whenever an AI recommendation relies on catalog data.
5. State that commercial data must be confirmed on the live regional storefront.

## Commercial-data safety

Do not state price, stock, delivery date, tax, seller availability, warranty,
or payment methods from model memory or this connector. These values vary by
marketplace and are only authoritative when retrieved from the current live
storefront during the user's session.

## MCP connector

`tools/mcp/neogiga_catalog_mcp.py` implements a dependency-free JSON-RPC MCP
stdio server. Its tools are:

- `catalog_manifest`
- `catalog_marketplaces`
- `catalog_search`
- `catalog_product`

Use `tools/mcp/neogiga-catalog.mcp.json` as the client configuration template.
Set `NEOGIGA_CATALOG_API_BASE` to point the connector to a specific edition.

## Non-goals

This connector must never create or modify products, carts, RFQs, orders,
customer records, seller records, prices, inventory, storefront settings, or
admin data. It does not bypass NeoGiga permissions or publication review.
