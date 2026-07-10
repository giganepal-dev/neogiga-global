# NEXT_GLOBAL_COMMERCE_BACKLOG (2026-07-10)

Ordered by dependency, continuing from GLOBAL_COMMERCE_IMPLEMENTATION_PLAN.md's Stage numbering.

## Immediate (unblocks everything else)
1. **Clarify prod-access scope with the user** and deploy this cycle's Stage 1/2 work to
   production (5 migrations, 3 services, 1 controller+view, 2 extended files, 1 route, the seeder)
   — see GLOBAL_COMMERCE_VALIDATION_REPORT.md for the exact deploy checklist.
2. **Panel-branch merge decision** (from the prior cycle, still open) — see PANEL_BRANCH_REVIEW.md.
   Its inventory-reservation cluster (`cart_reservations`) is directly relevant to Stage 3's
   `warehouse_stock_reservations` requirement — merge that cluster before building warehouse
   routing from scratch.
3. **JLCPCB import decision** — still on hold per the user's explicit instruction; unrelated to
   Global Commerce but occupies the same `products`/`product_categories` tables this work touches.

## Stage 2 continuation (central pricing engine)
4. ✅ DONE (committed 717c6e8, NOT deployed) — `CentralPricingService` formula service reading
   `exchange_rates`/`marketplace_product_prices`, writing `price_calculation_logs`, never
   overwriting live prices (see CENTRAL_PRICING_ENGINE_GUIDE.md).
5. PARTIAL — `ExchangeRateProviderInterface` + `ManualExchangeRateProvider` + `ExchangeRateService`
   + `pricing:refresh-exchange-rates` command all built (bb4b4b3 / 717c6e8). Still needed: one
   REAL (HTTP) provider + a scheduled refresh job + cart/order rate snapshotting (a hard
   requirement). The live provider is an operational/network decision — left for explicit setup.
6. ✅ DONE (committed d81fef0, NOT deployed) — `DutyService` + `ImportDutyRule` wired into the
   pricing formula, using the pre-existing `import_duty_rules` table (empty on prod, so inert).
   Still needed: a proper HS-code data model + duty seed data (operational). See
   TAX_AND_DUTY_ENGINE_GUIDE.md.

### Undeployed local commits awaiting review before prod push
- 717c6e8 central pricing engine v1
- d81fef0 import-duty wiring
Deploy target files: `app/Services/Pricing/CentralPricingService.php`,
`app/Services/Pricing/DutyService.php`, `app/Models/Marketplace/ImportDutyRule.php`,
`app/Console/Commands/RefreshExchangeRates.php` (the exchange-rate services + models + config are
already live on prod via bb4b4b3). All tables already exist on prod. Low risk: nothing is wired to
run automatically.

## Stage 3 (sellers, warehouses, freight, payments)
7. `seller_marketplace_approvals` + related tables (see REGIONAL_SELLER_GUIDE.md) — needs at least
   one real seller wanting to operate in a preview market to validate against.
8. Multi-warehouse network + `NearestFulfillmentService` extending `bestWarehouseRoute()` (see
   GLOBAL_WAREHOUSE_ROUTING_GUIDE.md) — blocked on having more than 1 real warehouse.
9. Freight rate cards + carrier integration (see FREIGHT_AND_ETA_GUIDE.md) — blocked on #8.
10. `marketplace_payment_methods` scoping once a specific marketplace is ready to go live with a
    specific gateway (see LOCAL_PAYMENT_ROUTING_GUIDE.md).

## Stage 4 (localization, full prefixed routing, SEO)
11. Full `/in/products`, `/in/categories/{slug}` etc. routing — extend the public route group with
    a prefix-aware layer once real localized content exists; do not duplicate routes 25×.
12. `product_localizations`/`category_localizations`/`manufacturer_localizations` tables (see
    GLOBAL_PIM_AUDIT.md).
13. hreflang generalized to all 25 marketplaces, country sitemap shards, Offer schema (see
    REGIONAL_SEO_GUIDE.md).
14. Enable specific `marketplace_redirect_rules` only after individual review — the master
    `redirect_enabled` switch stays off until then (see COUNTRY_REDIRECT_GUIDE.md).

## Stage 5 (admin control center, analytics, scale)
15. Admin UI for marketplace/currency/exchange-rate/pricing management (currently only
    `/admin/marketplaces` list view exists).
16. Sales/margin/exchange-rate-impact/duty-impact analytics per country.
17. Search index (OpenSearch/Meilisearch) with marketplace-prioritized ranking, per GLOBAL_COMMERCE_AUDIT.md's search gap finding.
18. Dedicated `manufacturers` + `manufacturer_aliases` tables, separate from marketing
    `product_brands`, with a normalized-MPN index for 10M+-row scale search (see GLOBAL_PIM_AUDIT.md).

## Housekeeping discovered during this cycle
19. GitHub push backlog continues to grow (unrelated to this project, standing item across many
    prior cycles) — still blocked on credentials on this machine.
