# Customer Import Audit

Generated: 2026-07-13

## Scope inspected

- Laravel 11.54 backend in `giga-nepal-backend`.
- Existing marketplace imports, generic `imports` / `import_rows` tables, CRM profile tables, admin routes, queue configuration, and permission model.
- Supplied workbook `Customer Invoice Details (8).xlsx`, worksheet `Customer Invoice Details`, range `A1:L2`.

## Workbook evidence

The workbook contains 12 headers and one source row. The headers match the requested invoice/customer profile exactly, including the trailing punctuation in `Customer Account Postal Code .` and `Customer Contact Name .`.

The source row contains invoice `117493066`, date `2025-10-27`, company `NORATEL INTERNATIONAL PVT LTD`, contact `ACHALA GUNASIRI`, email `achala@noratel.lk`, phone `+94 112250760`, country `SRI LANKA`, city `KATUNAYAKE`, postal code `GQ11450`, and source region `ASIA PACIFIC`.

## Existing capabilities

- The application has asynchronous database queues and a registered marketing migration path.
- Generic marketplace `imports` and `import_rows` tables exist, but currently contain only IDs and timestamps.
- CRM profile, address, consent, segment, suppression, newsletter, campaign, and message tables exist.
- Admin pages already use the current NeoGiga server-rendered design.

## Root causes and gaps

1. There is no customer spreadsheet reader, reusable mapping profile, import command, import job, upload/preview flow, or resumable import service.
2. Generic import tables cannot record file identity, mapping, row state, warnings, errors, provenance, or restart position.
3. Company, contact, email, phone, invoice reference, and login-user identities are not separated.
4. The existing `customer_profiles` contact uniqueness constraint is unsuitable for company/contact deduplication.
5. Country resolution accepts numeric IDs only; no canonical ISO alias resolution or conflict recording exists.
6. Existing consent fields are booleans and do not represent `unknown`, `transactional_only`, bounce, complaint, or review states.
7. There is no immutable original-row store, value-change history, merge log, or rollback metadata.
8. There is no field-level export neutralization for spreadsheet formula prefixes.

## Upgrade decision

Keep all existing import and CRM tables. Add customer-import-specific ledgers and normalized identity tables, then link imported contacts into the existing `customer_profiles` table so existing segmentation and marketing screens continue to work. The saved profile will be configuration-backed and persisted per import. Dry-run remains the default safe operating mode in documentation and never grants promotional consent.

## Safety baseline

- No source workbook modification.
- No production import or migration during audit.
- Idempotency key: source identity plus worksheet plus normalized invoice ID/row hash.
- Imported marketing status: `unknown` unless explicit evidence is supplied.
- Source values remain in immutable JSON and field-level provenance records.
