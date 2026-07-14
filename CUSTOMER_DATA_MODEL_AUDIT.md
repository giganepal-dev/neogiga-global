# Customer Data Model Audit

Generated: 2026-07-13

## Existing model

NeoGiga currently has three unrelated customer concepts:

- `users`: login identity and authentication.
- `customers`: an older IoT/customer record with Nepal administrative-address fields.
- `customer_profiles`: the marketing/commerce CRM contact profile.

It also contains `customer_addresses`, `customer_preferences`, `customer_consents`, `customer_segments`, `customer_segment_members`, `contact_lists`, `suppression_lists`, `unsubscribes`, and newsletter/campaign tables. The optional `b2b_accounts` migration exists in another module but is not present in the audited local database and is not safe to treat as the canonical CRM company table.

## Root causes and gaps

- A company and its people cannot be represented independently.
- Multiple emails and phones per contact are not modeled.
- Invoice/source references cannot be retained independently from contact data.
- Consent history lacks status, lawful basis, evidence, policy, and source-import links.
- Imported values do not carry the required provenance fields.
- Country and marketplace access are not enforced consistently in CRM queries.
- Existing `customer_addresses.customer_profile_id` is required, so the upgrade must attach an imported address to the linked profile while adding an optional company link.

## Additive target model

- `customer_accounts`: canonical company/legal account.
- `customer_contacts`: people, linked to an account and optionally to an existing CRM profile/user.
- `contact_email_addresses` and `contact_phone_numbers`: normalized multi-value contact points.
- Existing `customer_addresses`: extended with account and import provenance links.
- `customer_imports`, `customer_import_files`, `customer_import_rows`, `customer_import_errors`: resumable import ledger.
- `customer_sources` and `customer_invoice_references`: source and invoice history.
- `customer_merge_logs` and `customer_value_histories`: reversible matching/change evidence.
- Existing consent, subscription, preference, suppression, segment, campaign, and message tables: extended in place.

Every imported row will retain `source_name`, `source_url`, `source_file`, `source_page_url`, `downloaded_at`, `imported_at`, `data_year`, `license_note`, `confidence_level`, `original_raw_value`, and `normalized_value` in the import ledger. Normalized records will retain source/import links and a compact provenance snapshot.

## Data ownership rules

1. A login user is not automatically a company or consent record.
2. A company can have many contacts and addresses.
3. A contact can have many email/phone records; only verified primary records drive transactional communication.
4. An invoice reference is immutable source evidence, not an order record.
5. Marketing consent and transactional eligibility remain separate.
6. Ambiguous matches create review records; names alone never trigger an automatic merge.
7. Campaign recipients are immutable snapshots once prepared.
