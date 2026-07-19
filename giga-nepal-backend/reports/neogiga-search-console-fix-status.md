# Search Console Fix Status — Product Structured Data — 2026-07-19

| GSC issue | Real cause | Status |
|---|---|---|
| Missing `returnFees` in hasMerchantReturnPolicy | Stale crawl — live HTML already has it (verified by curl 2026-07-19) | Will clear on recrawl; hardened + regionalized in this fix |
| Missing `shippingDetails` in offers | Stale crawl — live HTML already has it | Same |
| Missing `aggregateRating` / `review` | Optional-field warnings; products have no approved reviews — omission is the required behavior (no fabricated ratings) | Now emitted automatically when genuine approved reviews exist |
| (Not shown in GSC but found) US country leaking to regional domains | Locale-prefix bug in the generator | **Fixed** — marketplace country |
| (Not shown) fake 0.00 offers on price-less products | else-branch stub offer | **Fixed** — Offer omitted |

## Order of operations

1. Deploy branch `fix/transactional-email-and-product-schema`.
2. `php artisan view:clear && php artisan config:clear && php artisan config:cache` on prod; bump page cache: `php artisan tinker --execute="Cache::forever('catalog:page-cache-version',(string) now()->getTimestampMs());"` (do **not** flush Redis).
3. If Cloudflare fronts the domains, purge ONLY the affected product URLs (or wait out `s-maxage=300`).
4. Run the batch validation script (see neogiga-product-schema-audit.md) — every regional host must show its own country/currency.
5. Validate the 3 affected URLs in Google Rich Results Test.
6. **Only then** click "Validate fix" in Search Console.

Do not request validation before step 4 passes — a failed validation run delays the next attempt by weeks.
