# NeoGiga Platform Audit — Phase 1 of Marketplace/POS Finalization
Date: 2026-07-20 · Auditor: Claude (code + live prod DB census) · Status: **pre-implementation audit — no changes made**

Evidence base: full service/model/controller/view/test inventory of the repo + live PostgreSQL table census on neogiga-prod (~350 tables). Verdict up front: **most of the brief already exists in some form.** The dominant risk is duplicate rebuilding, not missing foundations. The real work is (a) wiring/completing existing skeletons, (b) a handful of genuinely missing modules, (c) one design system over the existing portals.

## Classification matrix

Legend: ✅ existing+working (live evidence) · 🟡 existing but incomplete (code/tables exist, wiring/UI partial) · 🔴 missing · ⚠ unsafe/needs decision

### 1. Regional marketplace core — ✅ working (live)
26 marketplaces, domains, currencies, per-marketplace settings/flags (`marketplace_feature_flags` table exists — use it for all new modules). Regional pricing (CentralPricingService, LandedCostCalculator, DutyService, RegionalTaxResolver, import_duty_rules, TaxZone), region stock visibility (+tests), marketplace resolution by Host. Payment-method regional filtering exists (`PaymentMethodPolicyService`, `pos_payment_methods`). Server-side enforcement exists for catalog/pricing; **cart/checkout regional isolation needs verification tests, not new code.**

### 2. Customer account & dashboard — 🟡 incomplete
`CustomerDashboardController` + `views/frontend/account/`, customer_accounts/profiles/addresses/consents/activity_logs tables, login_histories, order tracking controller, email preferences. Missing vs brief: saved products/compare persistence (compare controller exists), 2FA (🔴), unified confirmations audit. Mostly a **completion + redesign** job.

### 3. Institutional customers — 🟡 substantially built as "B2B" module
`b2b_accounts, b2b_account_users, b2b_quotations(+items), b2b_credit_terms, b2b_price_lists(+items), b2b_purchase_orders(+items), b2b_quote_requests, b2b_approval_workflows(+steps)` + 8 B2B services incl. `B2BApprovalWorkflowService`, `B2BQuotationService` + `views/b2b/` portal (dashboard/login/orders/products/rfqs). The brief's institutional workflow = **extend this module** (org types, document uploads, region-wise discount rules), NOT a new one. Quotation numbering exists (`DocumentNumberService`, `document_number_sequences`).

### 4. Reseller system — 🟡 skeleton
`resellers` table, `Reseller` model, `views/reseller/` portal (6 views), Web/Reseller + Api controllers, Services/Reseller. Missing: territory approvals (🔴 — `distributor_territories` exists but no reseller equivalent), application/compliance-document flow (SellerApplication pattern exists to copy), offer listing via existing `SellerOffer`/MPN matching (matcher exists in Bom/Catalog services). **Extend seller-portal pattern (Release-2, live+tested).**

### 5. Distributor system — 🟡 strongest of the four seller roles
Tables: distributor_applications, _territories, _commissions(+rules), _payouts, _staff, _downlines, _customers, _leads, _orders, _profiles, catalog_distributor_offers. Portal views + Api/Distributor controllers + application controller. Missing: credit limits/aging (🔴), brand/category authorization granularity, purchase forecasting (🔴 — defer, YAGNI until asked by a real distributor).

### 6. Manufacturer system — 🟡 skeleton
manufacturers + manufacturer_aliases tables (canonical identity ✅ — catalog already normalizes), `views/manufacturer/` portal, Services/Manufacturer. Canonical product model already exists (`canonical_products`, catalog_product_sources + aliases) — the brief's "one master product, regional offers" is **already the catalog architecture** (marketplace_product_prices = regional offers). Missing: manufacturer-facing inventory allocation UI, verification workflow.

