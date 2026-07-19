# NeoGiga Product Structured-Data Audit — 2026-07-19

## Live-state finding (important)

`curl` of the affected URL `https://neogiga.com/en/products/lx-ffc-20p-0-5mm-20cm-11107` shows the live page **already emits** `offers.hasMerchantReturnPolicy.returnFees` and `offers.shippingDetails` — the Search Console screenshot reflects a **stale crawl**. `aggregateRating`/`review` warnings are Google-*optional* fields, correctly omitted while the product has no approved reviews (per the no-fabrication rule).

## Defects found in the generator (now fixed)

| # | Defect | Where | Fix |
|---|--------|-------|-----|
| 1 | `applicableCountry`/`shippingDestination` derived from the **URL locale prefix** (`/en/` → `US`) so np/in/pk pages emitted `US` | `products/show.blade.php` | Country now from the resolved **marketplace** (`marketplaceContext.country_code`); global default configurable |
| 2 | Zero-price products emitted a fake `price: "0.00"` + `InStock` Offer | same | **No Offer at all** without a real price |
| 3 | `review` never emitted even when approved public reviews exist | same | Up to 5 approved reviews (display name only, never emails), matching the on-page review list |
| 4 | `aggregateRating` lacked `bestRating`/`worstRating`; raw average unrounded | same | Added; value normalized to 1 decimal |
| 5 | Return/shipping values hardcoded inline per-page | same | Centralized: config defaults + per-marketplace override |
| 6 | No `seller`, `refundType`, `returnPolicyUrl` | same | Added (`NeoGiga` org, `FullRefund`, `{origin}/returns`) |
| 7 | JSON-LD logic embedded in the Blade (untestable, duplicable) | same | **Central builder** `app/Services/Seo/ProductSchemaService.php` used by every regional page (all regions render this one blade) |
| 8 | `Cache` facade import missing in `AppServiceProvider` — storefront pages fatal on this branch (pre-existing, deploy blocker) | `AppServiceProvider.php` | Import added; brand/product page tests green again |

## Data sources (no fabrication)

- Price/currency: `marketplace_product_prices` per marketplace (unchanged).
- Availability: real `stock_quantity` (unchanged).
- Reviews: `product_reviews` where `status='approved'` only — the same rows rendered on the page.
- Return/shipping policy: `config('neogiga_global.schema_commerce')` defaults — these mirror the currently published live policy — overridden per marketplace via `marketplaces.settings['commerce_schema']` (admin-settable JSON). **Action for business:** set real regional values (e.g. `{"commerce_schema":{"return":{"days":7,"fees":"https://schema.org/ReturnShippingFees"},"shipping":{"rate":"150.00","transit_min":1,"transit_max":5}}}`) before launching paid regional logistics, and keep `/returns` content in sync.

## Regional isolation

Country comes from the marketplace row (NP/IN/PK…), currency from the regional price row; page cache keys on host (`CachePublicPages` sha1(host+url)) so no cross-region HTML leakage. Unit-tested: NP context → `NP`+`NPR` everywhere; PK override honored; no country → configured default (US, matching current live global output).

## LIVE OUTCOME (2026-07-19, deployed + verified)

All four hosts verified on the affected URL after deploy + FPM reload:

| Host | priceCurrency | applicableCountry | shipDest | returnFees | refundType | seller | aggregateRating |
|---|---|---|---|---|---|---|---|
| neogiga.com | USD | US | US | ✔ | ✔ | ✔ | absent (no reviews — correct) |
| np.neogiga.com | NPR | NP | NP | ✔ | ✔ | ✔ | absent |
| in.neogiga.com | INR | IN | IN | ✔ | ✔ | ✔ | absent |
| pk.neogiga.com | PKR | PK | PK | ✔ | ✔ | ✔ | absent |

Extra live-caught fix: the GLOBAL edition's pseudo-country iso is `GL` (reads as
Greenland) — the builder now ignores it and uses the configured default (US).

**Incident + prevention:** the first page-cache version bump caused a thundering
herd (~95% bot traffic — Amazonbot/GPTBot/Ahrefs — on faceted `/products` URLs;
load 64 on 6 cores, PG statement timeouts). Mitigated permanently in
`CachePublicPages`: crawlers get `503 Retry-After: 3600` on **uncached
parameterized** listing URLs (which are noindex anyway); cached copies and all
clean URLs serve bots normally. Verified live: GPTBot+params → 503 in 0.9s;
human+params → 200; second cache bump caused **no load spike** (stayed ~10, the
box's normal baseline). Ops note: `systemctl reload php8.4-fpm` is required after
deploying PHP files — workers pick up CLI-side immediately, web does not.

## Post-deploy validation (Part C/D)

```bash
for u in lx-ffc-20p-0-5mm-20cm-11107 lx-ffc-ffc-20p-0-5mm-8cm-122342 lx-ffc-10p-0-5mm-25cm-11106; do
  for h in neogiga.com np.neogiga.com in.neogiga.com pk.neogiga.com; do
    curl -sSL "https://$h/en/products/$u" | python3 -c "
import sys,re,html,json
s=sys.stdin.read()
for b in re.findall(r'<script[^>]*ld\+json[^>]*>(.*?)</script>',s,re.S):
    d=json.loads(html.unescape(b.strip()))
    if isinstance(d,dict) and d.get('@type')=='Product':
        o=d.get('offers') or {}
        print('$h','$u','OK',o.get('priceCurrency'),(o.get('hasMerchantReturnPolicy') or {}).get('applicableCountry'),'aggregateRating' in d)"
  done
done
```

Then validate 3 URLs in Google Rich Results Test / validator.schema.org, and only after live HTML shows the corrected schema click **Validate fix** in Search Console.
