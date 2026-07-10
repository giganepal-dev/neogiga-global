# PRICE_FLOOR_AND_MARGIN_POLICY (2026-07-10)

BUILT this cycle. Enforced by `PricingRuleResolver::finalize()` against `price_floor_rules` and
`margin_floor_rules`.

## Tables
- `price_floor_rules`: `marketplace_id?`, `scope_type` (global|marketplace|category|brand|seller|
  product), `scope_id?`, `min_absolute_price?` + `currency_code?`, `max_discount_percent?`,
  `max_fixed_discount?`, `is_active`.
- `margin_floor_rules`: `marketplace_id?`, `scope_type`, `scope_id?`, `min_gross_margin_percent?`,
  `min_net_margin_percent?`, `min_contribution_margin_percent?`, `require_approval_below`, `is_active`.

## Enforcement (built)
After all rules apply, the resolver computes the final price and gross margin
`(final − cost) / final × 100`, then checks every active floor/margin rule for the marketplace:
- Final price below an active `min_absolute_price` (currency-converted) ⇒ **blocked** with reason.
- Gross margin below `min_gross_margin_percent` ⇒ **blocked** with reason.

`price()` returns `blocked: true` + `block_reasons[]`. **The resolver never silently clamps below a
floor** — it surfaces the violation so the caller can refuse, warn, or route to approval. This is the
codex's "block it / warn / require admin approval / or allow approved loss-leader campaign" contract
at the engine layer.

## Deferred (documented, not built this cycle)
- `loss_leader_approvals` / `discount_exception_requests` tables and the time-limited, audited
  approval workflow that would let an authorized campaign price below floor.
- Net-margin and contribution-margin computation (needs freight/payment/operating cost inputs that
  the pricing formula does not yet carry — see NEXT_PRICING_PHASE_BACKLOG.md).
- `max_discount_percent` / `max_fixed_discount` enforcement, which belongs to the promotion engine
  (needs a "regular price" reference the promotion layer supplies).

Until those exist, a below-floor price is simply blocked; no override path is wired.
