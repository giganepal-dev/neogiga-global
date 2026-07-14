# NeoGiga Email Deliverability Plan

Generated: 2026-07-13
Status: application controls implemented; external provider and DNS activation intentionally pending

## Current Safety State

- Marketing defaults to `sandbox`, test mode is enabled, and production sending is disabled until an authorized administrator saves and enables an encrypted SMTP/API configuration.
- Transactional delivery defaults to the Laravel `log` mailer, test mode is enabled, and delivery is disabled until an authorized administrator saves and enables encrypted SMTP configuration. Environment settings remain a fallback before admin configuration exists.
- All seeded sender profiles are disabled and unverified. All seeded domain checks are `unknown`.
- Campaign approval is required by default. Test sends are restricted to the configured allowlist.
- No DNS record is changed automatically and no real customer campaign is sent by installation, migration, seeding, or import.

## Channel and Domain Separation

| Marketplace | Public base URL | Marketing subdomain | Transactional subdomain | Currency |
| --- | --- | --- | --- | --- |
| NeoGiga Global | `https://neogiga.com` | `news.neogiga.com` | `mail.neogiga.com` | USD |
| NeoGiga India | `https://neogiga.in` | `news.neogiga.in` | `mail.neogiga.in` | INR |
| Giga Nepal | `https://giganepal.com` | `news.giganepal.com` | `mail.giganepal.com` | NPR |

Each marketplace has separate marketing and transactional sender-profile records. Support, Orders, Billing, RFQ, and Seller Communication identities are also seeded disabled. Regional branding resolves from the campaign/order marketplace; a regional message does not fall back to an unrelated region's sender.

## DNS and Provider Checklist

Complete these manually for every sending subdomain before enabling its sender profile:

1. Add exactly one SPF TXT policy for the domain and include only the selected provider's documented mechanism.
2. Publish the provider-generated DKIM selector and key. Do not reuse private keys between environments.
3. Publish DMARC at `_dmarc.<domain>`. Begin with reporting (`p=none`), review aggregate reports, then progress to quarantine/reject after alignment is proven.
4. Configure a custom return-path/bounce domain such as `return.news.neogiga.com` and confirm SPF alignment.
5. Configure provider bounce and complaint webhooks with a unique HMAC secret.
6. Configure reply handling or a monitored mailbox; do not use an unmonitored reply-to address.
7. Verify that the provider reports the domain and sender as verified, then record that status in Communication Settings.
8. Send only to the configured internal test recipients and inspect Gmail/Outlook headers for SPF, DKIM, and DMARC pass/alignment.

## Application Activation Gates

Marketing production delivery requires all of the following:

- `MARKETING_EMAIL_PROVIDER=generic_http` (or a reviewed adapter), not `sandbox`;
- API URL/key/account ID and `MARKETING_EMAIL_WEBHOOK_SECRET` configured outside source control;
- `MARKETING_EMAIL_TEST_MODE=false` and `MARKETING_EMAIL_SENDING_ENABLED=true`;
- a verified, enabled regional marketing sender profile;
- a valid reply-to, approved campaign, immutable audience snapshot, valid template, consent, subscription, and no applicable suppression;
- configured rate/daily limits and active workers for the dedicated marketing queues.

Transactional production delivery requires either an enabled non-test admin SMTP configuration or the equivalent environment flags, plus a reviewed credential configuration and a verified enabled regional transactional sender. Marketing unsubscribe does not block required service messages; hard bounce, complaint, invalid-address, blocked, provider, legal, or security suppressions do.

## Reputation and Ramp Plan

- Start with recent, explicitly consented recipients and small daily volumes.
- Increase volume gradually only while hard-bounce and complaint rates remain within provider policy.
- Do not purchase, scrape, or infer consent for lists. Imported invoice contacts remain `unknown`/transactional-only unless evidence is recorded.
- Pause a campaign on abnormal provider failures, complaint growth, or authentication regression.
- Reconfirm inactive recipients before re-engagement and respect country-specific legal review.
- Keep marketing content out of transactional templates.

## Monitoring

The Communication Settings page exposes sender/domain status, provider mode, queue separation, and the SPF/DKIM/DMARC checklist. Signed webhooks record accepted, queued, sent, delivered, opened, clicked, deferred, failure, bounce, blocked, complaint, and unsubscribe events idempotently. Hard bounces and complaints suppress immediately; repeated soft bounces use `EMAIL_SOFT_BOUNCE_THRESHOLD`.

Metrics such as opens are privacy-limited and provider-dependent. The dashboard labels these limitations and never fabricates unavailable events.

## Rollback

The fastest delivery rollback is configuration-only: set `MARKETING_EMAIL_SENDING_ENABLED=false`, `MARKETING_EMAIL_TEST_MODE=true`, and `TRANSACTIONAL_EMAIL_ENABLED=false`, then clear/rebuild configuration cache. Pause active campaigns and stop only the marketing worker if necessary; keep transactional and webhook workers isolated. Do not delete campaign snapshots, delivery events, suppressions, or communication logs during rollback.
