# NeoGiga Codex Complete Marketing Analytics Command

Goal: replace marketing/analytics placeholders with functional safe-mode workflows.

Evidence:
- Marketing schema/controllers/services exist.
- Jobs such as trending products, abandoned carts, transactional email are placeholders.
- GA4 support appears settings-level only.

Tasks:
1. Implement analytics aggregation jobs.
2. Implement abandoned cart detection/reminder safe queue.
3. Complete transactional email queue for account/order events using log provider.
4. Add GA4 config placeholders and consent-aware event emitting.
5. Add tests for unsubscribe/suppression/consent.

Rules:
- No real outbound campaign delivery unless provider explicitly configured.
- Preserve suppression and consent checks.
- Update `CHANGELOG.md`.

Verification:
- Jobs update analytics tables.
- Campaign safe-mode logs only.
- Consent/unsubscribe tests pass.

Deliverable:
- `NEOGIGA_MARKETING_ANALYTICS_IMPLEMENTATION_REPORT.md`