### 7. RFQ / quotation / bidding — 🟡
rfq_requests(+items, status_histories), quotations(+items), Erp RfqService/QuotationService, frontend RFQ pages, BOM→RFQ (Release A). Missing: reseller bid publishing/assignment (🔴 rfq_assignments, supplier_bids), bid privacy walls, quote-accept signed-token email flow (🔴 — communication_logs + signed URLs exist as building blocks).

### 8. Privacy-masked customer↔seller messaging — 🔴 missing (the one truly net-new communication module)
No conversations/messages/participants tables (only ai_conversations, support_ticket_messages, vendor_support_tickets). Masking service, contact-info blocking, moderation — all new. Support tickets ✅ exist both sides.

### 9. POS — 🟡 backend strong, standalone UI missing
Tables: pos_sales(+items), pos_payments, pos_refunds, pos_registers, pos_sessions, pos_shifts, pos_shift_closings, pos_terminals, pos_cash_movements, pos_offline_sync_events, pos_payment_methods. PosService + AiPosInvoiceService + admin pos.blade.php + pos-sale-detail. 406 route-file mentions. Inventory ledger ✅ (inventory_movements + audits — brief's "ledger not overwrite" rule already the pattern). Missing: dedicated cashier UI (`views/pos/` is empty — POS runs inside admin), barcode/QR label generation (🔴), shift-variance report UI, refund-exceeds-sold guard needs test proof.

### 10. Accounting — 🔴 missing as double-entry; partial ledgers exist
commission_ledger, wallet_ledger_entries, expenses, invoices(+items), refunds/returns exist. No chart_of_accounts/accounting_entries. ⚠ Building double-entry is high-risk/high-effort — recommend LAST, behind a feature flag, or integrate an existing package rather than hand-rolling.

### Cross-cutting
- **Roles/permissions**: ✅ roles/permissions/role_permissions + vendor_roles/vendor_permissions + 22-role seeder + permission middleware (live).
- **Email/notifications**: ✅ extensive (providers, templates+versions, suppression, delivery logs, preferences, webhooks) — hardened 2026-07-19 (sender-profile fallback). Template *coverage* for the brief's ~30 events is partial.
- **Audit logging**: ✅ audit_logs + domain-specific audit tables everywhere.
- **Compliance documents**: 🔴 no unified compliance_documents/expiry tracking (seller/distributor applications hold ad-hoc docs).
- **2FA**: 🔴. **Barcode/QR**: 🔴. **Discount rule engine**: 🟡 pricing rule engine built+tested 2026-07-10 but INERT/undeployed — extend it for institutional/customer-type rules instead of writing a new one.
- **Design system**: 🟡 each portal (admin/seller/distributor/reseller/manufacturer/b2b/frontend) has its own layout.blade.php; frontend has a coherent "Precision Engineering" system + icon components. Work = one shared portal layout/component set, not a rebuild.

## Recommended build order (each slice shippable, feature-flagged, on existing foundations)
1. **Portal unification + completion** — shared portal design system; bring reseller/manufacturer/b2b portals to seller-portal parity. Low risk, high visible value.
2. **Institutional workflow** — extend B2B module: org types + documents + admin review states + quotation-accept signed-token flow + discount rules via the inert pricing engine (deploy it).
3. **Reseller/distributor territory approvals** — generalize distributor_territories into per-role, per-region approval states + admin UI.
4. **POS cashier UI** — standalone `views/pos/` app over the existing PosService; barcode/QR labels; shift close report.
5. **Privacy messaging** — net-new conversations module with masking (small schema, big rules).
6. **Accounting** — last, flagged, possibly package-based.

## Hard rules confirmed by audit
- Never bump `catalog:page-cache-version` without the bot guard (deployed 2026-07-19). FPM reload required after every PHP deploy. Prod is file-sync, not git — diff before every push. marketplace_feature_flags table is the flag mechanism. Existing MPN alias/normalization lives in catalog_product_source_aliases + manufacturer_aliases + brand_aliases — reuse for reseller listing matching.
