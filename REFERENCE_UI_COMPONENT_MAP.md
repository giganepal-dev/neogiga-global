# REFERENCE_UI_COMPONENT_MAP (2026-07-09)

Archive admin UI = AIZ theme (Bootstrap 4 + jQuery, CDN-heavy). NeoGiga admin = self-hosted,
CSP `script-src 'self'`, vanilla SSR, navy `#0F172A` sidebar / cyan `#19D3F5` accent / gold accents /
white cards / soft-gray body. **Verdict: keep NeoGiga's design system everywhere; archive is layout
inspiration only.** Branding: no MyStoreNepal strings, logos, or assets are copied anywhere.

| Component (prompt list) | Archive | NeoGiga equivalent | Action |
|---|---|---|---|
| Dark left sidebar | AIZ `inc/admin_sidenav` | live (navy, active-state, sections: Platform/Catalog/Commerce/Growth) | keep; added Orders link |
| Top navigation bar | AIZ topbar | live (`topbar`: burger, title, crumb, session) | keep |
| Dashboard metric cards | `dashboard.blade.php` cards | live `.kpi` grid | keep |
| Charts | jQuery plugins | none (CSP: no JS charts yet) | backlog: self-hosted sparkline |
| Order stat widgets | orders dashboard | **added** — KPIs on new `/admin/orders` | ✅ built |
| Top categories / brands | dashboard widgets | dashboard has top-level categories table | keep |
| Recent orders | dashboard table | **added** on `/admin/orders` (latest, filterable) | ✅ built |
| Action buttons / filter bars | AIZ buttons/filters | `.btn/.btn-primary/.btn-ghost` + GET filter forms | ✅ used on orders |
| Tables / status badges | AIZ tables | `.tbl` + `.badge b-ok/b-info/b-muted` | ✅ used |
| Detail panels | order show page | **added** `/admin/orders/{id}` split cards | ✅ built |
| Timeline/order updates | order history | **added** — `order_status_histories` timeline on detail page | ✅ built |
| Invoice layout | `backend/invoices` | **added** print-CSS invoice (NeoGiga brand header) | ✅ built |
| Frontend product cards/gallery/filters | absent in archive | **added natively**: `/products` grid + `/products/{slug}` spec sheet | ✅ built |
