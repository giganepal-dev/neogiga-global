# COUNTRY_LOCALIZATION_GAP_REPORT (2026-07-10)

Gap analysis specifically for reaching the 25-country target
(in, np, bd, lk, pk, bt, mv, ae, sa, qa, om, kw, us, ca, uk, de, fr, it, es, nl, au, nz, br, za, ke).

| Requirement | Current state | Gap | Priority |
|---|---|---|---|
| Marketplace rows | 3 seeded (GLOBAL, NEPAL, INDIA) | Need 22 more, seeded **inactive/preview** per this cycle's instruction | P0 |
| Country rows | 10 seeded | Need up to 25 (some countries may already be represented without a marketplace attached) | P0 |
| Currency rows | 10 seeded | Likely need a few more (e.g. AED, SAR, QAR, OMR, KWD, BDT, LKR, PKR, NZD, ZAR, KES if not already present) — exact gap to confirm against the 10 existing rows once prod reads are unblocked | P0 |
| URL routing | Domain-based only (3 domains) | **No path-prefix routing exists.** This is the single largest gap — every one of the 22 new countries needs `/xx` prefix resolution added to `routes/web.php` + a resolver, since buying 22 more branded domains is out of scope | P0 |
| GeoIP detection | Not implemented | Needed for the "recommend/redirect to matching country path" resolution step (Phase 4, step 5). No provider chosen. **Recommend: do NOT build/enable in this cycle** — the instruction explicitly says stop before enabling live geo redirects | P1 (deferred) |
| Marketplace preference persistence | ✅ Exists (cookie-based) | Reusable as-is for the new marketplaces | — |
| hreflang | Exists but domain-scoped (3 entries) | Needs to scale to 25 path-based entries + x-default | P1 |
| Localized content tables (`localized_content`, `localized_slugs`, `marketplace_page_overrides`, etc.) | ❌ Missing entirely | Out of scope for this cycle (Phase 12, Stage 4) | P2 (deferred) |
| Localized manufacturer/category/product names | ❌ Missing (no localization tables anywhere in PIM) | Out of scope for this cycle (Stage 4) | P2 (deferred) |
| Seller/warehouse marketplace scoping | `seller_marketplace_approvals`-style tables not confirmed to exist; `user_country_access`/`user_seller_access` exist for admin RBAC only | Out of scope for this cycle (Stage 3) | P2 (deferred) |
| Payment method visibility per marketplace | No `marketplace_payment_methods` join table | Out of scope for this cycle (Stage 3) | P2 (deferred) |
| Checkout enablement per marketplace | `marketplaces` has no explicit `checkout_enabled` flag — `is_active`/`allow_vendor_registration` exist but nothing checkout-specific | **This cycle adds the flag as part of the marketplace-model migration**, defaulted `false` for all 22 new countries per the explicit instruction not to enable checkout | P0 |

## What this cycle will NOT touch (explicitly deferred per the prompt's own scoping)
- Live GeoIP-based redirects
- Real tax rates for any of the 22 new countries
- Live payment gateway credentials/routing per country
- Freight/carrier integration
- Localized page content (manufacturer/category/product translated copy)
- Seller/warehouse country approval workflows

## Recommendation
Implement Stage 1 exactly as scoped: extend the marketplace model with the missing flags
(`checkout_enabled`, `launch_status`, `global_fallback`, redirect toggle), add path-prefix
resolution as a new capability alongside (not replacing) the 3 domain-based marketplaces, seed
all 25 as data rows with the 22 new ones `launch_status=preview`/`checkout_enabled=false`, and
build the country selector + fallback UI. Do not attempt GeoIP or live redirects this cycle.
