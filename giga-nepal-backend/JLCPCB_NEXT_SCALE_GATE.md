# JLCPCB Next Scale Gate

Do not scale beyond 1,000 rows until the remaining gates below are complete.

## Required before 10,000 rows

- Completed: 1,000-row pilot has zero adapter errors.
- Completed: idempotency rerun shows no duplicate products/source links/offers.
- Completed: rollback dry-run is source-scoped and safe.
- Completed: health/home/products/admin smoke tests passed.
- Completed: queue remains healthy with 0 jobs and 0 failed jobs.
- Completed: database size after pilot reviewed at 58 MB.
- Pending: manually inspect sample imported products in admin.
- Pending: review pending brands/categories created during failed stopped attempts.
- Pending: implement or schedule search-index/facet rebuild for imported products.
- Pending: define sitemap/SEO publication gate so thin imported pages remain hidden until review.

## Still forbidden in current execution

- full import
- 10,000-row import
- 100,000-row import
- publishing thin imported pages publicly
