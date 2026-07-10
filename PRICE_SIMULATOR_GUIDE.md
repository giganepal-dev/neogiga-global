# PRICE_SIMULATOR_GUIDE (2026-07-10)

BUILT this cycle: `App\Services\Pricing\PriceSimulator` + `PricingContext`. **Read-only** — it never
writes. Persisting a simulated price is a separate, explicit, approved action (not yet built).

## Usage
```php
$ctx = new PricingContext(
    productId: 501,
    marketplace: $marketplace,      // App\Models\Marketplace\Marketplace
    costBasisAmount: 1.00,          // in the marketplace currency, already landed/converted
    currencyCode: 'USD',
    quantity: 1,
    customerSegment: null,          // e.g. 'b2b', 'education'
    categoryId: null, brandId: null, manufacturerId: null,
    sellerId: null, warehouseId: null, countryId: null,
    at: null,                       // Carbon time for scheduled-rule simulation; defaults to now()
);

$result = app(PriceSimulator::class)->simulate($ctx);
```

## Result shape
```
cost_basis, currency, final_price, gross_margin_percent,
blocked (bool), block_reasons[],
applied_rule_ids[], trace[],       // full applied/skipped rule trace
simulated: true, persisted: false
```

## Guarantees (tested)
- No DB writes (`test_simulator_never_writes` asserts row counts unchanged).
- Same engine as live resolution (`PricingRuleResolver`), so a simulation matches what the live
  price would be for that context — the codex's "simulate before activation" requirement.
- `at` lets you simulate a future date/time to preview scheduled rules before they go live.

## Deferred
The `/admin/pricing/simulator` UI, and simulating tax + promotions end-to-end (promotion engine not
built this cycle). The service already returns the pricing-rule breakdown; tax/promo layers plug in
later without changing this contract.
