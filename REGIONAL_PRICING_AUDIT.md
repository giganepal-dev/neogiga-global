# REGIONAL_PRICING_AUDIT (2026-07-10)

Scope: pricing, exchange rates, tax/duty, freight, and payment-routing infrastructure. Local-repo
audit (see GLOBAL_COMMERCE_AUDIT.md for the prod-read constraint note).

## Regional pricing
| Requested table | State | Evidence |
|---|---|---|
| `regional_prices` | ✅ Exists as `marketplace_product_prices` | product_id, product_variant_id, marketplace_id, base_price, sale_price, cost_price, currency_code, is_tax_inclusive, tax_rate, sale_start_date/sale_end_date, is_active. **This already covers most of the spec's "regional_prices" requirement** — per-marketplace price with its own currency and tax-inclusive flag. |
| `regional_price_history` | ❌ Missing | no audit trail of price changes over time. |
| `regional_price_overrides` / `regional_price_approvals` | ⚠️ Partial | `is_active`/date-range fields on `marketplace_product_prices` function as a manual override mechanism (admin can toggle via `AdminCommerce::toggleMarketplaceProductPrice`), but there's no distinct "approval workflow" (pending → approved) layer. |
| `global_product_costs` / `supplier_costs` | ❌ Missing | `products.base_price` exists but there's no separate USD landed-cost or supplier-cost table — the formula-driven pricing engine (base cost → landed cost → local price) described in Phase 5 has no schema to build on yet. |
| `price_calculation_logs` | ❌ Missing | no audit trail for how a displayed price was derived. |

## Exchange rates
| Item | State | Evidence |
|---|---|---|
| `exchange_rates` (history/audit table) | ❌ Missing | confirmed via `Schema::hasTable` check in the JLCPCB investigation earlier today: **no `exchange_rates` table exists**. |
| Exchange rate storage | ⚠️ Minimal | `currencies.exchange_rate` + `currencies.exchange_rate_updated_at` — a single current-value column, no history, no source attribution, no per-country spread/buffer. |
| `ExchangeRateProviderInterface` / scheduler / fallback provider | ❌ Missing | nothing found. |
| Order/cart rate snapshotting | ❌ Missing | no evidence any order captures the exchange rate used at time of purchase. |

## Tax / import duty
| Requested table | State | Evidence |
|---|---|---|
| `tax_rules` | ✅ Exists, well-designed | marketplace_id, country_id, region_id, tax_name, tax_type, tax_rate, fixed_amount, is_compound, is_inclusive, applies_to, category_ids (json), product_ids (json), effective_from/effective_until, is_active. **This already supports most of Phase 7's versioning/effective-date/scoping requirements** — a real, usable tax engine schema, not a stub. |
| `tax_jurisdictions` | ❌ Missing as a dedicated table | `country_id`/`region_id` scoping on `tax_rules` covers jurisdiction targeting without a separate lookup table — acceptable simplification. |
| `hs_codes` / `hs_code_country_rules` / `import_duty_rules` / `customs_fee_rules` | ❌ Missing | no import-duty/HS-code infrastructure at all. |
| `tax_exemptions` / `business_tax_profiles` | ❌ Missing | no B2B tax-ID/exemption handling found. |
| `RegionalCommerceService` | ✅ Exists, uses `tax_rules` | `app/Services/Marketplace/RegionalCommerceService.php` — `applyCartEstimates()`, `taxZone()`, `lineTax()`, `shippingEstimate()`, `bestWarehouseRoute()`. **This is a working cart-side tax+shipping+warehouse-routing calculator already wired into the cart**, not a stub. |

## Freight
| Item | State |
|---|---|
| `carriers` / `carrier_services` / `freight_rate_cards` / `shipping_zones` | ❌ Missing entirely. |
| Shipping estimate | ⚠️ Minimal | `RegionalCommerceService::shippingEstimate()` — a simple subtotal-based estimate, not a rate-card/carrier-aware calculation. |
| `bestWarehouseRoute()` | ✅ Exists | warehouse selection logic already present in `RegionalCommerceService`, though the warehouse network itself is minimal (1 row, per the JLCPCB investigation). |

## Payments
| Requested table | State | Evidence |
|---|---|---|
| `payment_gateways` / `payment_gateway_accounts` | ❌ Missing under that name | NeoGiga has **`payment_providers`** instead (code, name, is_enabled, is_live, supported_currencies (json), config (json), sort_order) — functionally equivalent, already live with **8 providers seeded disabled**: cod, bank_transfer, wallet, esewa, khalti, fonepay, stripe, paypal. This already covers the Nepal (eSewa/Khalti/Fonepay) and global (Stripe/PayPal) gateway list the spec asks for, minus live credentials by design. |
| `marketplace_payment_methods` | ❌ Missing | no join table restricting which providers are visible per marketplace — `payment_providers.supported_currencies` is the closest proxy but isn't marketplace-scoped. |
| `payment_transactions` / `payment_webhook_logs` | ✅ Exists (different names) | `payment_transaction_events` (from the payments-abstraction module, wraps the existing `payments`/`refunds` tables) — covers transaction/webhook audit. |
| `payment_fees` / `payment_reconciliations` | ❌ Missing | no fee-rule or reconciliation tables. |
| Encrypted gateway credentials | ⚠️ Unverified | `payment_providers.config` is a plain `json` column — admin `updateProvider` action was built (2026-07-08) to **strip secret-looking keys** before persisting, but the column itself is not encrypted-at-rest. Flag as a security follow-up before any gateway goes live. |

## Summary verdict
Pricing/tax/payment infrastructure is **partially built and further along than "missing"** for the
core primitives (regional per-marketplace prices, a real tax-rules engine wired into cart estimates,
a payment-provider abstraction with 8 sandboxed gateways). The genuine, clean gaps for Stage 2
foundations are: **exchange-rate history/audit, a USD landed-cost/supplier-cost layer, HS-code/
import-duty tables, and freight rate cards** — none of which exist in any form yet. Recommend Stage 2
of this cycle build ONLY the exchange-rate history table + calculation-log/regional-price-history
scaffolding (schema, no live formula wiring) — duty/freight/gateway-credential work is Stage 3+ per
the prompt's own release ordering.
