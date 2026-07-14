# Live Platform Audit — neogiga.com / regional / admin / pcb (2026-07-12)

Method: read-only HTTP probes (status, redirects, titles, headers) from this host. No production
file, DB, or config was touched. Server-side state (PHP version, pending migrations, deployed-file
inventory) was **not** inspected — the auto-mode gate requires explicit approval for SSH into
`precious`; run that pass on request.

## 1. Main storefront — HEALTHY

| Route | Result |
|---|---|
| `/` | 301 → `/en` (correct locale canonicalization) |
| `/en`, `/en/products`, `/en/categories`, `/cart` | 200 (26–97 KB, 0.2–1.7 s) |
| `/rfq` | 301 → `/en/rfq` (works) |
| `/sitemap.xml` | 200 (67 KB) |
| `/up` health | 200 |
| `/preview/home` (redesign) | 200 — still live as noindex preview |
| `/bom`, `/pcb/quote` | **404 — the icon-system branch work is NOT deployed** (sits on `claude/icon-system`) |

Security headers: **excellent** — full CSP (`default-src 'self'`, no inline JS), HSTS w/
`includeSubDomains`, `X-Frame-Options: DENY`, nosniff, referrer-policy. robots.txt sane
(admin API disallowed, knowledge pages open).

## 2. Regional editions — SERVING, with one real SEO defect

- **Path editions:** `/np /in /ae /us /uk /de /au /sa /qa /sg` all 200. NP/IN/AU render localized
  titles; the rest fall back to the global title (consistent with preview/inactive states).
- **Country subdomains:** `au.` `bd.` `pk.` neogiga.com serve the app with localized titles —
  wildcard vhost works.
- ⚠️ **P1 SEO DEFECT — broken hreflang targets:** `/en` emits
  `en-np → https://giganepal.com/en` (301 → www → WordPress with cache-busting 307, not the app) and
  `en-in → https://neogiga.in/en` (**404** "Page Not Found – NeoGiga India"). Search engines are
  being told the NP/IN editions live on domains that don't serve them, while the real editions
  (`/np`, `/in`, subdomains) work. Fix = point hreflang at the app-serving URLs (marketplace
  domain config) or stand the app up on those domains first. This is the exact de-indexing risk the
  SEO alignment work was designed to avoid.

## 3. Admin (admin.neogiga.com) — HEALTHY, fail-closed

- `/` → `/admin` → `/admin/login` (200, styled). Every module page redirects to login when
  unauthenticated; unauthenticated POST to a write route → **419** (CSRF rejects before auth).
- **Deployed module map** (302 = live behind login / 404 = not deployed):
  - LIVE: dashboard, **system-health**, orders, rfqs, **bom-imports**, **pos**, inventory,
    marketing (full suite), **promotions**, payments, support, settings.
  - **NOT deployed (the remaining Release-1 gaps): `employees`, `announcements`, `roles`** — 404,
    matching the gap reports exactly.

## 4. PCB (pcb.neogiga.com) — LIVE portal shell

- Vhost + DNS live; `/` → `/en` serving a **dedicated branded landing**: "NeoGiga PCB — Secure PCB
  Fabrication Projects and Engineering Quotes" (21 pcb/gerber mentions). The parallel PCB program
  deployed a portal front.
- `/pcb/quote` 404 on this subdomain too — the working quote-intake form (icon-system branch) is
  not deployed anywhere yet.

## 5. API + backend

- `backend.neogiga.com/up` 200. Wallet canary via `-L`: **401** ✅. BOM imports API unauth: 401 ✅.
  Admin API unauth: 401 ✅. Public products API: 200 ✅. Auth boundaries hold.

## Verdict & priority actions

1. **P1 — fix hreflang for en-np/en-in** (broken external targets; live SEO damage vector).
2. **P1 — deploy the missing Release-1 admin modules** (employees, announcements, roles) — the only
   404s on the admin surface; roles/announcements are already scoped in the gap reports.
3. **P2 — deploy `/bom` + `/pcb/quote` intake** (branch `claude/icon-system`, 27 tests green) —
   pcb.neogiga.com is a shell without a quote path; this branch fills it.
4. **P2 — server-side pass (needs your approval):** SSH read-only check of PHP version, pending
   migrations, queue failures, and whether PR #11 debris reached prod.
5. Note: prod is healthier than local `main` (prod boots; local main has the PR #11 breakage —
   fix session task_79162b43 still in flight).
