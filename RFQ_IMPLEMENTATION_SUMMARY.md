# RFQ_IMPLEMENTATION_SUMMARY (2026-07-09)

**Design decision:** the task's `rfqs`/`rfq_lines` tables were NOT created — NeoGiga already has a
live RFQ module (`rfq_requests`/`rfq_items` + RfqService + quotations pipeline, deployed 2026-07-07).
Creating parallel tables would repeat the duplicate-module hazard this project keeps fighting.
Everything below builds ON the existing module. Only one new table was added.

## Shipped (live on neogiga.com)
| Piece | Detail |
|---|---|
| Public RFQ form | `GET /rfq` (+ `?product={slug}` prefill: name, MPN, brand banner). SSR, NeoGiga design, CSP-safe |
| Submit | `POST /rfq` — CSRF + `throttle:6,1`; validated (name/email required, qty ≥1, target price ≥0, required date ≥ today, message ≤2000) |
| Data flow | `RfqService::create` → `rfq_requests` + `rfq_items` (RFQ- number, MPN in item notes); country/required-date/source-product-page/channel → `rfq_requests.meta` |
| All task fields | customer name ✓ email ✓ phone ✓ company ✓ country ✓ product id ✓ MPN ✓ quantity ✓ target price ✓ required date ✓ message ✓ source page ✓ status ✓ |
| Product page CTA | mailto replaced with `/rfq?product={slug}` |
| New table | `rfq_status_histories` (guarded, additive): rfq FK, previous_status→status, notes, changed_by, timestamps |
| Admin list | `/admin/rfqs` — KPIs (total/open/quoted/accepted), status filter, pagination |
| Admin detail | `/admin/rfqs/{id}` — contact/company/country/required-by/source-page, items with target prices, status update form, **audit timeline** |
| Status writes | both the public submit (initial `open`) and admin changes append history; prod's own `auditAdminAction` sink kept alongside |
| Email notification | placeholder only (`Log::info`) — mailer template not chosen yet (per task: placeholder if mail not configured) |
| Nav | "RFQ Inbox" in the Commerce sidebar, next to RFQ & Quotations |

## Integration with the prod panel build
During deploy, prod was found to have evolved again (admin panel execution build: support tickets
module, order tracking, admin CRUD actions, enhanced views, its own `updateRfqStatus` +
`storeRfqQuotation`). Handled by the standard union procedure: prod's 26 evolved + 5 new files
synced into git, RFQ pieces re-applied on top (prod's updateRfqStatus enhanced with the history
write rather than replaced). Nothing was overwritten in either direction.

## Live end-to-end proof
A real submission through the live form created **RFQ-00001**: 1 item, 1 history row,
`meta.country=Nepal` — verified in the prod database. All smoke checks green (see
VALIDATION_REPORT.md).
