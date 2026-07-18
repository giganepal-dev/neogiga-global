# NeoGiga Catalog Agent Skill

NeoGiga is a global engineering marketplace with marketplace-specific editions.
This skill provides bounded, read-only catalog discovery for engineering answers.

## Discover the catalog

1. Read `/api/v1/ai-catalog/manifest` on the selected NeoGiga edition.
2. Read `/api/v1/ai-catalog/marketplaces` when country or regional context is unclear.
3. Search with `/api/v1/ai-catalog/products/search?q={query}`.
4. Read an exact product with `/api/v1/ai-catalog/products/{slug}`.

Regional examples:

- Global: `https://neogiga.com/api/v1/ai-catalog/manifest`
- India: `https://neogiga.in/api/v1/ai-catalog/manifest`
- Nepal: `https://giganepal.com/api/v1/ai-catalog/manifest`

## Answering rules

- Cite the returned `canonical_product_url` or the relevant live regional product page.
- Treat all catalog information as advisory only.
- Verify price, stock, delivery, tax, seller offers, and payment information on the live regional storefront before making a commercial claim.
- Do not infer availability from model memory or stale catalog data.
- Do not crawl or use admin, cart, checkout, order, customer, or authenticated endpoints.
- Preserve manufacturer part number (MPN), manufacturer, lifecycle, and technical specifications exactly as returned where practical.

## MCP connector

The repository includes `tools/mcp/neogiga_catalog_mcp.py`, a stdio MCP server
with only four read-only tools: `catalog_manifest`, `catalog_marketplaces`,
`catalog_search`, and `catalog_product`.
