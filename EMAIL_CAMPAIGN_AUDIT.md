# Email Campaign Audit

Generated: 2026-07-13

## Existing capabilities

- Newsletter and email campaign tables, templates, recipients, events, settings, admin pages, jobs, and a campaign execution service exist.
- Campaign creation supports a template, schedule, country/segment targeting, and a test recipient.
- The code writes recipient/message/event rows in safe mode.
- Suppression and unsubscribe tables exist.

## Root causes and gaps

1. `EmailProviderManager` returns synthetic statuses and has no provider contract or adapter.
2. Campaign execution writes all recipients synchronously and is not dispatched in bounded batches.
3. Newsletter execution checks suppression but does not independently prove marketing consent.
4. Test sends accept any syntactically valid address instead of a configured allow-list.
5. Campaigns have no approval workflow, immutable audience snapshot, pause/resume cursor, sender profile, regional domain, exclusion counts, or provider campaign ID.
6. `CampaignSuppressionService` currently returns all profiles and does not apply suppression rules.
7. There is no webhook receiver/signature validation, bounce/complaint normalization, or idempotent provider-event store.
8. Template validation does not block unresolved variables or require unsubscribe/preferences/footer content.
9. Marketing jobs do not select a dedicated queue.
10. Admin marketing routes are role-gated but do not have action-specific web permissions.

## Upgrade decision

Retain the existing newsletter, email campaign, recipient, message, and event tables. Add provider contracts, disabled/sandbox adapters, campaign preparation snapshots, eligibility decisions, sender/domain records, webhook ledgers, bounce/complaint records, and permission-scoped endpoints. Production sending remains disabled by default and test sends use an explicit allow-list.
