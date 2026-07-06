# Changelog

## 2026-07-07 - Smartend-Style Admin Console Foundation

- Added additive admin console foundation tables for admin settings, managed media assets, SEO pages, and SEO redirects.
- Completed the existing `product_seo_meta` shell with safe optional SEO columns.
- Added protected admin console APIs for overview metrics, navigation, settings, media uploads, SEO metadata, redirects, permissions, and approval queues.
- Added admin Settings, Media, and SEO pages under `/admin/*` using the existing NeoGiga admin theme.
- Extended the admin sidebar with stable Smartend-inspired console groups without copying reference source code.

## 2026-07-06

- Added marketing admin audit logging with an incremental `marketing_admin_audit_logs` table, logger service, protected audit page, and action logging for marketing admin writes.
- Added validated protected admin marketing form actions for creating segments, newsletter/email/WhatsApp templates and campaigns, refreshing segments, and saving non-secret marketing/analytics settings.
- Added protected admin marketing UI foundation under `/admin/marketing` with CRM, newsletter, email campaign, automation, abandoned cart, WhatsApp, analytics, and settings views backed by the Phase 2 tables.
- Added Phase 2 marketing automation foundation: CRM/segments, newsletter, email automation, WhatsApp placeholder campaigns, OTP login, abandoned cart recovery schema, analytics, dashboard APIs, settings, queue jobs, scheduler hooks, seeders, and documentation without overwriting `.env` or deleting existing data.

- Installed Let's Encrypt SSL for `neogiga.com`, `www.neogiga.com`, `mail.neogiga.com`, and `admin.neogiga.com` on the existing Apache/Virtualmin vhost without changing site content or database data.
- Added and deployed a production Next.js 15 NeoGiga landing page on `neogiga.com` as an additive app release under `/home/neogiga/app`, preserving backups of the prior Virtualmin welcome root and Apache vhost.
- Deployed `/Users/ashokdhamala/Downloads/neogiga.zip` to `neogiga.com` as a Laravel release under `/home/neogiga/laravel/releases/20260706-140308`, switched Apache to the Laravel `public/` web root, preserved rollback backups, and added non-destructive sitemap/mobile overflow fixes without running database migrations or seeders.
- Redesigned the Laravel front page for `neogiga.com` as a professional marketplace/platform page with no direct API links, added `backend.neogiga.com` as the SSL-protected backend host, and routed public `/api` traffic to the backend subdomain.
- Created an isolated `neogiga_app` MySQL database during backend setup and patched migration compatibility issues for MySQL table ordering, foreign keys, and long composite index names; live audit confirmed the deployed Laravel runtime currently uses the existing PostgreSQL `neogiga` connection.
- Removed remaining front-page `/api` link targets found during live audit so public UI cards and seller copy no longer navigate users into API endpoints.

## 2026-06-26

- Added read-only public production audit reports for `giganepal.com`, including technical SEO, robots/sitemap behavior, query-string canonical risk, product data quality, image alt SEO, and Nepal/international SEO opportunity findings.
- Added raw public audit evidence under `giganepal-audit-data/` without modifying production WordPress code, database data, products, orders, customers, media, categories, or SEO settings.

## 2026-06-12

- Added `wp-content/mu-plugins/ecoholiday-conversion-upgrade/` as an additive EcoHolidayAsia conversion layer.
- Added responsive homepage, tour package, travel guide/blog package, booking/account, inquiry, and AI trip-help UI without deleting existing themes, routes, products, posts, plugins, or data.
- Added versioned database tables for inquiries, chat history, and source audit metadata with required source fields and pre-migration schema snapshots.
- Added admin audit page under Tools > EcoHolidayAsia Audit for inquiry/source review with raw source URLs kept out of public page overload.
## 2026-07-06 - Inventory Ledger and POS Foundation

- Completed existing POS shell tables with additive columns for terminals, sessions, sales, sale items, and local payments.
- Added inventory procurement support tables for suppliers, purchase orders, and purchase order items.
- Added inventory services for stock movements, reservations, purchase receiving, and warehouse transfers.
- Added POS service for opening/closing sessions, product search, sale creation, stock-out posting, sale lookup, and local payment recording.
- Replaced inventory reservation and POS 501 placeholders with validated API behavior; POS mutation routes now require API token auth.
- Added protected admin inventory APIs for overview, stocks, movements, low-stock rows, adjustments, transfers, and receiving.
- Added read-only admin Inventory and POS dashboard pages.

## 2026-07-06 - LMS Adaptation Foundation

- Completed the existing LMS shell tables with additive migrations for courses, categories, modules, lessons, projects, components, code samples, product links, enrollments, progress events, quizzes, assignments, and certificates.
- Added NeoGiga-native LMS services for course catalog, enrollment, progress tracking, and certificate issuance.
- Replaced LMS 501 public API placeholders with queryable course/project endpoints and learner enrollment/progress APIs.
- Added protected admin LMS API endpoints for overview, courses, projects, lessons, enrollments, and certificates.
- Added a read-only LMS admin dashboard page at `/admin/lms`.
- Added SEO-ready public LMS pages at `/learn` and `/learn/projects/{slug}`.
- Reference LMS code was used for architecture mapping only; no commercial source files, secrets, SQL dumps, or nulled code were copied.

## 2026-07-06 - WhatsApp Manual Export Queue

- Added safe-mode WhatsApp campaign queue execution with provider `manual_export`; no provider delivery is attempted.
- Added opt-in and suppression filtering for WhatsApp audiences, including segment and country targeting support.
- Added admin API `send-now` and export endpoints for queued WhatsApp recipients.
- Added admin web queue/test actions for WhatsApp campaigns with marketing audit log records.
- Updated `SendWhatsAppCampaignJob` to queue campaigns for manual export instead of placeholder logging.

## 2026-07-06 - Marketing Campaign Safe Queue Execution

- Added safe-mode email and newsletter campaign execution through provider `log` only; no real outbound email is sent.
- Added campaign audience building with opt-in, consent, unsubscribe, suppression, segment, and country filters.
- Added admin API `send-now` endpoints and web admin queue/test actions for email and newsletter campaigns.
- Updated campaign queue jobs to use the execution service instead of placeholder logging.
- Recorded campaign queue/test actions in the marketing admin audit log.
