# 3 — Reference Security Risk Report (2026-07-12)

Subject: `~/Desktop/reference` (MultiVendor E-Commerce, Laravel 8). Risks matter in two ways:
(a) risks **of running** it — irrelevant, we won't run it; (b) risks **of porting from** it — the
list below is what must never cross into NeoGiga.

## Findings

| # | Risk | Evidence | Porting rule |
|---|---|---|---|
| 1 | **EOL framework** — Laravel 8 stopped receiving security fixes Jan 2023; PHP 8.0 EOL | composer.json | Never copy framework-coupled code; rebuild on L11 |
| 2 | **Abandoned/deprecated packages**: `fideloper/proxy` (abandoned), `paypal/rest-api-sdk-php` (deprecated 2021), `fruitcake/laravel-cors` (absorbed into framework), `nexmo/laravel` (renamed/stale), CKEditor **4.22** (EOL 2023, known XSS advisories) | composer.json | None of these may be added to NeoGiga; use maintained equivalents |
| 3 | **Debug/installer attack surface**: `routes/test.php`, `TestForDataInsertController`, `InstallController`, `UpdateController`, `SoftwareUpdateController` | routes/, app/Http/Controllers | Do not port; NeoGiga must never gain an install/update wizard |
| 4 | **Root `.rnd` artifact** | project root | Treat as an unrelated local/random artifact. Do not port; inspect only in a dedicated security task if needed |
| 5 | **Mixed auth stacks** (Passport + Sanctum + custom guards) | composer.json + controllers | Port no auth code; NeoGiga keeps its single token scheme + RBAC |
| 6 | **MySQL-isms** (`ext-mysqli`, raw enum/zero-date conventions) | composer.json, migrations | Schemas re-designed for PostgreSQL, never copied |
| 7 | **Client-side stack CVE tail**: jQuery 3.x plugins bundle, Bootstrap 4, Vue 2 (EOL) with compiled assets in `public/` | package.json, public/ | Never copy compiled assets (also a mission rule) |
| 8 | **Licensing** — CodeCanyon-style commercial lineage (install/licence/update/addon machinery, demo video, "mySpecs.html"). Copying its PHP/Blade wholesale into NeoGiga would be a **license violation risk** | root files, controllers | **Patterns and workflows only.** No file-level copying of app code, views, or assets |
| 9 | **No secrets observed in scanned scope** — no `.env`, no SQL dumps, no key material observed at scanned depth/scope | find results | This is not a guarantee for excluded/deeper paths. If dumps or secrets are later found, do not open them into model context or port them |

## What is safe

- **Reading** it for workflow/schema understanding (done).
- **Depending on maintained upstream packages it also uses** where NeoGiga has a real gap, after
  per-version license, security-advisory, and transitive-dependency review:
  `phpoffice/phpspreadsheet` (XLSX import), `barryvdh/laravel-dompdf` (invoice PDFs),
  `laravel/socialite` (social login), and `milon/barcode` only after LGPLv3/legal review for
  POS/labels. Installing a package from Packagist is not copying the reference's code.

## Verdict

Reference is **quarantined as read-only inspiration**. The mission's "never copy" list (env, dumps,
credentials, branding, vendor files, compiled assets) is extended with: **no PHP/Blade file-level
copying at all** (risk #7). Enforcement: every adapted feature must be written against NeoGiga
services with NeoGiga tests, per `REFERENCE_REUSE_DECISION.md`.
