# TAX_AND_DUTY_ENGINE_GUIDE

**Status: the TAX side is real and live. The DUTY side does not exist.**

## Tax — already built (pre-existing, not this cycle)
`tax_rules`: marketplace_id, country_id, region_id, tax_name, tax_type, tax_rate, fixed_amount,
is_compound, is_inclusive, applies_to, category_ids (json), product_ids (json), effective_from/
effective_until, is_active. `App\Services\Marketplace\RegionalCommerceService::taxZone()` +
`lineTax()` already compute cart-line tax from this table and are wired into the live cart
(`applyCartEstimates()`). No changes were made to this system this cycle.

**None of the 25 new marketplaces have any `tax_rules` rows** — by design (this cycle explicitly
does not invent tax rates). Until a country gets real rules entered, its cart estimate for that
marketplace context will simply compute zero tax, not an error.

## Import duty — does not exist
No `hs_codes`, `hs_code_country_rules`, `import_duty_rules`, or `customs_fee_rules` tables exist.
No HS-code classification exists on any product. This is a genuine, complete gap — not a naming
mismatch like several other "missing" items in this audit turned out to be.

## Jurisdiction / exemptions — partial
No dedicated `tax_jurisdictions` table (the `country_id`/`region_id` columns on `tax_rules` cover
this without one). No `tax_exemptions` or `business_tax_profiles` — no B2B tax-ID/exemption
handling anywhere.

## What this cycle did NOT change
Zero changes to `tax_rules`, `RegionalCommerceService`, or any tax calculation path. The existing
Nepal/India tax behavior (if any rules exist for those marketplaces) is completely unaffected.

## Recommended build order (future stage)
1. HS-code table + a per-product `hs_code` column (additive).
2. `import_duty_rules` keyed by (hs_code, destination_country) with an effective-date pattern
   matching `tax_rules`'s existing convention.
3. Extend `RegionalCommerceService` (or a new sibling service) to add duty on top of tax for
   cross-border orders, snapshotting the result the same way tax is already snapshotted.
4. Populate real tax rules per new marketplace only once verified against local regulation —
   never invent a rate.
