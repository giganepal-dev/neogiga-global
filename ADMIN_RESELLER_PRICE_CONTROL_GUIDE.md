# ADMIN_RESELLER_PRICE_CONTROL_GUIDE (2026-07-10)

Status: the **data model and engine** that make admin/reseller price control possible are BUILT;
the **UI, permission wiring, and seller-facing enforcement flow** are DEFERRED (see
NEXT_PRICING_PHASE_BACKLOG.md).

## What the schema already supports
`pricing_rules.owner_type` ∈ {global_admin, marketplace_admin, seller, reseller, manufacturer} with
`owner_id`, plus `approval_status` and `pricing_rule_approvals`. This lets the engine represent every
authority in the codex without code changes:
- Global admin defines inherited defaults (`scope_type = global`, no owner_id).
- Country/marketplace admin defines marketplace-scoped rules (`marketplace_id` set); the resolver
  already refuses to apply one marketplace's rule to another (`test_marketplace_isolation`).
- Seller/reseller rules carry `owner_type = seller|reseller` + `owner_id`; the engine only ever
  applies **approved** rules, so a seller-proposed rule is inert until an admin approves it.

## Control invariants (to enforce in the DEFERRED policy/UI layer)
- Reseller price cannot fall below the admin `price_floor_rules` floor → already blocked by the
  resolver today; the UI must surface the block reason and route to approval.
- Reseller discount cannot exceed `price_floor_rules.max_discount_percent` (enforcement lives in the
  promotion engine — deferred).
- A seller may edit only their own `owner_id` rules/offers (authorization policy — deferred; the
  `owner_type`/`owner_id` columns are the hook).
- Purchase cost / margin visibility gated by the `pricing.cost.view` permission (deferred; see §23 of
  the codex). The engine returns cost + margin in its result, so the presentation layer is
  responsible for redacting them from unauthorized sellers.

## Not built this cycle
Admin pages (`/admin/pricing*`), the pricing-rule wizard, the seller pricing panel, bulk update,
scheduling UI, and the `seller_price_constraints` enforcement surface. The engine and schema are
ready for them.
