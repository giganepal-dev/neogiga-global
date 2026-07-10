# COUNTRY_REDIRECT_GUIDE

**Status: schema built this cycle. Redirect execution is OFF everywhere — nothing redirects a visitor today.**

## Current behavior (unchanged from before this cycle, and by design)
NeoGiga never force-redirects anyone based on geography. `GlobalMarketplaceContextService::context()`
computes a `recommended` marketplace (from `CF-IPCountry`/`Accept-Language`, generalized to all 25
marketplaces this cycle) and a `show_recommendation` flag the existing frontend uses to show an
optional switch-marketplace banner. **The visitor's actual page never changes** unless they click
through themselves. Unsupported countries simply see the global site — there is no fallback logic
that could ever produce a redirect loop, because there is no redirect at all.

## What's new this cycle: `marketplace_redirect_rules` (schema only, inert)
`marketplace_redirect_rules`: marketplace_id, from_pattern, to_pattern, redirect_type
(temporary|permanent), is_active. Rows may be authored here for future review, **but storing a
rule does not make it fire** — every marketplace also has a new `redirect_enabled` boolean
(default `false` on all 26 rows, including GLOBAL/NEPAL/INDIA) that must be explicitly flipped
before any rule for that marketplace is even eligible to run. No code path in this cycle reads
`marketplace_redirect_rules` at all — the table exists purely as a place to stage future rules for
review, per the instruction not to enable real geo redirects without reviewed configuration.

## When live redirects are eventually enabled
- Verified search-engine crawlers must never be redirected (check user-agent/robots status before
  applying any rule).
- Use `temporary` (302) for first-time country detection, `permanent` (301) only for retired/
  canonicalized URLs — exactly as the original spec specifies.
- Preserve the deep URL path when a localized equivalent page exists; otherwise keep the visitor on
  the page they requested rather than bouncing them to a country homepage.
- Re-run the existing "no redirect loop" test coverage pattern (see
  GLOBAL_COMMERCE_VALIDATION_REPORT.md) against the specific rules before flipping any
  `redirect_enabled` flag in production.
